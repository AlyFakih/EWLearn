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
$db_handle = new StudentDBController();

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
    
    // Prepare and execute the update query
    $sql = "UPDATE users SET 
            fullName = '$fullName', 
            email = '$email', 
            mobile = '$mobile', 
            gender = '$gender', 
            bloodType = '$bloodType', 
            country = '$country' 
            WHERE id = $user_id AND role = 'student'";
            
    if ($db_handle->executeQuery($sql)) {
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
