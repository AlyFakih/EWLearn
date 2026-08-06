<?php
session_start();
require_once "dbcontroller.php";

// Check if user is logged in and is a teacher (role = 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    $response = array('success' => false, 'message' => 'Unauthorized access');
    echo json_encode($response);
    exit();
}

$db_handle = new DBController();
$user_id = $_SESSION['user_id'];

// Check if submission ID was provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $response = array('success' => false, 'message' => 'Missing submission ID');
    echo json_encode($response);
    exit();
}

$submission_id = $db_handle->cleanData($_GET['id']);

// Get submission details with prepared statement
$query = "SELECT s.id, s.student_id, u.full_name as student_name, 
          s.assignment_id, a.title as assignment_title, a.description,
          a.max_points, c.courseTitle as course_name, 
          s.submitted_at, s.status, s.file_path, s.content,
          g.points as grade_points, g.feedback 
          FROM assignment_submissions s
          JOIN users u ON s.student_id = u.id
          JOIN assignment a ON s.assignment_id = a.id
          JOIN courses c ON a.course_id = c.id
          LEFT JOIN grades g ON g.submission_id = s.id
          WHERE s.id = ? AND c.teacher_id = ?";

$result = $db_handle->executeSelectPrepared($query, "ii", [$submission_id, $user_id]);

if (empty($result)) {
    $response = array('success' => false, 'message' => 'Submission not found or you do not have permission to view it');
    echo json_encode($response);
    exit();
}

// Return submission details
$submission = $result[0];

// Add grade information if available
if (!empty($submission['grade_points'])) {
    $submission['grade'] = true;
} else {
    $submission['grade'] = false;
}

$response = array('success' => true, 'data' => $submission);
header('Content-Type: application/json');
echo json_encode($response);
exit();
?>
