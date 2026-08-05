<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

ob_start();
include "./config.php";

$response = array(); // Initialize an associative array for the response

$loginEmail = htmlspecialchars($_POST['loginEmail']);
$loginPassword = htmlspecialchars($_POST['loginPassword']);

// Retrieve user data based on the provided email
$getUserQuery = "SELECT * FROM users WHERE email = '$loginEmail'";
$getUserResult = $conn->query($getUserQuery);

if ($getUserResult->num_rows > 0) {
    $userData = $getUserResult->fetch_assoc();

    // Retrieve the hashed password and role from the database
    $hashedPassword = $userData['password'];
    $userRole = $userData['role'];

    // Verify the provided password
    if (password_verify($loginPassword, $hashedPassword)) {
        // Password is correct

        if ($userRole === 'admin') {
            // Redirect to the admin dashboard
            $response['status'] = 'success';
            $response['role'] = 'admin';
            $response['redirect'] = '../pages/dashboardAdmin/AdminDash.html';
        } else if ($userRole === 'instructor') {
            // Redirect to the instructor dashboard
            $response['status'] = 'success';
            $response['role'] = 'instructor';
            $response['redirect'] = '../pages/Teacher%20Dashboard/profile-dashboard.php';
        } else if ($userRole === 'student') {
            // Redirect to the student dashboard
            $response['status'] = 'success';
            $response['role'] = 'student';
            $response['redirect'] = '../pages/Home.html';
        }
    } else {
        // Incorrect password
        $response['status'] = 'error';
        $response['message'] = 'Incorrect password. Please try again.';
    }
} else {
    // Email not found
    $response['status'] = 'error';
    $response['message'] = 'Email not found. Please check your email or register.';
}

$conn->close();
ob_end_flush();

// Send the JSON-encoded response to the frontend
echo json_encode($response);
?>
