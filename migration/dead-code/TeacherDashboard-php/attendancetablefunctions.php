<?php
// Server-side authorization gate: instructor only. Runs before any other
// logic so nothing is emitted to an unauthenticated or wrong-role caller.
require_once __DIR__ . '/../../../core/auth_guard.php';
auth_require_role('instructor');

require_once "../../../core/DBController.php";

// This endpoint has no callers anywhere in the app (attendance_functions.php
// is the live implementation). It previously built SQL by string
// interpolation with no session/authorization check at all, so it is left
// in place only with the same guard and parameterized queries as the rest
// of the Teacher Dashboard, to close that hole rather than leave a live
// SQL-injection / auth-bypass endpoint reachable by direct URL.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(array('success' => false, 'message' => 'Unauthorized access'));
    exit();
}

$db_handle = new DBController();
$user_id = $_SESSION['user_id'];

header('HTTP/1.1 410 Gone');
echo json_encode(array('success' => false, 'message' => 'This endpoint is not implemented. Use attendance_functions.php.'));
exit();
