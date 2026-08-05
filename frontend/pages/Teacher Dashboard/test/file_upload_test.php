<?php
/**
 * File Upload Test Script
 * 
 * This script tests file upload functionality across the system, particularly for:
 * - Assignment submissions
 * - Profile image uploads (if implemented)
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

// Function to check if directory exists and is writable
function checkDirectory($path) {
    if (!file_exists($path)) {
        return [
            'exists' => false,
            'writable' => false,
            'message' => "Directory does not exist"
        ];
    }
    
    if (!is_dir($path)) {
        return [
            'exists' => true,
            'writable' => false,
            'message' => "Path exists but is not a directory"
        ];
    }
    
    if (!is_writable($path)) {
        return [
            'exists' => true,
            'writable' => false,
            'message' => "Directory exists but is not writable"
        ];
    }
    
    return [
        'exists' => true,
        'writable' => true,
        'message' => "Directory exists and is writable"
    ];
}

// Function to check if file handler exists in the system
function checkFileHandler($filePath) {
    if (!file_exists($filePath)) {
        return [
            'exists' => false,
            'message' => "File handler does not exist"
        ];
    }
    
    $content = file_get_contents($filePath);
    
    // Check for file upload functionality
    $hasFileUpload = strpos($content, 'move_uploaded_file') !== false;
    
    return [
        'exists' => true,
        'hasFileUpload' => $hasFileUpload,
        'message' => $hasFileUpload ? "File handler exists and has file upload functionality" : "File handler exists but may not handle file uploads"
    ];
}

// Function to test file upload to a directory
function testUploadToDirectory($directory) {
    // Create test file
    $testFile = tempnam(sys_get_temp_dir(), 'upload_test_');
    file_put_contents($testFile, "This is a test file for upload functionality");
    
    // Test data
    $testFileName = "test_upload_" . time() . ".txt";
    $destination = rtrim($directory, '/') . '/' . $testFileName;
    
    // Try to upload
    $success = copy($testFile, $destination);
    
    // Clean up test files
    @unlink($testFile);
    if ($success) {
        @unlink($destination);
    }
    
    return [
        'success' => $success,
        'message' => $success ? "Successfully uploaded test file to directory" : "Failed to upload test file to directory"
    ];
}

// Directories to check for file uploads
$uploadDirectories = [
    '../../../../uploads/assignments/' => 'Assignment Uploads',
    '../../../../uploads/profile_images/' => 'Profile Images',
    '../../../../uploads/' => 'General Uploads'
];

// File handlers to check
$fileHandlers = [
    '../php/get_submission.php' => 'Assignment Submission Viewer',
    '../php/grade_submission.php' => 'Assignment Grading Handler',
    '../php/delete_submission.php' => 'Assignment Deletion Handler'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload Test</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { padding: 20px; font-family: Arial, sans-serif; }
        .test-section { margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        h2 { margin-bottom: 20px; color: #007bff; }
        .summary { margin-top: 30px; font-weight: bold; }
        .passed { color: green; }
        .failed { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="my-4">File Upload Functionality Test</h1>
        
        <div class="test-section">
            <h2>Upload Directories Check</h2>
            <?php
            $directoryTestsPassed = 0;
            $directoryTestsTotal = count($uploadDirectories) * 2; // Existence + upload test
            
            foreach ($uploadDirectories as $directory => $description) {
                echo "<h4>Testing $description</h4>";
                
                // Check if directory exists and is writable
                $directoryCheck = checkDirectory($directory);
                logTestResult(
                    "Directory exists and is writable", 
                    $directoryCheck['exists'] && $directoryCheck['writable'],
                    $directoryCheck['message']
                );
                
                if ($directoryCheck['exists'] && $directoryCheck['writable']) {
                    $directoryTestsPassed++;
                    
                    // Try uploading a test file
                    $uploadTest = testUploadToDirectory($directory);
                    logTestResult(
                        "Test file upload", 
                        $uploadTest['success'],
                        $uploadTest['message']
                    );
                    
                    if ($uploadTest['success']) $directoryTestsPassed++;
                } else {
                    logTestResult(
                        "Test file upload", 
                        false,
                        "Skipped because directory check failed"
                    );
                }
            }
            ?>
            <div class="summary">
                <p>Directory Tests: <span class="<?php echo ($directoryTestsPassed == $directoryTestsTotal) ? 'passed' : 'failed'; ?>">
                    <?php echo "$directoryTestsPassed of $directoryTestsTotal passed"; ?>
                </span></p>
            </div>
        </div>
        
        <div class="test-section">
            <h2>File Handlers Check</h2>
            <?php
            $handlerTestsPassed = 0;
            $handlerTestsTotal = count($fileHandlers) * 2; // Existence + functionality
            
            foreach ($fileHandlers as $handlerPath => $description) {
                echo "<h4>Testing $description</h4>";
                
                // Check if file handler exists and has file upload functionality
                $handlerCheck = checkFileHandler($handlerPath);
                logTestResult(
                    "File handler exists", 
                    $handlerCheck['exists'],
                    $handlerCheck['exists'] ? "Handler found at $handlerPath" : "Handler not found"
                );
                
                if ($handlerCheck['exists']) {
                    $handlerTestsPassed++;
                    
                    // Check for file upload functionality
                    $hasUpload = isset($handlerCheck['hasFileUpload']) ? $handlerCheck['hasFileUpload'] : false;
                    logTestResult(
                        "File handler has upload functionality", 
                        $hasUpload,
                        $hasUpload ? "Upload functionality detected" : "No direct upload functionality detected (may use another handler)"
                    );
                    
                    if ($hasUpload) $handlerTestsPassed++;
                } else {
                    logTestResult(
                        "File upload functionality", 
                        false,
                        "Skipped because file handler check failed"
                    );
                }
            }
            ?>
            <div class="summary">
                <p>Handler Tests: <span class="<?php echo ($handlerTestsPassed == $handlerTestsTotal) ? 'passed' : ($handlerTestsPassed > 0 ? 'warning' : 'failed'); ?>">
                    <?php echo "$handlerTestsPassed of $handlerTestsTotal passed"; ?>
                </span></p>
            </div>
        </div>
        
        <div class="test-section">
            <h2>Upload Form Test</h2>
            <p>Use this form to test manual file uploads to the system:</p>
            
            <form action="file_upload_test_handler.php" method="post" enctype="multipart/form-data" class="mb-4">
                <div class="form-group">
                    <label for="uploadType">Upload Type:</label>
                    <select class="form-control" id="uploadType" name="uploadType">
                        <option value="assignment">Assignment Submission</option>
                        <option value="profile">Profile Image</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="uploadFile">Select file to upload:</label>
                    <input type="file" class="form-control-file" id="uploadFile" name="uploadFile">
                </div>
                <button type="submit" class="btn btn-primary">Upload File</button>
            </form>
            
            <div class="alert alert-info">
                <strong>Note:</strong> This form submits to a handler script that needs to be created separately. This is only for manual testing purposes.
            </div>
        </div>
        
        <div class="card mt-4 mb-4">
            <div class="card-header">
                <h3>Manual Verification Steps</h3>
            </div>
            <div class="card-body">
                <p>Please perform these additional manual checks to fully verify file upload functionality:</p>
                <ol>
                    <li>Try submitting an assignment through the Assignment Dashboard</li>
                    <li>Verify the file is properly stored in the uploads/assignments directory</li>
                    <li>Check that the file can be viewed through the dashboard</li>
                    <li>Verify that file permissions are set correctly (readable by web server)</li>
                    <li>Test uploading different file types (PDF, DOC, images) to ensure proper handling</li>
                    <li>Check for proper error handling when invalid files are uploaded</li>
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
