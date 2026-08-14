<?php
// Server-side authorization gate: admin only. Placed first so no data is
// emitted before the caller is proven to be an authenticated admin.
require_once __DIR__ . '/../frontend/core/auth_guard.php';
auth_require_role('admin');

require './config.php';

// Fetch data from the POST request
$courseTitle = $_POST['title'] ?? '';
$image = $_POST['pathImage'] ?? '';
$category = $_POST['category'] ?? '';
$price = $_POST['price'] ?? '';
$calendar = $_POST['calendar'] ?? '';
$description = $_POST['description'] ?? '';
$courseSeats = $_POST['seats'] ?? '';

if ($courseTitle === '' || $image === '' || $category === '' || $price === '' || $calendar === '' || $description === '' || $courseSeats === '') {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

// Validate date is in the future
$currentDate = date('Y-m-d');
if ($calendar <= $currentDate) {
    echo json_encode(['status' => 'error', 'message' => 'Course date must be in the future']);
    exit; // Stop execution if validation fails
}
// Perform the update operation. Parameterised - every one of these values
// comes straight from the request and was previously interpolated into the SQL.
$stmt = $conn->prepare(
    "UPDATE courses
     SET image = ?,
         category = ?,
         price = ?,
         calendar = ?,
         description = ?,
         courseSeats = ?
     WHERE courseTitle = ?"
);
$stmt->bind_param("ssissis", $image, $category, $price, $calendar, $description, $courseSeats, $courseTitle);
$result = $stmt->execute();

if ($result) {
    echo json_encode(['status' => 'success', 'message' => 'Course updated successfully']);
} else {
    // Do not echo mysqli_error() to the client: it leaks schema and query text.
    error_log("updateCourse.php failed: " . $stmt->error);
    echo json_encode(['status' => 'error', 'message' => 'Error updating course']);
}
?>
