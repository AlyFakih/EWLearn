<?php
// Server-side authorization gate: admin only. Placed first so no data is
// emitted before the caller is proven to be an authenticated admin.
require_once __DIR__ . '/../../frontend/core/auth_guard.php';
auth_require_role('admin');

require '../config.php';

// Fetch data from the AJAX request
$userStudentID = isset($_POST['userStudentID']) ? $_POST['userStudentID'] : null;
$courseID = isset($_POST['courseID']) ? $_POST['courseID'] : null;
$updatedStudentName = $_POST['updatedStudentName'];
$updatedInstructorName = $_POST['updatedInstructorName'];
$updatedCourseTitle = $_POST['updatedCourseTitle'];

// Check if any field is empty
if (empty($updatedStudentName) || empty($updatedInstructorName) || empty($updatedCourseTitle)) {
    $response = ['status' => 'error', 'message' => 'Invalid request. Please provide necessary details.'];
} else {
    // Check if the combination of userStudentID and courseID already exists.
    // Parameterised - these values come straight from the request and were
    // previously interpolated into the SQL.
    $checkStmt = $conn->prepare(
        "SELECT COUNT(*) as count FROM studentcourse
         WHERE userStudentID = ? AND courseID = ? AND NOT (userStudentID = ? AND courseID = ?)"
    );
    $checkStmt->bind_param("ssss", $updatedStudentName, $updatedCourseTitle, $userStudentID, $courseID);
    $checkStmt->execute();
    $checkData = $checkStmt->get_result()->fetch_assoc();

    if ($checkData['count'] > 0) {
        // Combination already exists, return an error
        $response = ['status' => 'error', 'message' => 'This combination already exists in the studentcourse table.'];
    } else {
        // Update the student course in the database
        $updateStmt = $conn->prepare(
            "UPDATE studentcourse SET userStudentID = ?, userInstructorID = ?, courseID = ?
             WHERE userStudentID = ? AND courseID = ?"
        );
        $updateStmt->bind_param("sssss", $updatedStudentName, $updatedInstructorName, $updatedCourseTitle, $userStudentID, $courseID);
        $result = $updateStmt->execute();

        if ($result) {
            $response = ['status' => 'success', 'message' => 'Course updated successfully'];
        } else {
            $response = ['status' => 'error', 'message' => 'Error updating course: ' . mysqli_error($conn)];
        }
    }
}

echo json_encode($response);
?>
