<?php
// Server-side gate. These pages compose components/ directly instead of
// going through php/header.php, so they must load the guard themselves -
// otherwise they would run with only an inline session check and no
// database re-validation of the account.
require_once __DIR__ . "/../../core/auth_guard.php";
auth_require_role("instructor", "page", "../loginRegister.html");

// Start session and include required files
require_once "../../core/DBController.php";
require_once "../common/header_includes.php";

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    header("Location: ../loginRegister.html");
    exit();
}

$db_handle = new DBController();
$user_id = $_SESSION['user_id'];

// Get exams with course details using prepared statements. Teacher-course
// ownership is recorded in instructorcourse (users.fullName <-> courses.courseTitle),
// there is no courses.teacher_id column.
$sql = "SELECT e.id, e.date, e.time, e.course_id, c.courseTitle, c.courseCode, e.subject, e.room
        FROM exam e
        JOIN courses c ON e.course_id = c.id
        JOIN instructorcourse ic ON ic.courseID = c.courseTitle
        JOIN users tu ON tu.fullName = ic.userInstructorID
        WHERE tu.id = ?
        ORDER BY e.date ASC";
$exams = $db_handle->executeSelectPrepared($sql, "i", [$user_id]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Exams</title>
    
    <!-- Shared design system -->
    <link rel="stylesheet" href="./css/dashboard-theme.css">

    <!-- Include common CSS (jQuery, notifications, FullCalendar) -->
    <?php include_once "../common/header_includes.php"; ?>

    <!-- Page specific stylesheet -->
    <link rel="stylesheet" href="./css/exam-dashboard.css">

    <style>
        #calendar { max-width: 1100px; margin: var(--space-5) auto; }
        #mini-calendar { height: 250px; margin-bottom: var(--space-4); }
        .table-container { margin-top: var(--space-5); }
        .modal.open { display: flex !important; }
    </style>
</head>

<body>
    <div class="app-shell">
        <?php include_once "components/sidebar.php"; ?>
        <div class="main-col">
            <?php include_once "components/header.php"; ?>
            <main class="page-content">

            <div class="content-header">
                <h1><i class="fas fa-pencil-alt"></i> Exam Management</h1>
                <button id="add-exam-btn" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Exam</button>
            </div>

            <div class="table-container card">
                <div class="table-header card-header">
                    <h2>Scheduled Exams</h2>
                    <div class="search-container">
                        <input type="text" id="search-exams" placeholder="Search exams...">
                        <i class="fas fa-search"></i>
                    </div>
                </div>
                
                <div class="table-wrap">
                <table id="exams-table" class="data-table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Room</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($exams)): ?>
                            <?php foreach ($exams as $exam): ?>
                                <tr data-id="<?php echo $exam['id']; ?>">
                                    <td data-field="course">
                                        <?php echo $exam['courseCode'] . " - " . $exam['courseTitle']; ?>
                                    </td>
                                    <td data-field="subject"><?php echo $exam['subject']; ?></td>
                                    <td data-field="date"><?php echo date('M d, Y', strtotime($exam['date'])); ?></td>
                                    <td data-field="time"><?php echo date('g:i A', strtotime($exam['time'])); ?></td>
                                    <td data-field="room"><?php echo $exam['room']; ?></td>
                                    <td class="action-btns">
                                        <button class="edit-exam" data-id="<?php echo $exam['id']; ?>" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="view-exam" data-id="<?php echo $exam['id']; ?>" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="delete-exam" data-id="<?php echo $exam['id']; ?>" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="no-data">No exams scheduled yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Calendar Section -->
            <div class="calendar-section card" style="margin-top: var(--space-5);">
                <h2 class="section-title">Exam Calendar</h2>
                <div id="calendar"></div>
            </div>
            </main>
        </div>
    </div>

    <!-- Add Exam Modal -->
    <div id="add-exam-modal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Add New Exam</h2>
            <form id="add-exam-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="course_id">Course:</label>
                        <select id="course_id" name="course_id" required>
                            <option value="">Select Course</option>
                            <?php
                            // Get courses taught by this teacher
                            $courses_query = "SELECT c.id, c.courseTitle, c.courseCode FROM courses c
                                             JOIN instructorcourse ic ON ic.courseID = c.courseTitle
                                             JOIN users tu ON tu.fullName = ic.userInstructorID
                                             WHERE tu.id = ?";
                            $courses = $db_handle->executeSelectPrepared($courses_query, "i", [$user_id]);
                            
                            if (!empty($courses)) {
                                foreach ($courses as $course) {
                                    echo "<option value='" . $course['id'] . "'>" . $course['courseCode'] . " - " . $course['courseTitle'] . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject/Topic:</label>
                        <input type="text" id="subject" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label for="exam_date">Date:</label>
                        <input type="date" id="exam_date" name="exam_date" required>
                    </div>
                    <div class="form-group">
                        <label for="exam_time">Time:</label>
                        <input type="time" id="exam_time" name="exam_time" required>
                    </div>
                    <div class="form-group">
                        <label for="room">Room:</label>
                        <input type="text" id="room" name="room" required>
                    </div>
                    <div class="form-group">
                        <label for="duration">Duration (minutes):</label>
                        <input type="number" id="duration" name="duration" min="15" step="15" value="60" required>
                    </div>
                </div>
                <div class="form-buttons">
                    <button type="submit" class="btn-primary">Add Exam</button>
                    <button type="button" class="btn-secondary cancel-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Exam Modal -->
    <div id="edit-exam-modal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Edit Exam</h2>
            <form id="edit-exam-form">
                <input type="hidden" id="edit_exam_id" name="exam_id">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_course_id">Course:</label>
                        <select id="edit_course_id" name="course_id" required>
                            <option value="">Select Course</option>
                            <?php
                            if (!empty($courses)) {
                                foreach ($courses as $course) {
                                    echo "<option value='" . $course['id'] . "'>" . $course['courseCode'] . " - " . $course['courseTitle'] . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_subject">Subject/Topic:</label>
                        <input type="text" id="edit_subject" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_exam_date">Date:</label>
                        <input type="date" id="edit_exam_date" name="exam_date" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_exam_time">Time:</label>
                        <input type="time" id="edit_exam_time" name="exam_time" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_room">Room:</label>
                        <input type="text" id="edit_room" name="room" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_duration">Duration (minutes):</label>
                        <input type="number" id="edit_duration" name="duration" min="15" step="15" value="60" required>
                    </div>
                </div>
                <div class="form-buttons">
                    <button type="submit" class="btn-primary">Update Exam</button>
                    <button type="button" class="btn-secondary cancel-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- View Exam Modal -->
    <div id="view-exam-modal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Exam Details</h2>
            <div class="exam-details">
                <p><strong>Course:</strong> <span id="view_course"></span></p>
                <p><strong>Subject:</strong> <span id="view_subject"></span></p>
                <p><strong>Date:</strong> <span id="view_date"></span></p>
                <p><strong>Time:</strong> <span id="view_time"></span></p>
                <p><strong>Room:</strong> <span id="view_room"></span></p>
                <p><strong>Duration:</strong> <span id="view_duration"></span></p>
                <hr>
                <h3>Students Enrolled in This Course</h3>
                <div id="enrolled-students-list">
                    <!-- Students will be loaded dynamically -->
                </div>
            </div>
            <div class="form-buttons">
                <button type="button" class="btn-secondary cancel-modal">Close</button>
            </div>
        </div>
    </div>
    
    <!-- jQuery already loaded via header_includes.php -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
    <script src="js/sidebar-toggle.js"></script>

    <!-- Page specific scripts -->
    <script src="js/exam-combined.js"></script>
    
    <!-- Include the common footer -->
    <?php include_once "components/footer.php"; ?>
</body>
</html>
