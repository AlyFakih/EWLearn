<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has teacher role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../../login.php");
    exit;
}

$test = isset($_GET['test']) ? $_GET['test'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EWLearn Dashboard Test Runner</title>
    
    <!-- Include common header resources -->
    <?php include_once "components/header_includes.php"; ?>
    
    <style>
        .test-nav {
            margin-bottom: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .test-container {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-top: 20px;
        }
        .test-result {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- Include the common header -->
    <?php include_once "components/header.php"; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-md-3 col-lg-2">
                    <?php include_once "components/sidebar.php"; ?>
                </div>
                
                <!-- Main content area -->
                <div class="col-md-9 col-lg-10">
                    <div class="content-wrapper">
                        <div class="content-header">
                            <div class="container-fluid">
                                <div class="row mb-2">
                                    <div class="col-sm-6">
                                        <h1 class="m-0">Dashboard Tests</h1>
                                    </div>
                                    <div class="col-sm-6">
                                        <ol class="breadcrumb float-sm-right">
                                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                                            <li class="breadcrumb-item active">Tests</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <section class="content">
                            <div class="container-fluid">
                                <div class="test-nav">
                                    <div class="btn-group mb-3" role="group" aria-label="Test Navigation">
                                        <a href="?test=dashboard" class="btn btn-<?php echo $test == 'dashboard' ? 'primary' : 'secondary'; ?>">
                                            Integration Tests
                                        </a>
                                        <a href="?test=upload" class="btn btn-<?php echo $test == 'upload' ? 'primary' : 'secondary'; ?>">
                                            File Upload Tests
                                        </a>
                                    </div>
                                    <p class="text-muted">
                                        Select a test to run. Results will be displayed below.
                                    </p>
                                </div>
                                
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <?php echo $test == 'dashboard' ? 'Dashboard Integration Tests' : 'File Upload Tests'; ?>
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="test-container">
                                            <?php
                                            if ($test == 'dashboard') {
                                                // Run dashboard integration tests
                                                include_once 'test/dashboard_integration_test_content.php';
                                            } else {
                                                // Run file upload tests
                                                include_once 'test/file_upload_test_content.php';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include the common footer -->
    <?php include_once "components/footer.php"; ?>
</body>
</html>
