<?php
// Server-side authorization gate: instructor only. Runs before any other
// logic so nothing is emitted to an unauthenticated or wrong-role caller.
require_once __DIR__ . '/../../../core/auth_guard.php';
auth_require_role('instructor');

require_once "../../../core/DBController.php";
require_once "../../common/notifications.php";
require_once "../../common/calendar.php";

// Check if user is logged in and is a teacher (role = 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    $response = array('success' => false, 'message' => 'Unauthorized access');
    echo json_encode($response);
    exit();
}

$db_handle = new DBController();
$notification_manager = new NotificationManager($db_handle);
$calendar_manager = new CalendarManager($db_handle);
$user_id = $_SESSION['user_id'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = array();
    
    // Validate required fields
    if (empty($_POST['course_id']) || empty($_POST['title']) || empty($_POST['description']) || empty($_POST['deadline'])) {
        $response = array('success' => false, 'message' => 'All fields are required');
    } else {
        // Clean and sanitize input
        $course_id = $db_handle->cleanData($_POST['course_id']);
        $title = $db_handle->cleanData($_POST['title']);
        $description = $db_handle->cleanData($_POST['description']);
        $deadline = $db_handle->cleanData($_POST['deadline']);
        $max_points = isset($_POST['max_points']) ? $db_handle->cleanData($_POST['max_points']) : 100;

        // Verify that the teacher owns this course
        if (!$db_handle->isCourseOwnedByTeacher($course_id, $user_id)) {
            $response = array('success' => false, 'message' => 'You do not have permission to create assignments for this course');
        } else {
            // Insert the assignment (due_date/max_score are the actual
            // column names; studentID/studentName/courseName/date/status are
            // legacy per-student fields that don't apply to a course-wide
            // assignment and are nullable)
            $insert_query = "INSERT INTO assignment (title, description, course_id, due_date, max_score, created_at)
                           VALUES (?, ?, ?, ?, ?, NOW())";
            $result = $db_handle->executeUpdatePrepared($insert_query, "ssisd",
                [$title, $description, $course_id, $deadline, $max_points]);

            if ($result) {
                // Get the new assignment ID
                $assignment_id = $db_handle->getLastInsertId();

                // Add calendar event for this assignment
                $calendar_manager->createEvent(
                    'Assignment Due: ' . $title,
                    $description,
                    $deadline,
                    $deadline,
                    false,
                    'assignment',
                    $course_id,
                    '#e74c3c',
                    $user_id
                );

                // Get all students enrolled in this course (studentcourse is
                // keyed by users.fullName / courses.courseTitle)
                $students_query = "SELECT u.id AS student_id
                                  FROM studentcourse sc
                                  JOIN courses c ON c.courseTitle = sc.courseID
                                  JOIN users u ON u.fullName = sc.userStudentID
                                  WHERE c.id = ?";
                $students = $db_handle->executeSelectPrepared($students_query, "i", [$course_id]);

                // Get course name for notifications
                $course_query = "SELECT courseTitle FROM courses WHERE id = ?";
                $course_result = $db_handle->executeSelectPrepared($course_query, "i", [$course_id]);
                $course_name = $course_result[0]['courseTitle'];
                
                // Create notifications for all enrolled students
                if (!empty($students)) {
                    foreach ($students as $student) {
                        $notification_manager->createNotification(
                            $student['student_id'],
                            "New Assignment: {$title}",
                            "A new assignment '{$title}' has been posted for {$course_name}. Due date: " . date('Y-m-d H:i', strtotime($deadline)),
                            "assignment",
                            $assignment_id
                        );
                    }
                }
                
                $response = array('success' => true, 'message' => 'Assignment created successfully', 'id' => $assignment_id);
            } else {
                $response = array('success' => false, 'message' => 'Failed to create assignment');
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
} else {
    // Not a POST request
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(array('success' => false, 'message' => 'Method not allowed'));
    exit();
}
?>
