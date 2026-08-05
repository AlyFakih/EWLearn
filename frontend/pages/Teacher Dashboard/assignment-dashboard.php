<?php
// Set page variables for header
$page_title = "Assignment Dashboard";
$current_page = "assignments";
$page_css = "assignments-dashboard";
$page_js = "assignment";

// Include the header which handles session, DB connection and common includes
include_once "php/header.php";

// Get assignments with prepared statements
$query = "SELECT s.id as submission_id, s.student_id, u.full_name as student_name, 
          a.id as assignment_id, a.title as assignment_title, c.courseTitle as course_name, 
          s.submitted_at, s.status, s.file_path 
          FROM assignment_submissions s
          JOIN users u ON s.student_id = u.id
          JOIN assignment a ON s.assignment_id = a.id
          JOIN courses c ON a.course_id = c.id
          WHERE c.teacher_id = ?
          ORDER BY s.submitted_at DESC";
$submissions = $db_handle->executeSelectPrepared($query, "i", [$user_id]);

// Get upcoming assignment deadlines
$deadlines_query = "SELECT a.id, a.title, a.deadline, c.courseTitle 
                  FROM assignment a
                  JOIN courses c ON a.course_id = c.id
                  WHERE c.teacher_id = ? AND a.deadline > NOW()
                  ORDER BY a.deadline ASC
                  LIMIT 5";
$upcoming_deadlines = $db_handle->executeSelectPrepared($deadlines_query, "i", [$user_id]);

// Get courses taught by this teacher for the assignment form
$courses_query = "SELECT id, courseTitle FROM courses WHERE teacher_id = ?";
$courses = $db_handle->executeSelectPrepared($courses_query, "i", [$user_id]);
?>

<div class="container">
    <div class="cont-skill">
        <aside>
            <!-- Profile Section -->
            <div class="profilee">
                <div class="top">
                    <div class="profilee-photo">
                        <img src="./images/teacher.jpg" alt="Teacher profile image" />
                    </div>
                    <div class="para-detials">
                        <p>Hey, <b><?php echo $teacher['full_name']; ?></b></p>
                        <small class="text-muted"><?php echo $teacher['id']; ?></small>
                    </div>
                </div>
                
                <!-- Calendar Widget -->
                <div class="calendar-widget">
                    <h4>Academic Calendar</h4>
                    <div class="mini-calendar" id="mini-calendar"></div>
                </div>
                
                <!-- Upcoming Assignment Deadlines -->
                <div class="upcoming-deadlines">
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
                                        <h5><?php echo $deadline['title']; ?></h5>
                                        <p><?php echo $deadline['courseTitle']; ?></p>
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
        </aside>
    </div>

    <section class="main">
        <div class="main-top">
            <h1><i class="fas fa-tasks"></i> Assignments</h1>
        </div>

        <section class="attendance">
            <div class="attendance-list">
                <div class="cont">
                    <h1>Assignment Submissions</h1>
                    <a href="#" class="customButton" id="showForm">
                        <button>New Assignment <i class="fas fa-plus"></i></button>
                    </a>
                </div>

                <div id="list-product">
                    <table class="table-attend">
                        <thead>
                            <tr>
                                <th><strong>ID</strong></th>
                                <th><strong>Student</strong></th>
                                <th><strong>Assignment</strong></th>
                                <th><strong>Course</strong></th>
                                <th><strong>Date</strong></th>
                                <th><strong>Status</strong></th>
                                <th><strong>Actions</strong></th>
                            </tr>
                        </thead>

                        <tbody id="ajax-response">
                            <?php if (!empty($submissions)): ?>
                                <?php foreach ($submissions as $submission): ?>
                                    <tr>
                                        <td data-id="submission_id"><?php echo $submission['submission_id']; ?></td>
                                        <td data-id="student_name"><?php echo $submission['student_name']; ?></td>
                                        <td data-id="assignment_title"><?php echo $submission['assignment_title']; ?></td>
                                        <td data-id="course_name"><?php echo $submission['course_name']; ?></td>
                                        <td data-id="submitted_at"><?php echo date('Y-m-d H:i', strtotime($submission['submitted_at'])); ?></td>
                                        <td data-id="status">
                                            <span class="status-badge status-<?php echo $submission['status']; ?>">
                                                <?php echo ucfirst($submission['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="view-btn" data-id="<?php echo $submission['submission_id']; ?>">
                                                <i class="fas fa-eye" aria-hidden="true"></i>
                                            </button>
                                            <button class="grade-btn" data-id="<?php echo $submission['submission_id']; ?>">
                                                <i class="fas fa-graduation-cap" aria-hidden="true"></i>
                                            </button>
                                            <button class="del" data-id="<?php echo $submission['submission_id']; ?>">
                                                <i class="fas fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-data">No assignment submissions found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Add New Assignment Modal -->
        <div id="addModal" class="modal">
            <h2>Create New Assignment</h2>
            <form id="addAssignmentForm" method="post" action="php/create_assignment.php">
                <div>
                    <label for="course_id">Course:</label>
                    <select id="course_id" name="course_id" required>
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>"><?php echo $course['courseTitle']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" required />
                </div>
                <div>
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="3" required></textarea>
                </div>
                <div>
                    <label for="deadline">Deadline:</label>
                    <input type="datetime-local" id="deadline" name="deadline" required />
                </div>
                <div>
                    <label for="max_points">Maximum Points:</label>
                    <input type="number" id="max_points" name="max_points" min="0" max="100" value="100" required />
                </div>
                <div class="buttons">
                    <button type="submit" id="addAssignment" name="addAssignment">Create Assignment</button>
                    <button type="button" id="closeForm" name="closeForm">Cancel</button>
                </div>
            </form>
        </div>

        <!-- View Assignment Submission Modal -->
        <div id="viewModal" class="modal">
            <h2>View Submission</h2>
            <div id="submissionDetails">
                <div class="loader">Loading...</div>
            </div>
            <div class="buttons">
                <button type="button" id="closeViewModal">Close</button>
            </div>
        </div>

        <!-- Grade Assignment Modal -->
        <div id="gradeModal" class="modal">
            <h2>Grade Assignment</h2>
            <form id="gradeForm" method="post">
                <input type="hidden" id="submission_id" name="submission_id" />
                <div>
                    <label for="grade_points">Points:</label>
                    <input type="number" id="grade_points" name="grade_points" min="0" max="100" required />
                    <span id="max_points_display">(out of 100)</span>
                </div>
                <div>
                    <label for="feedback">Feedback:</label>
                    <textarea id="feedback" name="feedback" rows="4"></textarea>
                </div>
                <div class="buttons">
                    <button type="submit" id="submitGrade">Submit Grade</button>
                    <button type="button" id="closeGradeModal">Cancel</button>
                </div>
            </form>
        </div>
    </section>
</div>

<!-- Include the calendar page for events section -->
<div class="calendar-section" style="margin: 30px;">
    <h2>Academic Calendar</h2>
    <div id="calendar"></div>
</div>

<?php 
// Include the footer which contains scripts and closing tags
include_once "php/footer.php"; 
?>
