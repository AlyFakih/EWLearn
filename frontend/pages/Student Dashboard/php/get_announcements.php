<?php
// Start the session to maintain user login state
session_start();

// Check if the user is logged in and is a student (role = 0)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 0) {
  echo json_encode([]);
  exit();
}

// Include database controller
require_once "dbcontroller.php";
$db_handle = new DBController();

// Get student information
$user_id = $_SESSION['user_id'];

// Get the student's enrolled courses
$courseSql = "SELECT course_id FROM studentcourse WHERE studentID = $user_id";
$courses = $db_handle->readData($courseSql);

// Build a comma-separated list of course IDs
$courseIds = [];
foreach ($courses as $course) {
  $courseIds[] = $course['course_id'];
}
$courseIdList = !empty($courseIds) ? implode(',', $courseIds) : '0'; // Default to 0 if no courses found

// Get recent announcements for:
// 1. All users (target_type = 'all')
// 2. Course-specific announcements for the student's enrolled courses
$sql = "SELECT 
          a.id,
          a.title,
          a.content,
          a.important,
          u.fullName as posted_by,
          DATE_FORMAT(a.created_at, '%b %d, %Y') as date,
          IF(a.target_type = 'all', 'General', c.courseTitle) as course_name
        FROM 
          announcements a
        LEFT JOIN 
          courses c ON a.target_type = 'course' AND a.target_id = c.id
        JOIN
          users u ON a.created_by = u.id
        WHERE 
          a.target_type = 'all' 
          OR (a.target_type = 'course' AND a.target_id IN ($courseIdList))
        ORDER BY 
          a.important DESC,
          a.created_at DESC
        LIMIT 10";

$result = $db_handle->readData($sql);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($result);
?>
