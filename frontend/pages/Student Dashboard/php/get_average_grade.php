<?php
// Server-side authorization gate: student only. Runs first so nothing is
// emitted to an unauthenticated, wrong-role, expired or deleted account.
require_once __DIR__ . '/../../../core/auth_guard.php';
auth_require_role('student');


if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    echo "N/A";
    exit();
}

require_once "../../../core/DBController.php";
$db_handle = new DBController();

$user_id = intval($_SESSION['user_id']);

$sql = "SELECT AVG(overall_grade) AS average_grade
        FROM course_grades
        WHERE student_id = $user_id";

$result = $db_handle->readData($sql);

if (!empty($result) && $result[0]['average_grade'] !== null) {
    echo number_format((float)$result[0]['average_grade'], 1);
} else {
    echo "N/A";
}
?>
