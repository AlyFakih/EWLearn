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
$sql = "SELECT * FROM users WHERE id = $user_id AND role = 'student'";
$result = $db_handle->readData($sql);
$student = $result[0];

$sql = "SELECT c.id, c.courseTitle as name, c.description, c.image,
        sc.userInstructorID as instructor_name, c.calendar
        FROM courses c
        JOIN studentcourse sc ON c.courseTitle = sc.courseID
        JOIN users u ON sc.userStudentID = u.fullName
        WHERE u.id = $user_id";
$courses = $db_handle->readData($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Courses - Student Dashboard - EWLearn</title>
  <link rel="stylesheet" href="./css/dashboard.css">
  <link rel="stylesheet" href="./css/dashboard-menu.css">
  <link rel="stylesheet" href="./css/my-courses.css">
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
        <a class="active" href="./my-courses.php">
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
      <h1>My Courses</h1>
      <div class="search-container">
        <input type="text" id="courseSearch" placeholder="Search courses...">
        <i class="fas fa-search"></i>
      </div>
    </div>

    <div class="courses-container">
      <?php if (!empty($courses)): ?>
        <?php foreach($courses as $course): ?>
          <div class="course-card">
            <div class="course-header">
              <img src="<?php echo !empty($course['image']) ? './images/' . $course['image'] : './images/default-course.png'; ?>" alt="<?php echo $course['name']; ?>">
              <div class="overlay">
                <a href="course-details.php?id=<?php echo $course['id']; ?>" class="view-button">View Course</a>
              </div>
            </div>
            <div class="course-body">
              <h3><?php echo $course['name']; ?></h3>
              <p class="instructor"><i class="fas fa-user-tie"></i> <?php echo $course['instructor_name']; ?></p>
              <div class="course-info">
                <div class="info-item">
                  <i class="fas fa-calendar-alt"></i>
                  <span><?php echo $course['calendar'] ? date('M d, Y', strtotime($course['calendar'])) : 'Date TBD'; ?></span>
                </div>
              </div>
              <p class="description"><?php echo substr($course['description'], 0, 100) . '...'; ?></p>
            </div>
            <div class="course-footer">
              <a href="course-details.php?id=<?php echo $course['id']; ?>" class="btn">AccessMaterials</a>
              <a href="assignments.php?course=<?php echo $course['id']; ?>" class="btn outline">Assignments</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-courses">
          <img src="./images/no-courses.svg" alt="No courses">
          <h2>No Courses Found</h2>
          <p>You are not enrolled in any courses yet.</p>
          <a href="../courses.php" class="btn">Browse Available Courses</a>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script>
    $(document).ready(function() {
      $("#courseSearch").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $(".course-card").filter(function() {
          $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
        if ($(".course-card:visible").length == 0) {
          if ($(".no-results").length == 0) {
            $(".courses-container").append('<div class="no-results"><p>No courses match your search.</p></div>');
          }
        } else {
          $(".no-results").remove();
        }
      });
    });
  </script>
</body>
</html>
