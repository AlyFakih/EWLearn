<?php
// Set page variables for the shared header/footer shell
$page_title = "Courses";
$current_page = "courses";
$page_css = "course-dashboard";
$page_js = "course";

// Include the header which handles session, DB connection and common includes
include_once "php/header.php";

// Only show courses this teacher actually teaches, with enrollment counts.
// Course ownership lives in instructorcourse (users.fullName <->
// courses.courseTitle) - there is no courses.teacher_id column.
$courses_query = "SELECT c.id, c.courseTitle, c.courseCode, c.courseDescription,
                         c.startDate, c.endDate,
                         (SELECT COUNT(*) FROM studentcourse sc WHERE sc.courseID = c.courseTitle) AS student_count
                  FROM courses c
                  JOIN instructorcourse ic ON ic.courseID = c.courseTitle
                  JOIN users tu ON tu.fullName = ic.userInstructorID
                  WHERE tu.id = ?
                  ORDER BY c.courseTitle ASC";
$courses = $db_handle->executeSelectPrepared($courses_query, "i", [$user_id]);
?>

<div class="card">
    <div class="card-header">
        <h2>My Courses</h2>
        <div class="card-actions">
            <button type="button" class="btn btn-primary" id="showForm">
                <i class="fas fa-plus"></i> Add Course
            </button>
        </div>
    </div>

    <div class="card-grid" id="course-grid">
        <?php if (!empty($courses)): ?>
            <?php foreach ($courses as $course): ?>
                <div class="course-card" data-id="<?php echo (int)$course['id']; ?>">
                    <div class="course-header">
                        <h3><?php echo htmlspecialchars($course['courseTitle']); ?></h3>
                        <?php if (!empty($course['courseCode'])): ?>
                            <span class="course-code"><?php echo htmlspecialchars($course['courseCode']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="course-body">
                        <p class="course-description"><?php echo htmlspecialchars($course['courseDescription'] ?? ''); ?></p>
                        <div class="course-stats">
                            <div class="stat">
                                <i class="fas fa-user-graduate"></i>
                                <span><?php echo (int)$course['student_count']; ?> Students</span>
                            </div>
                            <?php if (!empty($course['startDate']) && !empty($course['endDate'])): ?>
                                <div class="stat">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo date('Y-m-d', strtotime($course['startDate'])); ?> to <?php echo date('Y-m-d', strtotime($course['endDate'])); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="course-footer">
                        <button type="button" class="btn btn-secondary view-course" data-id="<?php echo (int)$course['id']; ?>">View Details</button>
                        <button type="button" class="btn btn-icon edit-course" data-id="<?php echo (int)$course['id']; ?>"><i class="fas fa-edit"></i></button>
                        <button type="button" class="btn btn-icon delete-course" data-id="<?php echo (int)$course['id']; ?>"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-book empty-icon"></i>
                <h3>No courses yet</h3>
                <p>Create your first course to get started.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Course Modal -->
<div id="addModal" class="modal-overlay">
    <div class="modal-panel">
        <h2>Add New Course</h2>
        <form id="addCourseForm">
            <div class="form-grid">
                <div class="form-group">
                    <label for="courseTitle">Course Title</label>
                    <input type="text" id="courseTitle" name="courseTitle" required>
                </div>
                <div class="form-group">
                    <label for="courseCode">Course Code</label>
                    <input type="text" id="courseCode" name="courseCode" required>
                </div>
                <div class="form-group">
                    <label for="credits">Credits</label>
                    <input type="number" id="credits" name="credits" min="0" max="10">
                </div>
                <div class="form-group">
                    <label for="semester">Semester</label>
                    <input type="text" id="semester" name="semester" placeholder="e.g. Fall 2026">
                </div>
                <div class="form-group">
                    <label for="startDate">Start Date</label>
                    <input type="date" id="startDate" name="startDate" required>
                </div>
                <div class="form-group">
                    <label for="endDate">End Date</label>
                    <input type="date" id="endDate" name="endDate" required>
                </div>
            </div>
            <div class="form-group">
                <label for="courseDescription">Description</label>
                <textarea id="courseDescription" name="courseDescription" rows="3"></textarea>
            </div>
            <div class="card-actions">
                <button type="submit" class="btn btn-primary">Add Course</button>
                <button type="button" class="btn btn-secondary" id="closeForm">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- View Course Modal -->
<div id="viewModal" class="modal-overlay">
    <div class="modal-panel">
        <h2 id="viewTitle"></h2>
        <p><span class="badge badge-muted" id="viewCode"></span></p>
        <p id="viewDescription"></p>
        <div class="form-grid">
            <div><strong>Credits:</strong> <span id="viewCredits"></span></div>
            <div><strong>Semester:</strong> <span id="viewSemester"></span></div>
            <div><strong>Start:</strong> <span id="viewStartDate"></span></div>
            <div><strong>End:</strong> <span id="viewEndDate"></span></div>
        </div>
        <h3 style="margin-top: var(--space-4);">Enrolled Students</h3>
        <ul id="viewStudents"></ul>
        <h3>Assignments</h3>
        <ul id="viewAssignments"></ul>
        <div class="card-actions">
            <button type="button" class="btn btn-secondary" id="closeViewModal">Close</button>
        </div>
    </div>
</div>

<!-- Edit Course Modal -->
<div id="editModal" class="modal-overlay">
    <div class="modal-panel">
        <h2>Edit Course</h2>
        <form id="editCourseForm">
            <input type="hidden" id="editCourseId" name="id">
            <div class="form-grid">
                <div class="form-group">
                    <label for="editCourseTitle">Course Title</label>
                    <input type="text" id="editCourseTitle" name="courseTitle" required>
                </div>
                <div class="form-group">
                    <label for="editCourseCode">Course Code</label>
                    <input type="text" id="editCourseCode" name="courseCode" required>
                </div>
                <div class="form-group">
                    <label for="editCredits">Credits</label>
                    <input type="number" id="editCredits" name="credits" min="0" max="10">
                </div>
                <div class="form-group">
                    <label for="editSemester">Semester</label>
                    <input type="text" id="editSemester" name="semester">
                </div>
                <div class="form-group">
                    <label for="editStartDate">Start Date</label>
                    <input type="date" id="editStartDate" name="startDate" required>
                </div>
                <div class="form-group">
                    <label for="editEndDate">End Date</label>
                    <input type="date" id="editEndDate" name="endDate" required>
                </div>
            </div>
            <div class="form-group">
                <label for="editCourseDescription">Description</label>
                <textarea id="editCourseDescription" name="courseDescription" rows="3"></textarea>
            </div>
            <div class="card-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" class="btn btn-secondary" id="closeEditForm">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php
include_once "php/footer.php";
?>
