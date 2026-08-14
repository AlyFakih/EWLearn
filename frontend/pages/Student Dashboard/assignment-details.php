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

// Check if assignment ID is provided
if (!isset($_GET['id'])) {
  header("Location: assignments.php");
  exit();
}

$assignment_id = intval($_GET['id']);

// Get assignment details
$assignment_sql = "SELECT a.*, c.courseTitle as course_name
                  FROM assignment a
                  JOIN courses c ON a.course_id = c.id
                  WHERE a.id = $assignment_id";
$assignment_result = $db_handle->readData($assignment_sql);

if (empty($assignment_result)) {
  header("Location: assignments.php");
  exit();
}

$assignment = $assignment_result[0];

// Check if student is enrolled in the course
$enrollment_check = "SELECT sc.*
                    FROM studentcourse sc
                    JOIN users u ON sc.userStudentID = u.fullName
                    WHERE u.id = $user_id
                    AND sc.courseID = '{$assignment['course_name']}'";
$enrollment_result = $db_handle->readData($enrollment_check);

if (empty($enrollment_result)) {
  header("Location: assignments.php");
  exit();
}

// Check if assignment is already submitted
$submission_sql = "SELECT * FROM assignment_submissions 
                  WHERE student_id = $user_id 
                  AND assignment_id = $assignment_id";
$submission_result = $db_handle->readData($submission_sql);
$is_submitted = !empty($submission_result);
$submission = $is_submitted ? $submission_result[0] : null;

// Process submission if form submitted
$submission_message = '';
$submission_success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && !$is_submitted) {
  $submission_text = trim($_POST['submission_text']);
  $file_path = '';

  // Same upload pattern as submit_assignment.php: FileHandler saves into
  // uploads/assignments/, relative path is identical since this file lives
  // at the same directory depth (frontend/pages/Student Dashboard/).
  if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] !== UPLOAD_ERR_NO_FILE) {
    require_once "../common/file_handler.php";
    $fileHandler = new FileHandler(
      ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'txt', 'jpg', 'jpeg', 'png'],
      10485760, // 10MB max size
      '../../../uploads/'
    );
    $newFilename = "assignment_{$assignment_id}_student_{$user_id}_" . time();
    $uploadResult = $fileHandler->uploadFile($_FILES['submission_file'], 'assignments/', $newFilename);

    if (!$uploadResult['success']) {
      $submission_message = 'File upload failed: ' . $uploadResult['message'];
    } else {
      $file_path = $uploadResult['file_path'];
    }
  }

  if ($submission_message === '' && (!empty($submission_text) || !empty($file_path))) {
    $submission_date = date('Y-m-d H:i:s');

    // Parameterised - the previous version interpolated $submission_text
    // straight into the SQL string.
    $insert_sql = "INSERT INTO assignment_submissions
                  (student_id, assignment_id, submission_text, file_path, submitted_at, status)
                  VALUES (?, ?, ?, ?, ?, 'submitted')";

    if ($db_handle->executeUpdatePrepared($insert_sql, "iisss", [$user_id, $assignment_id, $submission_text, $file_path, $submission_date])) {
      $submission_success = true;
      $submission_message = "Assignment submitted successfully!";

      // Refresh the submission data
      $submission_result = $db_handle->readData($submission_sql);
      $is_submitted = true;
      $submission = $submission_result[0];
    } else {
      $submission_message = "Error submitting assignment. Please try again.";
    }
  } elseif ($submission_message === '') {
    $submission_message = "Please provide a submission text or attach a file.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assignment Details - Student Dashboard - EWLearn</title>
  <link rel="stylesheet" href="./css/dashboard.css">
  <link rel="stylesheet" href="./css/dashboard-menu.css">
  <link rel="stylesheet" href="./css/assignment-details.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
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

  <!-- Main Content -->
  <section class="main">
    <!-- Back Link -->
    <div class="back-link">
      <a href="./assignments.php"><i class="fas fa-arrow-left"></i> Back to Assignments</a>
    </div>
    
    <!-- Assignment Details Card -->
    <div class="assignment-card">
      <div class="card-header">
        <div class="assignment-title">
          <h1><?php echo $assignment['title']; ?></h1>
          <div class="course-info">
            <span><?php echo $assignment['course_name']; ?></span>
          </div>
        </div>
        <div class="assignment-meta">
          <?php
            $due_date = strtotime($assignment['due_date']);
            $current_date = time();
            $is_overdue = $due_date < $current_date && !$is_submitted;
            
            if ($is_submitted && ($submission['grade'] ?? $submission['score'] ?? 'N/A') !== null) {
              $status_class = 'graded';
              $status_text = 'Graded';
            } elseif ($is_submitted) {
              $status_class = 'submitted';
              $status_text = 'Submitted';
            } elseif ($is_overdue) {
              $status_class = 'overdue';
              $status_text = 'Overdue';
            } else {
              $status_class = 'pending';
              $status_text = 'Pending';
            }
          ?>
          <div class="meta-item">
            <i class="fas fa-user-tie"></i>
            <span>Instructor: <?php echo ($assignment['instructor_name'] ?? 'N/A'); ?></span>
          </div>
          <div class="meta-item">
            <i class="fas fa-calendar"></i>
            <span>Due Date: <?php echo date('M d, Y', $due_date); ?></span>
            <?php if ($is_overdue): ?>
              <span class="overdue-badge">Overdue</span>
            <?php endif; ?>
          </div>
          <div class="meta-item">
            <i class="fas fa-star"></i>
            <span>Points: <?php echo $assignment['max_score']; ?></span>
          </div>
          <div class="meta-item">
            <i class="fas fa-tag"></i>
            <span>Type: <?php echo !empty($assignment['type']) ? $assignment['type'] : 'Assignment'; ?></span>
          </div>
          <div class="meta-item">
            <i class="fas fa-clock"></i>
            <span>Status: <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></span>
          </div>
        </div>
      </div>
      
      <div class="card-body">
        <div class="assignment-section">
          <h3>Assignment Description</h3>
          <div class="assignment-description">
            <?php echo nl2br($assignment['description']); ?>
          </div>
        </div>
        
        <?php if ($assignment['file_url']): ?>
        <div class="assignment-section">
          <h3>Assignment Files</h3>
          <div class="assignment-files">
            <a href="<?php echo $assignment['file_url']; ?>" class="file-link" download>
              <i class="fas fa-file-alt"></i>
              <span>Download Assignment File</span>
            </a>
          </div>
        </div>
        <?php endif; ?>
        
        <div class="assignment-section">
          <h3><?php echo $is_submitted ? 'Your Submission' : 'Submit Assignment'; ?></h3>
          
          <?php if ($submission_message): ?>
            <div class="submission-message <?php echo $submission_success ? 'success' : 'error'; ?>">
              <?php echo $submission_message; ?>
            </div>
          <?php endif; ?>
          
          <?php if (!$is_submitted): ?>
            <form method="post" class="submission-form" enctype="multipart/form-data">
              <div class="form-group">
                <label for="submission_text">Your Answer</label>
                <textarea id="submission_text" name="submission_text" rows="8" placeholder="Type your answer here..."></textarea>
              </div>
              
              <div class="form-group">
                <label for="submission_file">Attachment (Optional)</label>
                <div class="file-upload">
                  <input type="file" id="submission_file" name="submission_file">
                  <label for="submission_file">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Choose a file</span>
                  </label>
                </div>
                <div class="file-name" id="file-name"></div>
              </div>
              
              <div class="form-buttons">
                <button type="submit" class="submit-btn">Submit Assignment</button>
              </div>
            </form>
          <?php else: ?>
            <div class="submission-details">
              <div class="submission-meta">
                <div class="meta-item">
                  <i class="fas fa-calendar-check"></i>
                  <span>Submitted on: <?php echo date('M d, Y \a\t h:i A', strtotime($submission['submitted_at'])); ?></span>
                </div>
                
                <?php if ($submission['score'] !== null): ?>
                <div class="meta-item">
                  <i class="fas fa-award"></i>
                  <span>Grade: <?php echo $submission['score']; ?> / <?php echo $assignment['max_score']; ?></span>
                </div>
                <?php endif; ?>
              </div>
              
              <div class="submission-text">
                <h4>Your Answer</h4>
                <div class="text-content">
                  <?php echo nl2br(($submission['content'] ?? $submission['submission_text'] ?? '')); ?>
                </div>
              </div>
              
              <?php if (($submission['attachment_url'] ?? $submission['file_path'] ?? '')): ?>
              <div class="submission-files">
                <h4>Your Attachments</h4>
                <a href="<?php echo ($submission['attachment_url'] ?? $submission['file_path'] ?? ''); ?>" class="file-link" download>
                  <i class="fas fa-file-alt"></i>
                  <span>View/Download Attachment</span>
                </a>
              </div>
              <?php endif; ?>
              
              <?php if ($submission['feedback']): ?>
              <div class="submission-feedback">
                <h4>Instructor Feedback</h4>
                <div class="feedback-content">
                  <?php echo nl2br($submission['feedback']); ?>
                </div>
              </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script>
    $(document).ready(function() {
      // Display file name when file is selected
      $('#submission_file').change(function() {
        const fileName = $(this).val().split('\\').pop();
        $('#file-name').text(fileName ? fileName : '');
      });
    });
  </script>
</body>
</html>
