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
    // Log the full error details for debugging
    if (strpos($errfile, 'auth_guard') !== false || strpos($errfile, 'config') !== false) {
        error_log("Error details: $errstr in $errfile at line $errline");
    }
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
error_log("LOGIN: Starting session initialization");
require_once __DIR__ . "/../frontend/core/auth_guard.php";
error_log("LOGIN: auth_guard.php loaded");

try {
    auth_session_boot();
    error_log("LOGIN: Session booted successfully");
} catch (Exception $e) {
    error_log("LOGIN: Session boot failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode($response);
    exit;
}

// Safely include config.php and check if connection was successful
error_log("LOGIN: Including config.php");
try {
    include "./config.php";
    error_log("LOGIN: config.php loaded, checking connection");
} catch (Exception $e) {
    error_log("LOGIN: Config include failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode($response);
    exit;
}

// Verify connection was established
if (!isset($conn)) {
    error_log("LOGIN: Connection variable not set after config.php");
    http_response_code(500);
    echo json_encode($response);
    exit;
}
error_log("LOGIN: Connection established successfully");

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
    error_log("LOGIN: Missing email or password");
    $response['message'] = 'Email and password are required.';
    echo json_encode($response);
    exit;
}

error_log("LOGIN: Attempting to authenticate user: " . $loginEmail);

// Parameterised: the email came straight from the client and was previously
// interpolated into the SQL string.
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
if (!$stmt) {
    error_log("LOGIN: Prepare failed: " . $conn->error);
    echo json_encode($response);
    exit;
}

if (!$stmt->bind_param("s", $loginEmail)) {
    error_log("LOGIN: Bind param failed: " . $stmt->error);
    $stmt->close();
    echo json_encode($response);
    exit;
}

if (!$stmt->execute()) {
    error_log("LOGIN: Execute failed: " . $stmt->error);
    $stmt->close();
    echo json_encode($response);
    exit;
}

error_log("LOGIN: Query executed successfully");
$getUserResult = $stmt->get_result();
if (!$getUserResult) {
    error_log("LOGIN: Get result failed: " . $stmt->error);
    $stmt->close();
    echo json_encode($response);
    exit;
}

error_log("LOGIN: Got result set, checking row count");

if ($getUserResult->num_rows > 0) {
    error_log("LOGIN: User found, fetching data");
    $userData = $getUserResult->fetch_assoc();
    $stmt->close();

    if (!$userData) {
        error_log("LOGIN: Fetch assoc failed");
        echo json_encode($response);
        exit;
    }

    // Retrieve the hashed password and role from the database
    $hashedPassword = $userData['password'] ?? null;
    $userRole = $userData['role'] ?? null;
    $userId = $userData['id'] ?? null;
    $fullName = $userData['fullName'] ?? 'User';

    error_log("LOGIN: User data retrieved - Role: $userRole, ID: $userId");

    if (!$hashedPassword || !$userRole || !$userId) {
        error_log("LOGIN: Missing required user fields");
        echo json_encode($response);
        exit;
    }

    // Verify the provided password
    if (password_verify($loginPassword, $hashedPassword)) {
        error_log("LOGIN: Password verified, setting up session for user ID: $userId");

        // Issues a brand-new session ID so any ID an attacker planted in the
        // victim's browser before login becomes worthless (session fixation),
        // and stamps the session for idle/absolute expiry.
        auth_login_session($userId, $userRole, $fullName);
        error_log("LOGIN: Session created successfully");

        // Password is correct
        if ($userRole === 'admin') {
            // Redirect to the admin dashboard
            $response['status'] = 'success';
            $response['role'] = 'admin';
            $response['redirect'] = '/admin';
            error_log("LOGIN: Admin login successful for user ID: $userId");
        } else if ($userRole === 'instructor') {
            // Redirect to the instructor dashboard
            $response['status'] = 'success';
            $response['role'] = 'instructor';
            $response['redirect'] = '../pages/Teacher%20Dashboard/profile-dashboard.php';
            error_log("LOGIN: Instructor login successful for user ID: $userId");
        } else if ($userRole === 'student') {
            // Redirect to the student dashboard
            $response['status'] = 'success';
            $response['role'] = 'student';
            $response['redirect'] = '../pages/Student%20Dashboard/dashboard.php';
            error_log("LOGIN: Student login successful for user ID: $userId");
        } else {
            error_log("LOGIN: Unknown role in login.php: " . $userRole);
            $response['message'] = 'Unknown user role.';
        }
    } else {
        // Incorrect password
        error_log("LOGIN: Invalid password for user: $loginEmail");
        $response['status'] = 'error';
        $response['message'] = 'Incorrect password. Please try again.';
    }
} else {
    // Email not found
    error_log("LOGIN: User not found: $loginEmail");
    $stmt->close();
    $response['status'] = 'error';
    $response['message'] = 'Email not found. Please check your email or register.';
}

$conn->close();
restore_error_handler();

// Send the JSON-encoded response to the frontend
echo json_encode($response);
?>
