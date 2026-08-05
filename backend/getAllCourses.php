<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
require './config.php';

// Fetch data from the POST request
$courseTitle = $_POST['title'];

// Perform the select operation
$query = "SELECT * FROM courses WHERE courseTitle = '$courseTitle'";

// Execute the query using the same connection
$result = mysqli_query($conn, $query);

if ($result) {
    // Check if any rows were returned
    if (mysqli_num_rows($result) > 0) {
        // Fetch and encode the course data
        $courseData = mysqli_fetch_assoc($result);
        echo json_encode($courseData,JSON_UNESCAPED_SLASHES);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Course not found']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error fetching course']);
}
?>
