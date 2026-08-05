<?php
// Start the session to maintain user login state
session_start();

// Check if the user is logged in and is a student (role = 0)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 0) {
  echo "N/A";
  exit();
}

// Include database controller
require_once "dbcontroller.php";
$db_handle = new DBController();

// Get student information
$user_id = $_SESSION['user_id'];

// Calculate average grade for the student
$sql = "SELECT AVG(cg.grade) as average_grade
        FROM course_grades cg
        WHERE cg.student_id = $user_id";

$result = $db_handle->readData($sql);

if (!empty($result) && $result[0]['average_grade'] !== null) {
  echo number_format($result[0]['average_grade'], 1);
} else {
  echo "N/A";
}
?>
