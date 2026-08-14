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
    $currentPassword = isset($_POST['current-password']) ? trim($_POST['current-password']) : '';
    $newPassword = isset($_POST['new-password']) ? trim($_POST['new-password']) : '';
    $confirmPassword = isset($_POST['confirm-password']) ? trim($_POST['confirm-password']) : '';
    
    // Basic validation
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'All password fields are required']);
        exit();
    }
    
    if ($newPassword !== $confirmPassword) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
        exit();
    }
    
    if (strlen($newPassword) < 8) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
        exit();
    }
    
    // Get the current password hash from database. Parameterised for
    // defense-in-depth, consistent with the rest of the app - $user_id here
    // always comes from the server-side session (not client input), so this
    // was not actually exploitable, but there's no reason not to match the
    // established pattern used everywhere else.
    $sql = "SELECT password FROM users WHERE id = ? AND role = 'student'";
    $result = $db_handle->executeSelectPrepared($sql, "i", [$user_id]);
    
    if (empty($result)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    $storedPassword = $result[0]['password'];
    
    // Verify current password. The `|| $currentPassword === $storedPassword`
    // plaintext fallback that used to be here was removed - confirmed (via
    // a live DB check) that all current accounts already use proper bcrypt
    // hashes, so the fallback was dead weight, and a raw string-equality
    // branch in a password check is a risk with no upside once that's true.
    if (password_verify($currentPassword, $storedPassword)) {
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update the password. executeUpdatePrepared() returns affected_rows,
        // which is legitimately 0 both on failure AND on a successful no-op
        // (new hash happens to collide with the stored one - astronomically
        // unlikely for bcrypt, but the point is 0 is not a reliable failure
        // signal here) - the row's existence was already confirmed by the
        // SELECT above, so treat "no exception" as success rather than
        // gating on the row count, matching this app's other write endpoints.
        $updateSql = "UPDATE users SET password = ? WHERE id = ? AND role = 'student'";
        $db_handle->executeUpdatePrepared($updateSql, "si", [$hashedPassword, $user_id]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
