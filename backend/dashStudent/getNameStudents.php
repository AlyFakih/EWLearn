<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
require '../config.php';

// Perform the select operation
$query = "SELECT fullName FROM users WHERE role = 'student'";

// Execute the query using the same connection
$result = mysqli_query($conn, $query);

if ($result) {
    // Check if any rows were returned
    if (mysqli_num_rows($result) > 0) {
        // Fetch and encode the instructor names
        $students = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $students[] = $row; // Use the entire row
        }
        echo json_encode($students, JSON_UNESCAPED_SLASHES);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No students available']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error fetching students']);
}

$conn->close();
?>
