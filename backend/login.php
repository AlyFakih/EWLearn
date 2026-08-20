<?php
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

$response = array('status' => 'error', 'message' => 'An unexpected error occurred.');

// Set up error handling to catch any PHP errors that might corrupt JSON output
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    global $response;
    error_log("PHP Error in login.php: [$errno] $errstr in $errfile:$errline");
    if (empty($response)) {
        $response = [];
    }
    if (!isset($response['status'])) {
        $response['status'] = 'error';
        $response['message'] = 'An unexpected error occurred. Please try again later.';
    }
    return true;
});

// Set up exception handler to catch fatal errors
set_exception_handler(function($exception) {
    global $response;
    error_log("Exception in login.php: " . $exception->getMessage());
    if (empty($response)) {
        $response = [];
    }
    $response['status'] = 'error';
    $response['message'] = 'An unexpected error occurred. Please try again later.';
    http_response_code(500);
    echo json_encode($response);
    exit;
});

// Register shutdown handler to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        global $response;
        error_log("Fatal error in login.php: " . $error['message']);
        if (empty($response)) {
            $response = [];
        }
        $response['status'] = 'error';
        $response['message'] = 'An unexpected error occurred. Please try again later.';
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code(500);
        echo json_encode($response);
    }
});

// Starts the session with hardened cookie flags (HttpOnly, SameSite=Lax,
// strict mode). Must run before any other session handling.
require_once __DIR__ . "/../frontend/core/auth_guard.php";
auth_session_boot();

// Safely include config.php and check if connection was successful
try {
    include "./config.php";
} catch (Exception $e) {
    error_log("Config include failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode($response);
    exit;
}

// Validate database connection
if (!$conn) {
    error_log("Database connection failed in login.php");
    echo json_encode($response);
    exit;
}

// Do NOT htmlspecialchars() credentials: that is an output-encoding function,
// not an input filter, and mangling the password before password_verify()
// would silently break any password containing < > & " '.
$loginEmail = isset($_POST['loginEmail']) ? trim($_POST['loginEmail']) : '';
$loginPassword = isset($_POST['loginPassword']) ? $_POST['loginPassword'] : '';

// Validate input
if (empty($loginEmail) || empty($loginPassword)) {
    $response['message'] = 'Email and password are required.';
    echo json_encode($response);
    exit;
}

// Parameterised: the email came straight from the client and was previously
// interpolated into the SQL string.
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
if (!$stmt) {
    error_log("Prepare failed in login.php: " . $conn->error);
    echo json_encode($response);
    exit;
}

if (!$stmt->bind_param("s", $loginEmail)) {
    error_log("Bind param failed in login.php: " . $stmt->error);
    $stmt->close();
    echo json_encode($response);
    exit;
}

if (!$stmt->execute()) {
    error_log("Execute failed in login.php: " . $stmt->error);
    $stmt->close();
    echo json_encode($response);
    exit;
}

$getUserResult = $stmt->get_result();
if (!$getUserResult) {
    error_log("Get result failed in login.php: " . $stmt->error);
    $stmt->close();
    echo json_encode($response);
    exit;
}

if ($getUserResult->num_rows > 0) {
    $userData = $getUserResult->fetch_assoc();
    $stmt->close();

    if (!$userData) {
        error_log("Fetch assoc failed in login.php");
        echo json_encode($response);
        exit;
    }

    // Retrieve the hashed password and role from the database
    $hashedPassword = $userData['password'] ?? null;
    $userRole = $userData['role'] ?? null;
    $userId = $userData['id'] ?? null;
    $fullName = $userData['fullName'] ?? 'User';

    if (!$hashedPassword || !$userRole || !$userId) {
        error_log("Missing required user fields in login.php");
        echo json_encode($response);
        exit;
    }

    // Verify the provided password
    if (password_verify($loginPassword, $hashedPassword)) {
        // Issues a brand-new session ID so any ID an attacker planted in the
        // victim's browser before login becomes worthless (session fixation),
        // and stamps the session for idle/absolute expiry.
        auth_login_session($userId, $userRole, $fullName);
        // Password is correct

        if ($userRole === 'admin') {
            // Redirect to the admin dashboard
            $response['status'] = 'success';
            $response['role'] = 'admin';
            $response['redirect'] = '/admin';
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
        } else {
            error_log("Unknown role in login.php: " . $userRole);
            $response['message'] = 'Unknown user role.';
        }
    } else {
        // Incorrect password
        $response['status'] = 'error';
        $response['message'] = 'Incorrect password. Please try again.';
    }
} else {
    // Email not found
    $stmt->close();
    $response['status'] = 'error';
    $response['message'] = 'Email not found. Please check your email or register.';
}

$conn->close();
restore_error_handler();

// Send the JSON-encoded response to the frontend
echo json_encode($response);
?>
