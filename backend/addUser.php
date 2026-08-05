<?php
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

    // Perform the insert operation
    $query = "INSERT INTO users (role, fullName, country, email, mobile, blood, gender, password)
              VALUES ('$role','$fullName', '$country', '$email', '$phoneNumber', '$bloodType', '$gender', '$hashedPassword')";

    // Execute the query using the same connection
    $result = mysqli_query($conn, $query);

    if ($result) {
        $response = ['status' => 'success', 'message' => 'Instructor added successfully'];
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
