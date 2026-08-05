<?php
session_start();
require_once "dbcontroller.php";
require_once "../../common/notifications.php";

// Check if user is logged in and is a teacher (role = 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    $response = array('success' => false, 'message' => 'Unauthorized access');
    echo json_encode($response);
    exit();
}

$db_handle = new DBController();
$notification_manager = new NotificationManager();
$user_id = $_SESSION['user_id'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = array();
    
    // Validate required fields
    if (empty($_POST['submission_id']) || !isset($_POST['grade_points'])) {
        $response = array('success' => false, 'message' => 'Missing required fields');
    } else {
        // Clean and sanitize input
        $submission_id = $db_handle->cleanData($_POST['submission_id']);
        $grade_points = $db_handle->cleanData($_POST['grade_points']);
        $feedback = isset($_POST['feedback']) ? $db_handle->cleanData($_POST['feedback']) : '';
        
        // Verify that this submission is for a course the teacher owns
        $verify_query = "SELECT s.id, s.student_id, u.full_name as student_name, 
                         a.title as assignment_title, c.id as course_id, c.courseTitle as course_name 
                         FROM assignment_submissions s
                         JOIN users u ON s.student_id = u.id  
                         JOIN assignment a ON s.assignment_id = a.id
                         JOIN courses c ON a.course_id = c.id 
                         WHERE s.id = ? AND c.teacher_id = ?";
        $result = $db_handle->executeSelectPrepared($verify_query, "ii", [$submission_id, $user_id]);
        
        if (empty($result)) {
            $response = array('success' => false, 'message' => 'You do not have permission to grade this submission');
        } else {
            $submission = $result[0];
            
            // Check if a grade already exists
            $check_grade = "SELECT id FROM grades WHERE submission_id = ?";
            $grade_exists = $db_handle->executeSelectPrepared($check_grade, "i", [$submission_id]);
            
            if (!empty($grade_exists)) {
                // Update existing grade
                $update_query = "UPDATE grades SET 
                               points = ?, 
                               feedback = ?, 
                               updated_at = NOW() 
                               WHERE submission_id = ?";
                $result = $db_handle->executeUpdatePrepared($update_query, "dsi", [$grade_points, $feedback, $submission_id]);
                
                if ($result) {
                    // Update submission status
                    $db_handle->executeUpdatePrepared("UPDATE assignment_submissions SET status = 'graded' WHERE id = ?", "i", [$submission_id]);
                    
                    // Create notification for student
                    $notification_manager->createNotification(
                        $submission['student_id'],
                        "Assignment Graded: {$submission['assignment_title']}",
                        "Your assignment '{$submission['assignment_title']}' for {$submission['course_name']} has been graded. Points: {$grade_points}.",
                        "grade",
                        $submission_id
                    );
                    
                    $response = array('success' => true, 'message' => 'Grade updated successfully');
                } else {
                    $response = array('success' => false, 'message' => 'Failed to update grade');
                }
            } else {
                // Insert new grade
                $insert_query = "INSERT INTO grades (submission_id, points, feedback, created_at) 
                               VALUES (?, ?, ?, NOW())";
                $result = $db_handle->executeUpdatePrepared($insert_query, "ids", [$submission_id, $grade_points, $feedback]);
                
                if ($result) {
                    // Update submission status
                    $db_handle->executeUpdatePrepared("UPDATE assignment_submissions SET status = 'graded' WHERE id = ?", "i", [$submission_id]);
                    
                    // Create notification for student
                    $notification_manager->createNotification(
                        $submission['student_id'],
                        "Assignment Graded: {$submission['assignment_title']}",
                        "Your assignment '{$submission['assignment_title']}' for {$submission['course_name']} has been graded. Points: {$grade_points}.",
                        "grade",
                        $submission_id
                    );
                    
                    $response = array('success' => true, 'message' => 'Assignment graded successfully');
                } else {
                    $response = array('success' => false, 'message' => 'Failed to save grade');
                }
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
