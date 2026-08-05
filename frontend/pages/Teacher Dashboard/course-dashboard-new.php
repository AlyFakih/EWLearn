<?php
// Set page variables for header
$page_title = "Course Dashboard";
$current_page = "courses";
$page_css = "course-dashboard";
$page_js = "course";

// Include the header which handles session, DB connection and common includes
include_once "php/header.php";

// Get courses taught by this teacher with prepared statements
$query = "SELECT c.*, COUNT(sc.id) as enrolled_students 
         FROM courses c 
         LEFT JOIN studentcourse sc ON c.id = sc.course_id 
         WHERE c.teacher_id = ? 
         GROUP BY c.id 
         ORDER BY c.id DESC";
$courses = $db_handle->executeSelectPrepared($query, "i", [$user_id]);
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
                    <h4>Course Updates</h4>
                    <div class="activity-list">
                        <?php
                        $recent_query = "SELECT c.courseTitle, c.courseCode, c.lastUpdated
                                         FROM courses c
                                         WHERE c.teacher_id = ?
                                         ORDER BY c.lastUpdated DESC LIMIT 5";
                        $recent_updates = $db_handle->executeSelectPrepared($recent_query, "i", [$user_id]);
                        
                        if (!empty($recent_updates)) {
                            foreach ($recent_updates as $update) {
                                echo '<div class="activity-item">';
                                echo '<div class="activity-date">' . date('d M', strtotime($update['lastUpdated'])) . '</div>';
                                echo '<div class="activity-details">';
                                echo '<h5>' . $update['courseTitle'] . '</h5>';
                                echo '<p>' . $update['courseCode'] . '</p>';
                                echo '</div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p class="no-activity">No recent course updates</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <section class="main">
        <div class="main-top">
            <h1><i class="fas fa-book"></i> Courses</h1>
        </div>

        <section class="courses">
            <div class="courses-list">
                <div class="cont">
                    <h1>My Courses</h1>
                    <a href="#" class="customButton" id="showForm">
                        <button>Add Course <i class="fas fa-plus"></i></button>
                    </a>
                </div>

                <div class="course-cards">
                    <?php if(!empty($courses)): ?>
                        <?php foreach($courses as $course): ?>
                        <div class="course-card" data-id="<?php echo $course['id']; ?>">
                            <div class="course-header">
                                <h3><?php echo $course['courseTitle']; ?></h3>
                                <span class="course-code"><?php echo $course['courseCode']; ?></span>
                            </div>
                            <div class="course-body">
                                <p class="course-description"><?php echo $course['courseDescription']; ?></p>
                                <div class="course-stats">
                                    <div class="stat">
                                        <i class="fas fa-user-graduate"></i>
                                        <span><?php echo $course['enrolled_students']; ?> Students</span>
                                    </div>
                                    <div class="stat">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo date('Y-m-d', strtotime($course['startDate'])); ?> to <?php echo date('Y-m-d', strtotime($course['endDate'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="course-footer">
                                <button class="view-course" data-id="<?php echo $course['id']; ?>">View Details</button>
                                <button class="edit-course" data-id="<?php echo $course['id']; ?>"><i class="fas fa-edit"></i></button>
                                <button class="delete-course" data-id="<?php echo $course['id']; ?>"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-courses">
                            <p>No courses found. Click "Add Course" to create your first course.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Add Course Modal -->
        <div id="addModal" class="modal">
            <h2>Add New Course</h2>
            <form id="addForm" method="post">
                <div>
                    <label for="courseTitle">Course Title:</label>
                    <input type="text" id="courseTitle" name="courseTitle" required />
                </div>
                <div>
                    <label for="courseCode">Course Code:</label>
                    <input type="text" id="courseCode" name="courseCode" required />
                </div>
                <div>
                    <label for="courseDescription">Description:</label>
                    <textarea id="courseDescription" name="courseDescription" rows="3"></textarea>
                </div>
                <div>
                    <label for="credits">Credits:</label>
                    <input type="number" id="credits" name="credits" min="1" max="6" required />
                </div>
                <div>
                    <label for="semester">Semester:</label>
                    <select id="semester" name="semester">
                        <option value="Fall">Fall</option>
                        <option value="Spring">Spring</option>
                        <option value="Summer">Summer</option>
                        <option value="Winter">Winter</option>
                    </select>
                </div>
                <div>
                    <label for="startDate">Start Date:</label>
                    <input type="date" id="startDate" name="startDate" required />
                </div>
                <div>
                    <label for="endDate">End Date:</label>
                    <input type="date" id="endDate" name="endDate" required />
                </div>
                <div class="buttons">
                    <button type="button" id="adddata" name="adddata">Add</button>
                    <button type="button" id="closeForm" name="closeForm">Cancel</button>
                </div>
            </form>
        </div>

        <!-- View Course Modal -->
        <div id="viewModal" class="modal">
            <h2>Course Details</h2>
            <div id="courseDetails">
                <!-- Course details will be loaded here via AJAX -->
            </div>
            <div class="buttons">
                <button type="button" id="closeViewModal">Close</button>
            </div>
        </div>

        <!-- Edit Course Modal -->
        <div id="editModal" class="modal">
            <h2>Edit Course</h2>
            <form id="editForm" method="post">
                <input type="hidden" id="editCourseId" name="id">
                <div>
                    <label for="editCourseTitle">Course Title:</label>
                    <input type="text" id="editCourseTitle" name="courseTitle" required />
                </div>
                <div>
                    <label for="editCourseCode">Course Code:</label>
                    <input type="text" id="editCourseCode" name="courseCode" required />
                </div>
                <div>
                    <label for="editCourseDescription">Description:</label>
                    <textarea id="editCourseDescription" name="courseDescription" rows="3"></textarea>
                </div>
                <div>
                    <label for="editCredits">Credits:</label>
                    <input type="number" id="editCredits" name="credits" min="1" max="6" required />
                </div>
                <div>
                    <label for="editSemester">Semester:</label>
                    <select id="editSemester" name="semester">
                        <option value="Fall">Fall</option>
                        <option value="Spring">Spring</option>
                        <option value="Summer">Summer</option>
                        <option value="Winter">Winter</option>
                    </select>
                </div>
                <div>
                    <label for="editStartDate">Start Date:</label>
                    <input type="date" id="editStartDate" name="startDate" required />
                </div>
                <div>
                    <label for="editEndDate">End Date:</label>
                    <input type="date" id="editEndDate" name="endDate" required />
                </div>
                <div class="buttons">
                    <button type="button" id="updateCourse">Update</button>
                    <button type="button" id="closeEditForm">Cancel</button>
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
