<?php
// Server-side authorization gate: student only. Runs first so nothing is
// emitted to an unauthenticated, wrong-role, expired or deleted account.
require_once __DIR__ . '/../../../core/auth_guard.php';
auth_require_role('student');

// Start the session to maintain user login state

// Check if the user is logged in and is a student (role = 'student')
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
  echo "0";
  exit();
}

// Include database controller
require_once "../../../core/DBController.php";
$db_handle = new DBController();

// Get student information
$user_id = $_SESSION['user_id'];

// Calculate attendance rate for the student
$sql = "SELECT 
          COUNT(CASE WHEN a.status = 'present' OR a.status = 'online' THEN 1 END) as present_count,
          COUNT(*) as total_count
        FROM attendance a 
        WHERE a.studentID = $user_id";

$result = $db_handle->readData($sql);

if (!empty($result) && $result[0]['total_count'] > 0) {
  $attendance_rate = round(($result[0]['present_count'] / $result[0]['total_count']) * 100);
  echo $attendance_rate;
} else {
  echo "100"; // Default to 100% if no attendance records
}
?>
