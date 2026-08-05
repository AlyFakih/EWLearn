<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

require './config.php';

// Assuming you are receiving the courseTitle through POST
$courseTitle = isset($_POST['courseTitle']) ? $_POST['courseTitle'] : null;

if (!$courseTitle) {
    // If courseTitle is not provided, return an error response
    echo json_encode(['status' => 'error', 'message' => 'Course title not provided']);
    exit;
}

// Perform the deletion in the database
$deleteQuery = "DELETE FROM courses WHERE courseTitle = '$courseTitle'";
$deleteResult = mysqli_query($conn, $deleteQuery);

if ($deleteResult) {
    // If deletion was successful, return a success response
    echo json_encode(['status' => 'success', 'message' => 'Course deleted successfully']);
} else {
    // If deletion failed, return an error response
    echo json_encode(['status' => 'error', 'message' => 'Error deleting course']);
}

$conn->close();
?>
