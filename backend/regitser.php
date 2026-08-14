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

// This endpoint is public and unauthenticated, so every value below is fully
// attacker-controlled: all three statements are parameterised.
// Check if email already exists
$checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
$checkEmail->bind_param("s", $email);
$checkEmail->execute();
$checkEmailResult = $checkEmail->get_result();

// Check if mobile number already exists
$checkMobile = $conn->prepare("SELECT id FROM users WHERE mobile = ?");
$checkMobile->bind_param("s", $mobile);
$checkMobile->execute();
$checkMobileResult = $checkMobile->get_result();

if ($checkEmailResult->num_rows > 0) {
    $response = array('status' => 'error', 'message' => 'Email already exists. Please choose a different email.');
} elseif ($checkMobileResult->num_rows > 0) {
    $response = array('status' => 'error', 'message' => 'Mobile number already exists. Please choose a different mobile number.');
} else {
    // Insert data into the table.
    // `role` is deliberately NOT accepted from the request and not listed here:
    // the column defaults to 'student', so public self-registration can never
    // mint an instructor or admin account.
    $sql = "INSERT INTO users (fullName, country, email, mobile, blood, gender, password)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $fullName, $country, $email, $mobile, $blood, $gender, $password);

    if ($stmt->execute()) {
        $response = array('status' => 'success', 'message' => 'Registration successful');

    } else {
        error_log("regitser.php insert failed: " . $stmt->error);
        $response = array('status' => 'error', 'message' => 'Error during registration.');
    }
}

$conn->close();
ob_end_flush();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
