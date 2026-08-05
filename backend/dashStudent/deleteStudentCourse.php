<?php
require '../config.php';

// Fetch data from the AJAX request

$userStudentID = isset($_POST['userStudentID']) ? $_POST['userStudentID'] : null;
$courseID = isset($_POST['courseID']) ? $_POST['courseID'] : null;
// Perform the delete operation from the studentcourse table
$query = "DELETE FROM studentcourse WHERE userStudentID = '$userStudentID' AND courseID = '$courseID'";

// Execute the query using the same connection
$result = mysqli_query($conn, $query);

if ($result) {
    $response = ['status' => 'success', 'message' => 'Course deleted successfully'];
} else {
    $response = ['status' => 'error', 'message' => 'Error deleting course: ' . mysqli_error($conn)];
}

echo json_encode($response);
?>
