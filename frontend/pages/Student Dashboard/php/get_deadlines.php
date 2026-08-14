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

// Get upcoming deadlines for the student's enrolled courses. `assignment`
// has no `type` column, and studentcourse is keyed by users.fullName /
// courses.courseTitle, not by numeric IDs.
$sql = "SELECT
          a.title,
          c.courseTitle as course_name,
          'Assignment' as type,
          DATE_FORMAT(a.due_date, '%b %d, %Y') as due_date
        FROM
          assignment a
        JOIN
          courses c ON a.course_id = c.id
        JOIN
          studentcourse sc ON sc.courseID = c.courseTitle
        JOIN
          users su ON su.fullName = sc.userStudentID
        LEFT JOIN
          assignment_submissions s ON a.id = s.assignment_id AND s.student_id = ?
        WHERE
          su.id = ?
          AND a.due_date >= CURDATE()
          AND s.id IS NULL
        ORDER BY
          a.due_date ASC
        LIMIT 5";

$result = $db_handle->executeSelectPrepared($sql, "ii", [$user_id, $user_id]);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($result);
?>
