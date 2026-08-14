<?php
// Server-side authorization gate: student only. Runs first so nothing is
// emitted to an unauthenticated, wrong-role, expired or deleted account.
require_once __DIR__ . '/../../core/auth_guard.php';
auth_require_role('student', 'page', '../loginRegister.html');

// Start the session to maintain user login state

// Check if the user is logged in and is a student (role = 'student')
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
  // Redirect to login page if not logged in as student
  header("Location: ../loginRegister.html");
  exit();
}

// Include database controller
require_once "../../core/DBController.php";
$db_handle = new DBController();

// Get student information
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = $user_id AND role = 'student'";
$result = $db_handle->readData($sql);
$student = $result[0];
$studentImage = "./images/profile.jpg";

if (!empty($student['image'])) {
    $studentImage = "../../images/" . $student["image"];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - Student Dashboard - EWLearn</title>
  <link rel="stylesheet" href="./css/dashboard.css">
  <link rel="stylesheet" href="./css/dashboard-menu.css">
  <link rel="stylesheet" href="./css/profile.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
</head>
<body>
  <!-- Sidebar Menu -->
  <div class="menu">
    <ul>
      <li class="profile">
        <div class="img-box">
       <img src="<?php echo $studentImage; ?>" alt="profile image">  
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
        <a href="./attendance.php">
          <i class="fas fa-user-check"></i>
          <p>Attendance</p>
        </a>
      </li>
      <li>
        <a class="active" href="./profile.php">
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
      <h1>My Profile</h1>
      <div class="date">
        <p><?php echo date('l, F j, Y'); ?></p>
      </div>
    </div>

    <div class="profile-container">
      <div class="profile-header">
        <div class="profile-image">
          <img src="<?php echo $studentImage; ?>" alt="Profile Picture">
          <div class="edit-overlay">
            <label for="profile-upload" class="edit-button">
              <i class="fas fa-camera"></i>
            </label>
            <input type="file" id="profile-upload" style="display: none;">
          </div>
        </div>
        <div class="profile-info">
          <h2><?php echo $student['fullName']; ?></h2>
          <p class="student-id">Student ID: <?php echo $student['id']; ?></p>
          <p><i class="fas fa-envelope"></i> <?php echo $student['email']; ?></p>
          <p><i class="fas fa-phone"></i> <?php echo $student['mobile']; ?></p>
        </div>
      </div>

      <div class="profile-tabs">
        <button class="tab-button active" data-tab="personal">Personal Information</button>
        <button class="tab-button" data-tab="academic">Academic Details</button>
        <button class="tab-button" data-tab="security">Security Settings</button>
      </div>

      <div class="tab-content">
        <!-- Personal Information Tab -->
        <div class="tab-pane active" id="personal-tab">
          <form id="personal-form">
            <div class="form-group">
              <label for="fullName">Full Name</label>
              <input type="text" id="fullName" name="fullName" value="<?php echo $student['fullName']; ?>">
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo $student['email']; ?>">
              </div>
              <div class="form-group">
                <label for="mobile">Mobile Number</label>
                <input type="text" id="mobile" name="mobile" value="<?php echo $student['mobile']; ?>">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender">
                  <option value="male" <?php echo ($student['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                  <option value="female" <?php echo ($student['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                </select>
              </div>
              <div class="form-group">
                <label for="bloodType">Blood Type</label>
                <select id="bloodType" name="bloodType">
                  <option value="A+" <?php echo (($student['blood'] ?? '') == 'A+') ? 'selected' : ''; ?>>A+</option>
                  <option value="A-" <?php echo (($student['blood'] ?? '') == 'A-') ? 'selected' : ''; ?>>A-</option>
                  <option value="B+" <?php echo (($student['blood'] ?? '') == 'B+') ? 'selected' : ''; ?>>B+</option>
                  <option value="B-" <?php echo (($student['blood'] ?? '') == 'B-') ? 'selected' : ''; ?>>B-</option>
                  <option value="AB+" <?php echo (($student['blood'] ?? '') == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                  <option value="AB-" <?php echo (($student['blood'] ?? '') == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                  <option value="O+" <?php echo (($student['blood'] ?? '') == 'O+') ? 'selected' : ''; ?>>O+</option>
                  <option value="O-" <?php echo (($student['blood'] ?? '') == 'O-') ? 'selected' : ''; ?>>O-</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label for="country">Country</label>
              <input type="text" id="country" name="country" value="<?php echo $student['country']; ?>">
            </div>
            <div class="form-buttons">
              <button type="submit" class="save-btn">Save Changes</button>
            </div>
          </form>
        </div>

        <!-- Academic Details Tab -->
        <div class="tab-pane" id="academic-tab">
          <div class="academic-info">
            <div class="academic-section">
              <h3>Enrolled Courses</h3>
              <?php 
              $sql = "SELECT c.courseTitle as name, sc.userInstructorID as instructor_name
                      FROM courses c
                      JOIN studentcourse sc ON c.courseTitle = sc.courseID
                      JOIN users u ON sc.userStudentID = u.fullName
                      WHERE u.id = $user_id";
              $courses = $db_handle->readData($sql);
              
              if (!empty($courses)): 
              ?>
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Course Name</th>
                      <th>Instructor</th>
                      <th>Start Date</th>
                      <th>End Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach($courses as $course): ?>
                    <tr>
                      <td><?php echo $course['name']; ?></td>
                      <td><?php echo $course['instructor_name']; ?></td>
                      <td colspan="2">N/A</td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php else: ?>
                <p class="no-data">You are not enrolled in any courses.</p>
              <?php endif; ?>
            </div>
            
            <div class="academic-section">
              <h3>Performance Summary</h3>
              <div class="performance-stats">
                <div class="stat-box">
                  <span class="stat-label">Average Grade</span>
                  <span class="stat-value" id="avg-grade">Loading...</span>
                </div>
                <div class="stat-box">
                  <span class="stat-label">Attendance Rate</span>
                  <span class="stat-value" id="attendance-rate">Loading...</span>
                </div>
                <div class="stat-box">
                  <span class="stat-label">Assignments Completed</span>
                  <span class="stat-value" id="completed-assignments">Loading...</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Security Settings Tab -->
        <div class="tab-pane" id="security-tab">
          <form id="security-form">
            <div class="form-group">
              <label for="current-password">Current Password</label>
              <input type="password" id="current-password" name="current-password">
            </div>
            <div class="form-group">
              <label for="new-password">New Password</label>
              <input type="password" id="new-password" name="new-password">
            </div>
            <div class="form-group">
              <label for="confirm-password">Confirm New Password</label>
              <input type="password" id="confirm-password" name="confirm-password">
            </div>
            <div class="form-buttons">
              <button type="submit" class="save-btn">Change Password</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script>
    $(document).ready(function() {
      // Tab switching functionality
      $('.tab-button').click(function() {
        var tabId = $(this).data('tab');
        
        // Remove active class from all tabs and panes
        $('.tab-button').removeClass('active');
        $('.tab-pane').removeClass('active');
        
        // Add active class to current tab and pane
        $(this).addClass('active');
        $('#' + tabId + '-tab').addClass('active');
      });

      // Load performance data
      $.ajax({
        url: 'php/get_average_grade.php',
        type: 'GET',
        success: function(response) {
          $('#avg-grade').text(response);
        }
      });

      $.ajax({
        url: 'php/get_attendance_rate.php',
        type: 'GET',
        success: function(response) {
          $('#attendance-rate').text(response + '%');
        }
      });

      // Personal information form submission
      $('#personal-form').submit(function(e) {
        e.preventDefault();
        $.ajax({
          type: "POST",
          url: "php/update_profile.php",
          data: $(this).serialize(),
          success: function(response) {
            alert("Profile updated successfully!");
          },
          error: function() {
            alert("Error updating profile. Please try again.");
          }
        });
      });

      // Security form submission
      $('#security-form').submit(function(e) {
        e.preventDefault();
        
        // Password validation
        var newPassword = $('#new-password').val();
        var confirmPassword = $('#confirm-password').val();
        
        if (newPassword !== confirmPassword) {
          alert("Passwords do not match!");
          return;
        }
        
        $.ajax({
          type: "POST",
          url: "php/update_password.php",
          data: $(this).serialize(),
          success: function(response) {
            alert("Password changed successfully!");
            $('#security-form')[0].reset();
          },
          error: function() {
            alert("Error changing password. Please check your current password and try again.");
          }
        });
      });
    });
  </script>
</body>
</html>
