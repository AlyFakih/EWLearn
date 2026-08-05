<?php
require './config.php';

// Fetch data from the POST request
$image = $_POST['pathImage'];
$category = $_POST['category'];
$price = $_POST['price'];
$calendar = $_POST['calendar'];
$description = $_POST['description'];
$courseTitle = $_POST['title'];
$courseSeats = $_POST['seats'];

// Check if the course title already exists
$checkQuery = "SELECT * FROM courses WHERE courseTitle = '$courseTitle'";
$checkResult = mysqli_query($conn, $checkQuery);

if (mysqli_num_rows($checkResult) > 0) {
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
$insertQuery = "INSERT INTO courses (image, category, price, calendar, description, courseTitle, courseSeats)
                VALUES ('$image', '$category', '$price', '$calendar', '$description', '$courseTitle', '$courseSeats')";

// Execute the query using the same connection
$insertResult = mysqli_query($conn, $insertQuery);

if ($insertResult) {
    // Fetch the newly added course details
    $newCourse = mysqli_query($conn, "SELECT * FROM courses WHERE courseTitle = '$courseTitle'");
    $courseDetails = mysqli_fetch_assoc($newCourse);

    echo json_encode(['status' => 'success', 'message' => 'Course added successfully', 'courseDetails' => $courseDetails]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error adding course']);
}

?>
