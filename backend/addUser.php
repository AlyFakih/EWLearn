<?php
// Server-side authorization gate: admin only. Placed first so no data is
// emitted before the caller is proven to be an authenticated admin.
require_once __DIR__ . '/../frontend/core/auth_guard.php';
auth_require_role('admin');

require './config.php';

// Fetch data from the POST request
$fullName = $_POST['full_name'];
$bloodType = $_POST['blood'];
$email = $_POST['email'];
$phoneNumber = $_POST['phone_number'];
$role = $_POST['role'];
$country = $_POST['country'];
$password = $_POST['password'];
$confirmPassword = $_POST['confirm_password'];
$gender = $_POST['gender'];

// Validate inputs
if (empty($fullName) || empty($bloodType) || empty($email) || empty($phoneNumber) || empty($role) || empty($country) || empty($password) || empty($confirmPassword) || empty($gender)) {
    $response = ['status' => 'error', 'message' => 'All fields are required'];
} elseif ($password !== $confirmPassword) {
    $response = ['status' => 'error', 'message' => 'Passwords do not match'];
} else {
    // Hash the password before storing it in the database
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Only these roles may ever be created.
    $allowedRoles = ['admin', 'instructor', 'student'];
    if (!in_array($role, $allowedRoles, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid role']);
        exit;
    }

    // Perform the insert operation. Parameterised - all of these came straight
    // from the request and were previously interpolated into the SQL string.
    $query = "INSERT INTO users (role, fullName, country, email, mobile, blood, gender, password)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssssss", $role, $fullName, $country, $email, $phoneNumber, $bloodType, $gender, $hashedPassword);
    $result = $stmt->execute();

    if ($result) {
        $response = ['status' => 'success', 'message' => ucfirst($role) . ' added successfully'];
    } else {
        // Check if the error is due to a unique constraint violation
        if (mysqli_errno($conn) == 1062) {
            $response = ['status' => 'error', 'message' => 'Phone number is already in use. Please choose a different number.'];
        } else {
            $response = ['status' => 'error', 'message' => 'Error adding instructor'];
        }
    }
}

echo json_encode($response);
?>
