<?php
require '../config.php';

// Perform a SELECT query to retrieve data from studentcourse table
$sql = "SELECT userStudentID, userInstructorID, courseID FROM studentcourse";
$result = $conn->query($sql);

// Convert the result to an associative array
$studentCourses = [];
while ($row = $result->fetch_assoc()) {
    $studentCourses[] = $row;
}

// Return the data as JSON
header('Content-Type: application/json');
echo json_encode($studentCourses);

?>
