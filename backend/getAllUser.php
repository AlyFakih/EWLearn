<?php
// Server-side authorization gate: admin only. Placed first so no data is
// emitted before the caller is proven to be an authenticated admin.
require_once __DIR__ . '/../frontend/core/auth_guard.php';
auth_require_role('admin');

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

// Fetch user data from the database.
// Parameterised, and the column list is now explicit: `SELECT *` returned the
// bcrypt `password` hash to the browser on every call, handing an offline
// cracking target to anyone who could reach this endpoint.
$query = "SELECT id, role, fullName, country, email, mobile, blood, gender, image
          FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

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
