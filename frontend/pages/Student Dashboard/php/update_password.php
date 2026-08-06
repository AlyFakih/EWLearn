<?php
// Start the session to maintain user login state
session_start();

// Check if the user is logged in and is a student (role = 'student')
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    // Return error response
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database controller
require_once "dbcontroller.php";
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
    
    // Get the current password hash from database
    $sql = "SELECT password FROM users WHERE id = $user_id AND role = 'student'";
    $result = $db_handle->readData($sql);
    
    if (empty($result)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    $storedPassword = $result[0]['password'];
    
    // Verify current password
    if (password_verify($currentPassword, $storedPassword) || $currentPassword === $storedPassword) {
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update the password
        $updateSql = "UPDATE users SET password = '$hashedPassword' WHERE id = $user_id AND role = 'student'";
        
        if ($db_handle->executeQuery($updateSql)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error updating password']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
