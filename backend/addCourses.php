<?php
// Server-side authorization gate: admin only. Placed first so no data is
// emitted before the caller is proven to be an authenticated admin.
require_once __DIR__ . '/../frontend/core/auth_guard.php';
auth_require_role('admin');

require './config.php';

// Fetch data from the POST request
$image = $_POST['pathImage'] ?? '';
$category = $_POST['category'] ?? '';
$price = $_POST['price'] ?? '';
$calendar = $_POST['calendar'] ?? '';
$courseTitle = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$courseSeats = $_POST['seats'] ?? '';

if ($image === '' || $category === '' || $price === '' || $calendar === '' || $courseTitle === '' || $description === '' || $courseSeats === '') {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

// Perform the insert operation. Parameterised - every one of these values
// comes straight from the request and was previously interpolated into the SQL.
$stmt = $conn->prepare(
    "INSERT INTO courses (image, category, price, calendar, courseTitle, description, courseSeats)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("ssisssi", $image, $category, $price, $calendar, $courseTitle, $description, $courseSeats);
$result = $stmt->execute();

if ($result) {
    // Course added successfully
    echo json_encode(['status' => 'success', 'message' => 'Course added successfully']);
} else {
    // Error adding course
    echo json_encode(['status' => 'error', 'message' => 'Error adding course']);
}
?>
