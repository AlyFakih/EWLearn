<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

require './config.php'; // Adjust the path to your database configuration file

// Assuming you are receiving the email through POST
$email = isset($_POST['email']) ? $_POST['email'] : null;

if (!$email) {
    // If email is not provided, return an error response
    echo json_encode(['status' => 'error', 'message' => 'Email not provided']);
    exit;
}

// Fetch user data from the database
$query = "SELECT * FROM users WHERE email = '$email'";
$result = mysqli_query($conn, $query);

if ($result) {
    // Check if user with the provided email exists
    if (mysqli_num_rows($result) > 0) {
        // Fetch user details
        $user = mysqli_fetch_assoc($result);
        // Return user details as JSON response
        echo json_encode( $user,JSON_UNESCAPED_SLASHES);
    } else {
        // If user with the provided email doesn't exist, return an error response
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
} else {
    // If the database query fails, return an error response
    echo json_encode(['status' => 'error', 'message' => 'Error fetching user data']);
}

$conn->close();
?>
