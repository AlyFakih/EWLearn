<?php
// Server-side authorization gate: admin only. Placed first so no data is
// emitted before the caller is proven to be an authenticated admin.
require_once __DIR__ . '/../frontend/core/auth_guard.php';
auth_require_role('admin');


// Enable CORS
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

// Perform the deletion in the database.
// Parameterised: $email came straight from POST and was interpolated into the
// SQL, so a value like  ' OR '1'='1  deleted the ENTIRE users table.
$stmt = $conn->prepare("DELETE FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$deleteResult = $stmt->execute();

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
