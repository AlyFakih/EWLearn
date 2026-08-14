<?php
// Server-side authorization gate: instructor only. Runs before any other
// logic so nothing is emitted to an unauthenticated or wrong-role caller.
require_once __DIR__ . '/../../../core/auth_guard.php';
auth_require_role('instructor');

require_once "../../../core/DBController.php";

/**
 * Rewritten for security: the previous version had no session/role check at
 * all, built every query via raw string interpolation of $_POST (SQL
 * injection), and let any caller UPDATE or DELETE arbitrary rows in `users`
 * by ID - including other teachers' or admins' accounts - since nothing
 * scoped the target to a student actually enrolled in this teacher's
 * courses. It also tried to INSERT into `users` without the required
 * `password`, `blood`, `gender`, and `image` columns (all NOT NULL with no
 * default), which would fail outright, and set role = '0' even though
 * `role` is a string column ('student'/'instructor').
 *
 * Account creation/editing is out of scope for this endpoint - students
 * register through the normal signup flow. This now only lists students
 * enrolled in the current teacher's courses and allows removing a student
 * from one of those courses (it does not delete the user's account).
 */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(array('success' => false, 'message' => 'Unauthorized access'));
    exit();
}

$db_handle = new DBController();
$user_id = $_SESSION['user_id'];

function renderStudentRow($row) {
    $html = '<tr>';
    $html .= '<td data-id="student_id">' . htmlspecialchars($row['ID']) . '</td>';
    $html .= '<td data-id="student_name">' . htmlspecialchars($row['NAME']) . '</td>';
    $html .= '<td data-id="student_email">' . htmlspecialchars($row['EMAIL']) . '</td>';
    $html .= '<td data-id="student_mobile">' . htmlspecialchars($row['MOBILE']) . '</td>';
    $html .= '<td data-id="student_country">' . htmlspecialchars($row['COUNTRY']) . '</td>';
    $html .= '<td>';
    $html .= '<button class="del btn btn-warning" data-id="' . (int)$row['ID'] . '"><i class="fas fa-trash" aria-hidden="true"></i></button>';
    $html .= '</td>';
    $html .= '</tr>';
    return $html;
}

// Remove a student from one of this teacher's courses
if (isset($_POST['action']) && $_POST['action'] === 'del') {
    $student_id = (int)$db_handle->cleanData($_POST['id']);

    if ($student_id <= 0) {
        echo 0;
        exit();
    }

    $delete_query = "DELETE sc FROM studentcourse sc
                      JOIN instructorcourse ic ON ic.courseID = sc.courseID
                      JOIN users tu ON tu.fullName = ic.userInstructorID
                      JOIN users su ON su.fullName = sc.userStudentID
                      WHERE su.id = ? AND tu.id = ?";

    $affected = $db_handle->executeUpdatePrepared($delete_query, "ii", [$student_id, $user_id]);
    echo $affected > 0 ? 1 : 0;
    exit();
}

// List students enrolled in any course taught by this teacher
$list_query = "SELECT DISTINCT su.id AS ID, su.fullName AS NAME, su.email AS EMAIL,
                      su.mobile AS MOBILE, su.country AS COUNTRY
               FROM studentcourse sc
               JOIN instructorcourse ic ON ic.courseID = sc.courseID
               JOIN users tu ON tu.fullName = ic.userInstructorID
               JOIN users su ON su.fullName = sc.userStudentID
               WHERE tu.id = ?";
$rows = $db_handle->executeSelectPrepared($list_query, "i", [$user_id]);

$html = '';
foreach ($rows as $row) {
    $html .= renderStudentRow($row);
}
echo $html;
