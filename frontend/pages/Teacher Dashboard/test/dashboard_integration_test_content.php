<?php
/**
 * Dashboard Integration Test Content
 * 
 * This script tests notification and calendar integration across all Teacher Dashboard pages.
 * It creates test notifications, verifies they appear properly, and tests calendar event creation.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has teacher role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 1) {
    echo "Unauthorized access. Please log in as a teacher.";
    exit;
}

// Include database controller
require_once "php/dbcontroller.php";
$db_handle = new DBController();
$conn = $db_handle->connectDB();

// Include NotificationManager
require_once "../../../common/notification_manager.php";
$notificationManager = new NotificationManager($conn);

// Function to log test results
function logTestResult($test, $result, $details = '') {
    $status = $result ? 'PASSED' : 'FAILED';
    $color = $result ? 'green' : 'red';
    echo "<div style='margin-bottom: 10px;'><strong style='color: $color;'>[$status]</strong> $test";
    if (!empty($details)) {
        echo "<br><em>Details: $details</em>";
    }
    echo "</div>";
}

// Function to create a test notification
function createTestNotification($conn, $notificationManager, $userId) {
    $timestamp = date('Y-m-d H:i:s');
    $message = "Test notification created at $timestamp";
    $entityType = "test";
    $entityId = 0;
    
    $result = $notificationManager->createNotification($userId, $message, $entityType, $entityId);
    return [
        'success' => $result !== false,
        'notification_id' => $result,
        'message' => $message
    ];
}

// Function to check if a notification exists
function checkNotificationExists($conn, $notificationId) {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE id = ?");
    $stmt->bind_param("i", $notificationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

// Function to create a test calendar event
function createCalendarEvent($conn, $userId) {
    $title = "Test Calendar Event";
    $start = date('Y-m-d H:i:s');
    $end = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $color = "#3788d8";
    $description = "Test event created by integration test script";
    
    $stmt = $conn->prepare("
        INSERT INTO calendar_events (user_id, title, start_date, end_date, color, description) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssss", $userId, $title, $start, $end, $color, $description);
    $success = $stmt->execute();
    $eventId = $success ? $stmt->insert_id : 0;
    $stmt->close();
    
    return [
        'success' => $success,
        'event_id' => $eventId,
        'title' => $title
    ];
}

// Function to check if a calendar event exists
function checkCalendarEventExists($conn, $eventId) {
    $stmt = $conn->prepare("SELECT * FROM calendar_events WHERE id = ?");
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

// Function to check if a component is included in a file
function checkComponentInFile($filePath, $componentString) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $fileContent = file_get_contents($filePath);
    return strpos($fileContent, $componentString) !== false;
}

// List of all dashboard files to test
$dashboardFiles = [
    'assignment-dashboard-new.php',
    'attendence-dashboard-new.php',
    'course-dashboard-new.php',
    'exam-dashboard-new.php',
    'grades-dashboard-new.php',
    'profile-dashboard-new.php',
    'student-dashboard-new.php'
];

// Check if JS files contain notification update function
$jsFiles = [
    'js/assignment.js',
    'js/attendence.js',
    'js/course.js',
    'js/exam.js',
    'js/grades-new.js',
    'js/profile-new.js',
    'js/student-new.js'
];

// Component Integration Test
echo '<div class="test-section">';
echo '<h4>Component Integration Test</h4>';

$componentTestsPassed = 0;
$componentTestsTotal = count($dashboardFiles) * 3; // Header, footer, notification components

foreach ($dashboardFiles as $file) {
    $fileName = basename($file);
    
    // Check header component
    $hasHeader = checkComponentInFile($file, 'include_once "components/header.php"');
    logTestResult("$fileName includes header component", $hasHeader);
    if ($hasHeader) $componentTestsPassed++;
    
    // Check footer component
    $hasFooter = checkComponentInFile($file, 'include_once "components/footer.php"');
    logTestResult("$fileName includes footer component", $hasFooter);
    if ($hasFooter) $componentTestsPassed++;
    
    // Check notification/calendar includes
    $hasNotification = checkComponentInFile($file, 'include_once "components/header_includes.php"');
    logTestResult("$fileName includes notification/calendar components", $hasNotification);
    if ($hasNotification) $componentTestsPassed++;
}

echo '<div class="summary">';
echo '<p>Component Integration Tests: <span class="' . (($componentTestsPassed == $componentTestsTotal) ? 'text-success' : 'text-danger') . '">';
echo "$componentTestsPassed of $componentTestsTotal passed";
echo '</span></p>';
echo '</div>';
echo '</div>';

// Notification Integration Test
echo '<div class="test-section mt-4">';
echo '<h4>Notification Integration Test</h4>';

$notificationTestsPassed = 0;
$notificationTestsTotal = count($jsFiles) + 2; // JS files + create notification + check notification

// Check if JS files have updateNotificationCount function
foreach ($jsFiles as $file) {
    $fileName = basename($file);
    $hasUpdateFunction = checkComponentInFile($file, 'updateNotificationCount');
    logTestResult("$fileName includes notification update function", $hasUpdateFunction);
    if ($hasUpdateFunction) $notificationTestsPassed++;
}

// Create a test notification
$userId = $_SESSION['user_id'];
$notificationResult = createTestNotification($conn, $notificationManager, $userId);
logTestResult(
    "Create test notification", 
    $notificationResult['success'], 
    $notificationResult['success'] ? "Created notification ID: {$notificationResult['notification_id']}" : "Failed to create notification"
);
if ($notificationResult['success']) $notificationTestsPassed++;

// Verify notification exists in database
if ($notificationResult['success']) {
    $notificationExists = checkNotificationExists($conn, $notificationResult['notification_id']);
    logTestResult(
        "Verify notification exists in database", 
        $notificationExists, 
        "Notification ID: {$notificationResult['notification_id']}"
    );
    if ($notificationExists) $notificationTestsPassed++;
} else {
    logTestResult("Verify notification exists in database", false, "Skipped because notification creation failed");
}

echo '<div class="summary">';
echo '<p>Notification Integration Tests: <span class="' . (($notificationTestsPassed == $notificationTestsTotal) ? 'text-success' : 'text-danger') . '">';
echo "$notificationTestsPassed of $notificationTestsTotal passed";
echo '</span></p>';
echo '</div>';
echo '</div>';

// Calendar Integration Test
echo '<div class="test-section mt-4">';
echo '<h4>Calendar Integration Test</h4>';

$calendarTestsPassed = 0;
$calendarTestsTotal = 2; // Create event + check event

// Create a test calendar event
$calendarResult = createCalendarEvent($conn, $userId);
logTestResult(
    "Create test calendar event", 
    $calendarResult['success'], 
    $calendarResult['success'] ? "Created event ID: {$calendarResult['event_id']}" : "Failed to create calendar event"
);
if ($calendarResult['success']) $calendarTestsPassed++;

// Verify calendar event exists in database
if ($calendarResult['success']) {
    $eventExists = checkCalendarEventExists($conn, $calendarResult['event_id']);
    logTestResult(
        "Verify calendar event exists in database", 
        $eventExists, 
        "Event ID: {$calendarResult['event_id']}"
    );
    if ($eventExists) $calendarTestsPassed++;
} else {
    logTestResult("Verify calendar event exists in database", false, "Skipped because event creation failed");
}

echo '<div class="summary">';
echo '<p>Calendar Integration Tests: <span class="' . (($calendarTestsPassed == $calendarTestsTotal) ? 'text-success' : 'text-danger') . '">';
echo "$calendarTestsPassed of $calendarTestsTotal passed";
echo '</span></p>';
echo '</div>';
echo '</div>';

// Overall Test Summary
echo '<div class="test-section mt-4">';
echo '<h4>Overall Test Summary</h4>';

$totalTests = $componentTestsTotal + $notificationTestsTotal + $calendarTestsTotal;
$passedTests = $componentTestsPassed + $notificationTestsPassed + $calendarTestsPassed;
$percentPassed = round(($passedTests / $totalTests) * 100, 1);

echo '<div class="summary">';
echo '<p>Total Tests: <strong>' . $totalTests . '</strong></p>';
echo '<p>Passed Tests: <span class="' . (($passedTests == $totalTests) ? 'text-success' : 'text-danger') . '">' . $passedTests . '</span></p>';
echo '<p>Success Rate: <strong>' . $percentPassed . '%</strong></p>';
echo '</div>';

echo '<div class="alert ' . (($passedTests == $totalTests) ? 'alert-success' : 'alert-warning') . ' mt-4">';
if ($passedTests == $totalTests) {
    echo '<strong>All tests passed!</strong> The notification and calendar integration is working properly across all dashboards.';
} else {
    echo '<strong>Some tests failed.</strong> Please review the results above to identify and fix integration issues.';
}
echo '</div>';
echo '</div>';

// Manual Verification Steps
echo '<div class="card mt-4">';
echo '<div class="card-header">';
echo '<h4>Manual Verification Steps</h4>';
echo '</div>';
echo '<div class="card-body">';
echo '<p>Please perform these additional manual checks to fully verify integration:</p>';
echo '<ol>
    <li>Visit each dashboard page and confirm the notification badge displays correctly</li>
    <li>Click on the notification icon and verify notifications appear in the dropdown</li>
    <li>Check that the sidebar calendar displays properly on each page</li>
    <li>Create a new calendar event and verify it appears in the calendar on all pages</li>
    <li>Verify that notification counts update in real-time when new notifications are created</li>
</ol>';
echo '</div>';
echo '</div>';
?>
