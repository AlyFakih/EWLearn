<?php
session_start();

// Check if user is logged in and is a teacher (role = 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    header("Location: ../../../login.php");
    exit();
}

// Include database controller and required classes
require_once "dbcontroller.php";
require_once "../../common/notifications.php";
require_once "../../common/calendar.php";

$db_handle = new DBController();
$notification_manager = new NotificationManager();
$calendar_manager = new CalendarManager();

// Get teacher information
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ? AND role = 1";
$result = $db_handle->executeSelectPrepared($query, "i", [$user_id]);

if (empty($result)) {
    header("Location: ../../../login.php");
    exit();
}

$teacher = $result[0];

// Count unread notifications
$unread_count = $notification_manager->countUnreadNotifications($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? $page_title : 'Teacher Dashboard'; ?> - EWLearn</title>
  
  <!-- Common CSS -->
  <link rel="stylesheet" href="./css/dashboard-menu.css">
  <?php if (isset($page_css) && !empty($page_css)): ?>
  <link rel="stylesheet" href="./css/<?php echo $page_css; ?>.css">
  <?php endif; ?>
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
  
  <!-- Include notification and calendar styles -->
  <?php include_once "../../common/header_includes.php"; ?>
</head>
<body>
  <!-- Sidebar Menu -->
  <div class="menu">
    <ul>
      <li class="profile">
        <div class="img-box">
          <img src="./images/logo.jpg" alt="logo image">
        </div>
        <h2><?php echo $teacher['full_name']; ?></h2>
      </li>
      <li>
        <a <?php echo $current_page === 'profile' ? 'class="active"' : ''; ?> href="./profile-dashboard.php">
          <i class="fas fa-home"></i>
          <p>Profile</p>
        </a>
      </li>
      <li>
        <a <?php echo $current_page === 'courses' ? 'class="active"' : ''; ?> href="./course-dashboard.php">
          <i class="fas fa-book"></i>
          <p>Courses</p>
        </a>
      </li>
      <li>
        <a <?php echo $current_page === 'students' ? 'class="active"' : ''; ?> href="./student-dashboard.php">
          <i class="fas fa-user-group"></i>
          <p>Students</p>
        </a>
      </li>
      <li>
        <a <?php echo $current_page === 'exams' ? 'class="active"' : ''; ?> href="./exam-dashboard.php">
          <i class="fas fa-pencil-alt"></i>
          <p>Exams</p>
        </a>
      </li>
      <li>
        <a <?php echo $current_page === 'grades' ? 'class="active"' : ''; ?> href="./grades-dashboard.php">
          <i class="fas fa-graduation-cap"></i>
          <p>Grades</p>
        </a>
      </li>
      <li>
        <a <?php echo $current_page === 'assignments' ? 'class="active"' : ''; ?> href="./assignment-dashboard.php">
          <i class="fas fa-tasks"></i>
          <p>Assignments</p>
        </a>
      </li>
      <li>
        <a <?php echo $current_page === 'attendance' ? 'class="active"' : ''; ?> href="./attendence-dashboard.php">
          <i class="fas fa-user-check"></i>
          <p>Attendance</p>
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
    <!-- Header with notification bell -->
    <div class="header">
      <h1><?php echo isset($page_title) ? $page_title : 'Teacher Dashboard'; ?></h1>
      <div class="date">
        <p><?php echo date('l, F j, Y'); ?></p>
      </div>
      <!-- Notification Bell -->
      <div class="notification-bell">
        <i class="fas fa-bell"></i>
        <?php if($unread_count > 0): ?>
          <span class="notification-count"><?php echo $unread_count; ?></span>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Page content will be here -->
