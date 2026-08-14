<?php
// Server-side authorization gate: instructor only. Runs before any other
// logic so nothing is emitted to an unauthenticated or wrong-role caller.
require_once __DIR__ . '/../../../core/auth_guard.php';
auth_require_role('instructor');

require_once "../../../core/DBController.php";
require_once "../../common/notifications.php";

// Initialize database controller
$db_handle = new DBController();

// Check if user is logged in and is a teacher
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

    // Extract and sanitize the form data. course_grades has no generic
    // "grade"/"term" columns - it stores a numeric overall_grade per
    // (student_id, course_id) pair, which is UNIQUE, so this is an upsert.
    $student_id = isset($_POST['newstudentID']) ? intval($_POST['newstudentID']) : 0;
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    $grade = isset($_POST['newGrade']) ? $_POST['newGrade'] : '';

    // Validation
    if ($student_id <= 0 || $course_id <= 0 || $grade === '' || !is_numeric($grade)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid input data. Please check all fields.'
        ]);
        exit;
    }

    // A teacher may only grade students in courses they teach
    if (!$db_handle->isCourseOwnedByTeacher($course_id, $_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'You do not have permission to grade this course.'
        ]);
        exit;
    }

    try {
        // Prepare the insert statement
        $conn = $db_handle->connectDB();
        $stmt = $conn->prepare(
            "INSERT INTO course_grades (student_id, course_id, overall_grade)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE overall_grade = VALUES(overall_grade)"
        );
        $stmt->bind_param("iid", $student_id, $course_id, $grade);

        // Execute the statement
        if ($stmt->execute()) {
            // Get the name of the student for the response
            $stmt_user = $conn->prepare("SELECT fullName FROM users WHERE id = ?");
            $stmt_user->bind_param("i", $student_id);
            $stmt_user->execute();
            $result = $stmt_user->get_result();
            $user = $result->fetch_assoc();
            $full_name = $user ? $user['fullName'] : 'Unknown Student';

            $stmt_course = $conn->prepare("SELECT courseTitle FROM courses WHERE id = ?");
            $stmt_course->bind_param("i", $course_id);
            $stmt_course->execute();
            $course_row = $stmt_course->get_result()->fetch_assoc();
            $course_name = $course_row ? $course_row['courseTitle'] : '';

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
            $html .= '<td data-id="course_name">' . htmlspecialchars($course_name) . '</td>';
            $html .= '<td data-id="student_grade">' . $grade . '</td>';
            $html .= '<td>';
            $html .= '<button class="btn btn-icon view-grade" data-id="' . $student_id . '" data-course-id="' . $course_id . '" title="View"><i class="fas fa-eye" aria-hidden="true"></i></button>';
            $html .= '<button class="btn btn-icon edit" title="Edit"><i class="fas fa-pencil" aria-hidden="true"></i></button>';
            $html .= '<button class="btn btn-icon save" style="display:none;" data-id="' . $student_id . '" data-course-id="' . $course_id . '" title="Save"><i class="fas fa-check" aria-hidden="true"></i></button>';
            $html .= '<button class="btn btn-icon cancel" style="display:none;" title="Cancel"><i class="fas fa-times" aria-hidden="true"></i></button>';
            $html .= '<button class="btn btn-icon del" data-id="' . $student_id . '" data-course-id="' . $course_id . '" title="Delete"><i class="fas fa-trash" aria-hidden="true"></i></button>';
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
    
    // Extract and sanitize the input data. course_id disambiguates when a
    // student has grades in more than one of this teacher's courses (a
    // student_id-only lookup would non-deterministically pick a row, which
    // could be a different course's grade and fail the ownership check).
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

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
        $query = "SELECT
                g.student_id, g.course_id, g.overall_grade, g.midterm_grade, g.final_grade,
                g.comments, g.last_updated AS created_at,
                u.fullName AS student_name, u.email AS student_email,
                c.courseTitle, c.courseCode
            FROM course_grades g
            JOIN users u ON g.student_id = u.id
            JOIN courses c ON g.course_id = c.id
            WHERE g.student_id = ?";
        if ($course_id > 0) {
            $query .= " AND g.course_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $id, $course_id);
        } else {
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $grade = $result->fetch_assoc();

        if ($grade && !$GLOBALS['db_handle']->isCourseOwnedByTeacher($grade['course_id'], $_SESSION['user_id'])) {
            $grade = null;
        }

        if ($grade) {
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
        // Extract and sanitize the form data. course_id disambiguates when
        // a student has grades in more than one of this teacher's courses.
        $student_id = intval($_POST['student_id']);
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        $grade = isset($_POST['student_grade']) ? $_POST['student_grade'] : '';
        
        // Validation
        if ($student_id <= 0 || empty($grade)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid input data. Please check all fields.'
            ]);
            exit;
        }
        
        if (!is_numeric($grade)) {
            echo json_encode([
                'success' => false,
                'message' => 'Grade must be numeric.'
            ]);
            exit;
        }

        try {
            $conn = $db_handle->connectDB();

            // Update the grade, scoped to courses this teacher actually
            // teaches (course_grades has no notion of "current teacher",
            // so this is enforced via instructorcourse on every write)
            $teacher_id = $_SESSION['user_id'];
            $query = "UPDATE course_grades g
                 JOIN courses c ON c.id = g.course_id
                 JOIN instructorcourse ic ON ic.courseID = c.courseTitle
                 JOIN users tu ON tu.fullName = ic.userInstructorID
                 SET g.overall_grade = ?
                 WHERE g.student_id = ? AND tu.id = ?";
            if ($course_id > 0) {
                $query .= " AND g.course_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("diii", $grade, $student_id, $teacher_id, $course_id);
            } else {
                $stmt = $conn->prepare($query);
                $stmt->bind_param("dii", $grade, $student_id, $teacher_id);
            }

            if ($stmt->execute() && $stmt->affected_rows > 0) {
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
    
    // Extract and sanitize the input data. course_id disambiguates when a
    // student has grades in more than one of this teacher's courses.
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $course_id_param = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;

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
        if ($course_id_param > 0) {
            $stmt = $conn->prepare("SELECT course_id FROM course_grades WHERE student_id = ? AND course_id = ?");
            $stmt->bind_param("ii", $id, $course_id_param);
        } else {
            $stmt = $conn->prepare("SELECT course_id FROM course_grades WHERE student_id = ?");
            $stmt->bind_param("i", $id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $grade = $result->fetch_assoc();
        $course_id = $grade ? $grade['course_id'] : 0;

        // Delete the grade, scoped to courses this teacher teaches (see
        // updateGrade for why this join is needed)
        $teacher_id = $_SESSION['user_id'];
        $query = "DELETE g FROM course_grades g
             JOIN courses c ON c.id = g.course_id
             JOIN instructorcourse ic ON ic.courseID = c.courseTitle
             JOIN users tu ON tu.fullName = ic.userInstructorID
             WHERE g.student_id = ? AND tu.id = ?";
        if ($course_id_param > 0) {
            $query .= " AND g.course_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iii", $id, $teacher_id, $course_id_param);
        } else {
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $id, $teacher_id);
        }

        if ($stmt->execute() && $stmt->affected_rows > 0) {
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
        
        // Prepare statement to get all grades, restricted to courses this
        // teacher teaches (course_grades has no teacher column of its own)
        $teacher_id = $_SESSION['user_id'];
        $stmt = $conn->prepare(
            "SELECT
                cg.student_id AS StudentID,
                u.fullName AS Name,
                cg.overall_grade AS Grade,
                cg.course_id AS CourseID,
                c.courseTitle AS CourseName
            FROM course_grades cg
            JOIN users u ON cg.student_id = u.id
            JOIN courses c ON c.id = cg.course_id
            JOIN instructorcourse ic ON ic.courseID = c.courseTitle
            JOIN users tu ON tu.fullName = ic.userInstructorID
            WHERE u.role = 'student' AND tu.id = ?"
        );

        $stmt->bind_param("i", $teacher_id);
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
                $html .= '<td data-id="course_name">' . htmlspecialchars($row['CourseName']) . '</td>';
                $html .= '<td data-id="student_grade">' . $row['Grade'] . '</td>';
                $html .= '<td>';
                $html .= '<button class="btn btn-icon view-grade" data-id="' . $row['StudentID'] . '" data-course-id="' . $row['CourseID'] . '" title="View"><i class="fas fa-eye" aria-hidden="true"></i></button>';
                $html .= '<button class="btn btn-icon edit" title="Edit"><i class="fas fa-pencil" aria-hidden="true"></i></button>';
                $html .= '<button class="btn btn-icon save" style="display:none;" data-id="' . $row['StudentID'] . '" data-course-id="' . $row['CourseID'] . '" title="Save"><i class="fas fa-check" aria-hidden="true"></i></button>';
                $html .= '<button class="btn btn-icon cancel" style="display:none;" title="Cancel"><i class="fas fa-times" aria-hidden="true"></i></button>';
                $html .= '<button class="btn btn-icon del" data-id="' . $row['StudentID'] . '" data-course-id="' . $row['CourseID'] . '" title="Delete"><i class="fas fa-trash" aria-hidden="true"></i></button>';
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
