<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

require './config.php'; // Adjust the path to your database configuration file

// Fetch instructor courses from the database
$query = "SELECT name FROM instructorcourse";
$result = mysqli_query($conn, $query);

if (!$result) {
    // If there is an error in the query, return a 500 Internal Server Error
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal Server Error'], JSON_UNESCAPED_SLASHES);
    exit;
}

$instructorCourses = [];
while ($row = mysqli_fetch_assoc($result)) {
    $instructorCourses[] = $row;
}

if (empty($instructorCourses)) {
    // If no instructor courses are found, return a 404 Not Found
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'No instructor courses found'], JSON_UNESCAPED_SLASHES);
} else {
    // If successful, return a 200 OK with only the instructor course names
    http_response_code(200);
    echo json_encode($instructorCourses, JSON_UNESCAPED_SLASHES);
}

$conn->close();
?>
