<?php
$page_title = "Students";
$current_page = "students";
$page_css = "student-dashboard";
$page_js = "student";

include_once "php/header.php";

// Only students enrolled in a course this teacher teaches - not every
// student in the system
$sql = "SELECT DISTINCT su.id AS ID, su.fullName AS NAME, su.email AS EMAIL,
               su.mobile AS MOBILE, su.country AS COUNTRY
        FROM studentcourse sc
        JOIN instructorcourse ic ON ic.courseID = sc.courseID
        JOIN users tu ON tu.fullName = ic.userInstructorID
        JOIN users su ON su.fullName = sc.userStudentID
        WHERE tu.id = ?
        ORDER BY su.fullName ASC";
$studentResult = $db_handle->executeSelectPrepared($sql, "i", [$user_id]);
?>

<div class="card">
    <div class="card-header">
        <h2>My Students</h2>
        <div class="card-actions">
            <input type="search" id="searchinstructor" placeholder="Search students...">
        </div>
    </div>

    <?php if (!empty($studentResult)): ?>
        <div class="table-wrap">
            <table id="table1" class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Country</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="ajax-response">
                    <?php foreach ($studentResult as $v): ?>
                        <tr>
                            <td data-id="student_id"><?php echo (int)$v['ID']; ?></td>
                            <td data-id="student_name"><?php echo htmlspecialchars($v['NAME']); ?></td>
                            <td data-id="student_email"><?php echo htmlspecialchars($v['EMAIL']); ?></td>
                            <td data-id="student_mobile"><?php echo htmlspecialchars($v['MOBILE']); ?></td>
                            <td data-id="student_country"><?php echo htmlspecialchars($v['COUNTRY']); ?></td>
                            <td>
                                <button type="button" class="btn btn-icon del" data-id="<?php echo (int)$v['ID']; ?>" title="Remove from my courses">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-user-group empty-icon"></i>
            <h3>No students yet</h3>
            <p>Students will appear here once they enroll in one of your courses.</p>
        </div>
    <?php endif; ?>
</div>

<?php
include_once "php/footer.php";
?>
