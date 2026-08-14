<?php
// Set page variables for header
$page_title = "Assignment Dashboard";
$current_page = "assignments";
$page_css = "assignments-dashboard";
$page_js = "assignment";

// Include the header which handles session, DB connection and common includes
include_once "php/header.php";

// Get assignments with prepared statements. Teacher-course ownership is
// recorded in instructorcourse (users.fullName <-> courses.courseTitle);
// assignment's due date column is due_date, not deadline.
$query = "SELECT s.id as submission_id, s.student_id, u.fullName as student_name,
          a.id as assignment_id, a.title as assignment_title, c.courseTitle as course_name,
          s.submitted_at, s.status, s.file_path
          FROM assignment_submissions s
          JOIN users u ON s.student_id = u.id
          JOIN assignment a ON s.assignment_id = a.id
          JOIN courses c ON a.course_id = c.id
          JOIN instructorcourse ic ON ic.courseID = c.courseTitle
          JOIN users tu ON tu.fullName = ic.userInstructorID
          WHERE tu.id = ?
          ORDER BY s.submitted_at DESC";
$submissions = $db_handle->executeSelectPrepared($query, "i", [$user_id]);

// Get upcoming assignment deadlines
$deadlines_query = "SELECT a.id, a.title, a.due_date AS deadline, c.courseTitle
                  FROM assignment a
                  JOIN courses c ON a.course_id = c.id
                  JOIN instructorcourse ic ON ic.courseID = c.courseTitle
                  JOIN users tu ON tu.fullName = ic.userInstructorID
                  WHERE tu.id = ? AND a.due_date > NOW()
                  ORDER BY a.due_date ASC
                  LIMIT 5";
$upcoming_deadlines = $db_handle->executeSelectPrepared($deadlines_query, "i", [$user_id]);

// Get courses taught by this teacher for the assignment form
$courses_query = "SELECT c.id, c.courseTitle FROM courses c
                 JOIN instructorcourse ic ON ic.courseID = c.courseTitle
                 JOIN users tu ON tu.fullName = ic.userInstructorID
                 WHERE tu.id = ?";
$courses = $db_handle->executeSelectPrepared($courses_query, "i", [$user_id]);
?>

<div class="card" style="margin-bottom: var(--space-5);">
    <div class="card-header">
        <div>
            <h2>Hey, <?php echo htmlspecialchars($teacher['fullName']); ?></h2>
        </div>
    </div>
    <div class="form-grid">
        <div>
            <h4>Academic Calendar</h4>
            <div class="mini-calendar" id="mini-calendar"></div>
        </div>
        <div>
            <h4>Upcoming Deadlines</h4>
            <div class="deadlines-list">
                <?php if (!empty($upcoming_deadlines)): ?>
                    <?php foreach ($upcoming_deadlines as $deadline): ?>
                        <div class="deadline-item">
                            <div class="deadline-date">
                                <span class="day"><?php echo date('d', strtotime($deadline['deadline'])); ?></span>
                                <span class="month"><?php echo date('M', strtotime($deadline['deadline'])); ?></span>
                            </div>
                            <div class="deadline-details">
                                <h5><?php echo htmlspecialchars($deadline['title']); ?></h5>
                                <p><?php echo htmlspecialchars($deadline['courseTitle']); ?></p>
                                <small><?php echo date('h:i A', strtotime($deadline['deadline'])); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-deadlines">No upcoming deadlines</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Assignment Submissions</h2>
        <div class="card-actions">
            <button type="button" class="btn btn-primary" id="showForm"><i class="fas fa-plus"></i> New Assignment</button>
        </div>
    </div>

    <?php if (!empty($submissions)): ?>
        <div class="table-wrap">
            <table id="table1" class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Assignment</th>
                        <th>Course</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="ajax-response">
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td data-id="submission_id"><?php echo (int)$submission['submission_id']; ?></td>
                            <td data-id="student_name"><?php echo htmlspecialchars($submission['student_name']); ?></td>
                            <td data-id="assignment_title"><?php echo htmlspecialchars($submission['assignment_title']); ?></td>
                            <td data-id="course_name"><?php echo htmlspecialchars($submission['course_name']); ?></td>
                            <td data-id="submitted_at"><?php echo date('Y-m-d H:i', strtotime($submission['submitted_at'])); ?></td>
                            <td data-id="status">
                                <span class="badge <?php echo $submission['status'] === 'graded' ? 'badge-success' : 'badge-info'; ?>">
                                    <?php echo ucfirst($submission['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-icon view-btn" data-id="<?php echo (int)$submission['submission_id']; ?>" title="View"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-icon grade-btn" data-id="<?php echo (int)$submission['submission_id']; ?>" title="Grade"><i class="fas fa-graduation-cap"></i></button>
                                <button class="btn btn-icon del" data-id="<?php echo (int)$submission['submission_id']; ?>" title="Delete"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-tasks empty-icon"></i>
            <h3>No assignment submissions yet</h3>
            <p>Submissions will appear here once students turn in their work.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Add New Assignment Modal -->
<div id="addModal" class="modal-overlay">
    <div class="modal-panel">
        <h2>Create New Assignment</h2>
        <form id="addAssignmentForm" method="post" action="php/create_assignment.php">
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
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required />
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <label for="deadline">Deadline</label>
                <input type="datetime-local" id="deadline" name="deadline" required />
            </div>
            <div class="form-group">
                <label for="max_points">Maximum Points</label>
                <input type="number" id="max_points" name="max_points" min="0" max="100" value="100" required />
            </div>
            <div class="card-actions">
                <button type="submit" class="btn btn-primary" id="addAssignment" name="addAssignment">Create Assignment</button>
                <button type="button" class="btn btn-secondary" id="closeForm" name="closeForm">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- View Assignment Submission Modal -->
<div id="viewModal" class="modal-overlay">
    <div class="modal-panel">
        <h2>View Submission</h2>
        <div id="submissionDetails">
            <div class="loader">Loading...</div>
        </div>
        <div class="card-actions">
            <button type="button" class="btn btn-secondary" id="closeViewModal">Close</button>
        </div>
    </div>
</div>

<!-- Grade Assignment Modal -->
<div id="gradeModal" class="modal-overlay">
    <div class="modal-panel">
        <h2>Grade Assignment</h2>
        <form id="gradeForm" method="post">
            <input type="hidden" id="submission_id" name="submission_id" />
            <div class="form-group">
                <label for="grade_points">Points</label>
                <input type="number" id="grade_points" name="grade_points" min="0" max="100" required />
                <span id="max_points_display">(out of 100)</span>
            </div>
            <div class="form-group">
                <label for="feedback">Feedback</label>
                <textarea id="feedback" name="feedback" rows="4"></textarea>
            </div>
            <div class="card-actions">
                <button type="submit" class="btn btn-primary" id="submitGrade">Submit Grade</button>
                <button type="button" class="btn btn-secondary" id="closeGradeModal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Include the calendar page for events section -->
<div class="calendar-section card" style="margin-top: var(--space-5);">
    <h2>Academic Calendar</h2>
    <div id="calendar"></div>
</div>

<?php 
// Include the footer which contains scripts and closing tags
include_once "php/footer.php"; 
?>
