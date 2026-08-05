<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
require './config.php';
// Perform a SELECT query to retrieve instructor data
$sql = "SELECT id, fullName, email, mobile , country FROM users WHERE role = 'instructor'";
$result = $conn->query($sql);

// Convert the result to an associative array
$instructors = [];
while ($row = $result->fetch_assoc()) {
    $instructors[] = $row;
}

// Return the data as JSON
header('Content-Type: application/json');
echo json_encode($instructors);
?>