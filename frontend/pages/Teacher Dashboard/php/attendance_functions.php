<?php
// Server-side authorization gate: instructor only. Runs before any other
// logic so nothing is emitted to an unauthenticated or wrong-role caller.
require_once __DIR__ . '/../../../core/auth_guard.php';
auth_require_role('instructor');

require_once "../../../core/DBController.php";
require_once "../../common/notifications.php";

// Check if user is logged in and is a teacher (role = 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    $response = array('success' => false, 'message' => 'Unauthorized access');
    echo json_encode($response);
    exit();
}

$db_handle = new DBController();
$notification_manager = new NotificationManager($db_handle);
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
        
        // Verify the course is taught by this teacher, and that the student
        // is enrolled in it (studentcourse is keyed by users.fullName /
        // courses.courseTitle, not by numeric IDs)
        if (!$db_handle->isCourseOwnedByTeacher($course_id, $user_id)) {
            $result = [];
        } else {
            $verify_query = "SELECT sc.id
                            FROM studentcourse sc
                            JOIN courses c ON c.courseTitle = sc.courseID
                            JOIN users u ON u.fullName = sc.userStudentID
                            WHERE u.id = ? AND c.id = ?";
            $result = $db_handle->executeSelectPrepared($verify_query, "ii", [$student_id, $course_id]);
        }

        if (empty($result)) {
            $response = array('success' => false, 'message' => 'Student is not enrolled in this course or you do not teach this course');
        } else {
            // Check if an attendance record already exists for this student on this date for this course
            $check_query = "SELECT id FROM attendance
                          WHERE studentID = ? AND courseID = ? AND date = ?";
            $existing = $db_handle->executeSelectPrepared($check_query, "iis", [$student_id, $course_id, $date]);

            if (!empty($existing)) {
                $response = array('success' => false, 'message' => 'An attendance record already exists for this student, course and date');
            } else {
                // Get student and course information (attendance stores a
                // denormalized name/image snapshot; the schema has no
                // year/major fields anywhere, so those are left blank)
                $info_query = "SELECT u.fullName, u.image, c.courseTitle
                             FROM users u, courses c
                             WHERE u.id = ? AND c.id = ?";
                $info = $db_handle->executeSelectPrepared($info_query, "ii", [$student_id, $course_id]);

                if (empty($info)) {
                    $response = array('success' => false, 'message' => 'Failed to retrieve student and course details');
                } else {
                    $student_name = $info[0]['fullName'];
                    $student_image = $info[0]['image'];
                    $course_name = $info[0]['courseTitle'];

                    // Insert the attendance record
                    $insert_query = "INSERT INTO attendance (studentID, studentName, studentURLImage, year, courseID, major, date, status, notes)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

                    $result = $db_handle->executeUpdatePrepared($insert_query, "isssissss",
                        [$student_id, $student_name, $student_image, '', $course_id, '', $date, $status, $notes]);

                    if ($result) {
                        // Get the new attendance record ID
                        $attendance_id = $db_handle->getLastInsertId();
                        
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
                        
                        // Prepare the HTML for the new row to be inserted in the table.
                        // Course is implicit (the attendance page groups rows by
                        // course into per-course tabs), so it isn't its own column
                        // here - matching the initial server-rendered table layout.
                        $html = '<tr>';
                        $html .= '<td data-id="student_id">' . $student_id . '</td>';
                        $html .= '<td data-id="student_name">' . $student_name . '</td>';
                        $html .= '<td data-id="date">' . date('Y-m-d', strtotime($date)) . '</td>';
                        $html .= '<td data-id="status"><span class="badge badge-' . ($status === 'Present' ? 'success' : ($status === 'Absent' ? 'danger' : 'warning')) . '">' . $status . '</span></td>';
                        $html .= '<td data-id="notes">' . $notes . '</td>';
                        $html .= '<td>';
                        $html .= '<button class="btn btn-icon del" data-id="' . $attendance_id . '" title="Delete"><i class="fas fa-trash" aria-hidden="true"></i></button>';
                        $html .= '</td>';
                        $html .= '</tr>';

                        $response = array('success' => true, 'message' => 'Attendance record added successfully', 'html' => $html, 'course_id' => $course_id);
                    } else {
                        $response = array('success' => false, 'message' => 'Failed to add attendance record');
                    }
                }
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Update an existing attendance record. Must exclude action=delete, which
// also submits `id` via POST - this branch was previously catching delete
// requests before they could reach the delete branch below.
else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && (!isset($_POST['action']) || $_POST['action'] !== 'delete')) {
    $response = array();
    
    // Get the attendance ID
    $attendance_id = $db_handle->cleanData($_POST['id']);
    
    // Verify this attendance record belongs to a course taught by this teacher
    $verify_query = "SELECT a.id, a.studentID, a.courseID
                    FROM attendance a
                    JOIN courses c ON a.courseID = c.id
                    WHERE a.id = ?";

    $result = $db_handle->executeSelectPrepared($verify_query, "i", [$attendance_id]);

    if (!empty($result) && !$db_handle->isCourseOwnedByTeacher($result[0]['courseID'], $user_id)) {
        $result = [];
    }

    if (empty($result)) {
        $response = array('success' => false, 'message' => 'You do not have permission to update this attendance record');
    } else {
        $attendance = $result[0];
        $student_id = $attendance['studentID'];
        $course_id = $attendance['courseID'];
        
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
        $verify_query = "SELECT a.id, a.courseID
                        FROM attendance a
                        JOIN courses c ON a.courseID = c.id
                        WHERE a.id = ?";

        $result = $db_handle->executeSelectPrepared($verify_query, "i", [$attendance_id]);

        if (!empty($result) && !$db_handle->isCourseOwnedByTeacher($result[0]['courseID'], $user_id)) {
            $result = [];
        }

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
