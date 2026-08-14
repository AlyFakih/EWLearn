<?php
// Server-side authorization gate: admin only. Placed first so no data is
// emitted before the caller is proven to be an authenticated admin.
require_once __DIR__ . '/../../frontend/core/auth_guard.php';
auth_require_role('admin');

require '../config.php';

// Perform a SELECT query to retrieve data from studentcourse table
$sql = "SELECT userStudentID, userInstructorID, courseID FROM studentcourse";
$result = $conn->query($sql);

// Convert the result to an associative array
$studentCourses = [];
while ($row = $result->fetch_assoc()) {
    $studentCourses[] = $row;
}

// Return the data as JSON
header('Content-Type: application/json');
echo json_encode($studentCourses);

?>
