<?php
require './config.php';

// Fetch data from the POST request
$description = $_POST['description'];
$credit = $_POST['credit'];
$courseNameC = $_POST['courseNameC'];
$chapterDetails = json_decode($_POST['chapterDetails'], true);

// Perform the insert operation for course details
$query = "INSERT INTO coursedetails (courseID,description, credit) 
          VALUES ('$description', '$subjectsNum', '$credit')";

// Execute the query using the same connection
$result = mysqli_query($conn, $query);

if ($result) {
   // Course details added successfully

   

   // Perform the insert operation for chapter details
   foreach ($chapterDetails as $chapter) {
      $chapterName = $chapter['chapterName'];
      $query = "INSERT INTO chapterdetails (coursename, chapter, chapterName) 
                VALUES ('$courseNameC', '{$chapter['chapter']}', '$chapterName')";
      mysqli_query($conn, $query);
   }

   echo json_encode(['status' => 'success', 'message' => 'Course details and chapter details added successfully']);
} else {
   // Error adding course details
   echo json_encode(['status' => 'error', 'message' => 'Error adding course details']);
}
?>
