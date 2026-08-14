<?php
// Server-side authorization gate: student only. Runs first so nothing is
// emitted to an unauthenticated, wrong-role, expired or deleted account.
require_once __DIR__ . '/../../../core/auth_guard.php';
auth_require_role('student');

// Start the session to maintain user login state

// Check if the user is logged in and is a student (role = 'student')
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
  echo json_encode([]);
  exit();
}

// Include database controller
require_once "../../../core/DBController.php";
$db_handle = new DBController();

// Get student information
$user_id = $_SESSION['user_id'];

// Get the student's enrolled courses. studentcourse is keyed by
// users.fullName / courses.courseTitle, not by a numeric student/course ID.
$courseSql = "SELECT c.id AS course_id
              FROM studentcourse sc
              JOIN courses c ON c.courseTitle = sc.courseID
              JOIN users u ON u.fullName = sc.userStudentID
              WHERE u.id = ?";
$courses = $db_handle->executeSelectPrepared($courseSql, "i", [$user_id]);

// Build a comma-separated list of course IDs
$courseIds = [];
foreach ($courses as $course) {
  $courseIds[] = (int)$course['course_id'];
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
