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
 * Shared top bar (hamburger + page title + notification bell) for every
 * Teacher Dashboard page. Single source of truth for the topbar markup.
 *
 * Expects $db_handle (DBController) and $user_id to already be set by the
 * including page. Optionally reads $page_title.
 */
require_once __DIR__ . "/../../common/notifications.php";
$__header_notification_manager = new NotificationManager(isset($db_handle) ? $db_handle : null);
$__header_unread_count = $__header_notification_manager->countUnreadNotifications($user_id);
$__header_title = isset($page_title) ? $page_title : 'Teacher Dashboard';
?>
<div class="topbar">
    <div class="topbar-left">
        <button type="button" class="hamburger" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="topbar-title"><?php echo htmlspecialchars($__header_title); ?></h1>
    </div>
    <div class="topbar-right">
        <span class="topbar-date"><?php echo date('l, F j, Y'); ?></span>
        <div class="notification-bell">
            <i class="fas fa-bell"></i>
            <span id="notification-badge" class="notification-count notification-badge"<?php echo $__header_unread_count > 0 ? '' : ' style="display:none"'; ?>><?php echo $__header_unread_count; ?></span>
        </div>
    </div>
</div>
