<?php
// Start the session to maintain user login state
session_start();

// Check if the user is logged in and is a student (role = 'student')
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
  // Redirect to login page if not logged in as student
  header("Location: ../loginRegister.html");
  exit();
}

// Include database controller
require_once "php/dbcontroller.php";
$db_handle = new DBController();

// Get student information
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = $user_id AND role = 'student'";
$result = $db_handle->readData($sql);
$student = $result[0];

// Get course filter if provided
$course_filter = isset($_GET['course']) ? intval($_GET['course']) : null;

// Get month filter if provided (default to current month)
$current_month = date('m');
$current_year = date('Y');
$month_filter = isset($_GET['month']) ? intval($_GET['month']) : $current_month;
$year_filter = isset($_GET['year']) ? intval($_GET['year']) : $current_year;

// Get student's courses
$courses_sql = "SELECT c.id, c.courseTitle as name
                FROM courses c 
                JOIN studentcourse sc ON c.courseTitle = sc.courseID 
                JOIN users u ON sc.userStudentID = u.fullName
                WHERE u.id = $user_id";
$courses = $db_handle->readData($courses_sql);

// Get attendance data based on filters
$attendance_sql = "SELECT a.id, a.date, a.status, c.id as course_id, c.courseTitle as course_name
                  FROM attendance a
                  JOIN courses c ON a.courseID = c.id
                  WHERE a.studentID = $user_id";

if ($course_filter) {
  $attendance_sql .= " AND c.id = $course_filter";
}

if ($month_filter && $year_filter) {
  $attendance_sql .= " AND MONTH(a.date) = $month_filter AND YEAR(a.date) = $year_filter";
}

$attendance_sql .= " ORDER BY a.date DESC";
$attendance_records = $db_handle->readData($attendance_sql);

// Calculate attendance statistics
$stats = [
  'present' => 0,
  'absent' => 0,
  'late' => 0,
  'excused' => 0,
  'online' => 0, // Added for the new status value
  'total' => count($attendance_records)
];

foreach ($attendance_records as $record) {
  if (isset($stats[$record['status']])) {
    $stats[$record['status']]++;
  }
}

$attendance_rate = $stats['total'] > 0 ? round(($stats['present'] / $stats['total']) * 100, 1) : 0;

// Group attendance by date
$grouped_attendance = [];
foreach ($attendance_records as $record) {
  $date = $record['date'];
  if (!isset($grouped_attendance[$date])) {
    $grouped_attendance[$date] = [];
  }
  $grouped_attendance[$date][] = $record;
}

// Month names for dropdown
$months = [
  1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
  5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
  9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Attendance - Student Dashboard - EWLearn</title>
  <link rel="stylesheet" href="./css/dashboard.css">
  <link rel="stylesheet" href="./css/dashboard-menu.css">
  <link rel="stylesheet" href="./css/attendance.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <!-- Sidebar Menu -->
  <div class="menu">
    <ul>
      <li class="profile">
        <div class="img-box">
          <img src="./images/profile.jpg" alt="profile image">
        </div>
        <h2><?php echo $student['fullName']; ?></h2>
      </li>
      <li>
        <a href="./dashboard.php">
          <i class="fas fa-home"></i>
          <p>Dashboard</p>
        </a>
      </li>
      <li>
        <a href="./my-courses.php">
          <i class="fas fa-book"></i>
          <p>My Courses</p>
        </a>
      </li>
      <li>
        <a href="./assignments.php">
          <i class="fas fa-tasks"></i>
          <p>Assignments</p>
        </a>
      </li>
      <li>
        <a href="./grades.php">
          <i class="fas fa-graduation-cap"></i>
          <p>Grades</p>
        </a>
      </li>
      <li>
        <a class="active" href="./attendance.php">
          <i class="fas fa-user-check"></i>
          <p>Attendance</p>
        </a>
      </li>
      <li>
        <a href="./profile.php">
          <i class="fas fa-user"></i>
          <p>Profile</p>
        </a>
      </li>
      <li class="log-out">
        <a href="php/logout.php">
          <i class="fas fa-sign-out"></i>
          <p>Log Out</p>
        </a>
      </li>
    </ul>
  </div>

  <!-- Main Content -->
  <section class="main">
    <!-- Page Header -->
    <div class="header">
      <h1>My Attendance</h1>
      <div class="filter-container">
        <select id="month-filter">
          <?php foreach ($months as $num => $name): ?>
            <option value="<?php echo $num; ?>" <?php echo ($month_filter == $num) ? 'selected' : ''; ?>>
              <?php echo $name; ?> <?php echo ($num == $month_filter) ? $year_filter : ''; ?>
            </option>
          <?php endforeach; ?>
        </select>
        <select id="course-filter">
          <option value="all">All Courses</option>
          <?php foreach ($courses as $course): ?>
            <option value="<?php echo $course['id']; ?>" <?php echo ($course_filter == $course['id']) ? 'selected' : ''; ?>>
              <?php echo $course['name']; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Attendance Summary -->
    <div class="attendance-summary">
      <div class="summary-card overall">
        <div class="attendance-rate">
          <div class="rate-circle" data-value="<?php echo $attendance_rate; ?>">
            <div class="rate-value"><?php echo $attendance_rate; ?>%</div>
            <div class="rate-label">Attendance Rate</div>
          </div>
        </div>
        <div class="chart-container">
          <canvas id="attendanceChart"></canvas>
        </div>
      </div>
      
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon present">
            <i class="fas fa-check-circle"></i>
          </div>
          <div class="stat-info">
            <span class="stat-value"><?php echo $stats['present']; ?></span>
            <span class="stat-label">Present</span>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon absent">
            <i class="fas fa-times-circle"></i>
          </div>
          <div class="stat-info">
            <span class="stat-value"><?php echo $stats['absent']; ?></span>
            <span class="stat-label">Absent</span>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon late">
            <i class="fas fa-clock"></i>
          </div>
          <div class="stat-info">
            <span class="stat-value"><?php echo $stats['late']; ?></span>
            <span class="stat-label">Late</span>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon excused">
            <i class="fas fa-file-medical"></i>
          </div>
          <div class="stat-info">
            <span class="stat-value"><?php echo $stats['excused']; ?></span>
            <span class="stat-label">Excused</span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Attendance Records -->
    <div class="attendance-records">
      <h2>Attendance Records - <?php echo $months[$month_filter] . ' ' . $year_filter; ?></h2>
      
      <?php if (!empty($grouped_attendance)): ?>
        <?php foreach ($grouped_attendance as $date => $records): ?>
          <div class="date-group">
            <div class="date-header">
              <?php echo date('l, F j, Y', strtotime($date)); ?>
            </div>
            <div class="records-container">
              <?php foreach ($records as $record): ?>
                <div class="attendance-record <?php echo $record['status']; ?>">
                  <div class="course-name"><?php echo $record['course_name']; ?></div>
                  <div class="status-badge <?php echo $record['status']; ?>">
                    <?php 
                      switch ($record['status']) {
                        case 'present': echo 'Present'; break;
                        case 'absent': echo 'Absent'; break;
                        case 'late': echo 'Late'; break;
                        case 'excused': echo 'Excused'; break;
                        case 'online': echo 'Online'; break;
                        default: echo ucfirst($record['status']);
                      }
                    ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-records">
          <div class="empty-state">
            <i class="fas fa-calendar-times empty-icon"></i>
            <h3>No Attendance Records Found</h3>
            <p>There are no attendance records for the selected filters.</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script>
    $(document).ready(function() {
      // Filter changes
      $('#month-filter, #course-filter').change(function() {
        const month = $('#month-filter').val();
        const course = $('#course-filter').val();
        
        let url = 'attendance.php?month=' + month + '&year=<?php echo $year_filter; ?>';
        
        if (course !== 'all') {
          url += '&course=' + course;
        }
        
        window.location.href = url;
      });
      
      // Attendance chart
      const ctx = document.getElementById('attendanceChart').getContext('2d');
      const attendanceChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: ['Present', 'Absent', 'Late', 'Excused', 'Online'],
          datasets: [{
            data: [
              <?php echo $stats['present']; ?>, 
              <?php echo $stats['absent']; ?>, 
              <?php echo $stats['late']; ?>, 
              <?php echo $stats['excused']; ?>,
              <?php echo $stats['online']; ?>
            ],
            backgroundColor: [
              '#28a745', // Green for present
              '#dc3545', // Red for absent
              '#ffc107', // Yellow for late
              '#6c757d', // Gray for excused
              '#0d6efd'  // Blue for online
            ],
            borderWidth: 0,
            cutout: '70%'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                boxWidth: 12,
                padding: 15,
                font: {
                  size: 12
                }
              }
            }
          }
        }
      });
    });
  </script>
</body>
</html>
