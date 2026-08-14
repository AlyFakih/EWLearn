<?php
// Server-side authorization gate: admin only. Placed first so no data is
// emitted before the caller is proven to be an authenticated admin.
require_once __DIR__ . '/../frontend/core/auth_guard.php';
auth_require_role('admin');

header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
require './config.php';

// Perform the select operation
$query = "SELECT email FROM users  WHERE role = 'student'";

// Execute the query using the same connection
$result = mysqli_query($conn, $query);

if ($result) {
    // Check if any rows were returned
    if (mysqli_num_rows($result) > 0) {
        // Fetch and encode the emails
        $emails = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $emails[] = $row['email']; // Extract the 'email' field
        }
        echo json_encode($emails, JSON_UNESCAPED_SLASHES);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No emails available']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error fetching emails']);
}

$conn->close();
?>
