<?php
// Server-side authorization gate: admin only. Placed first so no data is
// emitted before the caller is proven to be an authenticated admin.
require_once __DIR__ . '/../../frontend/core/auth_guard.php';
auth_require_role('admin');

require '../config.php';

// Fetch data from the AJAX request
$studentName = $_POST['studentName'];
$instructorName = $_POST['instructorName'];
$courseTitle = $_POST['courseTitle'];

// Check if any field is empty
if (empty($studentName) || empty($instructorName) || empty($courseTitle)) {
    $response = ['status' => 'error', 'message' => 'Please choose from all fields. All fields are required.'];
} else {
    // Check if the combination of userStudentID and courseID already exists.
    // Parameterised - these values come straight from the request and were
    // previously interpolated into the SQL.
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM studentcourse WHERE userStudentID = ? AND courseID = ?");
    $checkStmt->bind_param("ss", $studentName, $courseTitle);
    $checkStmt->execute();
    $checkData = $checkStmt->get_result()->fetch_assoc();

    if ($checkData['count'] > 0) {
        // Combination already exists, return an error
        $response = ['status' => 'error', 'message' => 'This combination already exists in the studentcourse table.'];
    } else {
        // Perform the insert operation into studentcourse table
        $insertStmt = $conn->prepare(
            "INSERT INTO studentcourse (userStudentID, userInstructorID, courseID) VALUES (?, ?, ?)"
        );
        $insertStmt->bind_param("sss", $studentName, $instructorName, $courseTitle);
        $result = $insertStmt->execute();

        if ($result) {
            // Course added to studentcourse successfully
            $response = ['status' => 'success', 'message' => 'Course added to studentcourse successfully'];
        } else {
            // Error adding course to studentcourse
            $response = ['status' => 'error', 'message' => 'Error adding course to studentcourse: ' . mysqli_error($conn)];
        }
    }
}

echo json_encode($response);
?>
