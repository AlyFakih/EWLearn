<?php
// Public endpoint: the instructor roster is marketing content shown on the
// public Instructor.html page, so unlike admin-only endpoints in this
// directory this one has no auth gate by design. It intentionally excludes
// email/mobile (kept admin-only elsewhere, see getEmailsInstr.php) since
// there is no reason to expose that PII on a public page.
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
require './config.php';

// instructorcourse/courses are keyed by name strings (legacy schema, no
// numeric FKs) - same join already proven correct in
// DBController::getTeacherCourseIds().
$query = "SELECT u.id, u.fullName, u.country, u.image,
                 COUNT(DISTINCT c.id) AS courseCount,
                 GROUP_CONCAT(DISTINCT c.category SEPARATOR ', ') AS categories
          FROM users u
          LEFT JOIN instructorcourse ic ON ic.userInstructorID = u.fullName
          LEFT JOIN courses c ON c.courseTitle = ic.courseID
          WHERE u.role = 'instructor'
          GROUP BY u.id, u.fullName, u.country, u.image
          ORDER BY u.fullName ASC";

$result = $conn->query($query);

$instructors = [];
while ($row = $result->fetch_assoc()) {
    $row['courseCount'] = (int) $row['courseCount'];
    $row['categories'] = $row['categories'] ?? '';
    $instructors[] = $row;
}

echo json_encode($instructors, JSON_UNESCAPED_SLASHES);
?>
