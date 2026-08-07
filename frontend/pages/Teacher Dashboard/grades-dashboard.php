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

// Get all grades with prepared statement
$conn = $db_handle->connectDB();
$stmt = $conn->prepare(
    "SELECT
        cg.student_id AS StudentID,
        u.fullName AS Name,
        cg.overall_grade AS Grade
    FROM
        course_grades cg
    JOIN users u ON cg.student_id = u.id
    WHERE u.role = 'student'"
);
$stmt->execute();
$result = $stmt->get_result();
$gradesResult = [];
while ($row = $result->fetch_assoc()) {
    $gradesResult[] = $row;
}
$stmt->close();

// Get all courses for dropdown menus
$stmt = $conn->prepare("SELECT id, courseTitle FROM courses");
$stmt->execute();
$courses_result = $stmt->get_result();
$courses = [];
while ($course = $courses_result->fetch_assoc()) {
    $courses[] = $course;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grades Dashboard</title>
    
    <!-- Include common header resources -->
    <?php include_once "components/header_includes.php"; ?>
    
    <!-- Page specific CSS -->
    <link rel="stylesheet" href="./css/grades-dashboard.css">
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
                                        <h1 class="m-0">Grades Management</h1>
                                    </div>
                                    <div class="col-sm-6">
                                        <ol class="breadcrumb float-sm-right">
                                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                                            <li class="breadcrumb-item active">Grades</li>
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
                                                    <h3 class="card-title">Student Grades</h3>
                                                    <div class="card-tools">
                                                        <div class="input-group input-group-sm" style="width: 250px;">
                                                            <input type="text" id="searchinstructor" class="form-control float-right" placeholder="Search...">
                                                            <div class="input-group-append">
                                                                <button type="submit" class="btn btn-default">
                                                                    <i class="fas fa-search"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="d-flex justify-content-end mb-3">
                                                    <button id="showForm" class="btn btn-primary">
                                                        <i class="fas fa-plus"></i> Add Grade
                                                    </button>
                                                </div>
                                                <div class="table-responsive">
                                                    <table id="table1" class="table table-bordered table-hover">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>Student ID <i class="fas fa-sort"></i></th>
                                                                <th>Name <i class="fas fa-sort"></i></th>
                                                                <th>Grade <i class="fas fa-sort"></i></th>
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="ajax-response">
                                                            <?php
                                                            if (!empty($gradesResult)) {
                                                                foreach ($gradesResult as $v) {
                                                            ?>
                                                                <tr>
                                                                    <td data-id="student_id"><?php echo $v['StudentID']; ?></td>
                                                                    <td data-id="student_name"><?php echo $v['Name']; ?></td>
                                                                    <td data-id="student_grade"><?php echo $v['Grade']; ?></td>
                                                                    <td>
                                                                        <button class="btn btn-sm btn-info view-grade" data-id="<?php echo $v['StudentID']; ?>">
                                                                            <i class="fas fa-eye"></i>
                                                                        </button>
                                                                        <button class="btn btn-sm btn-primary edit">
                                                                            <i class="fas fa-pencil"></i>
                                                                        </button>
                                                                        <button class="btn btn-sm btn-success save" style="display:none;" data-id="<?php echo $v['StudentID']; ?>">
                                                                            <i class="fas fa-check"></i>
                                                                        </button>
                                                                        <button class="btn btn-sm btn-secondary cancel" style="display:none;">
                                                                            <i class="fas fa-times"></i>
                                                                        </button>
                                                                        <button class="btn btn-sm btn-danger del" data-id="<?php echo $v['StudentID']; ?>">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            <?php
                                                                }
                                                            } else {
                                                            ?>
                                                                <tr>
                                                                    <td colspan="4" class="text-center">No grades found</td>
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
    
    <!-- Add Grade Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Add New Grade</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="addForm" method="post">
                        <div class="form-group">
                            <label for="newstudentID">Student ID:</label>
                            <input type="number" class="form-control" id="newstudentID" name="newstudentID" required>
                        </div>
                        <div class="form-group">
                            <label for="newName">Student Name:</label>
                            <input type="text" class="form-control" id="newName" name="newName" required>
                        </div>
                        <div class="form-group">
                            <label for="course_id">Course:</label>
                            <select class="form-control" id="course_id" name="course_id" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>"><?php echo $course['courseTitle']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="newGrade">Grade:</label>
                            <input type="text" class="form-control" id="newGrade" name="newGrade" required>
                        </div>
                        <div class="form-group">
                            <label for="term">Term:</label>
                            <select class="form-control" id="term" name="term">
                                <option value="Fall 2025">Fall 2025</option>
                                <option value="Spring 2026">Spring 2026</option>
                                <option value="Summer 2026">Summer 2026</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" id="adddata" class="btn btn-primary">Add Grade</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- View Grade Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalLabel">Grade Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Student ID:</strong> <span id="view_student_id"></span></p>
                            <p><strong>Student Name:</strong> <span id="view_student_name"></span></p>
                            <p><strong>Email:</strong> <span id="view_student_email"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Course:</strong> <span id="view_course"></span></p>
                            <p><strong>Term:</strong> <span id="view_term"></span></p>
                            <p><strong>Date Added:</strong> <span id="view_date"></span></p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    Grade Information
                                </div>
                                <div class="card-body">
                                    <h3 class="text-center" id="view_grade"></h3>
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
    <script src="js/grades-new.js"></script>
</body>
</html>
