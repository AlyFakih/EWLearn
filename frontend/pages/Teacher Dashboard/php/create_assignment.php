<?php
session_start();
require_once "dbcontroller.php";
require_once "../../common/notifications.php";
require_once "../../common/calendar.php";

// Check if user is logged in and is a teacher (role = 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    $response = array('success' => false, 'message' => 'Unauthorized access');
    echo json_encode($response);
    exit();
}

$db_handle = new DBController();
$notification_manager = new NotificationManager();
$calendar_manager = new CalendarManager();
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
        $verify_query = "SELECT id FROM courses WHERE id = ? AND teacher_id = ?";
        $result = $db_handle->executeSelectPrepared($verify_query, "ii", [$course_id, $user_id]);
        
        if (empty($result)) {
            $response = array('success' => false, 'message' => 'You do not have permission to create assignments for this course');
        } else {
            // Insert the assignment
            $insert_query = "INSERT INTO assignment (title, description, course_id, deadline, max_points, created_at) 
                           VALUES (?, ?, ?, ?, ?, NOW())";
            $result = $db_handle->executeUpdatePrepared($insert_query, "ssisd", 
                [$title, $description, $course_id, $deadline, $max_points]);
            
            if ($result) {
                // Get the new assignment ID
                $assignment_id = $db_handle->getLastInsertId();
                
                // Add calendar event for this assignment
                $calendar_event = array(
                    'title' => 'Assignment Due: ' . $title,
                    'description' => $description,
                    'start_date' => $deadline,
                    'end_date' => $deadline, // Same as deadline for a due date
                    'type' => 'assignment',
                    'course_id' => $course_id,
                    'color' => '#e74c3c', // Red for assignments
                    'created_by' => $user_id
                );
                
                $event_id = $calendar_manager->createEvent($calendar_event);
                
                // Get all students enrolled in this course
                $students_query = "SELECT student_id FROM studentcourse WHERE course_id = ?";
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
