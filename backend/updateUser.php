<?php
require './config.php';

// Fetch data from the POST request
$fullName = $_POST['full_name'];
$bloodType = $_POST['blood'];
$email = $_POST['email'];
$phoneNumber = $_POST['phone_number'];
$role = $_POST['role'];
$country = $_POST['country'];
// $password = $_POST['password'];
// $confirmPassword = $_POST['confirm_password'];
// $gender = $_POST['gender'];

// // Check if the new email is unique
// $checkEmailQuery = "SELECT COUNT(*) as count FROM users WHERE email = '$email'";
// $emailResult = mysqli_query($conn, $checkEmailQuery);
// $emailCount = mysqli_fetch_assoc($emailResult)['count'];

// // Check if the new phone number is unique
// $checkPhoneNumberQuery = "SELECT COUNT(*) as count FROM users WHERE phone_number = '$phoneNumber'";
// $phoneNumberResult = mysqli_query($conn, $checkPhoneNumberQuery);
// $phoneNumberCount = mysqli_fetch_assoc($phoneNumberResult)['count'];

// // Check if both email and phone number are unique
// if ($emailCount > 0) {
//     echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
//     exit;
// }

// if ($phoneNumberCount > 0) {
//     echo json_encode(['status' => 'error', 'message' => 'Phone number already exists']);
//     exit;
// }

// Perform the update operation
$query = "UPDATE users 
          SET fullName = '$fullName', 
              blood = '$bloodType', 
              email = '$email', 
              mobile = '$phoneNumber', 
              role = '$role', 
              country = '$country'
                 
          WHERE email = '$email'";

// Execute the query using the same connection
$result = mysqli_query($conn, $query);

if ($result) {
    echo json_encode(['status' => 'success', 'message' => 'User updated successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error updating user']);
}
?>
