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

// Get teacher information
if (isset($_GET['action']) && $_GET['action'] == 'get_teacher_info') {
    $teacher_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    
    $stmt = $conn->prepare("SELECT fullName, image, country, email, mobile FROM users WHERE id = ? AND role = 1");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $teacher = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'teacher' => $teacher]);
    } else {
        handleError("Teacher not found");
    }
    $stmt->close();
    exit;
}

// Get all skills
if (isset($_GET['action']) && $_GET['action'] == 'get_skills') {
    $teacher_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    
    $stmt = $conn->prepare("SELECT id, subjectIcon, subjectName, progressPercentage, experience FROM instructor_skills WHERE instructorID = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $skills = [];
    while ($row = $result->fetch_assoc()) {
        $skills[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'skills' => $skills]);
    $stmt->close();
    exit;
}

// Add new skill
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $teacher_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    
    // Validate input data
    if (empty($_POST['subjectIcon']) || empty($_POST['subjectName']) || 
        empty($_POST['progressPercentage']) || empty($_POST['experience'])) {
        handleError("All fields are required");
    }
    
    // Sanitize input
    $subjectIcon = $_POST['subjectIcon'];
    $subjectName = $_POST['subjectName'];
    $progressPercentage = intval($_POST['progressPercentage']);
    $experience = $_POST['experience'];
    
    // Validate percentage
    if ($progressPercentage < 0 || $progressPercentage > 100) {
        handleError("Percentage must be between 0 and 100");
    }
    
    // Insert the skill
    $stmt = $conn->prepare("INSERT INTO instructor_skills (instructorID, subjectIcon, subjectName, progressPercentage, experience) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issis", $teacher_id, $subjectIcon, $subjectName, $progressPercentage, $experience);
    
    if ($stmt->execute()) {
        $skill_id = $stmt->insert_id;
        
        // Return HTML for the new skill card
        $html = getSkillCardHtml($skill_id, $subjectIcon, $subjectName, $progressPercentage, $experience);
        echo $html;
    } else {
        handleError("Error adding skill: " . $stmt->error);
    }
    
    $stmt->close();
    exit;
}

// Update skill
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update') {
    if (!isset($_POST['id']) || !isset($_POST['subjectIcon']) || !isset($_POST['subjectName']) || 
        !isset($_POST['progressPercentage']) || !isset($_POST['experience'])) {
        handleError("Missing required fields");
    }
    
    $teacher_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $skill_id = intval($_POST['id']);
    $subjectIcon = $_POST['subjectIcon'];
    $subjectName = $_POST['subjectName'];
    $progressPercentage = intval($_POST['progressPercentage']);
    $experience = $_POST['experience'];
    
    // Validate percentage
    if ($progressPercentage < 0 || $progressPercentage > 100) {
        handleError("Percentage must be between 0 and 100");
    }
    
    // Verify ownership of the skill
    $stmt = $conn->prepare("SELECT id FROM instructor_skills WHERE id = ? AND instructorID = ?");
    $stmt->bind_param("ii", $skill_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        handleError("Skill not found or you don't have permission to edit it");
    }
    
    $stmt->close();
    
    // Update the skill
    $stmt = $conn->prepare("UPDATE instructor_skills SET subjectIcon = ?, subjectName = ?, progressPercentage = ?, experience = ? WHERE id = ? AND instructorID = ?");
    $stmt->bind_param("ssissi", $subjectIcon, $subjectName, $progressPercentage, $experience, $skill_id, $teacher_id);
    
    if ($stmt->execute()) {
        // Return updated HTML for the skill card
        $html = getSkillCardHtml($skill_id, $subjectIcon, $subjectName, $progressPercentage, $experience);
        echo $html;
    } else {
        handleError("Error updating skill: " . $stmt->error);
    }
    
    $stmt->close();
    exit;
}

// Delete skill
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete') {
    if (!isset($_POST['id'])) {
        handleError("Skill ID is required");
    }
    
    $teacher_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $skill_id = intval($_POST['id']);
    
    // Verify ownership of the skill
    $stmt = $conn->prepare("SELECT id FROM instructor_skills WHERE id = ? AND instructorID = ?");
    $stmt->bind_param("ii", $skill_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        handleError("Skill not found or you don't have permission to delete it");
    }
    
    $stmt->close();
    
    // Delete the skill
    $stmt = $conn->prepare("DELETE FROM instructor_skills WHERE id = ? AND instructorID = ?");
    $stmt->bind_param("ii", $skill_id, $teacher_id);
    
    if ($stmt->execute()) {
        echo "1"; // Success
    } else {
        handleError("Error deleting skill: " . $stmt->error);
    }
    
    $stmt->close();
    exit;
}

// Helper function to generate skill card HTML
function getSkillCardHtml($id, $icon, $name, $percentage, $experience) {
    $iconClass = "";
    switch ($icon) {
        case 'code': 
            $iconClass = "fas fa-code"; 
            break;
        case 'database': 
            $iconClass = "fas fa-database"; 
            break;
        case 'laptop': 
            $iconClass = "fas fa-laptop-code"; 
            break;
        case 'computer': 
            $iconClass = "fas fa-desktop"; 
            break;
        case 'network': 
            $iconClass = "fas fa-network-wired"; 
            break;
        case 'drawing': 
            $iconClass = "fas fa-pen"; 
            break;
        case 'security': 
            $iconClass = "fas fa-shield-alt"; 
            break;
        default: 
            $iconClass = "fas fa-graduation-cap"; 
            break;
    }
    
    $html = <<<HTML
    <div class="card" data-id="{$id}">
        <div class="progress">
            <svg>
                <circle class="bg" cx="57" cy="57" r="52"></circle>
                <circle class="meter-{$id}" cx="57" cy="57" r="52" style="--percent: {$percentage}"></circle>
            </svg>
            <div class="number">
                <h3>{$percentage}<span>%</span></h3>
            </div>
        </div>
        <div class="subject">
            <div class="left">
                <i class="{$iconClass}"></i>
                <h3>{$name}</h3>
            </div>
            <div class="right">
                <i class="fas fa-ellipsis-v"></i>
            </div>
        </div>
        <div class="exp">{$experience}</div>
    </div>
HTML;

    return $html;
}
?>
