<?php

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

// Include your database configuration file
require './config.php';

// Assuming you are receiving the email through POST
$email = isset($_POST['email']) ? $_POST['email'] : null;

if (!$email) {
    // If email is not provided, return an error response
    echo json_encode(['status' => 'error', 'message' => 'Email not provided']);
    exit;
}

// Perform the deletion in the database
$deleteQuery = "DELETE FROM users WHERE email = '$email'";
$deleteResult = mysqli_query($conn, $deleteQuery);

if ($deleteResult) {
    // If deletion was successful, return a success response
    echo json_encode(['status' => 'success', 'message' => 'User deleted successfully']);
} else {
    // If deletion failed, return an error response
    echo json_encode(['status' => 'error', 'message' => 'Error deleting user']);
}

// Close the database connection
$conn->close();

?>
