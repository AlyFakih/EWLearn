<?php
require './config.php';

// Fetch data from the POST request
$courseTitle = $_POST['title'];
$image = $_POST['pathImage'];
$category = $_POST['category'];
$price = $_POST['price'];
$calendar = $_POST['calendar'];
$description = $_POST['description'];
$courseSeats = $_POST['seats'];
// Validate date is in the future
$currentDate = date('Y-m-d');
if ($calendar <= $currentDate) {
    echo json_encode(['status' => 'error', 'message' => 'Course date must be in the future']);
    exit; // Stop execution if validation fails
}
// Perform the update operation
$query = "UPDATE courses 
          SET image = '$image', 
              category = '$category', 
              price = '$price', 
              calendar = '$calendar', 
              description = '$description', 
              courseSeats = '$courseSeats'
          WHERE courseTitle = '$courseTitle'";

// Execute the query using the same connection
$result = mysqli_query($conn, $query);

if ($result) {
    echo json_encode(['status' => 'success', 'message' => 'Course updated successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error updating course']);
}
?>
