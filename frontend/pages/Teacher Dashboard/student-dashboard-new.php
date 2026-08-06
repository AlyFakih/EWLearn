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

// Include the database controller
require_once "php/dbcontroller.php";
$db_handle = new DBController();

// Get all students
$conn = $db_handle->connectDB();
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
$studentResult = [];
while ($row = $result->fetch_assoc()) {
    $studentResult[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management</title>
    
    <!-- Include common header resources -->
    <?php include_once "components/header_includes.php"; ?>
    
    <!-- Page specific CSS -->
    <link rel="stylesheet" href="./css/student-dashboard.css">
</head>
<body>
    <!-- Include the common header -->
    <?php include_once "components/header.php"; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar with calendar -->
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
                                        <h1 class="m-0">Student Management</h1>
                                    </div>
                                    <div class="col-sm-6">
                                        <ol class="breadcrumb float-sm-right">
                                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                                            <li class="breadcrumb-item active">Students</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <section class="content">
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h3 class="card-title">Student Directory</h3>
                                                    <div class="input-group input-group-sm" style="width: 250px;">
                                                        <input type="search" id="searchinstructor" class="form-control float-right" placeholder="Search Data...">
                                                        <div class="input-group-append">
                                                            <button type="submit" class="btn btn-default">
                                                                <i class="fas fa-search"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="d-flex justify-content-end mb-3">
                                                    <button id="showForm" class="btn btn-primary">
                                                        <i class="fas fa-plus"></i> Add Student
                                                    </button>
                                                </div>
                                                <div class="table-responsive" id="list-product">
                                                    <table id="table1" class="table table-bordered table-hover">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>ID <i class="fas fa-sort"></i></th>
                                                                <th>Name <i class="fas fa-sort"></i></th>
                                                                <th>Email <i class="fas fa-sort"></i></th>
                                                                <th>Mobile <i class="fas fa-sort"></i></th>
                                                                <th>Country <i class="fas fa-sort"></i></th>
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="ajax-response">
                                                            <?php
                                                            if (!empty($studentResult)) {
                                                                foreach ($studentResult as $v) {
                                                            ?>
                                                                <tr>
                                                                    <td data-id="student_id"><?php echo $v['ID']; ?></td>
                                                                    <td data-id="student_name"><?php echo $v['NAME']; ?></td>
                                                                    <td data-id="student_email"><?php echo $v['EMAIL']; ?></td>
                                                                    <td data-id="student_mobile"><?php echo $v['MOBILE']; ?></td>
                                                                    <td data-id="student_country"><?php echo $v['COUNTRY']; ?></td>
                                                                    <td>
                                                                        <button class="btn btn-sm btn-info view-student" data-id="<?php echo $v['ID']; ?>">
                                                                            <i class="fas fa-eye"></i>
                                                                        </button>
                                                                        <button class="btn btn-sm btn-primary edit">
                                                                            <i class="fas fa-pencil-alt"></i>
                                                                        </button>
                                                                        <button class="btn btn-sm btn-success save" style="display:none;" data-id="<?php echo $v['ID']; ?>">
                                                                            <i class="fas fa-check"></i>
                                                                        </button>
                                                                        <button class="btn btn-sm btn-secondary cancel" style="display:none;">
                                                                            <i class="fas fa-times"></i>
                                                                        </button>
                                                                        <button class="btn btn-sm btn-danger del" data-id="<?php echo $v['ID']; ?>">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            <?php
                                                                }
                                                            } else {
                                                            ?>
                                                                <tr>
                                                                    <td colspan="6" class="text-center">No students found</td>
                                                                </tr>
                                                            <?php
                                                            }
                                                            ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
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
    
    <!-- Add Student Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Add New Student</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="addForm" method="post">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="newstudentID">Student ID:</label>
                                <input type="number" class="form-control" id="newstudentID" name="newstudentID" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="newName">Full Name:</label>
                                <input type="text" class="form-control" id="newName" name="newName" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="newEmail">Email:</label>
                                <input type="email" class="form-control" id="newEmail" name="newEmail" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="newMobile">Mobile:</label>
                                <input type="text" class="form-control" id="newMobile" name="newMobile" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="newCountry">Country:</label>
                            <input type="text" class="form-control" id="newCountry" name="newCountry" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" id="adddata" class="btn btn-primary">Add Student</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- View Student Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalLabel">Student Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Personal Information</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>Student ID:</strong> <span id="view_student_id"></span></p>
                                    <p><strong>Name:</strong> <span id="view_student_name"></span></p>
                                    <p><strong>Email:</strong> <span id="view_student_email"></span></p>
                                    <p><strong>Mobile:</strong> <span id="view_student_mobile"></span></p>
                                    <p><strong>Country:</strong> <span id="view_student_country"></span></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">Enrolled Courses</h5>
                                </div>
                                <div class="card-body">
                                    <div id="view_courses">
                                        <p class="text-muted">No courses found</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Academic Performance</h5>
                                </div>
                                <div class="card-body">
                                    <div id="view_grades">
                                        <p class="text-muted">No grades found</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-warning text-white">
                                    <h5 class="mb-0">Recent Attendance</h5>
                                </div>
                                <div class="card-body">
                                    <div id="view_attendance">
                                        <p class="text-muted">No attendance records found</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include the common footer -->
    <?php include_once "components/footer.php"; ?>

    <!-- Page specific scripts -->
    <script src="js/student-new.js"></script>
</body>
</html>
