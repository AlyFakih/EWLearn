<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

require './config.php'; // Adjust the path to your database configuration file

// Assuming you are receiving the instructor, course, and instructorCourses through POST
$instructor = isset($_POST['instructor']) ? $_POST['instructor'] : null;
$course = isset($_POST['course']) ? $_POST['course'] : null;
$instructorCourses = isset($_POST['instructorCourses']) ? $_POST['instructorCourses'] : null;

if (!$instructor || !$course || !$instructorCourses) {
    // If instructor, course, or instructorCourses are not provided, return an error response
    echo json_encode(['status' => 'error', 'message' => 'Incomplete data for update']);
    exit;
}

// Fetch user ID based on the provided instructor
$queryUser = "SELECT id, fullName FROM users WHERE fullName = '$instructor'";
$resultUser = mysqli_query($conn, $queryUser);

if (!$resultUser || mysqli_num_rows($resultUser) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Instructor not found']);
    exit;
}

$userData = mysqli_fetch_assoc($resultUser);
$userID = $userData['id'];
$userFullName = $userData['fullName'];

// Fetch course ID based on the provided course
$queryCourse = "SELECT id, courseTitle FROM courses WHERE courseTitle = '$course'";
$resultCourse = mysqli_query($conn, $queryCourse);

if (!$resultCourse || mysqli_num_rows($resultCourse) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Course not found']);
    exit;
}

$courseData = mysqli_fetch_assoc($resultCourse);
$courseID = $courseData['id'];
$courseTitle = $courseData['courseTitle'];

// Calculate the value for the new field
$adjustedName = $userFullName . ' - ' . $courseTitle;

// Check if the combination of fullname and CourseTitle already exists in the database
$queryCheckName = "SELECT COUNT(*) as nameCount FROM instructorcourse WHERE userInstructorID = '$userFullName' AND courseID = '$courseTitle'";
$resultCheckName = mysqli_query($conn, $queryCheckName);

if (!$resultCheckName) {
    echo json_encode(['status' => 'error', 'message' => 'Error checking existing names']);
    exit;
}

$nameCount = mysqli_fetch_assoc($resultCheckName)['nameCount'];

if ($nameCount == 0) {
    // If the combination exists once, add 'A' before the name for the update
    $adjustedNameUpdate = 'A - ' . $adjustedName;
} elseif ($nameCount == 1) {
    // If the combination exists twice, add 'B' before the name for the update
    $adjustedNameUpdate = 'B - ' . $adjustedName;
} else {
    // If the combination exists more than twice, display an error message
    echo json_encode(['status' => 'error', 'message' => 'No more than two sections (A and B) are allowed']);
    exit;
}

// Proceed with the update
$queryUpdate = "UPDATE instructorcourse SET name = '$adjustedNameUpdate', userInstructorID = '$userFullName', courseID = '$courseTitle' WHERE name = '$instructorCourses' ";
$resultUpdate = mysqli_query($conn, $queryUpdate);

if ($resultUpdate) {
    echo json_encode(['status' => 'success', 'message' => 'Record updated successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error updating record']);
}

$conn->close();
?>
