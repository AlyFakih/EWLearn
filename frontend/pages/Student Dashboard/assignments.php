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

$assignments_sql = "SELECT a.id, a.title, a.description, a.due_date, a.max_score,
                    c.courseTitle as course_name, c.id as course_id,
                    s.id as submission_id, s.status, s.score, s.submitted_at, s.feedback,
                    CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END as is_submitted
                    FROM assignment a
                    JOIN courses c ON a.course_id = c.id
                    JOIN studentcourse sc ON c.courseTitle = sc.courseID
                    JOIN users u ON sc.userStudentID = u.fullName
                    LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = $user_id
                    WHERE u.id = $user_id";

if ($course_filter) {
  $assignments_sql .= " AND c.id = $course_filter";
}

$assignments_sql .= " ORDER BY a.due_date ASC";
$assignments = $db_handle->readData($assignments_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assignments - Student Dashboard - EWLearn</title>
  <link rel="stylesheet" href="./css/dashboard.css">
  <link rel="stylesheet" href="./css/dashboard-menu.css">
  <link rel="stylesheet" href="./css/assignments.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
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
        <a class="active" href="./assignments.php">
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
      <h1>My Assignments</h1>
      <div class="filter-container">
        <select id="status-filter">
          <option value="all">All Assignments</option>
          <option value="pending">Pending</option>
          <option value="submitted">Submitted</option>
          <option value="graded">Graded</option>
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

    <div class="assignments-container">
      <?php if (!empty($assignments)): ?>
        <table class="assignments-table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Course</th>
              <th>Type</th>
              <th>Due Date</th>
              <th>Status</th>
              <th>Points</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($assignments as $assignment):
              $due_date = strtotime($assignment['due_date']);
              $current_date = time();
              $is_overdue = $due_date < $current_date && !$assignment['is_submitted'];

              $status_class = '';
              if ($assignment['is_submitted']) {
                $status_class = $assignment['status'];
              } elseif ($is_overdue) {
                $status_class = 'overdue';
              } else {
                $status_class = 'pending';
              }
            ?>
            <tr class="<?php echo $status_class; ?>-row" data-status="<?php echo $status_class; ?>" data-course="<?php echo $assignment['course_id']; ?>">
              <td>
                <div class="assignment-title">
                  <?php echo $assignment['title']; ?>
                  <?php if ($is_overdue): ?>
                    <span class="overdue-badge">Overdue</span>
                  <?php endif; ?>
                </div>
              </td>
              <td><?php echo $assignment['course_name']; ?></td>
              <td>Assignment</td>
              <td><?php echo date('M d, Y', strtotime($assignment['due_date'])); ?></td>
              <td>
                <span class="status-badge <?php echo $status_class; ?>">
                  <?php
                    if ($assignment['is_submitted']) {
                      echo ucfirst($assignment['status']);
                    } elseif ($is_overdue) {
                      echo "Overdue";
                    } else {
                      echo "Pending";
                    }
                  ?>
                </span>
              </td>
              <td>
                <?php
                  if ($assignment['score'] !== null) {
                    echo $assignment['score'] . ' / ' . $assignment['max_score'];
                  } else {
                    echo $assignment['max_score'] . ' pts';
                  }
                ?>
              </td>
              <td>
                <a href="assignment-details.php?id=<?php echo $assignment['id']; ?>" class="view-btn">
                  <?php if (!$assignment['is_submitted']): ?>
                    <i class="fas fa-upload"></i> Submit
                  <?php else: ?>
                    <i class="fas fa-eye"></i> View
                  <?php endif; ?>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="no-assignments">
          <img src="./images/no-assignments.svg" alt="No assignments">
          <h2>No Assignments Found</h2>
          <p>You don't have any assignments yet.</p>
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
          window.location.href = 'assignments.php';
        } else {
          window.location.href = 'assignments.php?course=' + courseId;
        }
      });

      $('#status-filter').change(function() {
        var status = $(this).val();
        if (status === 'all') {
          $('.assignments-table tbody tr').show();
        } else {
          $('.assignments-table tbody tr').hide();
          $('.assignments-table tbody tr[data-status="' + status + '"]').show();
        }
      });
    });
  </script>
</body>
</html>
