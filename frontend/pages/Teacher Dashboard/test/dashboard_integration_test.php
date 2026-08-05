<?php
/**
 * Dashboard Integration Test Script
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
require_once "../php/dbcontroller.php";
$db_handle = new DBController();
$conn = $db_handle->connectDB();

// Include NotificationManager
require_once "../../../../common/notification_manager.php";
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
    '../assignment-dashboard-new.php',
    '../attendence-dashboard-new.php',
    '../course-dashboard-new.php',
    '../exam-dashboard-new.php',
    '../grades-dashboard-new.php',
    '../profile-dashboard-new.php',
    '../student-dashboard-new.php'
];

// Check if JS files contain notification update function
$jsFiles = [
    '../js/assignment.js',
    '../js/attendence.js',
    '../js/course.js',
    '../js/exam.js',
    '../js/grades-new.js',
    '../js/profile-new.js',
    '../js/student-new.js'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Integration Test</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { padding: 20px; font-family: Arial, sans-serif; }
        .test-section { margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        h2 { margin-bottom: 20px; color: #007bff; }
        .summary { margin-top: 30px; font-weight: bold; }
        .passed { color: green; }
        .failed { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="my-4">Dashboard Integration Test Results</h1>
        
        <div class="test-section">
            <h2>Component Integration Test</h2>
            <?php
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
            ?>
            <div class="summary">
                <p>Component Integration Tests: <span class="<?php echo ($componentTestsPassed == $componentTestsTotal) ? 'passed' : 'failed'; ?>">
                    <?php echo "$componentTestsPassed of $componentTestsTotal passed"; ?>
                </span></p>
            </div>
        </div>
        
        <div class="test-section">
            <h2>Notification Integration Test</h2>
            <?php
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
            ?>
            <div class="summary">
                <p>Notification Integration Tests: <span class="<?php echo ($notificationTestsPassed == $notificationTestsTotal) ? 'passed' : 'failed'; ?>">
                    <?php echo "$notificationTestsPassed of $notificationTestsTotal passed"; ?>
                </span></p>
            </div>
        </div>
        
        <div class="test-section">
            <h2>Calendar Integration Test</h2>
            <?php
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
            ?>
            <div class="summary">
                <p>Calendar Integration Tests: <span class="<?php echo ($calendarTestsPassed == $calendarTestsTotal) ? 'passed' : 'failed'; ?>">
                    <?php echo "$calendarTestsPassed of $calendarTestsTotal passed"; ?>
                </span></p>
            </div>
        </div>
        
        <div class="test-section">
            <h2>Overall Test Summary</h2>
            <?php
            $totalTests = $componentTestsTotal + $notificationTestsTotal + $calendarTestsTotal;
            $passedTests = $componentTestsPassed + $notificationTestsPassed + $calendarTestsPassed;
            $percentPassed = round(($passedTests / $totalTests) * 100, 1);
            ?>
            <div class="summary">
                <p>Total Tests: <strong><?php echo $totalTests; ?></strong></p>
                <p>Passed Tests: <span class="<?php echo ($passedTests == $totalTests) ? 'passed' : 'failed'; ?>"><?php echo $passedTests; ?></span></p>
                <p>Success Rate: <strong><?php echo $percentPassed; ?>%</strong></p>
            </div>
            
            <div class="alert <?php echo ($passedTests == $totalTests) ? 'alert-success' : 'alert-warning'; ?> mt-4">
                <?php if ($passedTests == $totalTests): ?>
                    <strong>All tests passed!</strong> The notification and calendar integration is working properly across all dashboards.
                <?php else: ?>
                    <strong>Some tests failed.</strong> Please review the results above to identify and fix integration issues.
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-4 mb-4">
            <div class="card-header">
                <h3>Manual Verification Steps</h3>
            </div>
            <div class="card-body">
                <p>Please perform these additional manual checks to fully verify integration:</p>
                <ol>
                    <li>Visit each dashboard page and confirm the notification badge displays correctly</li>
                    <li>Click on the notification icon and verify notifications appear in the dropdown</li>
                    <li>Check that the sidebar calendar displays properly on each page</li>
                    <li>Create a new calendar event and verify it appears in the calendar on all pages</li>
                    <li>Verify that notification counts update in real-time when new notifications are created</li>
                    <li>Test file uploads for assignments to ensure they work with the new components</li>
                </ol>
                <a href="../dashboard.php" class="btn btn-primary">Return to Dashboard</a>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
