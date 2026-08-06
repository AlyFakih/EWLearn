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

// Create a new exam
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $response = array();
    
    // Validate required fields
    if (empty($_POST['course_id']) || empty($_POST['subject']) || 
        empty($_POST['exam_date']) || empty($_POST['exam_time']) || empty($_POST['room'])) {
        $response = array('success' => false, 'message' => 'All required fields must be filled');
    } else {
        // Clean and sanitize input
        $course_id = (int)$db_handle->cleanData($_POST['course_id']);
        $subject = $db_handle->cleanData($_POST['subject']);
        $exam_date = $db_handle->cleanData($_POST['exam_date']);
        $exam_time = $db_handle->cleanData($_POST['exam_time']);
        $room = $db_handle->cleanData($_POST['room']);
        $duration = isset($_POST['duration']) ? (int)$db_handle->cleanData($_POST['duration']) : 60;
        
        // Verify this course is taught by this teacher
        $verify_query = "SELECT id, courseTitle, courseCode FROM courses WHERE id = ? AND teacher_id = ?";
        $course = $db_handle->executeSelectPrepared($verify_query, "ii", [$course_id, $user_id]);
        
        if (empty($course)) {
            $response = array('success' => false, 'message' => 'Course not found or you do not have permission to add an exam for this course');
        } else {
            // Insert the exam
            $insert_query = "INSERT INTO exam (course_id, subject, date, time, room, duration) 
                            VALUES (?, ?, ?, ?, ?, ?)";
            
            $result = $db_handle->executeUpdatePrepared($insert_query, "issssi", 
                [$course_id, $subject, $exam_date, $exam_time, $room, $duration]);
            
            if ($result) {
                $exam_id = $db_handle->getLastInsertId();
                
                // Create an event in the academic calendar
                $courseTitle = $course[0]['courseTitle'];
                $courseCode = $course[0]['courseCode'];
                
                // Format time for display
                $formatted_time = date('g:i A', strtotime($exam_time));
                
                // Create calendar event for the exam
                $calendar_query = "INSERT INTO academic_calendar (title, description, start_date, end_date, reference_type, reference_id) 
                                  VALUES (?, ?, ?, ?, ?, ?)";
                                  
                $event_title = "Exam: " . $courseCode . " - " . $subject;
                $event_description = "Exam for " . $courseTitle . " (" . $courseCode . ") on " . $subject . " in room " . $room;
                
                // Calculate end time using duration
                $start_datetime = $exam_date . ' ' . $exam_time;
                $end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime . " +" . $duration . " minutes"));
                $end_date = date('Y-m-d', strtotime($end_datetime));
                
                $db_handle->executeUpdatePrepared($calendar_query, "sssssi", 
                    [$event_title, $event_description, $exam_date, $end_date, 'exam', $exam_id]);
                
                // Get enrolled students for this course to notify them
                $students_query = "SELECT sc.student_id, u.full_name 
                                  FROM studentcourse sc 
                                  JOIN users u ON sc.student_id = u.id 
                                  WHERE sc.course_id = ?";
                $students = $db_handle->executeSelectPrepared($students_query, "i", [$course_id]);
                
                // Notify enrolled students about the new exam
                if (!empty($students)) {
                    foreach ($students as $student) {
                        $notification_manager->createNotification(
                            $student['student_id'],
                            "New Exam Scheduled: " . $courseCode,
                            "An exam for " . $courseTitle . " has been scheduled on " . date('F j, Y', strtotime($exam_date)) . 
                            " at " . $formatted_time . " in room " . $room . ". Subject: " . $subject,
                            'exam',
                            $exam_id
                        );
                    }
                }
                
                // Format the table row HTML for the frontend
                $formatted_date = date('M d, Y', strtotime($exam_date));
                $html = '<tr data-id="' . $exam_id . '">';
                $html .= '<td data-field="course">' . $courseCode . ' - ' . $courseTitle . '</td>';
                $html .= '<td data-field="subject">' . $subject . '</td>';
                $html .= '<td data-field="date">' . $formatted_date . '</td>';
                $html .= '<td data-field="time">' . $formatted_time . '</td>';
                $html .= '<td data-field="room">' . $room . '</td>';
                $html .= '<td class="action-btns">';
                $html .= '<button class="edit-exam" data-id="' . $exam_id . '" title="Edit"><i class="fas fa-edit"></i></button>';
                $html .= '<button class="view-exam" data-id="' . $exam_id . '" title="View Details"><i class="fas fa-eye"></i></button>';
                $html .= '<button class="delete-exam" data-id="' . $exam_id . '" title="Delete"><i class="fas fa-trash"></i></button>';
                $html .= '</td>';
                $html .= '</tr>';
                
                $response = array(
                    'success' => true, 
                    'message' => 'Exam added successfully', 
                    'html' => $html,
                    'exam_id' => $exam_id,
                    'exam' => array(
                        'id' => $exam_id,
                        'course_id' => $course_id,
                        'courseCode' => $courseCode,
                        'courseTitle' => $courseTitle,
                        'subject' => $subject,
                        'date' => $exam_date,
                        'formatted_date' => $formatted_date,
                        'time' => $exam_time,
                        'formatted_time' => $formatted_time,
                        'room' => $room,
                        'duration' => $duration
                    )
                );
            } else {
                $response = array('success' => false, 'message' => 'Failed to add exam');
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Get exam details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
    $exam_id = (int)$db_handle->cleanData($_GET['id']);
    
    // Get exam details with JOIN to courses
    $exam_query = "SELECT e.*, c.courseTitle, c.courseCode 
                  FROM exam e 
                  JOIN courses c ON e.course_id = c.id 
                  WHERE e.id = ? AND c.teacher_id = ?";
    $exam = $db_handle->executeSelectPrepared($exam_query, "ii", [$exam_id, $user_id]);
    
    if (empty($exam)) {
        $response = array('success' => false, 'message' => 'Exam not found or you do not have permission to view it');
    } else {
        $exam_data = $exam[0];
        
        // Format date and time for display
        $exam_data['formatted_date'] = date('F j, Y', strtotime($exam_data['date']));
        $exam_data['formatted_time'] = date('g:i A', strtotime($exam_data['time']));
        
        // Get enrolled students for this course
        $students_query = "SELECT u.id, u.full_name, sc.enrollment_date 
                          FROM studentcourse sc 
                          JOIN users u ON sc.student_id = u.id 
                          WHERE sc.course_id = ?
                          ORDER BY u.full_name ASC";
        $students = $db_handle->executeSelectPrepared($students_query, "i", [$exam_data['course_id']]);
        
        $response = array(
            'success' => true,
            'exam' => $exam_data,
            'students' => $students
        );
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Update an existing exam
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update' && isset($_POST['exam_id'])) {
    $response = array();
    
    // Get exam ID
    $exam_id = (int)$db_handle->cleanData($_POST['exam_id']);
    
    // Validate required fields
    if (empty($_POST['course_id']) || empty($_POST['subject']) || 
        empty($_POST['exam_date']) || empty($_POST['exam_time']) || empty($_POST['room'])) {
        $response = array('success' => false, 'message' => 'All required fields must be filled');
    } else {
        // Clean and sanitize input
        $course_id = (int)$db_handle->cleanData($_POST['course_id']);
        $subject = $db_handle->cleanData($_POST['subject']);
        $exam_date = $db_handle->cleanData($_POST['exam_date']);
        $exam_time = $db_handle->cleanData($_POST['exam_time']);
        $room = $db_handle->cleanData($_POST['room']);
        $duration = isset($_POST['duration']) ? (int)$db_handle->cleanData($_POST['duration']) : 60;
        
        // Verify this course is taught by this teacher
        $verify_query = "SELECT id, courseTitle, courseCode FROM courses WHERE id = ? AND teacher_id = ?";
        $course = $db_handle->executeSelectPrepared($verify_query, "ii", [$course_id, $user_id]);
        
        if (empty($course)) {
            $response = array('success' => false, 'message' => 'Course not found or you do not have permission to update an exam for this course');
        } else {
            // Verify the exam exists and is associated with a course taught by this teacher
            $exam_check_query = "SELECT e.id FROM exam e JOIN courses c ON e.course_id = c.id WHERE e.id = ? AND c.teacher_id = ?";
            $exam_check = $db_handle->executeSelectPrepared($exam_check_query, "ii", [$exam_id, $user_id]);
            
            if (empty($exam_check)) {
                $response = array('success' => false, 'message' => 'Exam not found or you do not have permission to update it');
            } else {
                // Update the exam
                $update_query = "UPDATE exam SET 
                                course_id = ?, 
                                subject = ?, 
                                date = ?, 
                                time = ?, 
                                room = ?, 
                                duration = ? 
                                WHERE id = ?";
                
                $result = $db_handle->executeUpdatePrepared($update_query, "issssii", 
                    [$course_id, $subject, $exam_date, $exam_time, $room, $duration, $exam_id]);
                
                if ($result) {
                    $courseTitle = $course[0]['courseTitle'];
                    $courseCode = $course[0]['courseCode'];
                    
                    // Format time for display
                    $formatted_time = date('g:i A', strtotime($exam_time));
                    
                    // Update calendar event for the exam
                    // First, delete existing event
                    $delete_event_query = "DELETE FROM academic_calendar WHERE reference_type = 'exam' AND reference_id = ?";
                    $db_handle->executeUpdatePrepared($delete_event_query, "i", [$exam_id]);
                    
                    // Create new calendar event
                    $calendar_query = "INSERT INTO academic_calendar (title, description, start_date, end_date, reference_type, reference_id) 
                                      VALUES (?, ?, ?, ?, ?, ?)";
                                      
                    $event_title = "Exam: " . $courseCode . " - " . $subject;
                    $event_description = "Exam for " . $courseTitle . " (" . $courseCode . ") on " . $subject . " in room " . $room;
                    
                    // Calculate end time using duration
                    $start_datetime = $exam_date . ' ' . $exam_time;
                    $end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime . " +" . $duration . " minutes"));
                    $end_date = date('Y-m-d', strtotime($end_datetime));
                    
                    $db_handle->executeUpdatePrepared($calendar_query, "sssssi", 
                        [$event_title, $event_description, $exam_date, $end_date, 'exam', $exam_id]);
                    
                    // Get enrolled students for this course to notify them
                    $students_query = "SELECT sc.student_id, u.full_name 
                                      FROM studentcourse sc 
                                      JOIN users u ON sc.student_id = u.id 
                                      WHERE sc.course_id = ?";
                    $students = $db_handle->executeSelectPrepared($students_query, "i", [$course_id]);
                    
                    // Notify enrolled students about the updated exam
                    if (!empty($students)) {
                        foreach ($students as $student) {
                            $notification_manager->createNotification(
                                $student['student_id'],
                                "Exam Updated: " . $courseCode,
                                "The exam for " . $courseTitle . " scheduled for " . date('F j, Y', strtotime($exam_date)) . 
                                " at " . $formatted_time . " has been updated. Room: " . $room . ". Subject: " . $subject,
                                'exam',
                                $exam_id
                            );
                        }
                    }
                    
                    $formatted_date = date('M d, Y', strtotime($exam_date));
                    $response = array(
                        'success' => true, 
                        'message' => 'Exam updated successfully',
                        'exam' => array(
                            'id' => $exam_id,
                            'course_id' => $course_id,
                            'courseCode' => $courseCode,
                            'courseTitle' => $courseTitle,
                            'subject' => $subject,
                            'date' => $exam_date,
                            'formatted_date' => $formatted_date,
                            'time' => $exam_time,
                            'formatted_time' => $formatted_time,
                            'room' => $room,
                            'duration' => $duration
                        )
                    );
                } else {
                    $response = array('success' => false, 'message' => 'Failed to update exam');
                }
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Delete an exam
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $response = array();
    
    // Get exam ID
    $exam_id = (int)$db_handle->cleanData($_POST['id']);
    
    // Verify the exam exists and is associated with a course taught by this teacher
    $exam_check_query = "SELECT e.id, e.subject, e.course_id, c.courseTitle, c.courseCode 
                        FROM exam e 
                        JOIN courses c ON e.course_id = c.id 
                        WHERE e.id = ? AND c.teacher_id = ?";
    $exam_check = $db_handle->executeSelectPrepared($exam_check_query, "ii", [$exam_id, $user_id]);
    
    if (empty($exam_check)) {
        $response = array('success' => false, 'message' => 'Exam not found or you do not have permission to delete it');
    } else {
        $exam_data = $exam_check[0];
        
        // Start transaction
        $db_handle->beginTransaction();
        
        try {
            // Delete calendar events for this exam
            $delete_event_query = "DELETE FROM academic_calendar WHERE reference_type = 'exam' AND reference_id = ?";
            $db_handle->executeUpdatePrepared($delete_event_query, "i", [$exam_id]);
            
            // Delete the exam
            $delete_query = "DELETE FROM exam WHERE id = ?";
            $result = $db_handle->executeUpdatePrepared($delete_query, "i", [$exam_id]);
            
            if ($result) {
                // Get enrolled students for this course to notify them
                $students_query = "SELECT sc.student_id, u.full_name 
                                  FROM studentcourse sc 
                                  JOIN users u ON sc.student_id = u.id 
                                  WHERE sc.course_id = ?";
                $students = $db_handle->executeSelectPrepared($students_query, "i", [$exam_data['course_id']]);
                
                // Notify enrolled students about the deleted exam
                if (!empty($students)) {
                    foreach ($students as $student) {
                        $notification_manager->createNotification(
                            $student['student_id'],
                            "Exam Canceled: " . $exam_data['courseCode'],
                            "The exam for " . $exam_data['courseTitle'] . " on " . $exam_data['subject'] . " has been canceled.",
                            'exam_canceled',
                            $exam_data['course_id']
                        );
                    }
                }
                
                $db_handle->commitTransaction();
                $response = array('success' => true, 'message' => 'Exam deleted successfully');
            } else {
                throw new Exception("Failed to delete exam");
            }
        } catch (Exception $e) {
            $db_handle->rollbackTransaction();
            $response = array('success' => false, 'message' => 'Failed to delete exam: ' . $e->getMessage());
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Invalid request
header('HTTP/1.1 405 Method Not Allowed');
echo json_encode(array('success' => false, 'message' => 'Method not allowed'));
exit();
?>
