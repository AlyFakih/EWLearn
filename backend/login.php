<?php
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

ob_start();
// Starts the session with hardened cookie flags (HttpOnly, SameSite=Lax,
// strict mode). Must run before any other session handling.
require_once __DIR__ . "/../frontend/core/auth_guard.php";
auth_session_boot();
include "./config.php";

$response = array(); // Initialize an associative array for the response

// Do NOT htmlspecialchars() credentials: that is an output-encoding function,
// not an input filter, and mangling the password before password_verify()
// would silently break any password containing < > & " '.
$loginEmail = isset($_POST['loginEmail']) ? trim($_POST['loginEmail']) : '';
$loginPassword = isset($_POST['loginPassword']) ? $_POST['loginPassword'] : '';

// Parameterised: the email came straight from the client and was previously
// interpolated into the SQL string.
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $loginEmail);
$stmt->execute();
$getUserResult = $stmt->get_result();

if ($getUserResult->num_rows > 0) {
    $userData = $getUserResult->fetch_assoc();

    // Retrieve the hashed password and role from the database
    $hashedPassword = $userData['password'];
    $userRole = $userData['role'];

    // Verify the provided password
    if (password_verify($loginPassword, $hashedPassword)) {

        // Issues a brand-new session ID so any ID an attacker planted in the
        // victim's browser before login becomes worthless (session fixation),
        // and stamps the session for idle/absolute expiry.
        auth_login_session($userData['id'], $userRole, $userData['fullName']);
        // Password is correct

        if ($userRole === 'admin') {
            // Redirect to the admin dashboard
            $response['status'] = 'success';
            $response['role'] = 'admin';
            $response['redirect'] = '../pages/dashboardAdmin/AdminDash.php';
        } else if ($userRole === 'instructor') {
            // Redirect to the instructor dashboard
            $response['status'] = 'success';
            $response['role'] = 'instructor';
            $response['redirect'] = '../pages/Teacher%20Dashboard/profile-dashboard.php';
        } else if ($userRole === 'student') {
            // Redirect to the student dashboard
            $response['status'] = 'success';
            $response['role'] = 'student';
            $response['redirect'] = '../pages/Student%20Dashboard/dashboard.php';
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
