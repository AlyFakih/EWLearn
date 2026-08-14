<?php
// Server-side authorization gate: instructor only. Runs before any other
// logic so nothing is emitted to an unauthenticated or wrong-role caller.
require_once __DIR__ . '/../../../core/auth_guard.php';
auth_require_role('instructor');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
}

// Check if user is logged in and has teacher role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Include the database controller
require_once "../../../core/DBController.php";
$db_handle = new DBController();
$conn = $db_handle->connectDB();

// Include NotificationManager
require_once "../../common/notifications.php";
$notificationManager = new NotificationManager($conn);

// Function to handle any errors
function handleError($message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// Get all students
if (isset($_GET['action']) && $_GET['action'] == 'get_all') {
    $stmt = $conn->prepare("
        SELECT 
            u.id AS ID, 
            u.fullName AS NAME, 
            u.email AS EMAIL, 
            u.mobile AS MOBILE, 
            u.country AS COUNTRY
        FROM 
            users u 
        WHERE 
            u.role = 'student'
        ORDER BY 
            u.fullName
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'students' => $students]);
    $stmt->close();
    exit;
}

// Get individual student
if (isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['id'])) {
    $student_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("
        SELECT 
            u.id AS ID, 
            u.fullName AS NAME, 
            u.email AS EMAIL, 
            u.mobile AS MOBILE, 
            u.country AS COUNTRY
        FROM 
            users u 
        WHERE 
            u.id = ? AND u.role = 'student'
    ");
    
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        
        // Get courses the student is enrolled in
        $courseStmt = $conn->prepare("
            SELECT 
                c.courseCode,
                c.courseTitle
            FROM 
                studentcourse sc
            JOIN
                courses c ON sc.course_id = c.id
            WHERE 
                sc.student_id = ?
        ");
        
        $courseStmt->bind_param("i", $student_id);
        $courseStmt->execute();
        $courseResult = $courseStmt->get_result();
        
        $courses = [];
        while ($course = $courseResult->fetch_assoc()) {
            $courses[] = $course;
        }
        $courseStmt->close();
        
        // Get student grades
        $gradeStmt = $conn->prepare("
            SELECT 
                cg.grade,
                c.courseCode,
                c.courseTitle
            FROM 
                course_grades cg
            JOIN
                courses c ON cg.course_id = c.id
            WHERE 
                cg.student_id = ?
        ");
        
        $gradeStmt->bind_param("i", $student_id);
        $gradeStmt->execute();
        $gradeResult = $gradeStmt->get_result();
        
        $grades = [];
        while ($grade = $gradeResult->fetch_assoc()) {
            $grades[] = $grade;
        }
        $gradeStmt->close();
        
        // Get student attendance
        $attendanceStmt = $conn->prepare("
            SELECT 
                a.status,
                a.date,
                c.courseCode,
                c.courseTitle
            FROM 
                attendance a
            JOIN
                courses c ON a.course_id = c.id
            WHERE 
                a.student_id = ?
            ORDER BY 
                a.date DESC
            LIMIT 5
        ");
        
        $attendanceStmt->bind_param("i", $student_id);
        $attendanceStmt->execute();
        $attendanceResult = $attendanceStmt->get_result();
        
        $attendance = [];
        while ($record = $attendanceResult->fetch_assoc()) {
            $attendance[] = $record;
        }
        $attendanceStmt->close();
        
        // Return all data
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'student' => $student,
            'courses' => $courses,
            'grades' => $grades,
            'attendance' => $attendance
        ]);
    } else {
        handleError("Student not found");
    }
    
    $stmt->close();
    exit;
}

// Add new student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['newstudentID'], $_POST['newName'], $_POST['newEmail'], $_POST['newMobile'], $_POST['newCountry'])) {
    // Check if ID already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $checkStmt->bind_param("i", $_POST['newstudentID']);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $checkStmt->close();
        handleError("Student ID already exists. Please use a different ID.");
    }
    $checkStmt->close();
    
    // Check if email already exists
    $checkEmailStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmailStmt->bind_param("s", $_POST['newEmail']);
    $checkEmailStmt->execute();
    $checkEmailResult = $checkEmailStmt->get_result();
    
    if ($checkEmailResult->num_rows > 0) {
        $checkEmailStmt->close();
        handleError("Email already exists. Please use a different email.");
    }
    $checkEmailStmt->close();
    
    // Insert new student
    $defaultPassword = password_hash("password123", PASSWORD_DEFAULT); // Default password
    $role = 'student'; // Student role
    
    $stmt = $conn->prepare("
        INSERT INTO users (
            id, fullName, email, mobile, country, password, role
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param(
        "isssssi", 
        $_POST['newstudentID'], 
        $_POST['newName'], 
        $_POST['newEmail'], 
        $_POST['newMobile'], 
        $_POST['newCountry'],
        $defaultPassword,
        $role
    );
    
    if ($stmt->execute()) {
        // Create notification for admin
        $teacherId = $_SESSION['user_id'];
        $notificationManager->createNotification(
            $teacherId, 
            "New student {$_POST['newName']} added to the system", 
            "student", 
            $_POST['newstudentID']
        );
        
        // Return HTML for new row
        echo '<tr>
                <td data-id="student_id">' . $_POST['newstudentID'] . '</td>
                <td data-id="student_name">' . $_POST['newName'] . '</td>
                <td data-id="student_email">' . $_POST['newEmail'] . '</td>
                <td data-id="student_mobile">' . $_POST['newMobile'] . '</td>
                <td data-id="student_country">' . $_POST['newCountry'] . '</td>
                <td>
                    <button class="edit"><i class="fas fa-pencil" aria-hidden="true"></i></button>
                    <button class="save" style="display:none;" data-id="' . $_POST['newstudentID'] . '"><i class="fas fa-check" aria-hidden="true"></i></button>
                    <button class="cancel" style="display:none;"><i class="fas fa-times" aria-hidden="true"></i></button>
                    <button class="del" data-id="' . $_POST['newstudentID'] . '"><i class="fas fa-trash" aria-hidden="true"></i></button>
                </td>
            </tr>';
    } else {
        handleError("Error adding student: " . $stmt->error);
    }
    
    $stmt->close();
    exit;
}

// Update student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update') {
    if (!isset($_POST['student_id'], $_POST['student_name'], $_POST['student_email'], $_POST['student_mobile'], $_POST['student_country'])) {
        handleError("Missing required fields");
    }
    
    $student_id = intval($_POST['student_id']);
    $name = $_POST['student_name'];
    $email = $_POST['student_email'];
    $mobile = $_POST['student_mobile'];
    $country = $_POST['student_country'];
    
    // Check if email already exists for another user
    $checkEmailStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $checkEmailStmt->bind_param("si", $email, $student_id);
    $checkEmailStmt->execute();
    $checkEmailResult = $checkEmailStmt->get_result();
    
    if ($checkEmailResult->num_rows > 0) {
        $checkEmailStmt->close();
        handleError("Email already exists for another student. Please use a different email.");
    }
    $checkEmailStmt->close();
    
    // Update student information
    $stmt = $conn->prepare("
        UPDATE users SET 
            fullName = ?, 
            email = ?, 
            mobile = ?, 
            country = ? 
        WHERE id = ? AND role = 'student'
    ");
    
    $stmt->bind_param("ssssi", $name, $email, $mobile, $country, $student_id);
    
    if ($stmt->execute()) {
        // Get all student data to refresh the table
        $refreshStmt = $conn->prepare("
            SELECT 
                u.id AS ID, 
                u.fullName AS NAME, 
                u.email AS EMAIL, 
                u.mobile AS MOBILE, 
                u.country AS COUNTRY
            FROM 
                users u 
            WHERE 
                u.role = 'student'
            ORDER BY 
                u.fullName
        ");
        
        $refreshStmt->execute();
        $result = $refreshStmt->get_result();
        
        $html = '';
        while ($row = $result->fetch_assoc()) {
            $html .= '<tr>
                <td data-id="student_id">' . $row['ID'] . '</td>
                <td data-id="student_name">' . $row['NAME'] . '</td>
                <td data-id="student_email">' . $row['EMAIL'] . '</td>
                <td data-id="student_mobile">' . $row['MOBILE'] . '</td>
                <td data-id="student_country">' . $row['COUNTRY'] . '</td>
                <td>
                    <button class="edit"><i class="fas fa-pencil" aria-hidden="true"></i></button>
                    <button class="save" style="display:none;" data-id="' . $row['ID'] . '"><i class="fas fa-check" aria-hidden="true"></i></button>
                    <button class="cancel" style="display:none;"><i class="fas fa-times" aria-hidden="true"></i></button>
                    <button class="del" data-id="' . $row['ID'] . '"><i class="fas fa-trash" aria-hidden="true"></i></button>
                </td>
            </tr>';
        }
        
        echo $html;
        $refreshStmt->close();
    } else {
        handleError("Error updating student: " . $stmt->error);
    }
    
    $stmt->close();
    exit;
}

// Delete student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete') {
    if (!isset($_POST['id'])) {
        handleError("Student ID is required");
    }
    
    $student_id = intval($_POST['id']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete student's attendance records
        $stmt1 = $conn->prepare("DELETE FROM attendance WHERE student_id = ?");
        $stmt1->bind_param("i", $student_id);
        $stmt1->execute();
        $stmt1->close();
        
        // Delete student's grades
        $stmt2 = $conn->prepare("DELETE FROM course_grades WHERE student_id = ?");
        $stmt2->bind_param("i", $student_id);
        $stmt2->execute();
        $stmt2->close();
        
        // Delete student's course enrollments
        $stmt3 = $conn->prepare("DELETE FROM studentcourse WHERE student_id = ?");
        $stmt3->bind_param("i", $student_id);
        $stmt3->execute();
        $stmt3->close();
        
        // Delete student's assignment submissions
        $stmt4 = $conn->prepare("DELETE FROM assignment_submissions WHERE student_id = ?");
        $stmt4->bind_param("i", $student_id);
        $stmt4->execute();
        $stmt4->close();
        
        // Delete student's notifications
        $stmt5 = $conn->prepare("DELETE FROM notifications WHERE user_id = ? OR entity_id = ?");
        $stmt5->bind_param("ii", $student_id, $student_id);
        $stmt5->execute();
        $stmt5->close();
        
        // Finally delete the student
        $stmt6 = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
        $stmt6->bind_param("i", $student_id);
        $stmt6->execute();
        
        if ($stmt6->affected_rows === 0) {
            // Rollback if no user was deleted
            $conn->rollback();
            $stmt6->close();
            handleError("Student not found or you don't have permission to delete");
        }
        
        $stmt6->close();
        
        // Commit the transaction
        $conn->commit();
        
        // Success response
        echo "1";
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        handleError("Error deleting student: " . $e->getMessage());
    }
    
    exit;
}
?>
