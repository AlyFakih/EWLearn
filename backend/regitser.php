<?php
ob_start();
include "./config.php";

$fullName = htmlspecialchars($_POST['fullName']);
$country = htmlspecialchars($_POST['country']);
$email = htmlspecialchars($_POST['email']);
$mobile = htmlspecialchars($_POST['mobileNumber']);
$blood = htmlspecialchars($_POST['bloodType']);
$gender = htmlspecialchars($_POST['gender']);
$password = password_hash($_POST['passwordsignup'], PASSWORD_DEFAULT);

// Check if email already exists
$checkEmailQuery = "SELECT * FROM users WHERE email = '$email'";
$checkEmailResult = $conn->query($checkEmailQuery);

// Check if mobile number already exists
$checkMobileQuery = "SELECT * FROM users WHERE mobile = '$mobile'";
$checkMobileResult = $conn->query($checkMobileQuery);

if ($checkEmailResult->num_rows > 0) {
    $response = array('status' => 'error', 'message' => 'Email already exists. Please choose a different email.');
} elseif ($checkMobileResult->num_rows > 0) {
    $response = array('status' => 'error', 'message' => 'Mobile number already exists. Please choose a different mobile number.');
} else {
    // Insert data into the table
    $sql = "INSERT INTO users (fullName, country, email, mobile, blood, gender, password) 
            VALUES ('$fullName', '$country', '$email', '$mobile', '$blood', '$gender', '$password')";

    if ($conn->query($sql) === TRUE) {
        $response = array('status' => 'success', 'message' => 'Registration successful');
       
    } else {
        $response = array('status' => 'error', 'message' => 'Error during registration.');
    }
}

$conn->close();
ob_end_flush();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
