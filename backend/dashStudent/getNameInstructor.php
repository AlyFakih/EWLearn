<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
require '../config.php';

// Perform a SELECT query to retrieve names of students
$sql = "SELECT fullName FROM users WHERE role = 'instructor'";
$result = $conn->query($sql);

// Check if the query was successful
if ($result) {
    // Convert the result to an associative array
    $instructors = [];
    while ($row = $result->fetch_assoc()) {
        $instructors[] = $row['fullName'];
    }

    // Return the data as JSON
    echo json_encode($instructors);
} else {
    // If the query fails, return an error response
    echo json_encode(['status' => 'error', 'message' => 'Error retrieving student names']);
}

?>
