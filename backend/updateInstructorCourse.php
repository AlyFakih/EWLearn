<?php
// Server-side authorization gate: admin only. Placed first so no data is
// emitted before the caller is proven to be an authenticated admin.
require_once __DIR__ . '/../frontend/core/auth_guard.php';
auth_require_role('admin');

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

// Fetch user ID based on the provided instructor. Parameterised - these
// values come straight from the request and were previously interpolated
// into the SQL.
$stmtUser = $conn->prepare("SELECT id, fullName FROM users WHERE fullName = ?");
$stmtUser->bind_param("s", $instructor);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();

if (!$resultUser || $resultUser->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Instructor not found']);
    exit;
}

$userData = $resultUser->fetch_assoc();
$userID = $userData['id'];
$userFullName = $userData['fullName'];

// Fetch course ID based on the provided course
$stmtCourse = $conn->prepare("SELECT id, courseTitle FROM courses WHERE courseTitle = ?");
$stmtCourse->bind_param("s", $course);
$stmtCourse->execute();
$resultCourse = $stmtCourse->get_result();

if (!$resultCourse || $resultCourse->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Course not found']);
    exit;
}

$courseData = $resultCourse->fetch_assoc();
$courseID = $courseData['id'];
$courseTitle = $courseData['courseTitle'];

// Calculate the value for the new field
$adjustedName = $userFullName . ' - ' . $courseTitle;

// Check if the combination of fullname and CourseTitle already exists in the database
$stmtCheckName = $conn->prepare("SELECT COUNT(*) as nameCount FROM instructorcourse WHERE userInstructorID = ? AND courseID = ?");
$stmtCheckName->bind_param("ss", $userFullName, $courseTitle);
$stmtCheckName->execute();
$resultCheckName = $stmtCheckName->get_result();

if (!$resultCheckName) {
    echo json_encode(['status' => 'error', 'message' => 'Error checking existing names']);
    exit;
}

$nameCount = $resultCheckName->fetch_assoc()['nameCount'];

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
$stmtUpdate = $conn->prepare("UPDATE instructorcourse SET name = ?, userInstructorID = ?, courseID = ? WHERE name = ?");
$stmtUpdate->bind_param("ssss", $adjustedNameUpdate, $userFullName, $courseTitle, $instructorCourses);
$resultUpdate = $stmtUpdate->execute();

if ($resultUpdate) {
    echo json_encode(['status' => 'success', 'message' => 'Record updated successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error updating record']);
}

$conn->close();
?>
