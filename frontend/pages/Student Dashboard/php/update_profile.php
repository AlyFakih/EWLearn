<?php
// Server-side authorization gate: student only. Runs first so nothing is
// emitted to an unauthenticated, wrong-role, expired or deleted account.
require_once __DIR__ . '/../../../core/auth_guard.php';
auth_require_role('student');

// Start the session to maintain user login state

// Check if the user is logged in and is a student (role = 'student')
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    // Return error response
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database controller
require_once "../../../core/DBController.php";
$db_handle = new DBController();

// Get the user ID from session
$user_id = $_SESSION['user_id'];

// Validate and sanitize input
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $fullName = isset($_POST['fullName']) ? trim($_POST['fullName']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
    $gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
    $bloodType = isset($_POST['bloodType']) ? trim($_POST['bloodType']) : '';
    $country = isset($_POST['country']) ? trim($_POST['country']) : '';
    
    // Basic validation
    if (empty($fullName) || empty($email)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Full name and email are required']);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit();
    }
    
    // Prepare and execute the update query. `blood` (not `bloodType`) is the
    // actual users table column - the old query referencing bloodType always
    // failed, so this form never actually saved a change until now.
    $sql = "UPDATE users SET
            fullName = ?,
            email = ?,
            mobile = ?,
            gender = ?,
            blood = ?,
            country = ?
            WHERE id = ? AND role = 'student'";

    $affected = $db_handle->executeUpdatePrepared(
        $sql,
        "ssssssi",
        [$fullName, $email, $mobile, $gender, $bloodType, $country, $user_id]
    );

    // mysqli_stmt::$affected_rows is -1 on a genuine query error, 0 when the
    // submitted values matched what was already stored (still a success),
    // or a positive count when rows actually changed.
    if ($affected !== -1) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error updating profile']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
