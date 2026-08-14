<?php
// Server-side authorization gate: admin only. Placed first so no data is
// emitted before the caller is proven to be an authenticated admin.
require_once __DIR__ . '/../../frontend/core/auth_guard.php';
auth_require_role('admin');

header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
require '../config.php';
// Perform a SELECT query to retrieve instructor data
$sql = "SELECT id, fullName, email, mobile , country FROM users WHERE role = 'student'";
$result = $conn->query($sql);

// Convert the result to an associative array
$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

// Return the data as JSON
header('Content-Type: application/json');
echo json_encode($students);
?>