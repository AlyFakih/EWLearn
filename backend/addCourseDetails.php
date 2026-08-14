<?php
// Server-side authorization gate: admin only. Placed first so no data is
// emitted before the caller is proven to be an authenticated admin.
require_once __DIR__ . '/../frontend/core/auth_guard.php';
auth_require_role('admin');

require './config.php';

// Fetch data from the POST request
$description = isset($_POST['description']) ? $_POST['description'] : '';
$credit = isset($_POST['credit']) ? $_POST['credit'] : '';
$courseNameC = isset($_POST['courseNameC']) ? $_POST['courseNameC'] : '';
$chapterDetails = isset($_POST['chapterDetails']) ? json_decode($_POST['chapterDetails'], true) : [];

if ($description === '' || $credit === '' || $courseNameC === '') {
   echo json_encode(['status' => 'error', 'message' => 'Course, description and credit are required']);
   exit;
}

// Insert the course details. Previously: courseID got $description's value,
// description got an undefined variable ($subjectsNum, never read from
// $_POST anywhere in this file) which PHP silently treated as an empty
// string, and credit was the only column that actually got the right value -
// every submission wrote a corrupted row. Also parameterised: these are
// user-controlled request values that were being interpolated directly into
// the SQL string.
$stmt = $conn->prepare("INSERT INTO coursedetails (courseID, description, credit) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $courseNameC, $description, $credit);
$result = $stmt->execute();

if ($result) {
   // Insert the chapter details, one row per chapter.
   if (is_array($chapterDetails)) {
      $chapterStmt = $conn->prepare("INSERT INTO chapterdetails (courseName, chapter, chapterName) VALUES (?, ?, ?)");
      foreach ($chapterDetails as $chapter) {
         $chapterNum = isset($chapter['chapter']) ? $chapter['chapter'] : '';
         $chapterName = isset($chapter['chapterName']) ? $chapter['chapterName'] : '';
         if ($chapterNum === '' && $chapterName === '') continue;
         $chapterStmt->bind_param("sss", $courseNameC, $chapterNum, $chapterName);
         $chapterStmt->execute();
      }
   }

   echo json_encode(['status' => 'success', 'message' => 'Course details and chapter details added successfully']);
} else {
   // Error adding course details
   echo json_encode(['status' => 'error', 'message' => 'Error adding course details']);
}
?>
