<?php
session_start();
require_once "php/dbcontroller.php";
require_once "../common/file_handler.php";
require_once "../common/notifications.php";

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../../login.php");
    exit;
}

$db_handle = new DBController();
$user_id = $_SESSION['user_id'];
$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    if (empty($_POST['assignment_id'])) {
        $response = array('success' => false, 'message' => 'Assignment ID is required');
    } else {
        $assignment_id = $db_handle->cleanData($_POST['assignment_id']);
        $content = isset($_POST['content']) ? $db_handle->cleanData($_POST['content']) : '';
        
        // Get assignment details to check deadline
        $assignmentQuery = "SELECT * FROM assignment WHERE id = ?";
        $assignments = $db_handle->executeSelectPrepared($assignmentQuery, "i", [$assignment_id]);
        
        if (empty($assignments)) {
            $response = array('success' => false, 'message' => 'Assignment not found');
        } else {
            $assignment = $assignments[0];
            $deadline = strtotime($assignment['deadline']);
            $now = time();
            
            // Check if deadline has passed
            $lateSubmission = false;
            if ($deadline < $now) {
                $lateSubmission = true;
                // You can decide whether to allow late submissions or not
                // For now, we'll allow it but mark it as late
            }
            
            // Check if a file was uploaded
            if (isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
                // Initialize file handler with allowed extensions
                $fileHandler = new FileHandler(
                    ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'txt', 'jpg', 'jpeg', 'png'],
                    10485760, // 10MB max size
                    '../../../uploads/'
                );
                
                // Generate a unique filename based on student_id, assignment_id and timestamp
                $newFilename = "assignment_{$assignment_id}_student_{$user_id}_" . time();
                
                // Upload the file to the assignments directory
                $uploadResult = $fileHandler->uploadFile($_FILES['file'], 'assignments/', $newFilename);
                
                if ($uploadResult['success']) {
                    $filePath = $uploadResult['file_path'];
                    
                    // Check if student has already submitted this assignment
                    $checkQuery = "SELECT id FROM assignment_submissions WHERE student_id = ? AND assignment_id = ?";
                    $existing = $db_handle->executeSelectPrepared($checkQuery, "ii", [$user_id, $assignment_id]);
                    
                    if (!empty($existing)) {
                        // Update existing submission
                        $updateQuery = "UPDATE assignment_submissions 
                                       SET content = ?, file_path = ?, submitted_at = NOW(), status = ? 
                                       WHERE student_id = ? AND assignment_id = ?";
                        $status = $lateSubmission ? 'late' : 'submitted';
                        $result = $db_handle->executeUpdatePrepared($updateQuery, "sssii", 
                            [$content, $filePath, $status, $user_id, $assignment_id]);
                        
                        if ($result) {
                            $response = array('success' => true, 'message' => 'Assignment updated successfully');
                        } else {
                            $response = array('success' => false, 'message' => 'Failed to update assignment');
                        }
                    } else {
                        // Insert new submission
                        $insertQuery = "INSERT INTO assignment_submissions (student_id, assignment_id, content, file_path, submitted_at, status) 
                                       VALUES (?, ?, ?, ?, NOW(), ?)";
                        $status = $lateSubmission ? 'late' : 'submitted';
                        $result = $db_handle->executeUpdatePrepared($insertQuery, "iisss", 
                            [$user_id, $assignment_id, $content, $filePath, $status]);
                        
                        if ($result) {
                            $response = array('success' => true, 'message' => 'Assignment submitted successfully');
                            
                            // Create notification for the teacher
                            $notificationManager = new NotificationManager();
                            $studentQuery = "SELECT full_name FROM users WHERE id = ?";
                            $studentData = $db_handle->executeSelectPrepared($studentQuery, "i", [$user_id]);
                            $studentName = $studentData[0]['full_name'];
                            
                            $courseQuery = "SELECT c.id, c.courseTitle, t.id as teacher_id 
                                          FROM assignment a 
                                          JOIN courses c ON a.course_id = c.id 
                                          JOIN users t ON c.teacher_id = t.id 
                                          WHERE a.id = ?";
                            $courseData = $db_handle->executeSelectPrepared($courseQuery, "i", [$assignment_id]);
                            
                            if (!empty($courseData)) {
                                $courseTitle = $courseData[0]['courseTitle'];
                                $teacher_id = $courseData[0]['teacher_id'];
                                
                                // Notify teacher about submission
                                $notificationManager->createNotification(
                                    $teacher_id,
                                    "New Assignment Submission",
                                    "{$studentName} has submitted the assignment \"{$assignment['title']}\" for {$courseTitle}.",
                                    "assignment",
                                    $result // The new submission ID
                                );
                            }
                        } else {
                            $response = array('success' => false, 'message' => 'Failed to submit assignment');
                        }
                    }
                } else {
                    $response = array('success' => false, 'message' => 'File upload failed: ' . $uploadResult['message']);
                }
            } else {
                // Text-only submission without file
                // Check if student has already submitted this assignment
                $checkQuery = "SELECT id FROM assignment_submissions WHERE student_id = ? AND assignment_id = ?";
                $existing = $db_handle->executeSelectPrepared($checkQuery, "ii", [$user_id, $assignment_id]);
                
                if (!empty($existing)) {
                    // Update existing submission
                    $updateQuery = "UPDATE assignment_submissions 
                                   SET content = ?, submitted_at = NOW(), status = ? 
                                   WHERE student_id = ? AND assignment_id = ?";
                    $status = $lateSubmission ? 'late' : 'submitted';
                    $result = $db_handle->executeUpdatePrepared($updateQuery, "ssii", 
                        [$content, $status, $user_id, $assignment_id]);
                    
                    if ($result) {
                        $response = array('success' => true, 'message' => 'Assignment updated successfully');
                    } else {
                        $response = array('success' => false, 'message' => 'Failed to update assignment');
                    }
                } else {
                    // Insert new submission
                    $insertQuery = "INSERT INTO assignment_submissions (student_id, assignment_id, content, submitted_at, status) 
                                   VALUES (?, ?, ?, NOW(), ?)";
                    $status = $lateSubmission ? 'late' : 'submitted';
                    $result = $db_handle->executeUpdatePrepared($insertQuery, "iiss", 
                        [$user_id, $assignment_id, $content, $status]);
                    
                    if ($result) {
                        $response = array('success' => true, 'message' => 'Assignment submitted successfully');
                        
                        // Notification logic (same as above)
                        $notificationManager = new NotificationManager();
                        $studentQuery = "SELECT full_name FROM users WHERE id = ?";
                        $studentData = $db_handle->executeSelectPrepared($studentQuery, "i", [$user_id]);
                        $studentName = $studentData[0]['full_name'];
                        
                        $courseQuery = "SELECT c.id, c.courseTitle, t.id as teacher_id 
                                      FROM assignment a 
                                      JOIN courses c ON a.course_id = c.id 
                                      JOIN users t ON c.teacher_id = t.id 
                                      WHERE a.id = ?";
                        $courseData = $db_handle->executeSelectPrepared($courseQuery, "i", [$assignment_id]);
                        
                        if (!empty($courseData)) {
                            $courseTitle = $courseData[0]['courseTitle'];
                            $teacher_id = $courseData[0]['teacher_id'];
                            
                            // Notify teacher about submission
                            $notificationManager->createNotification(
                                $teacher_id,
                                "New Assignment Submission",
                                "{$studentName} has submitted the assignment \"{$assignment['title']}\" for {$courseTitle}.",
                                "assignment",
                                $result // The new submission ID
                            );
                        }
                    } else {
                        $response = array('success' => false, 'message' => 'Failed to submit assignment');
                    }
                }
            }
        }
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// If not a POST request, redirect to dashboard
header("Location: index.php");
exit;
?>
