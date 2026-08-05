<?php
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
    // Check if the combination of userStudentID and courseID already exists
    $checkQuery = "SELECT COUNT(*) as count FROM studentcourse WHERE userStudentID = '$updatedStudentName' AND courseID = '$updatedCourseTitle' AND NOT (userStudentID = '$userStudentID' AND courseID = '$courseID')";
    $checkResult = mysqli_query($conn, $checkQuery);
    $checkData = mysqli_fetch_assoc($checkResult);

    if ($checkData['count'] > 0) {
        // Combination already exists, return an error
        $response = ['status' => 'error', 'message' => 'This combination already exists in the studentcourse table.'];
    } else {
        // Update the student course in the database
        $query = "UPDATE studentcourse SET userStudentID = '$updatedStudentName', userInstructorID = '$updatedInstructorName', courseID = '$updatedCourseTitle' WHERE userStudentID = '$userStudentID'  AND courseID = '$courseID'";

        // Execute the query using the same connection
        $result = mysqli_query($conn, $query);

        if ($result) {
            $response = ['status' => 'success', 'message' => 'Course updated successfully'];
        } else {
            $response = ['status' => 'error', 'message' => 'Error updating course: ' . mysqli_error($conn)];
        }
    }
}

echo json_encode($response);
?>
