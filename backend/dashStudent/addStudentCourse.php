<?php
require '../config.php';

// Fetch data from the AJAX request
$studentName = $_POST['studentName'];
$instructorName = $_POST['instructorName'];
$courseTitle = $_POST['courseTitle'];

// Check if any field is empty
if (empty($studentName) || empty($instructorName) || empty($courseTitle)) {
    $response = ['status' => 'error', 'message' => 'Please choose from all fields. All fields are required.'];
} else {
    // Check if the combination of userStudentID and courseID already exists
    $checkQuery = "SELECT COUNT(*) as count FROM studentcourse WHERE userStudentID = '$studentName' AND courseID = '$courseTitle'";
    $checkResult = mysqli_query($conn, $checkQuery);
    $checkData = mysqli_fetch_assoc($checkResult);

    if ($checkData['count'] > 0) {
        // Combination already exists, return an error
        $response = ['status' => 'error', 'message' => 'This combination already exists in the studentcourse table.'];
    } else {
        // Perform the insert operation into studentcourse table
        $insertQuery = "INSERT INTO studentcourse (userStudentID, userInstructorID, courseID)
                        VALUES ('$studentName', '$instructorName', '$courseTitle')";

        // Execute the query using the same connection
        $result = mysqli_query($conn, $insertQuery);

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
