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
$description = $_POST['description'] ?? '';
$courseTitle = $_POST['title'] ?? '';
$courseSeats = $_POST['seats'] ?? '';

if ($image === '' || $category === '' || $price === '' || $calendar === '' || $description === '' || $courseTitle === '' || $courseSeats === '') {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

// Check if the course title already exists. Parameterised - every one of
// these values comes straight from the request and was previously
// interpolated into the SQL.
$checkStmt = $conn->prepare("SELECT id FROM courses WHERE courseTitle = ?");
$checkStmt->bind_param("s", $courseTitle);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    // Course title already exists
    echo json_encode(['status' => 'error', 'message' => 'Course title already exists. Please choose a different title.']);
    exit;
}

// Validate date is in the future
$currentDate = date('Y-m-d');
if ($calendar <= $currentDate) {
    echo json_encode(['status' => 'error', 'message' => 'Course date must be in the future']);
    exit; // Stop execution if validation fails
}

// Perform the insert operation
$insertStmt = $conn->prepare(
    "INSERT INTO courses (image, category, price, calendar, description, courseTitle, courseSeats)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$insertStmt->bind_param("ssisssi", $image, $category, $price, $calendar, $description, $courseTitle, $courseSeats);
$insertResult = $insertStmt->execute();

if ($insertResult) {
    // Fetch the newly added course details
    $fetchStmt = $conn->prepare("SELECT * FROM courses WHERE courseTitle = ?");
    $fetchStmt->bind_param("s", $courseTitle);
    $fetchStmt->execute();
    $courseDetails = $fetchStmt->get_result()->fetch_assoc();

    echo json_encode(['status' => 'success', 'message' => 'Course added successfully', 'courseDetails' => $courseDetails]);
} else {
    // Do not echo mysqli_error() to the client: it leaks schema and query text.
    error_log("addCourse.php insert failed: " . $insertStmt->error);
    echo json_encode(['status' => 'error', 'message' => 'Error adding course']);
}

?>
