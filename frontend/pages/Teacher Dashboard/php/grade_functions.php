<?php
require_once "dbcontroller.php";
require_once "../../../common/notification_manager.php";

// Initialize database controller
$db_handle = new DBController();

// Check if user is logged in and is a teacher
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Only teachers can access this functionality.'
    ]);
    exit;
}

// Initialize notification manager
$notification_manager = new NotificationManager($db_handle);

// Handle different actions based on request type
$action = '';

// Determine the action based on request parameters
if (isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif (isset($_POST['action'])) {
    $action = $_POST['action'];
} elseif (isset($_POST['newstudentID'])) {
    $action = 'add';
} elseif (isset($_POST['student_id'])) {
    $action = 'update';
}

// Process the request based on the action
switch ($action) {
    case 'add':
        addGrade();
        break;
    case 'view':
        viewGrade();
        break;
    case 'update':
        updateGrade();
        break;
    case 'delete':
        deleteGrade();
        break;
    case 'get_all':
        getAllGrades();
        break;
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action specified'
        ]);
        break;
}

/**
 * Add a new grade to the database
 */
function addGrade() {
    global $db_handle, $notification_manager;
    
    // Extract and sanitize the form data
    $student_id = isset($_POST['newstudentID']) ? intval($_POST['newstudentID']) : 0;
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 1; // Default to course ID 1 if not specified
    $grade = isset($_POST['newGrade']) ? $_POST['newGrade'] : '';
    $term = isset($_POST['term']) ? $_POST['term'] : 'Fall 2025'; // Default term
    
    // Validation
    if ($student_id <= 0 || empty($grade)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid input data. Please check all fields.'
        ]);
        exit;
    }
    
    try {
        // Prepare the insert statement
        $conn = $db_handle->connectDB();
        $stmt = $conn->prepare("INSERT INTO course_grades (student_id, course_id, grade, term) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $student_id, $course_id, $grade, $term);
        
        // Execute the statement
        if ($stmt->execute()) {
            // Get the name of the student for the response
            $stmt_user = $conn->prepare("SELECT fullName FROM users WHERE id = ?");
            $stmt_user->bind_param("i", $student_id);
            $stmt_user->execute();
            $result = $stmt_user->get_result();
            $user = $result->fetch_assoc();
            $full_name = $user ? $user['fullName'] : 'Unknown Student';
            
            // Create notification for the student
            $notification_manager->createNotification(
                $student_id,
                "Grade Posted",
                "A new grade has been posted for course #$course_id: $grade",
                "grade"
            );
            
            // Generate HTML for response
            $html = '<tr>';
            $html .= '<td data-id="student_id">' . $student_id . '</td>';
            $html .= '<td data-id="student_name">' . $full_name . '</td>';
            $html .= '<td data-id="student_grade">' . $grade . '</td>';
            $html .= '<td>';
            $html .= '<button class="edit"><i class="fas fa-pencil" aria-hidden="true"></i></button>';
            $html .= '<button class="save" style="display:none;" data-id="' . $student_id . '"><i class="fas fa-check" aria-hidden="true"></i></button>';
            $html .= '<button class="cancel" style="display:none;"><i class="fas fa-times" aria-hidden="true"></i></button>';
            $html .= '<button class="del" data-id="' . $student_id . '"><i class="fas fa-trash" aria-hidden="true"></i></button>';
            $html .= '</td>';
            $html .= '</tr>';
            
            echo $html;
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to add grade.'
            ]);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

/**
 * View grade details
 */
function viewGrade() {
    global $db_handle;
    
    // Extract and sanitize the input data
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid student ID'
        ]);
        exit;
    }
    
    try {
        $conn = $db_handle->connectDB();
        
        // Get grade details with student and course information
        $stmt = $conn->prepare(
            "SELECT 
                g.student_id, g.course_id, g.grade, g.term, g.created_at,
                u.fullName AS student_name, u.email AS student_email,
                c.courseTitle, c.courseCode
            FROM course_grades g
            JOIN users u ON g.student_id = u.id
            JOIN courses c ON g.course_id = c.id
            WHERE g.student_id = ?"
        );
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($grade = $result->fetch_assoc()) {
            // Format dates for better readability
            $grade['created_at'] = date('F j, Y', strtotime($grade['created_at']));
            
            echo json_encode([
                'success' => true,
                'grade' => $grade
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Grade not found'
            ]);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Update an existing grade
 */
function updateGrade() {
    global $db_handle, $notification_manager;
    
    // Check if we're updating from the form submission
    if (isset($_POST['student_id'])) {
        // Extract and sanitize the form data
        $student_id = intval($_POST['student_id']);
        $grade = isset($_POST['student_grade']) ? $_POST['student_grade'] : '';
        
        // Validation
        if ($student_id <= 0 || empty($grade)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid input data. Please check all fields.'
            ]);
            exit;
        }
        
        try {
            $conn = $db_handle->connectDB();
            
            // Update the grade
            $stmt = $conn->prepare("UPDATE course_grades SET grade = ? WHERE student_id = ?");
            $stmt->bind_param("si", $grade, $student_id);
            
            if ($stmt->execute()) {
                // Create notification for the student
                $notification_manager->createNotification(
                    $student_id,
                    "Grade Updated",
                    "Your grade has been updated: $grade",
                    "grade"
                );
                
                // Get all grades to refresh the table
                getAllGrades();
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update grade.'
                ]);
            }
            
            $stmt->close();
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid form data'
        ]);
    }
}

/**
 * Delete a grade from the database
 */
function deleteGrade() {
    global $db_handle, $notification_manager;
    
    // Extract and sanitize the input data
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid grade ID'
        ]);
        exit;
    }
    
    try {
        $conn = $db_handle->connectDB();
        
        // Start a transaction
        $conn->begin_transaction();
        
        // Get student information for notification
        $stmt = $conn->prepare("SELECT course_id FROM course_grades WHERE student_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $grade = $result->fetch_assoc();
        $course_id = $grade ? $grade['course_id'] : 0;
        
        // Delete the grade
        $stmt = $conn->prepare("DELETE FROM course_grades WHERE student_id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // Create notification for the student
            if ($course_id > 0) {
                $notification_manager->createNotification(
                    $id,
                    "Grade Removed",
                    "Your grade for course #$course_id has been removed",
                    "grade"
                );
            }
            
            // Commit the transaction
            $conn->commit();
            echo "1"; // Success indicator for the frontend
        } else {
            // Rollback on error
            $conn->rollback();
            echo "0"; // Error indicator for the frontend
        }
        
        $stmt->close();
    } catch (Exception $e) {
        // Rollback on exception
        if (isset($conn)) {
            $conn->rollback();
        }
        echo "0"; // Error indicator for the frontend
    }
}

/**
 * Get all grades
 */
function getAllGrades() {
    global $db_handle;
    
    try {
        $conn = $db_handle->connectDB();
        
        // Prepare statement to get all grades
        $stmt = $conn->prepare(
            "SELECT
                cg.student_id AS StudentID,
                u.fullName AS Name,
                cg.grade AS Grade
            FROM
                course_grades cg
            JOIN users u ON cg.student_id = u.id
            WHERE u.role = 'student'"
        );
        
        $stmt->execute();
        $result = $stmt->get_result();
        $grades = [];
        
        while ($row = $result->fetch_assoc()) {
            $grades[] = $row;
        }
        
        // If this is an AJAX request for all grades
        if ($GLOBALS['action'] === 'get_all') {
            echo json_encode([
                'success' => true,
                'grades' => $grades
            ]);
            exit;
        } else {
            // Otherwise, this is for refreshing the table after an update
            $html = '';
            foreach ($grades as $row) {
                $html .= '<tr>';
                $html .= '<td data-id="student_id">' . $row['StudentID'] . '</td>';
                $html .= '<td data-id="student_name">' . $row['Name'] . '</td>';
                $html .= '<td data-id="student_grade">' . $row['Grade'] . '</td>';
                $html .= '<td>';
                $html .= '<button class="edit"><i class="fas fa-pencil" aria-hidden="true"></i></button>';
                $html .= '<button class="save" style="display:none;" data-id="' . $row['StudentID'] . '"><i class="fas fa-check" aria-hidden="true"></i></button>';
                $html .= '<button class="cancel" style="display:none;"><i class="fas fa-times" aria-hidden="true"></i></button>';
                $html .= '<button class="del" data-id="' . $row['StudentID'] . '"><i class="fas fa-trash" aria-hidden="true"></i></button>';
                $html .= '</td>';
                $html .= '</tr>';
            }
            echo $html;
            exit;
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}
?>
