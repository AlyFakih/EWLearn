<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

require './config.php'; // Adjust the path to your database configuration file

// Assuming you are receiving the username and coursename through POST
$username = isset($_POST['fullName']) ? $_POST['fullName'] : null;
$coursename = isset($_POST['courseTitle']) ? $_POST['courseTitle'] : null;

if (!$username || !$coursename) {
    // If username or coursename is not provided, return an error response
    echo json_encode(['status' => 'error', 'message' => 'Username or Coursename not provided']);
    exit;
}

// Fetch user ID based on the provided username
$queryUser = "SELECT id, fullName FROM users WHERE fullName = '$username'";
$resultUser = mysqli_query($conn, $queryUser);

if (!$resultUser || mysqli_num_rows($resultUser) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

$userData = mysqli_fetch_assoc($resultUser);
$userID = $userData['id'];
$userFullName = $userData['fullName'];

// Fetch course ID based on the provided coursename
$queryCourse = "SELECT id, courseTitle FROM courses WHERE courseTitle = '$coursename'";
$resultCourse = mysqli_query($conn, $queryCourse);

if (!$resultCourse || mysqli_num_rows($resultCourse) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Course not found']);
    exit;
}

$courseData = mysqli_fetch_assoc($resultCourse);
$courseID = $courseData['id'];
$courseTitle = $courseData['courseTitle'];

// Calculate the value for the new field
$instructorCourseName = $userFullName . ' - ' . $courseTitle;

// Check if the combination of fullname and CourseTitle already exists in the database
$queryCheckName = "SELECT COUNT(*) as nameCount FROM instructorcourse WHERE userInstructorID = '$userFullName' AND courseID = '$courseTitle'";
$resultCheckName = mysqli_query($conn, $queryCheckName);

if (!$resultCheckName) {
    echo json_encode(['status' => 'error', 'message' => 'Error checking existing names']);
    exit;
}

$nameCount = mysqli_fetch_assoc($resultCheckName)['nameCount'];

if ($nameCount == 0) {
    // If the combination exists once, add 'A' before the name
    $adjustedName = 'A - ' . $instructorCourseName;
} elseif ($nameCount == 1) {
    // If the combination exists twice, add 'B' before the name
    $adjustedName = 'B - ' . $instructorCourseName;
} else {
    // If the combination exists more than twice, display an error message
    echo json_encode(['status' => 'error', 'message' => 'No more than two sections (A and B) are allowed']);
    exit;
}

// Insert data into instructorcourse table with the adjusted name
$queryInsert = "INSERT INTO instructorcourse (userInstructorID, courseID, name) VALUES ('$userFullName', '$courseTitle', '$adjustedName')";
$resultInsert = mysqli_query($conn, $queryInsert);

if ($resultInsert) {
    echo json_encode(['status' => 'success', 'message' => 'Record inserted successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error inserting record']);
}

$conn->close();
?>
