<?php
// Public endpoint (see getInstructorsList.php for why this has no auth gate
// and excludes PII like email/mobile).
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
require './config.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Instructor id is required']);
    exit;
}

$stmt = $conn->prepare("SELECT id, fullName, country, image FROM users WHERE id = ? AND role = 'instructor'");
$stmt->bind_param("i", $id);
$stmt->execute();
$instructor = $stmt->get_result()->fetch_assoc();

if (!$instructor) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Instructor not found']);
    exit;
}

// Same join already proven correct in DBController::getTeacherCourseIds().
$stmt = $conn->prepare(
    "SELECT c.id, c.courseTitle, c.category, c.description
     FROM courses c
     JOIN instructorcourse ic ON ic.courseID = c.courseTitle
     JOIN users u ON u.fullName = ic.userInstructorID
     WHERE u.id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT subjectIcon, subjectName, progressPercentage, experience FROM instructor_skills WHERE instructorID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$skills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'instructor' => $instructor,
    'courses' => $courses,
    'skills' => $skills,
], JSON_UNESCAPED_SLASHES);
?>
