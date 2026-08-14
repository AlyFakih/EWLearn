<?php
// Server-side authorization gate: student only. Runs first so nothing is
// emitted to an unauthenticated, wrong-role, expired or deleted account.
require_once __DIR__ . '/../../core/auth_guard.php';
auth_require_role('student', 'page', '../loginRegister.html');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../loginRegister.html");
    exit();
}
require_once "../../core/DBController.php";
$db_handle = new DBController();
$user_id = $_SESSION['user_id'];
$course_id = intval($_GET['id'] ?? 0);

$sql = "SELECT c.id, c.courseTitle as name, c.description, c.image,
        sc.userInstructorID as instructor_name
        FROM courses c
        JOIN studentcourse sc ON c.courseTitle = sc.courseID
        JOIN users u ON sc.userStudentID = u.fullName
        WHERE u.id = $user_id AND c.id = $course_id";
$result = $db_handle->readData($sql);
$course = $result[0] ?? null;

$sql2 = "SELECT * FROM assignment WHERE course_id = $course_id";
$assignments = $db_handle->readData($sql2);

$student_query = "SELECT * FROM users WHERE id = $user_id";
$student_result = $db_handle->readData($student_query);
$student = $student_result[0] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Course Details - EWLearn</title>
  <link rel="stylesheet" href="./css/dashboard.css">
  <link rel="stylesheet" href="./css/dashboard-menu.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .course-detail-header {
      background: #8b0000;
      color: white;
      padding: 30px;
      border-radius: 10px;
      margin-bottom: 25px;
    }
    .course-detail-header h1 { margin: 0 0 10px 0; font-size: 28px; }
    .course-detail-header p { margin: 5px 0; opacity: 0.9; }
    .course-section {
      background: white;
      border-radius: 10px;
      padding: 25px;
      margin-bottom: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .course-section h2 {
      color: #8b0000;
      border-bottom: 2px solid #8b0000;
      padding-bottom: 10px;
      margin-bottom: 20px;
    }
    .assignment-list { list-style: none; padding: 0; }
    .assignment-list li {
      padding: 15px;
      border: 1px solid #eee;
      border-radius: 8px;
      margin-bottom: 10px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .assignment-list li:hover { background: #f9f9f9; }
    .badge {
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: bold;
    }
    .badge-pending { background: #fff3cd; color: #856404; }
    .badge-submitted { background: #d4edda; color: #155724; }
    .back-btn {
      display: inline-block;
      margin-bottom: 20px;
      color: #8b0000;
      text-decoration: none;
      font-weight: bold;
    }
    .back-btn:hover { text-decoration: underline; }
    .no-data { color: #888; font-style: italic; }
  </style>
</head>
<body>
<div class="menu">
  <ul>
    <li class="profile">
      <div class="img-box">
        <img src="./images/profile.jpg" alt="profile image">
      </div>
      <h2><?php echo $student['fullName'] ?? 'Student'; ?></h2>
    </li>
    <li><a href="./dashboard.php"><i class="fas fa-home"></i><p>Dashboard</p></a></li>
    <li><a class="active" href="./my-courses.php"><i class="fas fa-book"></i><p>My Courses</p></a></li>
    <li><a href="./assignments.php"><i class="fas fa-tasks"></i><p>Assignments</p></a></li>
    <li><a href="./grades.php"><i class="fas fa-chart-bar"></i><p>Grades</p></a></li>
    <li><a href="./attendance.php"><i class="fas fa-calendar-check"></i><p>Attendance</p></a></li>
    <li><a href="./profile.php"><i class="fas fa-user"></i><p>Profile</p></a></li>
    <li><a href="../loginRegister.html"><i class="fas fa-sign-out-alt"></i><p>Log Out</p></a></li>
  </ul>
</div>

<section class="main">
  <a href="./my-courses.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to My Courses</a>

  <?php if ($course): ?>
    <div class="course-detail-header">
      <h1><?php echo htmlspecialchars($course['name']); ?></h1>
      <p><i class="fas fa-user-tie"></i> Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?></p>
    </div>

    <div class="course-section">
      <h2>Course Description</h2>
      <p><?php echo htmlspecialchars($course['description']); ?></p>
    </div>

    <div class="course-section">
      <h2>Assignments</h2>
      <?php if (!empty($assignments)): ?>
        <ul class="assignment-list">
          <?php foreach($assignments as $a): ?>
            <li>
              <div>
                <strong><?php echo htmlspecialchars($a['title']); ?></strong><br>
                <small>Due: <?php echo date('M d, Y', strtotime($a['due_date'])); ?> &nbsp;|&nbsp; Points: <?php echo $a['max_score']; ?></small>
              </div>
              <div>
                <span class="badge badge-<?php echo $a['status']; ?>"><?php echo ucfirst($a['status']); ?></span>
                <a href="assignment-details.php?id=<?php echo $a['id']; ?>" style="margin-left:10px; color:#8b0000;">View</a>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="no-data">No assignments for this course yet.</p>
      <?php endif; ?>
    </div>

  <?php else: ?>
    <div class="course-section">
      <p class="no-data">Course not found or you are not enrolled in this course.</p>
    </div>
  <?php endif; ?>
</section>
</body>
</html>
