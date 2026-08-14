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

// Check if submission ID was provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $response = array('success' => false, 'message' => 'Missing submission ID');
    echo json_encode($response);
    exit();
}

$submission_id = $db_handle->cleanData($_GET['id']);

// Get submission details with prepared statement. assignment_submissions
// already has its own score/feedback columns - there is no separate
// `grades` table, and assignment has max_score/due_date, not
// max_points/deadline.
$query = "SELECT s.id, s.student_id, u.fullName as student_name,
          s.assignment_id, a.title as assignment_title, a.description,
          a.max_score, a.course_id, c.courseTitle as course_name,
          s.submitted_at, s.status, s.file_path, s.submission_text as content,
          s.score as grade_points, s.feedback
          FROM assignment_submissions s
          JOIN users u ON s.student_id = u.id
          JOIN assignment a ON s.assignment_id = a.id
          JOIN courses c ON a.course_id = c.id
          WHERE s.id = ?";

$result = $db_handle->executeSelectPrepared($query, "i", [$submission_id]);

if (!empty($result) && !$db_handle->isCourseOwnedByTeacher($result[0]['course_id'], $user_id)) {
    $result = [];
}

if (empty($result)) {
    $response = array('success' => false, 'message' => 'Submission not found or you do not have permission to view it');
    echo json_encode($response);
    exit();
}

// Return submission details
$submission = $result[0];

// file_path is stored relative to the uploads/ directory (e.g. "assignments/foo.pdf"),
// not relative to whatever page renders the download link. Convert it to a
// root-relative URL so the browser resolves it correctly regardless of which
// dashboard page the link is shown on.
if (!empty($submission['file_path'])) {
    $uploadsDir = realpath(__DIR__ . '/../../../../uploads/');
    if ($uploadsDir !== false) {
        $uploadsDir = str_replace('\\', '/', $uploadsDir);
        $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
        $baseUrl = (stripos($uploadsDir, $docRoot) === 0) ? substr($uploadsDir, strlen($docRoot)) : $uploadsDir;

        $encode = function ($path) {
            return implode('/', array_map('rawurlencode', explode('/', $path)));
        };
        $submission['file_path'] = $encode($baseUrl) . '/' . $encode($submission['file_path']);
    }
}

// Add grade information if available
if ($submission['grade_points'] !== null) {
    $submission['grade'] = true;
} else {
    $submission['grade'] = false;
}

$response = array('success' => true, 'data' => $submission);
header('Content-Type: application/json');
echo json_encode($response);
exit();
?>
