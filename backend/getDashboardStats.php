<?php
// Server-side authorization gate: admin only. Placed first so no data is
// emitted before the caller is proven to be an authenticated admin.
require_once __DIR__ . '/../frontend/core/auth_guard.php';
auth_require_role('admin');

header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
require './config.php';

// Real counts per role. No user input involved, so no injection surface.
// role is deliberately restricted to admin/instructor/student everywhere
// else in this app (see addUser.php/updateUser.php) - 'staff' was never a
// real role, so its count is always genuinely 0, not a placeholder.
$roleCounts = ['admin' => 0, 'instructor' => 0, 'student' => 0, 'staff' => 0];
$roleResult = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
while ($row = $roleResult->fetch_assoc()) {
    if (isset($roleCounts[$row['role']])) {
        $roleCounts[$row['role']] = (int) $row['count'];
    }
}
$totalUsers = array_sum($roleCounts);

// Real enrollment activity for the past 12 months, from studentcourse's own
// enrollment_date column - replaces the previous hardcoded "Earning" chart,
// which had no real data behind it at all (this app has no payments/earnings
// table anywhere in the schema).
$months = [];
$counts = [];
for ($i = 11; $i >= 0; $i--) {
    $months[] = date('Y-m', strtotime("-$i months"));
    $counts[date('Y-m', strtotime("-$i months"))] = 0;
}
$enrollResult = $conn->query(
    "SELECT DATE_FORMAT(enrollment_date, '%Y-%m') as ym, COUNT(*) as count
     FROM studentcourse
     WHERE enrollment_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     GROUP BY ym"
);
while ($row = $enrollResult->fetch_assoc()) {
    if (isset($counts[$row['ym']])) {
        $counts[$row['ym']] = (int) $row['count'];
    }
}
$enrollmentSeries = array_values($counts);

echo json_encode([
    'students' => $roleCounts['student'],
    'teachers' => $roleCounts['instructor'],
    'staff' => $roleCounts['staff'],
    'totalUsers' => $totalUsers,
    'roleBreakdown' => [
        'administration' => $roleCounts['admin'],
        'instructors' => $roleCounts['instructor'],
        'students' => $roleCounts['student'],
        'others' => $roleCounts['staff'],
    ],
    'enrollmentMonths' => $months,
    'enrollmentCounts' => $enrollmentSeries,
]);
?>
