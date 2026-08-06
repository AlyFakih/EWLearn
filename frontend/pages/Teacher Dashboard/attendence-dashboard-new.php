<?php
// Set page variables for header
$page_title = "Attendance Dashboard";
$current_page = "attendance";
$page_css = "attendence-dashboard";
$page_js = "attendence";

// Include the header which handles session, DB connection and common includes
include_once "php/header.php";

// Get attendance with prepared statements
$query = "SELECT a.id, a.student_id, u.full_name as student_name, 
         c.courseTitle as course_name, a.date, a.status, a.notes 
         FROM attendance a
         JOIN users u ON a.student_id = u.id
         JOIN courses c ON a.course_id = c.id
         ORDER BY a.date DESC";
$attendanceResult = $db_handle->executeSelectPrepared($query);

// Get courses taught by this teacher for the form
$courses_query = "SELECT id, courseTitle FROM courses WHERE teacher_id = ?";
$courses = $db_handle->executeSelectPrepared($courses_query, "i", [$user_id]);

// Get students for the form
$students_query = "SELECT id, full_name FROM users WHERE role = 'student'";
$students = $db_handle->executeSelectPrepared($students_query);
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
                
                <!-- Recent Activity -->
                <div class="recent-activity">
                    <h4>Recent Attendance</h4>
                    <div class="activity-list">
                        <?php
                        $recent_query = "SELECT a.date, u.full_name, a.status, c.courseTitle 
                                         FROM attendance a
                                         JOIN users u ON a.student_id = u.id
                                         JOIN courses c ON a.course_id = c.id
                                         WHERE c.teacher_id = ?
                                         ORDER BY a.date DESC LIMIT 5";
                        $recent_attendance = $db_handle->executeSelectPrepared($recent_query, "i", [$user_id]);
                        
                        if (!empty($recent_attendance)) {
                            foreach ($recent_attendance as $recent) {
                                echo '<div class="activity-item">';
                                echo '<div class="activity-date">' . date('d M', strtotime($recent['date'])) . '</div>';
                                echo '<div class="activity-details">';
                                echo '<h5>' . $recent['full_name'] . '</h5>';
                                echo '<p>' . $recent['courseTitle'] . '</p>';
                                echo '<span class="status-badge status-' . strtolower($recent['status']) . '">' . $recent['status'] . '</span>';
                                echo '</div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p class="no-activity">No recent attendance records</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <section class="main">
        <div class="main-top">
            <h1><i class="fas fa-user-check"></i> Attendance</h1>
        </div>

        <section class="attendance">
            <div class="attendance-list">
                <div class="cont">
                    <h1>Attendance Records</h1>
                    <a href="#" class="customButton" id="showForm">
                        <button>Add Attendance <i class="fas fa-plus"></i></button>
                    </a>
                </div>

                <div id="list-product">
                    <table id="table1" cellpadding="10" cellspacing="1" class="table-attend">
                        <thead>
                            <tr>
                                <th><strong>ID</strong></th>
                                <th><strong>Student</strong></th>
                                <th><strong>Course</strong></th>
                                <th><strong>Date</strong></th>
                                <th><strong>Status</strong></th>
                                <th><strong>Notes</strong></th>
                                <th><strong>Actions</strong></th>
                            </tr>
                        </thead>

                        <tbody id="ajax-response">
                            <?php if (!empty($attendanceResult)): ?>
                                <?php foreach ($attendanceResult as $record): ?>
                                    <tr>
                                        <td data-id="id"><?php echo $record['id']; ?></td>
                                        <td data-id="student_name"><?php echo $record['student_name']; ?></td>
                                        <td data-id="course_name"><?php echo $record['course_name']; ?></td>
                                        <td data-id="date"><?php echo date('Y-m-d', strtotime($record['date'])); ?></td>
                                        <td data-id="status">
                                            <span class="status-badge status-<?php echo strtolower($record['status']); ?>">
                                                <?php echo $record['status']; ?>
                                            </span>
                                        </td>
                                        <td data-id="notes"><?php echo $record['notes']; ?></td>
                                        <td>
                                            <button class="edit"><i class="fas fa-pencil" aria-hidden="true"></i></button>
                                            <button class="save" style="display:none;" data-id="<?php echo $record['id']; ?>">
                                                <i class="fas fa-check" aria-hidden="true"></i>
                                            </button>
                                            <button class="cancel" style="display:none;">
                                                <i class="fas fa-times" aria-hidden="true"></i>
                                            </button>
                                            <button class="del" data-id="<?php echo $record['id']; ?>">
                                                <i class="fas fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-data">No attendance records found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Add Attendance Modal -->
        <div id="addModal" class="modal">
            <h2>Add Attendance Record</h2>
            <form id="addForm" method="post">
                <div>
                    <label for="student_id">Student:</label>
                    <select id="student_id" name="student_id" required>
                        <option value="">Select Student</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>"><?php echo $student['full_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
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
                    <label for="date">Date:</label>
                    <input type="date" id="date" name="date" required />
                </div>
                <div>
                    <label for="status">Status:</label>
                    <select id="status" name="status">
                        <option value="Present">Present</option>
                        <option value="Absent">Absent</option>
                        <option value="Late">Late</option>
                        <option value="Excused">Excused</option>
                    </select>
                </div>
                <div>
                    <label for="notes">Notes:</label>
                    <textarea id="notes" name="notes" rows="3"></textarea>
                </div>
                <div class="buttons">
                    <button type="button" id="adddata" name="adddata">Add</button>
                    <button type="button" id="closeForm" name="closeForm">Cancel</button>
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
