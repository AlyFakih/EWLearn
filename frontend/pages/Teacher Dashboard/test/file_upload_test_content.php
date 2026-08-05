<?php
/**
 * File Upload Test Content
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
require_once "php/dbcontroller.php";
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

// Process file upload if submitted
$uploadResult = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_test'])) {
    if (isset($_FILES['test_file']) && $_FILES['test_file']['error'] == 0) {
        $uploadType = $_POST['upload_type'] ?? 'assignment';
        $targetDir = '';
        
        // Determine target directory
        if ($uploadType == 'assignment') {
            $targetDir = '../../../uploads/assignments/';
        } else if ($uploadType == 'profile') {
            $targetDir = '../../../uploads/profile_images/';
        }
        
        // Create directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Process upload
        $fileName = basename($_FILES['test_file']['name']);
        $targetFile = $targetDir . $fileName;
        
        if (move_uploaded_file($_FILES['test_file']['tmp_name'], $targetFile)) {
            $uploadResult = [
                'success' => true,
                'message' => "File uploaded successfully to: $targetFile"
            ];
        } else {
            $uploadResult = [
                'success' => false,
                'message' => "Failed to upload file. Error code: " . $_FILES['test_file']['error']
            ];
        }
    } else {
        $uploadResult = [
            'success' => false,
            'message' => "No file selected or upload error occurred. Error code: " . ($_FILES['test_file']['error'] ?? 'unknown')
        ];
    }
}

// Directories to check for file uploads
$uploadDirectories = [
    '../../../uploads/assignments/' => 'Assignment Uploads',
    '../../../uploads/profile_images/' => 'Profile Images',
    '../../../uploads/' => 'General Uploads'
];

// File handlers to check
$fileHandlers = [
    'php/get_submission.php' => 'Assignment Submission Viewer',
    'php/grade_submission.php' => 'Assignment Grading Handler',
    'php/delete_submission.php' => 'Assignment Deletion Handler'
];

// Display upload result if available
if ($uploadResult !== null) {
    echo '<div class="alert alert-' . ($uploadResult['success'] ? 'success' : 'danger') . ' mb-4">';
    echo '<strong>' . ($uploadResult['success'] ? 'Success!' : 'Error!') . '</strong> ';
    echo $uploadResult['message'];
    echo '</div>';
}

// Upload Directories Check
echo '<div class="test-section">';
echo '<h4>Upload Directories Check</h4>';

$directoryTestsPassed = 0;
$directoryTestsTotal = count($uploadDirectories) * 2; // Existence + upload test

foreach ($uploadDirectories as $directory => $description) {
    echo "<h5 class='mt-3'>Testing $description</h5>";
    
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
        
        // Create directories that don't exist
        if (!$directoryCheck['exists']) {
            echo '<div class="alert alert-warning mt-2">';
            echo "Directory $directory doesn't exist. Creating it now...";
            
            if (mkdir($directory, 0777, true)) {
                echo " <strong>Success!</strong> Directory created.";
            } else {
                echo " <strong>Failed!</strong> Could not create directory.";
            }
            
            echo '</div>';
        }
    }
}

echo '<div class="summary">';
echo '<p>Directory Tests: <span class="' . (($directoryTestsPassed == $directoryTestsTotal) ? 'text-success' : 'text-danger') . '">';
echo "$directoryTestsPassed of $directoryTestsTotal passed";
echo '</span></p>';
echo '</div>';
echo '</div>';

// File Handlers Check
echo '<div class="test-section mt-4">';
echo '<h4>File Handlers Check</h4>';

$handlerTestsPassed = 0;
$handlerTestsTotal = count($fileHandlers) * 2; // Existence + functionality

foreach ($fileHandlers as $handlerPath => $description) {
    echo "<h5 class='mt-3'>Testing $description</h5>";
    
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

echo '<div class="summary">';
echo '<p>Handler Tests: <span class="' . (($handlerTestsPassed == $handlerTestsTotal) ? 'text-success' : ($handlerTestsPassed > 0 ? 'text-warning' : 'text-danger')) . '">';
echo "$handlerTestsPassed of $handlerTestsTotal passed";
echo '</span></p>';
echo '</div>';
echo '</div>';

// Upload Form Test
echo '<div class="test-section mt-4">';
echo '<h4>Upload Form Test</h4>';
echo '<p>Use this form to test manual file uploads to the system:</p>';

echo '<form action="" method="post" enctype="multipart/form-data" class="mb-4">';
echo '<input type="hidden" name="upload_test" value="1">';
echo '<div class="form-group">';
echo '<label for="upload_type">Upload Type:</label>';
echo '<select class="form-control" id="upload_type" name="upload_type">';
echo '<option value="assignment">Assignment Submission</option>';
echo '<option value="profile">Profile Image</option>';
echo '</select>';
echo '</div>';
echo '<div class="form-group">';
echo '<label for="test_file">Select file to upload:</label>';
echo '<input type="file" class="form-control-file" id="test_file" name="test_file">';
echo '</div>';
echo '<button type="submit" class="btn btn-primary">Upload File</button>';
echo '</form>';

// Manual Verification Steps
echo '<div class="card mt-4">';
echo '<div class="card-header">';
echo '<h4>Manual Verification Steps</h4>';
echo '</div>';
echo '<div class="card-body">';
echo '<p>Please perform these additional manual checks to fully verify file upload functionality:</p>';
echo '<ol>
    <li>Try submitting an assignment through the Assignment Dashboard</li>
    <li>Verify the file is properly stored in the uploads/assignments directory</li>
    <li>Check that the file can be viewed through the dashboard</li>
    <li>Verify that file permissions are set correctly (readable by web server)</li>
    <li>Test uploading different file types (PDF, DOC, images) to ensure proper handling</li>
    <li>Check for proper error handling when invalid files are uploaded</li>
</ol>';
echo '</div>';
echo '</div>';
?>
