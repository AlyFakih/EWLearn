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
 * Shared footer hook for dashboard pages that build their own <html>/<head>.
 * Currently a no-op placeholder: each page supplies its own closing
 * </body></html> and page-specific <script> tags after this include.
 */
