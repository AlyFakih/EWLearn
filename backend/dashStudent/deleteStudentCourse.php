<?php
// Server-side authorization gate: admin only. Placed first so no data is
// emitted before the caller is proven to be an authenticated admin.
require_once __DIR__ . '/../../frontend/core/auth_guard.php';
auth_require_role('admin');

require '../config.php';

// Fetch data from the AJAX request

$userStudentID = isset($_POST['userStudentID']) ? $_POST['userStudentID'] : null;
$courseID = isset($_POST['courseID']) ? $_POST['courseID'] : null;
// Perform the delete operation from the studentcourse table. Parameterised -
// these values come straight from the request and were previously
// interpolated into the SQL.
$stmt = $conn->prepare("DELETE FROM studentcourse WHERE userStudentID = ? AND courseID = ?");
$stmt->bind_param("ss", $userStudentID, $courseID);
$result = $stmt->execute();

if ($result) {
    $response = ['status' => 'success', 'message' => 'Course deleted successfully'];
} else {
    $response = ['status' => 'error', 'message' => 'Error deleting course: ' . mysqli_error($conn)];
}

echo json_encode($response);
?>
