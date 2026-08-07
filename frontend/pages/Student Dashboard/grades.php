<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
  header("Location: ../loginRegister.html");
  exit();
}

require_once "php/dbcontroller.php";
$db_handle = new StudentDBController();

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = $user_id AND role = 'student'";
$result = $db_handle->readData($sql);
$student = $result[0];

$course_filter = isset($_GET['course']) ? intval($_GET['course']) : null;

$courses_sql = "SELECT c.id, c.courseTitle as name
                FROM courses c
                JOIN studentcourse sc ON c.courseTitle = sc.courseID
                JOIN users u ON sc.userStudentID = u.fullName
                WHERE u.id = $user_id";
$courses = $db_handle->readData($courses_sql);

$grades_sql = "SELECT a.id as assignment_id, a.title, a.max_score as points,
                c.id as course_id, c.courseTitle as course_name,
                s.score as grade, s.feedback, s.submitted_at as submission_date
                FROM assignment_submissions s
                JOIN assignment a ON s.assignment_id = a.id
                JOIN courses c ON a.course_id = c.id
                WHERE s.student_id = $user_id AND s.score IS NOT NULL";

if ($course_filter) {
  $grades_sql .= " AND c.id = $course_filter";
}

$grades_sql .= " ORDER BY course_name, a.due_date";
$grades = $db_handle->readData($grades_sql);

$courses_with_grades = [];
$overall_points = 0;
$overall_earned = 0;

foreach ($grades as $grade) {
  $course_id = $grade['course_id'];

  if (!isset($courses_with_grades[$course_id])) {
    $courses_with_grades[$course_id] = [
      'id' => $course_id,
      'name' => $grade['course_name'],
      'grades' => [],
      'total_points' => 0,
      'earned_points' => 0
    ];
  }

  $courses_with_grades[$course_id]['grades'][] = $grade;
  $courses_with_grades[$course_id]['total_points'] += $grade['points'];
  $courses_with_grades[$course_id]['earned_points'] += $grade['grade'];

  $overall_points += $grade['points'];
  $overall_earned += $grade['grade'];
}

foreach ($courses_with_grades as $course_id => $course) {
  if ($course['total_points'] > 0) {
    $courses_with_grades[$course_id]['percentage'] = number_format(($course['earned_points'] / $course['total_points']) * 100, 1);
    $courses_with_grades[$course_id]['letter_grade'] = calculateGradeLetter($courses_with_grades[$course_id]['percentage']);
  } else {
    $courses_with_grades[$course_id]['percentage'] = 0;
    $courses_with_grades[$course_id]['letter_grade'] = 'N/A';
  }
}

$overall_percentage = $overall_points > 0 ? number_format(($overall_earned / $overall_points)* 100, 1) : 0;
$overall_letter = calculateGradeLetter($overall_percentage);

function calculateGradeLetter($percentage) {
  if ($percentage >= 90) return 'A';
  if ($percentage >= 80) return 'B';
  if ($percentage >= 70) return 'C';
  if ($percentage >= 60) return 'D';
  return 'F';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Grades - Student Dashboard - EWLearn</title>
  <link rel="stylesheet" href="./css/dashboard.css">
  <link rel="stylesheet" href="./css/dashboard-menu.css">
  <link rel="stylesheet" href="./css/grades.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
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
        <a class="active" href="./grades.php">
          <i class="fas fa-graduation-cap"></i>
          <p>Grades</p>
        </a>
      </li>
      <li>
        <a href="./attendance.php">
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

  <section class="main">
    <div class="header">
      <h1>My Grades</h1>
      <div class="filter-container">
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

    <div class="grades-summary">
      <div class="summary-card overall">
        <div class="summary-info">
          <h3>Overall Grade</h3>
          <div class="grade-display">
            <div class="grade-letter"><?php echo $overall_letter; ?></div>
            <div class="grade-percent"><?php echo $overall_percentage; ?>%</div>
          </div>
          <div class="grade-points">
            <span><?php echo $overall_earned; ?> / <?php echo $overall_points; ?> points</span>
          </div>
        </div>
        <div class="chart-container">
          <canvas id="overallChart"></canvas>
        </div>
      </div>

      <div class="courses-grid">
        <?php foreach ($courses_with_grades as $course): ?>
        <div class="summary-card course">
          <h3><?php echo $course['name']; ?></h3>
          <div class="grade-display">
            <div class="grade-letter"><?php echo $course['letter_grade']; ?></div>
            <div class="grade-percent"><?php echo $course['percentage']; ?>%</div>
          </div>
          <div class="grade-points">
            <span><?php echo $course['earned_points']; ?> / <?php echo $course['total_points']; ?> points</span>
          </div>
          <div class="progress-container">
            <div class="progress-bar" style="width: <?php echo min(100, $course['percentage']); ?>%"></div>
          </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($courses_with_grades)): ?>
        <div class="no-grades">
          <p>No graded assignments yet.</p>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="detailed-grades">
      <?php foreach ($courses_with_grades as $course): ?>
      <div class="course-grades">
        <div class="course-header">
          <h3><?php echo $course['name']; ?></h3>
          <div class="course-grade">
            <span class="grade-letter"><?php echo $course['letter_grade']; ?></span>
            <span class="grade-percent"><?php echo $course['percentage']; ?>%</span>
          </div>
        </div>

        <table class="grades-table">
          <thead>
            <tr>
              <th>Assignment</th>
              <th>Type</th>
              <th>Score</th>
              <th>Submission Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($course['grades'] as $grade): ?>
            <tr>
              <td class="assignment-name">
                <a href="assignment-details.php?id=<?php echo $grade['assignment_id']; ?>">
                  <?php echo $grade['title']; ?>
                </a>
              </td>
              <td><?php echo !empty($grade['type']) ? $grade['type'] : 'Assignment'; ?></td>
              <td>
                <div class="score-display">
                  <span><?php echo $grade['grade']; ?> / <?php echo $grade['points']; ?></span>
                  <span class="score-percent">(<?php echo number_format(($grade['grade'] / $grade['points']) * 100, 1); ?>%)</span>
                </div>
              </td>
              <td><?php echo date('M d, Y', strtotime($grade['submission_date'])); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endforeach; ?>

      <?php if (empty($courses_with_grades)): ?>
      <div class="no-detailed-grades">
        <i class="fas fa-award empty-icon"></i>
        <h3>No Grades Available</h3>
        <p>You don't have any graded assignments yet. Once your instructors grade your submissions, they will appear here.</p>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script>
    $(document).ready(function() {
      $('#course-filter').change(function() {
        var courseId = $(this).val();
        if (courseId === 'all') {
          window.location.href = 'grades.php';
        } else {
          window.location.href = 'grades.php?course=' + courseId;
        }
      });

      const ctx = document.getElementById('overallChart').getContext('2d');
      const overallChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: ['Earned', 'Remaining'],
          datasets: [{
            data: [<?php echo $overall_percentage; ?>, <?php echo max(0, 100 - $overall_percentage); ?>],
            backgroundColor: [
              '#810000',
              '#e0e0e0'
            ],
            borderWidth: 0,
            cutout: '75%'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              enabled: false
            }
          }
        }
      });
    });
  </script>
</body>
</html>
