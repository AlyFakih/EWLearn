<?php
session_start();
require_once "dbcontroller.php";
require_once "../../common/notifications.php";

// Check if user is logged in and is a teacher (role = 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    $response = array('success' => false, 'message' => 'Unauthorized access');
    echo json_encode($response);
    exit();
}

$db_handle = new DBController();
$notification_manager = new NotificationManager();
$user_id = $_SESSION['user_id'];

// Create a new attendance record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $response = array();
    
    // Validate required fields
    if (empty($_POST['student_id']) || empty($_POST['course_id']) || empty($_POST['date']) || empty($_POST['status'])) {
        $response = array('success' => false, 'message' => 'All required fields must be filled');
    } else {
        // Clean and sanitize input
        $student_id = $db_handle->cleanData($_POST['student_id']);
        $course_id = $db_handle->cleanData($_POST['course_id']);
        $date = $db_handle->cleanData($_POST['date']);
        $status = $db_handle->cleanData($_POST['status']);
        $notes = isset($_POST['notes']) ? $db_handle->cleanData($_POST['notes']) : '';
        
        // Verify the student is enrolled in this course and the course is taught by this teacher
        $verify_query = "SELECT sc.id 
                        FROM studentcourse sc
                        JOIN courses c ON sc.course_id = c.id 
                        WHERE sc.student_id = ? 
                        AND sc.course_id = ? 
                        AND c.teacher_id = ?";
        
        $result = $db_handle->executeSelectPrepared($verify_query, "iii", [$student_id, $course_id, $user_id]);
        
        if (empty($result)) {
            $response = array('success' => false, 'message' => 'Student is not enrolled in this course or you do not teach this course');
        } else {
            // Check if an attendance record already exists for this student on this date for this course
            $check_query = "SELECT id FROM attendance 
                          WHERE student_id = ? AND course_id = ? AND date = ?";
            $existing = $db_handle->executeSelectPrepared($check_query, "iis", [$student_id, $course_id, $date]);
            
            if (!empty($existing)) {
                $response = array('success' => false, 'message' => 'An attendance record already exists for this student, course and date');
            } else {
                // Insert the attendance record
                $insert_query = "INSERT INTO attendance (student_id, course_id, date, status, notes) 
                              VALUES (?, ?, ?, ?, ?)";
                
                $result = $db_handle->executeUpdatePrepared($insert_query, "iisss", 
                    [$student_id, $course_id, $date, $status, $notes]);
                
                if ($result) {
                    // Get the new attendance record ID
                    $attendance_id = $db_handle->getLastInsertId();
                    
                    // Get student and course information
                    $info_query = "SELECT u.full_name, c.courseTitle 
                                 FROM users u, courses c 
                                 WHERE u.id = ? AND c.id = ?";
                    $info = $db_handle->executeSelectPrepared($info_query, "ii", [$student_id, $course_id]);
                    
                    if (!empty($info)) {
                        $student_name = $info[0]['full_name'];
                        $course_name = $info[0]['courseTitle'];
                        
                        // Create a notification for the student if they were marked absent or late
                        if ($status === 'Absent' || $status === 'Late') {
                            $notification_manager->createNotification(
                                $student_id,
                                "Attendance Update: $course_name",
                                "You were marked as $status for $course_name on " . date('Y-m-d', strtotime($date)) . 
                                ($notes ? ". Notes: $notes" : ""),
                                "attendance",
                                $attendance_id
                            );
                        }
                        
                        // Prepare the HTML for the new row to be inserted in the table
                        $html = '<tr>';
                        $html .= '<td data-id="id">' . $attendance_id . '</td>';
                        $html .= '<td data-id="student_name">' . $student_name . '</td>';
                        $html .= '<td data-id="course_name">' . $course_name . '</td>';
                        $html .= '<td data-id="date">' . date('Y-m-d', strtotime($date)) . '</td>';
                        $html .= '<td data-id="status"><span class="status-badge status-' . strtolower($status) . '">' . $status . '</span></td>';
                        $html .= '<td data-id="notes">' . $notes . '</td>';
                        $html .= '<td>';
                        $html .= '<button class="edit"><i class="fas fa-pencil" aria-hidden="true"></i></button>';
                        $html .= '<button class="save" style="display:none;" data-id="' . $attendance_id . '"><i class="fas fa-check" aria-hidden="true"></i></button>';
                        $html .= '<button class="cancel" style="display:none;"><i class="fas fa-times" aria-hidden="true"></i></button>';
                        $html .= '<button class="del" data-id="' . $attendance_id . '"><i class="fas fa-trash" aria-hidden="true"></i></button>';
                        $html .= '</td>';
                        $html .= '</tr>';
                        
                        $response = array('success' => true, 'message' => 'Attendance record added successfully', 'html' => $html);
                    } else {
                        $response = array('success' => false, 'message' => 'Failed to retrieve student and course details');
                    }
                } else {
                    $response = array('success' => false, 'message' => 'Failed to add attendance record');
                }
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Update an existing attendance record
else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $response = array();
    
    // Get the attendance ID
    $attendance_id = $db_handle->cleanData($_POST['id']);
    
    // Verify this attendance record belongs to a course taught by this teacher
    $verify_query = "SELECT a.id, a.student_id, a.course_id 
                    FROM attendance a
                    JOIN courses c ON a.course_id = c.id 
                    WHERE a.id = ? AND c.teacher_id = ?";
    
    $result = $db_handle->executeSelectPrepared($verify_query, "ii", [$attendance_id, $user_id]);
    
    if (empty($result)) {
        $response = array('success' => false, 'message' => 'You do not have permission to update this attendance record');
    } else {
        $attendance = $result[0];
        $student_id = $attendance['student_id'];
        $course_id = $attendance['course_id'];
        
        // Build the update query based on which fields were provided
        $fields = array();
        $types = "";
        $params = array();
        
        if (isset($_POST['student_name'])) {
            // We can't update the student name directly - we would need to update the student_id
            // For simplicity, we'll skip this as it's not a common use case
            $response = array('success' => false, 'message' => 'Cannot update student name directly');
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }
        
        if (isset($_POST['course_name'])) {
            // We can't update the course name directly - we would need to update the course_id
            // For simplicity, we'll skip this as it's not a common use case
            $response = array('success' => false, 'message' => 'Cannot update course name directly');
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }
        
        if (isset($_POST['date'])) {
            $date = $db_handle->cleanData($_POST['date']);
            $fields[] = "date = ?";
            $types .= "s";
            $params[] = $date;
        }
        
        if (isset($_POST['status'])) {
            $status = $db_handle->cleanData($_POST['status']);
            $fields[] = "status = ?";
            $types .= "s";
            $params[] = $status;
        }
        
        if (isset($_POST['notes'])) {
            $notes = $db_handle->cleanData($_POST['notes']);
            $fields[] = "notes = ?";
            $types .= "s";
            $params[] = $notes;
        }
        
        if (empty($fields)) {
            $response = array('success' => false, 'message' => 'No fields to update');
        } else {
            // Build the update query
            $update_query = "UPDATE attendance SET " . implode(", ", $fields) . " WHERE id = ?";
            
            // Add the attendance ID as the last parameter
            $types .= "i";
            $params[] = $attendance_id;
            
            // Execute the update
            $result = $db_handle->executeUpdatePrepared($update_query, $types, $params);
            
            if ($result) {
                // Create a notification for the student if they were marked absent or late
                if (isset($_POST['status']) && ($_POST['status'] === 'Absent' || $_POST['status'] === 'Late')) {
                    // Get course information
                    $info_query = "SELECT c.courseTitle 
                                 FROM courses c 
                                 WHERE c.id = ?";
                    $info = $db_handle->executeSelectPrepared($info_query, "i", [$course_id]);
                    
                    if (!empty($info)) {
                        $course_name = $info[0]['courseTitle'];
                        
                        $notification_manager->createNotification(
                            $student_id,
                            "Attendance Update: $course_name",
                            "Your attendance status has been updated to " . $_POST['status'] . " for $course_name" . 
                            (isset($_POST['date']) ? " on " . date('Y-m-d', strtotime($_POST['date'])) : "") . 
                            (isset($_POST['notes']) && $_POST['notes'] ? ". Notes: " . $_POST['notes'] : ""),
                            "attendance",
                            $attendance_id
                        );
                    }
                }
                
                $response = array('success' => true, 'message' => 'Attendance record updated successfully');
            } else {
                $response = array('success' => false, 'message' => 'Failed to update attendance record');
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Delete an attendance record
else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $response = array();
    
    // Validate required fields
    if (empty($_POST['id'])) {
        $response = array('success' => false, 'message' => 'Missing attendance ID');
    } else {
        // Clean and sanitize input
        $attendance_id = $db_handle->cleanData($_POST['id']);
        
        // Verify this attendance record belongs to a course taught by this teacher
        $verify_query = "SELECT a.id 
                        FROM attendance a
                        JOIN courses c ON a.course_id = c.id 
                        WHERE a.id = ? AND c.teacher_id = ?";
        
        $result = $db_handle->executeSelectPrepared($verify_query, "ii", [$attendance_id, $user_id]);
        
        if (empty($result)) {
            $response = array('success' => false, 'message' => 'You do not have permission to delete this attendance record');
        } else {
            // Delete the attendance record
            $delete_query = "DELETE FROM attendance WHERE id = ?";
            $result = $db_handle->executeUpdatePrepared($delete_query, "i", [$attendance_id]);
            
            if ($result) {
                $response = array('success' => true, 'message' => 'Attendance record deleted successfully');
            } else {
                $response = array('success' => false, 'message' => 'Failed to delete attendance record');
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Invalid request
else {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(array('success' => false, 'message' => 'Method not allowed'));
    exit();
}
?>
