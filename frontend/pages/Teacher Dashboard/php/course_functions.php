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

// Create a new course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $response = array();
    
    // Validate required fields
    if (empty($_POST['courseTitle']) || empty($_POST['courseCode']) || 
        empty($_POST['startDate']) || empty($_POST['endDate']) || empty($_POST['credits'])) {
        $response = array('success' => false, 'message' => 'All required fields must be filled');
    } else {
        // Clean and sanitize input
        $courseTitle = $db_handle->cleanData($_POST['courseTitle']);
        $courseCode = $db_handle->cleanData($_POST['courseCode']);
        $courseDescription = isset($_POST['courseDescription']) ? $db_handle->cleanData($_POST['courseDescription']) : '';
        $credits = (int)$db_handle->cleanData($_POST['credits']);
        $semester = isset($_POST['semester']) ? $db_handle->cleanData($_POST['semester']) : 'Fall';
        $startDate = $db_handle->cleanData($_POST['startDate']);
        $endDate = $db_handle->cleanData($_POST['endDate']);
        
        // Validate dates
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        if ($start > $end) {
            $response = array('success' => false, 'message' => 'Start date must be before end date');
        } else {
            // Check if course code already exists
            $check_query = "SELECT id FROM courses WHERE courseCode = ?";
            $existing = $db_handle->executeSelectPrepared($check_query, "s", [$courseCode]);
            
            if (!empty($existing)) {
                $response = array('success' => false, 'message' => 'A course with this code already exists');
            } else {
                // Current date for lastUpdated
                $lastUpdated = date('Y-m-d H:i:s');
                
                // Insert the course
                $insert_query = "INSERT INTO courses (courseTitle, courseCode, courseDescription, 
                                credits, semester, startDate, endDate, teacher_id, lastUpdated) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $result = $db_handle->executeUpdatePrepared($insert_query, "sssisssss", 
                    [$courseTitle, $courseCode, $courseDescription, $credits, $semester, 
                     $startDate, $endDate, $user_id, $lastUpdated]);
                
                if ($result) {
                    // Get the new course ID
                    $course_id = $db_handle->getLastInsertId();
                    
                    // Create academic calendar events for the course start and end
                    $calendar_manager = new CalendarManager();
                    
                    // Add course start event
                    $calendar_manager->createEvent(
                        "$courseTitle Start", 
                        "First day of $courseTitle ($courseCode)", 
                        $startDate, 
                        $startDate,
                        'course',
                        $course_id
                    );
                    
                    // Add course end event
                    $calendar_manager->createEvent(
                        "$courseTitle End", 
                        "Last day of $courseTitle ($courseCode)", 
                        $endDate, 
                        $endDate,
                        'course',
                        $course_id
                    );
                    
                    // Get teacher information
                    $teacher_query = "SELECT full_name FROM users WHERE id = ?";
                    $teacher = $db_handle->executeSelectPrepared($teacher_query, "i", [$user_id]);
                    $teacher_name = !empty($teacher) ? $teacher[0]['full_name'] : 'Teacher';
                    
                    // Prepare the course card HTML to be inserted in the UI
                    $html = '<div class="course-card" data-id="' . $course_id . '">';
                    $html .= '<div class="course-header">';
                    $html .= '<h3>' . $courseTitle . '</h3>';
                    $html .= '<span class="course-code">' . $courseCode . '</span>';
                    $html .= '</div>';
                    $html .= '<div class="course-body">';
                    $html .= '<p class="course-description">' . $courseDescription . '</p>';
                    $html .= '<div class="course-stats">';
                    $html .= '<div class="stat">';
                    $html .= '<i class="fas fa-user-graduate"></i>';
                    $html .= '<span>0 Students</span>';
                    $html .= '</div>';
                    $html .= '<div class="stat">';
                    $html .= '<i class="fas fa-calendar"></i>';
                    $html .= '<span>' . date('Y-m-d', strtotime($startDate)) . ' to ' . date('Y-m-d', strtotime($endDate)) . '</span>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '<div class="course-footer">';
                    $html .= '<button class="view-course" data-id="' . $course_id . '">View Details</button>';
                    $html .= '<button class="edit-course" data-id="' . $course_id . '"><i class="fas fa-edit"></i></button>';
                    $html .= '<button class="delete-course" data-id="' . $course_id . '"><i class="fas fa-trash"></i></button>';
                    $html .= '</div>';
                    $html .= '</div>';
                    
                    // Create system notification for course creation
                    $notification_manager->createSystemNotification(
                        "New Course Created: $courseTitle",
                        "A new course '$courseTitle' ($courseCode) has been created by $teacher_name, starting on " . 
                        date('Y-m-d', strtotime($startDate)) . ".",
                        'course',
                        $course_id
                    );
                    
                    $response = array(
                        'success' => true, 
                        'message' => 'Course added successfully', 
                        'html' => $html,
                        'course_id' => $course_id
                    );
                } else {
                    $response = array('success' => false, 'message' => 'Failed to add course');
                }
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Get course details
else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
    $course_id = (int)$db_handle->cleanData($_GET['id']);
    
    // Verify this course is taught by this teacher
    $verify_query = "SELECT * FROM courses WHERE id = ? AND teacher_id = ?";
    $course = $db_handle->executeSelectPrepared($verify_query, "ii", [$course_id, $user_id]);
    
    if (empty($course)) {
        $response = array('success' => false, 'message' => 'Course not found or you do not have permission to view it');
    } else {
        $course_data = $course[0];
        
        // Get enrolled students
        $students_query = "SELECT u.id, u.full_name, sc.enrollment_date 
                          FROM studentcourse sc 
                          JOIN users u ON sc.student_id = u.id 
                          WHERE sc.course_id = ?";
        $students = $db_handle->executeSelectPrepared($students_query, "i", [$course_id]);
        
        // Get course assignments
        $assignments_query = "SELECT id, title, description, due_date 
                            FROM assignment 
                            WHERE course_id = ? 
                            ORDER BY due_date ASC";
        $assignments = $db_handle->executeSelectPrepared($assignments_query, "i", [$course_id]);
        
        $response = array(
            'success' => true,
            'course' => $course_data,
            'students' => $students,
            'assignments' => $assignments
        );
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Update an existing course
else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update' && isset($_POST['id'])) {
    $response = array();
    
    // Get course ID
    $course_id = (int)$db_handle->cleanData($_POST['id']);
    
    // Verify this course is taught by this teacher
    $verify_query = "SELECT id FROM courses WHERE id = ? AND teacher_id = ?";
    $result = $db_handle->executeSelectPrepared($verify_query, "ii", [$course_id, $user_id]);
    
    if (empty($result)) {
        $response = array('success' => false, 'message' => 'Course not found or you do not have permission to update it');
    } else {
        // Validate required fields
        if (empty($_POST['courseTitle']) || empty($_POST['courseCode']) || 
            empty($_POST['startDate']) || empty($_POST['endDate']) || empty($_POST['credits'])) {
            $response = array('success' => false, 'message' => 'All required fields must be filled');
        } else {
            // Clean and sanitize input
            $courseTitle = $db_handle->cleanData($_POST['courseTitle']);
            $courseCode = $db_handle->cleanData($_POST['courseCode']);
            $courseDescription = isset($_POST['courseDescription']) ? $db_handle->cleanData($_POST['courseDescription']) : '';
            $credits = (int)$db_handle->cleanData($_POST['credits']);
            $semester = isset($_POST['semester']) ? $db_handle->cleanData($_POST['semester']) : 'Fall';
            $startDate = $db_handle->cleanData($_POST['startDate']);
            $endDate = $db_handle->cleanData($_POST['endDate']);
            
            // Validate dates
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);
            if ($start > $end) {
                $response = array('success' => false, 'message' => 'Start date must be before end date');
            } else {
                // Check if course code already exists (excluding this course)
                $check_query = "SELECT id FROM courses WHERE courseCode = ? AND id != ?";
                $existing = $db_handle->executeSelectPrepared($check_query, "si", [$courseCode, $course_id]);
                
                if (!empty($existing)) {
                    $response = array('success' => false, 'message' => 'Another course with this code already exists');
                } else {
                    // Current date for lastUpdated
                    $lastUpdated = date('Y-m-d H:i:s');
                    
                    // Update the course
                    $update_query = "UPDATE courses SET 
                                    courseTitle = ?, 
                                    courseCode = ?, 
                                    courseDescription = ?, 
                                    credits = ?, 
                                    semester = ?, 
                                    startDate = ?, 
                                    endDate = ?, 
                                    lastUpdated = ? 
                                    WHERE id = ?";
                    
                    $result = $db_handle->executeUpdatePrepared($update_query, "sssississi", 
                        [$courseTitle, $courseCode, $courseDescription, $credits, $semester, 
                         $startDate, $endDate, $lastUpdated, $course_id]);
                    
                    if ($result) {
                        // Update academic calendar events for the course
                        $calendar_manager = new CalendarManager();
                        
                        // Delete existing events for this course
                        $calendar_manager->deleteEventsByReference('course', $course_id);
                        
                        // Add course start event
                        $calendar_manager->createEvent(
                            "$courseTitle Start", 
                            "First day of $courseTitle ($courseCode)", 
                            $startDate, 
                            $startDate,
                            'course',
                            $course_id
                        );
                        
                        // Add course end event
                        $calendar_manager->createEvent(
                            "$courseTitle End", 
                            "Last day of $courseTitle ($courseCode)", 
                            $endDate, 
                            $endDate,
                            'course',
                            $course_id
                        );
                        
                        // Get enrolled students
                        $students_query = "SELECT student_id FROM studentcourse WHERE course_id = ?";
                        $students = $db_handle->executeSelectPrepared($students_query, "i", [$course_id]);
                        
                        // Notify enrolled students of course updates
                        if (!empty($students)) {
                            foreach ($students as $student) {
                                $notification_manager->createNotification(
                                    $student['student_id'],
                                    "Course Updated: $courseTitle",
                                    "Your course $courseTitle ($courseCode) has been updated. Please check the course details for changes.",
                                    'course',
                                    $course_id
                                );
                            }
                        }
                        
                        $response = array(
                            'success' => true, 
                            'message' => 'Course updated successfully',
                            'course' => array(
                                'id' => $course_id,
                                'courseTitle' => $courseTitle,
                                'courseCode' => $courseCode,
                                'courseDescription' => $courseDescription,
                                'credits' => $credits,
                                'semester' => $semester,
                                'startDate' => $startDate,
                                'endDate' => $endDate,
                                'lastUpdated' => $lastUpdated
                            )
                        );
                    } else {
                        $response = array('success' => false, 'message' => 'Failed to update course');
                    }
                }
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Delete a course
else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $response = array();
    
    // Get course ID
    $course_id = (int)$db_handle->cleanData($_POST['id']);
    
    // Verify this course is taught by this teacher
    $verify_query = "SELECT courseTitle, courseCode FROM courses WHERE id = ? AND teacher_id = ?";
    $course = $db_handle->executeSelectPrepared($verify_query, "ii", [$course_id, $user_id]);
    
    if (empty($course)) {
        $response = array('success' => false, 'message' => 'Course not found or you do not have permission to delete it');
    } else {
        $courseTitle = $course[0]['courseTitle'];
        $courseCode = $course[0]['courseCode'];
        
        // Start transaction
        $db_handle->beginTransaction();
        
        try {
            // Delete course assignments
            $delete_assignments_query = "DELETE FROM assignment WHERE course_id = ?";
            $db_handle->executeUpdatePrepared($delete_assignments_query, "i", [$course_id]);
            
            // Get student IDs enrolled in this course for notifications
            $students_query = "SELECT student_id FROM studentcourse WHERE course_id = ?";
            $students = $db_handle->executeSelectPrepared($students_query, "i", [$course_id]);
            
            // Delete course enrollments
            $delete_enrollments_query = "DELETE FROM studentcourse WHERE course_id = ?";
            $db_handle->executeUpdatePrepared($delete_enrollments_query, "i", [$course_id]);
            
            // Delete grades for this course
            $delete_grades_query = "DELETE FROM course_grades WHERE course_id = ?";
            $db_handle->executeUpdatePrepared($delete_grades_query, "i", [$course_id]);
            
            // Delete related attendance records
            $delete_attendance_query = "DELETE FROM attendance WHERE course_id = ?";
            $db_handle->executeUpdatePrepared($delete_attendance_query, "i", [$course_id]);
            
            // Delete calendar events related to this course
            $calendar_manager = new CalendarManager();
            $calendar_manager->deleteEventsByReference('course', $course_id);
            
            // Delete the course itself
            $delete_course_query = "DELETE FROM courses WHERE id = ?";
            $result = $db_handle->executeUpdatePrepared($delete_course_query, "i", [$course_id]);
            
            if ($result) {
                // Notify enrolled students about course deletion
                if (!empty($students)) {
                    foreach ($students as $student) {
                        $notification_manager->createNotification(
                            $student['student_id'],
                            "Course Deleted: $courseTitle",
                            "The course $courseTitle ($courseCode) has been removed from your enrolled courses.",
                            'course_deleted',
                            $course_id
                        );
                    }
                }
                
                $db_handle->commitTransaction();
                $response = array('success' => true, 'message' => 'Course deleted successfully');
            } else {
                throw new Exception("Failed to delete course");
            }
        } catch (Exception $e) {
            $db_handle->rollbackTransaction();
            $response = array('success' => false, 'message' => 'Failed to delete course: ' . $e->getMessage());
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

// Calendar Manager Class
class CalendarManager {
    private $db_handle;
    
    public function __construct() {
        $this->db_handle = new DBController();
    }
    
    public function createEvent($title, $description, $start_date, $end_date, $reference_type, $reference_id) {
        $insert_query = "INSERT INTO academic_calendar (title, description, start_date, end_date, reference_type, reference_id) 
                        VALUES (?, ?, ?, ?, ?, ?)";
        
        return $this->db_handle->executeUpdatePrepared(
            $insert_query, 
            "sssssi", 
            [$title, $description, $start_date, $end_date, $reference_type, $reference_id]
        );
    }
    
    public function deleteEventsByReference($reference_type, $reference_id) {
        $delete_query = "DELETE FROM academic_calendar WHERE reference_type = ? AND reference_id = ?";
        return $this->db_handle->executeUpdatePrepared($delete_query, "si", [$reference_type, $reference_id]);
    }
}
?>
