<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

require './config.php'; // Adjust the path to your database configuration file

// Assuming you are receiving the name through POST
$instructorCourseName = isset($_POST['instructorCourseName']) ? $_POST['instructorCourseName'] : null;

if (!$instructorCourseName) {
    // If name is not provided, return an error response
    echo json_encode(['status' => 'error', 'message' => 'Instructor course name not provided']);
    exit;
}

// Delete the record from the instructorcourse table based on the name
$queryDelete = "DELETE FROM instructorcourse WHERE name = '$instructorCourseName'";
$resultDelete = mysqli_query($conn, $queryDelete);

if ($resultDelete) {
    echo json_encode(['status' => 'success', 'message' => 'Instructor course deleted successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error deleting instructor course']);
}

$conn->close();
?>
