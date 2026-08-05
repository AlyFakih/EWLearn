<?php
session_start();
require_once "dbcontroller.php";

// Check if user is logged in and is a teacher (role = 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    $response = array('success' => false, 'message' => 'Unauthorized access');
    echo json_encode($response);
    exit();
}

$db_handle = new DBController();
$user_id = $_SESSION['user_id'];

// Process DELETE request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required parameter
    if (empty($_POST['id'])) {
        $response = array('success' => false, 'message' => 'Missing submission ID');
    } else {
        // Clean and sanitize input
        $submission_id = $db_handle->cleanData($_POST['id']);
        
        // Verify that this submission is for a course the teacher owns
        $verify_query = "SELECT s.id, s.file_path 
                         FROM assignment_submissions s
                         JOIN assignment a ON s.assignment_id = a.id
                         JOIN courses c ON a.course_id = c.id 
                         WHERE s.id = ? AND c.teacher_id = ?";
        $result = $db_handle->executeSelectPrepared($verify_query, "ii", [$submission_id, $user_id]);
        
        if (empty($result)) {
            $response = array('success' => false, 'message' => 'You do not have permission to delete this submission');
        } else {
            $submission = $result[0];
            
            // Begin transaction to ensure all related data is deleted
            $db_handle->beginTransaction();
            
            try {
                // Delete grade if exists
                $db_handle->executeUpdatePrepared("DELETE FROM grades WHERE submission_id = ?", "i", [$submission_id]);
                
                // Delete submission
                $result = $db_handle->executeUpdatePrepared("DELETE FROM assignment_submissions WHERE id = ?", "i", [$submission_id]);
                
                if ($result) {
                    // Delete the file if it exists
                    if (!empty($submission['file_path']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $submission['file_path'])) {
                        unlink($_SERVER['DOCUMENT_ROOT'] . $submission['file_path']);
                    }
                    
                    $db_handle->commitTransaction();
                    $response = array('success' => true, 'message' => 'Submission deleted successfully');
                } else {
                    throw new Exception("Failed to delete submission");
                }
            } catch (Exception $e) {
                $db_handle->rollbackTransaction();
                $response = array('success' => false, 'message' => $e->getMessage());
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
