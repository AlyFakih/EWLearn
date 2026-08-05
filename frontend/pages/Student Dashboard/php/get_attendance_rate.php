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

// Calculate attendance rate for the student
$sql = "SELECT 
          COUNT(CASE WHEN a.status = 'present' OR a.status = 'online' THEN 1 END) as present_count,
          COUNT(*) as total_count
        FROM attendance a 
        WHERE a.student_id = $user_id";

$result = $db_handle->readData($sql);

if (!empty($result) && $result[0]['total_count'] > 0) {
  $attendance_rate = round(($result[0]['present_count'] / $result[0]['total_count']) * 100);
  echo $attendance_rate;
} else {
  echo "100"; // Default to 100% if no attendance records
}
?>
