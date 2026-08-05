<?php
require './config.php';

// Fetch data from the POST request
$image = $_POST['pathImage'];
$category = $_POST['category'];
$price=$_POST['price'];
$calendar = $_POST['calendar'];
$courseTitle = $_POST['title'];
$description = $_POST['description'];
$courseSeats = $_POST['seats'];

// Perform the insert operation
$query = "INSERT INTO courses (image, category, price, calendar, courseTitle, description, courseSeats) 
          VALUES ('$image', '$category', '$price', '$calendar', '$courseTitle', '$description', '$courseSeats')";

// Execute the query using the same connection
$result = mysqli_query($conn, $query);

if ($result) {
    // Course added successfully
    echo json_encode(['status' => 'success', 'message' => 'Course added successfully']);
} else {
    // Error adding course
    echo json_encode(['status' => 'error', 'message' => 'Error adding course']);
}
?>
