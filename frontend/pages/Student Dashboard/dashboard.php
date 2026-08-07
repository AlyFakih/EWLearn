<?php
// Start the session to maintain user login state
session_start();

// Check if the user is logged in and is a student (role = 'student')
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
  // Redirect to login page if not logged in as student
  header("Location: ../loginRegister.html");
  exit();
}

// Include database controller and notifications
require_once "php/dbcontroller.php";
require_once "../common/notifications.php";
require_once "../common/calendar.php";

$db_handle = new StudentDBController();
$notification_manager = new NotificationManager();
$calendar_manager = new CalendarManager();

// Get student information
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ? AND role = 'student'";
$result = $db_handle->executeSelectPrepared($query, "i", [$user_id]);
$student = $result[0];

// Get enrolled courses
$query = "SELECT c.id, c.courseTitle as name, c.description, CONCAT('../../assets/images/', c.image) as image_path,
        sc.userInstructorID as instructor_name
        FROM courses c
        JOIN studentcourse sc ON c.courseTitle = sc.courseID
        JOIN users u ON sc.userStudentID = u.fullName
        WHERE u.id = ?";
$courses = $db_handle->executeSelectPrepared($query, "i", [$user_id]);

// Get upcoming events from calendar
$upcoming_events = $calendar_manager->getUpcomingEvents(3);

// Count unread notifications
$unread_count = $notification_manager->countUnreadNotifications($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard - EWLearn</title>
  <link rel="stylesheet" href="./css/dashboard.css">
  <link rel="stylesheet" href="./css/dashboard-menu.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
  <!-- Include notification and calendar styles -->
  <?php include_once "../common/header_includes.php"; ?>
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
        <a class="active" href="./dashboard.php">
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

  <!-- Main Content -->
  <section class="main">
    <!-- Welcome Header -->
    <div class="header">
      <h1>Welcome, <?php echo $student['fullName']; ?></h1>
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

    <!-- Dashboard Stats -->
    <div class="dashboard-stats">
      <div class="stat-card">
        <div class="icon">
          <i class="fas fa-book"></i>
        </div>
        <div class="details">
          <h3>My Courses</h3>
          <p><?php echo count($courses); ?></p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="icon">
          <i class="fas fa-tasks"></i>
        </div>
        <div class="details">
          <h3>Assignments</h3>
          <p id="assignment-count">Loading...</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="icon">
          <i class="fas fa-calendar-check"></i>
        </div>
        <div class="details">
          <h3>Attendance</h3>
          <p id="attendance-rate">Loading...</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="icon">
          <i class="fas fa-graduation-cap"></i>
        </div>
        <div class="details">
          <h3>Average Grade</h3>
          <p id="average-grade">Loading...</p>
        </div>
      </div>
    </div>

    <!-- Upcoming Deadlines -->
    <div class="upcoming-section">
      <h2>Upcoming Deadlines</h2>
      <div class="deadlines-container" id="deadlines-container">
        <p class="loading-text">Loading upcoming deadlines...</p>
      </div>
    </div>

    <!-- Recent Courses -->
    <div class="courses-section">
      <h2>My Courses</h2>
      <div class="courses-container">
        <?php if (!empty($courses)): ?>
          <?php foreach($courses as $course): ?>
            <div class="course-card">
              <div class="course-image">
                <img src="<?php echo $course['image_path'] ? $course['image_path'] : '../../assets/images/course-default.jpg'; ?>" alt="<?php echo $course['name']; ?>">
              </div>
              <div class="course-info">
                <h3><?php echo $course['name']; ?></h3>
                <p class="instructor">Instructor: <?php echo $course['instructor_name']; ?></p>
                <p class="description"><?php echo substr($course['description'], 0, 100) . '...'; ?></p>
                <a href="course-details.php?id=<?php echo $course['id']; ?>" class="view-button">View Course</a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-courses">
            <p>You are not enrolled in any courses yet.</p>
            <a href="../courses.html" class="btn">Browse Courses</a>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Recent Announcements -->
    <div class="announcements-section">
      <h2>Recent Announcements</h2>
      <div class="announcements-container" id="announcements-container">
        <p class="loading-text">Loading announcements...</p>
      </div>
    </div>
    
    <!-- Academic Calendar -->
    <div class="calendar-section">
      <h2>Academic Calendar</h2>
      <div class="calendar-container">
        <div id="calendar"></div>
      </div>
      
      <!-- Upcoming Events -->
      <div class="upcoming-events">
        <h3>Upcoming Events</h3>
        <div class="events-list">
          <?php if(!empty($upcoming_events)): ?>
            <?php foreach($upcoming_events as $event): ?>
              <div class="event-item">
                <div class="event-date">
                  <span class="date"><?php echo date('d', strtotime($event['start_date'])); ?></span>
                  <span class="month"><?php echo date('M', strtotime($event['start_date'])); ?></span>
                </div>
                <div class="event-details">
                  <h4><?php echo $event['title']; ?></h4>
                  <p class="event-time">
                    <i class="fas fa-clock"></i> 
                    <?php echo date('h:i A', strtotime($event['start_date'])); ?> - 
                    <?php echo date('h:i A', strtotime($event['end_date'])); ?>
                  </p>
                  <p class="event-course">
                    <i class="fas fa-book"></i> 
                    <?php echo $event['courseTitle'] ?? 'School-wide'; ?>
                  </p>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="no-events">No upcoming events</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script src="./js/dashboard.js"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize notification system
      const notificationSystem = new NotificationSystem();
      notificationSystem.init();
      
      // Initialize academic calendar if element exists
      const calendarEl = document.getElementById('calendar');
      if (calendarEl) {
        const calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: 'dayGridMonth',
          height: 450,
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
          },
          events: '../common/calendar_api.php?action=get_events',
          eventClick: function(info) {
            const event = info.event;
            alert(`Event: ${event.title}\nTime: ${event.start.toLocaleString()}\n${event.extendedProps.description || ''}`);
          }
        });
        calendar.render();
      }
    });
  </script>
</body>
</html>
