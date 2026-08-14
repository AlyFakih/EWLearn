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

// Only these roles may ever be written; without this an admin-supplied (or
// tampered) value could set an arbitrary role string on the account.
$allowedRoles = ['admin', 'instructor', 'student'];
if (!in_array($role, $allowedRoles, true)) {
    echo json_encode(["status" => "error", "message" => "Invalid role"]);
    exit;
}

// Perform the update operation. Parameterised - every one of these values comes
// straight from the request and was previously interpolated into the SQL.
$query = "UPDATE users
          SET fullName = ?,
              blood = ?,
              email = ?,
              mobile = ?,
              role = ?,
              country = ?
          WHERE email = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("sssssss", $fullName, $bloodType, $email, $phoneNumber, $role, $country, $email);
$result = $stmt->execute();

if ($result) {
    echo json_encode(["status" => "success", "message" => "User updated successfully", "affected_rows" => $stmt->affected_rows]);
} else {
    // Do not echo mysqli_error() to the client: it leaks schema and query text.
    error_log("updateUser.php failed: " . $stmt->error);
    echo json_encode(["status" => "error", "message" => "Error updating user"]);
}
?>
