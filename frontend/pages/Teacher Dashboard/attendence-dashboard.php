<?php
$page_title = "Attendance";
$current_page = "attendance";
$page_css = "attendence-dashboard";
$page_js = "attendence";

include_once "php/header.php";

// Courses this teacher teaches, for the Add form's course dropdown
$courses = $db_handle->executeSelectPrepared(
    "SELECT c.id, c.courseTitle FROM courses c
     JOIN instructorcourse ic ON ic.courseID = c.courseTitle
     JOIN users tu ON tu.fullName = ic.userInstructorID
     WHERE tu.id = ? ORDER BY c.courseTitle",
    "i", [$user_id]
);

// Students enrolled in any of this teacher's courses, for the Add form's student dropdown
$students = $db_handle->executeSelectPrepared(
    "SELECT DISTINCT su.id, su.fullName FROM studentcourse sc
     JOIN instructorcourse ic ON ic.courseID = sc.courseID
     JOIN users tu ON tu.fullName = ic.userInstructorID
     JOIN users su ON su.fullName = sc.userStudentID
     WHERE tu.id = ? ORDER BY su.fullName",
    "i", [$user_id]
);

// Attendance records for courses this teacher teaches, grouped by course so
// they can be shown one course at a time via tabs. The query itself is
// unchanged from before (still scoped to this teacher's own courses via
// instructorcourse) - grouping is purely a presentation concern, so no new
// authorization surface is introduced.
$attendanceResult = $db_handle->executeSelectPrepared(
    "SELECT a.id, a.studentID, a.studentName, c.id AS course_id, c.courseTitle AS course_name, a.date, a.status, a.notes
     FROM attendance a
     JOIN courses c ON a.courseID = c.id
     JOIN instructorcourse ic ON ic.courseID = c.courseTitle
     JOIN users tu ON tu.fullName = ic.userInstructorID
     WHERE tu.id = ?
     ORDER BY a.date DESC, a.id DESC",
    "i", [$user_id]
);

$attendanceByCourse = [];
foreach ($attendanceResult as $row) {
    $attendanceByCourse[$row['course_id']][] = $row;
}
?>

<div class="card">
    <div class="card-header">
        <h2>Attendance</h2>
        <div class="card-actions">
            <input type="search" id="searchAttendance" placeholder="Search attendance...">
            <button type="button" class="btn btn-primary" id="showForm"><i class="fas fa-plus"></i> Record Attendance</button>
        </div>
    </div>

    <?php if (!empty($courses)): ?>
        <div class="course-tabs" role="tablist">
            <?php foreach ($courses as $i => $c): ?>
                <button type="button"
                        class="course-tab<?php echo $i === 0 ? ' active' : ''; ?>"
                        data-course-id="<?php echo (int)$c['id']; ?>"
                        role="tab"
                        aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>">
                    <?php echo htmlspecialchars($c['courseTitle']); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="table-wrap">
            <table id="table1" class="data-table">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Student</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <?php foreach ($courses as $i => $c): ?>
                    <tbody class="course-panel<?php echo $i === 0 ? ' active' : ''; ?>"
                           data-course-id="<?php echo (int)$c['id']; ?>"
                           <?php echo $i === 0 ? '' : 'style="display:none"'; ?>>
                        <?php if (!empty($attendanceByCourse[$c['id']])): ?>
                            <?php foreach ($attendanceByCourse[$c['id']] as $row): ?>
                                <tr>
                                    <td data-id="student_id"><?php echo (int)$row['studentID']; ?></td>
                                    <td data-id="student_name"><?php echo htmlspecialchars($row['studentName']); ?></td>
                                    <td data-id="date"><?php echo htmlspecialchars($row['date']); ?></td>
                                    <td data-id="status">
                                        <span class="badge <?php echo $row['status'] === 'Present' ? 'badge-success' : ($row['status'] === 'Absent' ? 'badge-danger' : 'badge-warning'); ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td data-id="notes"><?php echo htmlspecialchars($row['notes']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-icon del" data-id="<?php echo (int)$row['id']; ?>" title="Delete"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="no-data-row">
                                <td colspan="6" class="text-center">No attendance records yet for this course.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                <?php endforeach; ?>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-user-check empty-icon"></i>
            <h3>No courses yet</h3>
            <p>You'll be able to record attendance once you have a course with enrolled students.</p>
        </div>
    <?php endif; ?>
</div>

<div class="calendar-section card" style="margin-top: var(--space-5);">
    <h2>Academic Calendar</h2>
    <div id="calendar"></div>
</div>

<!-- Add Attendance Modal -->
<div id="addModal" class="modal-overlay">
    <div class="modal-panel">
        <span class="close" style="float:right; cursor:pointer;" id="closeForm">&times;</span>
        <h2>Record Attendance</h2>
        <form id="addForm">
            <div class="form-grid">
                <div class="form-group">
                    <label for="student_id">Student</label>
                    <select id="student_id" name="student_id" required>
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
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['courseTitle']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" required>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="Present">Present</option>
                        <option value="Absent">Absent</option>
                        <option value="Late">Late</option>
                        <option value="Excused">Excused</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="2"></textarea>
            </div>
            <div class="card-actions">
                <button type="button" class="btn btn-primary" id="adddata">Save</button>
            </div>
        </form>
    </div>
</div>

<?php
include_once "php/footer.php";
?>
