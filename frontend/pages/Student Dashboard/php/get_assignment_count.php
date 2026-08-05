<?php
// Start the session to maintain user login state
session_start();

// Check if the user is logged in and is a student (role = 0)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 0) {
  echo "0";
  exit();
}

// Include database controller
require_once "dbcontroller.php";
$db_handle = new DBController();

// Get student information
$user_id = $_SESSION['user_id'];

// Count assignments for the student's enrolled courses
$sql = "SELECT COUNT(*) as assignment_count 
        FROM assignment a 
        JOIN studentcourse sc ON a.course_id = sc.course_id 
        WHERE sc.studentID = $user_id";

$result = $db_handle->readData($sql);

if (!empty($result)) {
  echo $result[0]['assignment_count'];
} else {
  echo "0";
}
?>
