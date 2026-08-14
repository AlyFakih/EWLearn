<?php
// Server-side authorization gate: instructor only. Runs before any other
// logic so nothing is emitted to an unauthenticated or wrong-role caller.
require_once __DIR__ . '/../../../core/auth_guard.php';
auth_require_role('instructor');

require_once "../../../core/DBController.php";
require_once "../../common/notifications.php";

// Check if user is logged in and is a teacher (role = 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    $response = array('success' => false, 'message' => 'Unauthorized access');
    echo json_encode($response);
    exit();
}

$db_handle = new DBController();
$notification_manager = new NotificationManager($db_handle);
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
        $verify_query = "SELECT s.id, s.student_id, u.fullName as student_name,
                         a.title as assignment_title, c.id as course_id, c.courseTitle as course_name
                         FROM assignment_submissions s
                         JOIN users u ON s.student_id = u.id
                         JOIN assignment a ON s.assignment_id = a.id
                         JOIN courses c ON a.course_id = c.id
                         WHERE s.id = ?";
        $result = $db_handle->executeSelectPrepared($verify_query, "i", [$submission_id]);

        if (!empty($result) && !$db_handle->isCourseOwnedByTeacher($result[0]['course_id'], $user_id)) {
            $result = [];
        }

        if (empty($result)) {
            $response = array('success' => false, 'message' => 'You do not have permission to grade this submission');
        } else {
            $submission = $result[0];

            // assignment_submissions already has score/feedback columns of
            // its own - there is no separate `grades` table to upsert into
            $update_query = "UPDATE assignment_submissions SET
                           score = ?,
                           feedback = ?,
                           status = 'graded'
                           WHERE id = ?";
            $result = $db_handle->executeUpdatePrepared($update_query, "dsi", [$grade_points, $feedback, $submission_id]);

            if ($result) {
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
