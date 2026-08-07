<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    echo "0";
    exit();
}

require_once "dbcontroller.php";
$db_handle = new StudentDBController();

$user_id = intval($_SESSION['user_id']);

$user_sql = "SELECT fullName
             FROM users
             WHERE id = $user_id
             AND role = 'student'";

$user_result = $db_handle->readData($user_sql);

if (empty($user_result)) {
    echo "0";
    exit();
}

$student_name = $user_result[0]['fullName'];

$sql = "SELECT COUNT(*) AS assignment_count
        FROM assignment a
        JOIN courses c ON a.course_id = c.id
        JOIN studentcourse sc ON c.courseTitle = sc.courseID
        JOIN users u ON sc.userStudentID = u.fullName
        WHERE u.id = $user_id";

$result = $db_handle->readData($sql);

if (!empty($result)) {
    echo $result[0]['assignment_count'];
} else {
    echo "0";
}
?>
