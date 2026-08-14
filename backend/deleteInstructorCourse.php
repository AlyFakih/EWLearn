<?php
// Server-side authorization gate: admin only. Placed first so no data is
// emitted before the caller is proven to be an authenticated admin.
require_once __DIR__ . '/../frontend/core/auth_guard.php';
auth_require_role('admin');

header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

require './config.php'; // Adjust the path to your database configuration file

// Assuming you are receiving the name through POST
$instructorCourseName = isset($_POST['instructorCourseName']) ? $_POST['instructorCourseName'] : null;

if (!$instructorCourseName) {
    // If name is not provided, return an error response
    echo json_encode(['status' => 'error', 'message' => 'Instructor course name not provided']);
    exit;
}

// Delete the record from the instructorcourse table based on the name.
// Parameterised - this value comes straight from the request and was
// previously interpolated into the SQL.
$deleteStmt = $conn->prepare("DELETE FROM instructorcourse WHERE name = ?");
$deleteStmt->bind_param("s", $instructorCourseName);
$resultDelete = $deleteStmt->execute();

if ($resultDelete) {
    echo json_encode(['status' => 'success', 'message' => 'Instructor course deleted successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error deleting instructor course']);
}

$conn->close();
?>
