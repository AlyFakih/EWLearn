<?php
// Start session and include required files
session_start();
require_once "php/dbcontroller.php";
require_once "../common/header_includes.php";

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: ../../login.php");
    exit();
}

$db_handle = new DBController();
$user_id = $_SESSION['user_id'];

// Get exams with course details using prepared statements
$sql = "SELECT e.id, e.date, e.time, e.course_id, c.courseTitle, c.courseCode, e.subject, e.room 
        FROM exam e 
        JOIN courses c ON e.course_id = c.id
        WHERE c.teacher_id = ?
        ORDER BY e.date ASC";
$exams = $db_handle->executeSelectPrepared($sql, "i", [$user_id]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Exams</title>
    
    <!-- Include common CSS -->
    <?php include_once "../common/header_includes.php"; ?>
    
    <!-- Page specific stylesheets -->
    <link rel="stylesheet" href="./css/exam-dashboard.css">
    <link rel="stylesheet" href="./css/dashboard-menu.css">
    
    <!-- Include FullCalendar for events -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css' rel='stylesheet' />
    
    <style>
        #calendar {
            max-width: 1100px;
            margin: 20px auto;
            padding: 0 10px;
        }
        
        #mini-calendar {
            height: 250px;
            margin-bottom: 20px;
        }
        
        .course-title {
            color: #810000;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .table-container {
            margin-top: 25px;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 1000;
        }
        
        .modal-content {
            position: relative;
            margin: 10% auto;
            width: 60%;
            max-width: 600px;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .modal h2 {
            color: #810000;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #810000;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .form-buttons {
            margin-top: 20px;
            text-align: center;
        }
        
        .form-buttons button {
            margin: 0 10px;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: #810000;
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
    </style>
</head>

<body>
    <!-- Include the common header with notification bell -->
    <?php include_once "components/header.php"; ?>
    
    <div class="dashboard-container">
        <!-- Include the sidebar with mini calendar -->
        <?php include_once "components/sidebar.php"; ?>
        
        <div class="dashboard-content">
            <div class="content-header">
                <h1><i class="fas fa-pencil-alt"></i> Exam Management</h1>
                <button id="add-exam-btn" class="btn-primary"><i class="fas fa-plus"></i> Add New Exam</button>
            </div>
            
            <div class="table-container">
                <div class="table-header">
                    <h2>Scheduled Exams</h2>
                    <div class="search-container">
                        <input type="text" id="search-exams" placeholder="Search exams...">
                        <i class="fas fa-search"></i>
                    </div>
                </div>
                
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
            
            <!-- Calendar Section -->
            <div class="calendar-section">
                <h2 class="section-title">Exam Calendar</h2>
                <div id="calendar"></div>
            </div>
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
                            $courses_query = "SELECT id, courseTitle, courseCode FROM courses WHERE teacher_id = ?";
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
    
    <!-- Include common JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
    
    <!-- Page specific scripts -->
    <script src="js/exam-combined.js"></script>
    
    <!-- Include the common footer -->
    <?php include_once "components/footer.php"; ?>
</body>
</html>
