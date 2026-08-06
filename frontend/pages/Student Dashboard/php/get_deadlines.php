<?php
// Start the session to maintain user login state
session_start();

// Check if the user is logged in and is a student (role = 'student')
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
  echo json_encode([]);
  exit();
}

// Include database controller
require_once "dbcontroller.php";
$db_handle = new DBController();

// Get student information
$user_id = $_SESSION['user_id'];

// Get upcoming deadlines for the student's enrolled courses
$sql = "SELECT 
          a.title,
          c.courseTitle as course_name, 
          IFNULL(a.type, 'Assignment') as type,
          DATE_FORMAT(a.due_date, '%b %d, %Y') as due_date
        FROM 
          assignment a
        JOIN 
          courses c ON a.course_id = c.id
        JOIN 
          studentcourse sc ON a.course_id = sc.course_id
        LEFT JOIN
          assignment_submissions s ON a.id = s.assignment_id AND s.student_id = $user_id
        WHERE 
          sc.studentID = $user_id
          AND a.due_date >= CURDATE()
          AND s.id IS NULL
        ORDER BY 
          a.due_date ASC
        LIMIT 5";

$result = $db_handle->readData($sql);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($result);
?>
