<?php
// Server-side authorization gate: admin only. Placed first so no data is
// emitted before the caller is proven to be an authenticated admin.
require_once __DIR__ . '/../frontend/core/auth_guard.php';
auth_require_role('admin');


header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
require './config.php';

// Fetch data from the POST request
$courseTitle = $_POST['title'];

// Perform the select operation. Parameterised - this value comes straight
// from the request and was previously interpolated into the SQL.
$stmt = $conn->prepare("SELECT * FROM courses WHERE courseTitle = ?");
$stmt->bind_param("s", $courseTitle);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    // Check if any rows were returned
    if ($result->num_rows > 0) {
        // Fetch and encode the course data
        $courseData = $result->fetch_assoc();
        echo json_encode($courseData,JSON_UNESCAPED_SLASHES);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Course not found']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error fetching course']);
}
?>
