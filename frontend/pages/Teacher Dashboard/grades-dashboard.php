<?php
// Server-side gate. These pages compose components/ directly instead of
// going through php/header.php, so they must load the guard themselves -
// otherwise they would run with only an inline session check and no
// database re-validation of the account.
require_once __DIR__ . "/../../core/auth_guard.php";
auth_require_role("instructor", "page", "../loginRegister.html");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
}

// Check if user is logged in and has teacher role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../loginRegister.html");
    exit;
}

// Include the database controller
require_once "../../core/DBController.php";
$db_handle = new DBController();
$user_id = $_SESSION['user_id'];

// Get grades restricted to courses this teacher teaches (course_grades has
// no teacher column of its own - ownership is via instructorcourse)
$conn = $db_handle->connectDB();
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
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$gradesResult = [];
while ($row = $result->fetch_assoc()) {
    $gradesResult[] = $row;
}
$stmt->close();

// Get only the courses this teacher teaches for dropdown menus
$stmt = $conn->prepare(
    "SELECT c.id, c.courseTitle FROM courses c
     JOIN instructorcourse ic ON ic.courseID = c.courseTitle
     JOIN users tu ON tu.fullName = ic.userInstructorID
     WHERE tu.id = ?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$courses_result = $stmt->get_result();
$courses = [];
while ($course = $courses_result->fetch_assoc()) {
    $courses[] = $course;
}
$stmt->close();

// Students enrolled in any of this teacher's courses, for the Add form
$stmt = $conn->prepare(
    "SELECT DISTINCT su.id, su.fullName FROM studentcourse sc
     JOIN instructorcourse ic ON ic.courseID = sc.courseID
     JOIN users tu ON tu.fullName = ic.userInstructorID
     JOIN users su ON su.fullName = sc.userStudentID
     WHERE tu.id = ? ORDER BY su.fullName"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$students_result = $stmt->get_result();
$students = [];
while ($s = $students_result->fetch_assoc()) {
    $students[] = $s;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grades Dashboard</title>

    <!-- Shared design system -->
    <link rel="stylesheet" href="./css/dashboard-theme.css">

    <!-- Include common header resources (jQuery, notifications) -->
    <?php include_once "../common/header_includes.php"; ?>
</head>
<body>
    <div class="app-shell">
        <?php include_once "components/sidebar.php"; ?>
        <div class="main-col">
            <?php include_once "components/header.php"; ?>
            <main class="page-content">

            <div class="card">
                <div class="card-header">
                    <h2>Student Grades</h2>
                    <div class="card-actions">
                        <input type="search" id="searchinstructor" placeholder="Search grades...">
                        <button id="showForm" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Grade
                        </button>
                    </div>
                </div>

                <?php if (!empty($gradesResult)): ?>
                    <div class="table-wrap">
                        <table id="table1" class="data-table">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Course</th>
                                    <th>Grade</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="ajax-response">
                                <?php foreach ($gradesResult as $v): ?>
                                    <tr>
                                        <td data-id="student_id"><?php echo (int)$v['StudentID']; ?></td>
                                        <td data-id="student_name"><?php echo htmlspecialchars($v['Name']); ?></td>
                                        <td data-id="course_name"><?php echo htmlspecialchars($v['CourseName']); ?></td>
                                        <td data-id="student_grade"><?php echo htmlspecialchars($v['Grade']); ?></td>
                                        <td>
                                            <button class="btn btn-icon view-grade" data-id="<?php echo (int)$v['StudentID']; ?>" data-course-id="<?php echo (int)$v['CourseID']; ?>" title="View"><i class="fas fa-eye"></i></button>
                                            <button class="btn btn-icon edit" title="Edit"><i class="fas fa-pencil"></i></button>
                                            <button class="btn btn-icon save" style="display:none;" data-id="<?php echo (int)$v['StudentID']; ?>" data-course-id="<?php echo (int)$v['CourseID']; ?>" title="Save"><i class="fas fa-check"></i></button>
                                            <button class="btn btn-icon cancel" style="display:none;" title="Cancel"><i class="fas fa-times"></i></button>
                                            <button class="btn btn-icon del" data-id="<?php echo (int)$v['StudentID']; ?>" data-course-id="<?php echo (int)$v['CourseID']; ?>" title="Delete"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-graduation-cap empty-icon"></i>
                        <h3>No grades yet</h3>
                        <p>Add a grade for one of your students to see it here.</p>
                    </div>
                <?php endif; ?>
            </div>
            </main>
        </div>
    </div>

    <!-- Add Grade Modal -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-panel">
            <h2>Add New Grade</h2>
            <form id="addForm" method="post">
                <div class="form-group">
                    <label for="newstudentID">Student</label>
                    <select id="newstudentID" name="newstudentID" required>
                        <option value="">Select Student</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['fullName']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="course_id">Course</label>
                    <select id="course_id" name="course_id" required>
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo (int)$course['id']; ?>"><?php echo htmlspecialchars($course['courseTitle']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="newGrade">Grade (0-100)</label>
                    <input type="number" id="newGrade" name="newGrade" min="0" max="100" step="0.01" required>
                </div>
            </form>
            <div class="card-actions">
                <button type="button" id="adddata" class="btn btn-primary">Add Grade</button>
                <button type="button" class="btn btn-secondary cancel-modal">Close</button>
            </div>
        </div>
    </div>

    <!-- View Grade Modal -->
    <div class="modal-overlay" id="viewModal">
        <div class="modal-panel">
            <h2>Grade Details</h2>
            <div class="form-grid">
                <div>
                    <p><strong>Student ID:</strong> <span id="view_student_id"></span></p>
                    <p><strong>Student Name:</strong> <span id="view_student_name"></span></p>
                    <p><strong>Email:</strong> <span id="view_student_email"></span></p>
                </div>
                <div>
                    <p><strong>Course:</strong> <span id="view_course"></span></p>
                    <p><strong>Date Added:</strong> <span id="view_date"></span></p>
                </div>
            </div>
            <div class="stat-card" style="margin-top: var(--space-4);">
                <div class="stat-icon"><i class="fas fa-graduation-cap"></i></div>
                <div>
                    <div class="stat-value" id="view_grade"></div>
                    <div class="stat-label">Overall Grade</div>
                </div>
            </div>
            <div class="card-actions" style="margin-top: var(--space-4);">
                <button type="button" class="btn btn-secondary cancel-modal">Close</button>
            </div>
        </div>
    </div>

    <script src="js/sidebar-toggle.js"></script>
    <script src="js/modal-shim.js"></script>
    <!-- Page specific scripts -->
    <script src="js/grades-new.js"></script>

    <!-- Include the common footer -->
    <?php include_once "components/footer.php"; ?>
</body>
</html>
