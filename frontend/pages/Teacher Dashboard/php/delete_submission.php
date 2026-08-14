<?php
// Server-side authorization gate: instructor only. Runs before any other
// logic so nothing is emitted to an unauthenticated or wrong-role caller.
require_once __DIR__ . '/../../../core/auth_guard.php';
auth_require_role('instructor');

require_once "../../../core/DBController.php";

// Check if user is logged in and is a teacher (role = 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
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
        $verify_query = "SELECT s.id, s.file_path, a.course_id
                         FROM assignment_submissions s
                         JOIN assignment a ON s.assignment_id = a.id
                         JOIN courses c ON a.course_id = c.id
                         WHERE s.id = ?";
        $result = $db_handle->executeSelectPrepared($verify_query, "i", [$submission_id]);

        if (!empty($result) && !$db_handle->isCourseOwnedByTeacher($result[0]['course_id'], $user_id)) {
            $result = [];
        }

        if (empty($result)) {
            $response = array('success' => false, 'message' => 'You do not have permission to delete this submission');
        } else {
            $submission = $result[0];

            // Begin transaction to ensure all related data is deleted
            $db_handle->beginTransaction();

            try {
                // Delete submission (grade data lives on the submission row
                // itself - score/feedback - there is no separate `grades` table)
                $result = $db_handle->executeUpdatePrepared("DELETE FROM assignment_submissions WHERE id = ?", "i", [$submission_id]);
                
                if ($result) {
                    // Delete the file if it exists. file_path is stored relative to the
                    // uploads/ directory (e.g. "assignments/foo.pdf"), so resolve it from
                    // there rather than DOCUMENT_ROOT (which never actually matched - on
                    // this Windows/XAMPP setup DOCUMENT_ROOT uses forward slashes while
                    // the real filesystem path uses backslashes, so file_exists() always
                    // returned false and submission files were never actually cleaned up).
                    if (!empty($submission['file_path'])) {
                        $uploadsDir = realpath(__DIR__ . '/../../../../uploads/');
                        if ($uploadsDir !== false) {
                            $fullPath = $uploadsDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $submission['file_path']);
                            if (file_exists($fullPath)) {
                                unlink($fullPath);
                            }
                        }
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
