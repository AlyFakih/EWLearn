<?php
// Page fragment: only ever included from php/header.php (or php/footer.php),
// after the instructor guard has already run. Refuse direct HTTP access - on
// its own this file has no $db_handle/$user_id and would emit PHP warnings and
// a stack trace containing absolute server paths to an anonymous caller.
if (!function_exists("auth_user_id") || !auth_user_id()) {
    http_response_code(403);
    exit;
}
?>
<?php
/**
 * Shared left navigation for every Teacher Dashboard page. Single source
 * of truth for the sidebar markup - all pages now include this instead of
 * duplicating the nav inline.
 *
 * Expects $db_handle (DBController) and $user_id to already be set by the
 * including page.
 */
$__sidebar_teacher = $db_handle->executeSelectPrepared("SELECT fullName, image FROM users WHERE id = ?", "i", [$user_id]);
$__sidebar_teacher_name = !empty($__sidebar_teacher) ? $__sidebar_teacher[0]['fullName'] : 'Teacher';
// users.image is stored relative to frontend/images/ (same convention used on
// profile-dashboard.php) - this used to be hardcoded to a generic placeholder
// regardless of who was logged in, so it never reflected the real photo.
$__sidebar_teacher_image = (!empty($__sidebar_teacher) && !empty($__sidebar_teacher[0]['image']))
    ? '../../images/' . $__sidebar_teacher[0]['image']
    : './images/logo.jpg';
$__sidebar_current = basename($_SERVER['SCRIPT_NAME']);

function __sidebar_active($page, $current) {
    return $page === $current ? ' active' : '';
}
?>
<div class="sidebar-backdrop"></div>
<nav class="sidebar">
    <div class="sidebar-profile">
        <div class="img-box">
            <img src="<?php echo htmlspecialchars($__sidebar_teacher_image); ?>" alt="Profile photo">
        </div>
        <h2><?php echo htmlspecialchars($__sidebar_teacher_name); ?></h2>
    </div>
    <ul class="sidebar-nav">
        <li>
            <a class="<?php echo trim('nav-link' . __sidebar_active('profile-dashboard.php', $__sidebar_current)); ?>" href="./profile-dashboard.php">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </li>
        <li>
            <a class="<?php echo trim('nav-link' . __sidebar_active('course-dashboard.php', $__sidebar_current)); ?>" href="./course-dashboard.php">
                <i class="fas fa-book"></i>
                <span>Courses</span>
            </a>
        </li>
        <li>
            <a class="<?php echo trim('nav-link' . __sidebar_active('student-dashboard.php', $__sidebar_current)); ?>" href="./student-dashboard.php">
                <i class="fas fa-user-group"></i>
                <span>Students</span>
            </a>
        </li>
        <li>
            <a class="<?php echo trim('nav-link' . __sidebar_active('exam-dashboard.php', $__sidebar_current)); ?>" href="./exam-dashboard.php">
                <i class="fas fa-pencil-alt"></i>
                <span>Exams</span>
            </a>
        </li>
        <li>
            <a class="<?php echo trim('nav-link' . __sidebar_active('grades-dashboard.php', $__sidebar_current)); ?>" href="./grades-dashboard.php">
                <i class="fas fa-graduation-cap"></i>
                <span>Grades</span>
            </a>
        </li>
        <li>
            <a class="<?php echo trim('nav-link' . __sidebar_active('assignment-dashboard.php', $__sidebar_current)); ?>" href="./assignment-dashboard.php">
                <i class="fas fa-tasks"></i>
                <span>Assignments</span>
            </a>
        </li>
        <li>
            <a class="<?php echo trim('nav-link' . __sidebar_active('attendence-dashboard.php', $__sidebar_current)); ?>" href="./attendence-dashboard.php">
                <i class="fas fa-user-check"></i>
                <span>Attendance</span>
            </a>
        </li>
    </ul>
    <div class="sidebar-logout">
        <a href="php/logout.php">
            <i class="fas fa-sign-out"></i>
            <span>Log Out</span>
        </a>
    </div>
</nav>
