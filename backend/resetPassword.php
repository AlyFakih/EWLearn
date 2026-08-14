<?php
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
        // Look the account up once, with a parameterised query. This endpoint is
        // reachable unauthenticated (it is the "change my password" flow and
        // authorises via knowledge of the old password), so the email value is
        // fully attacker-controlled and must never be interpolated into SQL.
        $checkStmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkPasswordResult = $checkStmt->get_result();

        if ($checkPasswordResult->num_rows > 0) {
            $userData = $checkPasswordResult->fetch_assoc();
            $hashedPassword = $userData['password'];

            // Verify the old password
            if (password_verify($oldPassword, $hashedPassword)) {
                // Old password is correct, update the password
                $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                $updateStmt->bind_param("ss", $newPasswordHashed, $email);

                if ($updateStmt->execute()) {
                    $response['status'] = 'success';
                    $response['message'] = 'Password updated successfully';
                } else {
                    // Never surface the driver error: it leaks schema/query text.
                    error_log("resetPassword.php update failed: " . $updateStmt->error);
                    $response['status'] = 'error';
                    $response['message'] = 'Error updating password. Please try again.';
                }
            } else {
                $response['status'] = 'error';
                $response['message'] = 'Error: Incorrect email or password. Please try again.';
            }
        } else {
            // Same wording as the wrong-password branch above. Distinguishing
            // "email not found" from "wrong password" on an unauthenticated
            // endpoint lets anyone enumerate which email addresses hold accounts.
            $response['status'] = 'error';
            $response['message'] = 'Error: Incorrect email or password. Please try again.';
        }
    }
}

// Send the JSON-encoded response to the frontend
echo json_encode($response);
?>
