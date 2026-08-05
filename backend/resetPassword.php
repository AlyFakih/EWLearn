<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

include "./config.php";


$response = array(); // Initialize an associative array for the response

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $email = filter_var($_POST['EmailR'], FILTER_VALIDATE_EMAIL);
    $oldPassword = htmlspecialchars($_POST['oldpassword']);
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validate required fields
    if (empty($email) || empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
        $response['status'] = 'error';
        $response['message'] = 'All fields are required.';
    } elseif ($newPassword !== $confirmPassword) {
        $response['status'] = 'error';
        $response['message'] = 'Password and Confirm Password must match.';
    } else {
        // Hash the passwords
    $newPasswordHashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $confirmPasswordHashed = password_hash($confirmPassword, PASSWORD_DEFAULT);
        // Check if the email exists
        $checkEmailQuery = "SELECT * FROM users WHERE email = '$email'";
        $checkEmailResult = $conn->query($checkEmailQuery);

        if ($checkEmailResult->num_rows > 0) {
            // Email exists, now check if the old password is correct
            $checkPasswordQuery = "SELECT password FROM users WHERE email = '$email'";
            $checkPasswordResult = $conn->query($checkPasswordQuery);

            if ($checkPasswordResult->num_rows > 0) {
                $userData = $checkPasswordResult->fetch_assoc();
                $hashedPassword = $userData['password'];

                // Verify the old password
                if (password_verify($oldPassword, $hashedPassword)) {
                    // Old password is correct, update the password
                   
                 
                    $updatePasswordQuery = "UPDATE users SET password = '$newPasswordHashed' WHERE email = '$email'";
                 
                    if ($conn->query($updatePasswordQuery) === TRUE) {
                        $response['status'] = 'success';
                        $response['message'] = 'Password updated successfully';
                    } else {
                        $response['status'] = 'error';
                        $response['message'] = 'Error updating password: ' . $conn->error;
                    }
                    // After update password query
                 
                } else {
                   
                    $response['status'] = 'error';
                    $response['message'] = 'Error: Incorrect old password. Please try again.';
                }
            }
        } else {
            
          
            

            $response['status'] = 'error';
            $response['message'] = 'Error: Email not found.';
        }
    }
}

// Send the JSON-encoded response to the frontend
echo json_encode($response);
?>
