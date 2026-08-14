<?php
/**
 * Shared server-side authentication & authorization guard.
 *
 * Single source of truth for "who is making this request, and are they allowed
 * to?". Every protected page and every protected endpoint must include this
 * file FIRST (before any output and before any other session_start()) and then
 * call auth_require_login() or auth_require_role().
 *
 * Design notes:
 *  - The role is ALWAYS re-read from the database on every request. The session
 *    only carries the user id; a role value in the session (or anywhere the
 *    client can influence) is never used for an authorization decision. This
 *    means a user demoted or deleted in the database loses access immediately
 *    instead of keeping it until their session happens to expire.
 *  - Paths are anchored with __DIR__ because this file is included from several
 *    different directory depths (backend/, frontend/pages/dashboardAdmin/, ...)
 *    and PHP resolves bare relative includes against the top-level script's
 *    directory, not the including file's.
 *  - Callers declare whether they are an "api" (JSON 401/403) or a "page"
 *    (302 redirect to login) rather than this file trying to sniff the request,
 *    so the failure mode is always explicit and predictable.
 */

require_once __DIR__ . '/DBController.php';

/** Seconds of inactivity after which a session is considered expired. */
const AUTH_IDLE_TIMEOUT = 1800;      // 30 minutes

/** Maximum total session lifetime regardless of activity. */
const AUTH_ABSOLUTE_TIMEOUT = 43200; // 12 hours

/**
 * Start the session with hardened cookie settings.
 *
 * Must run before any session_start() elsewhere, otherwise the cookie flags
 * below are silently ignored (PHP applies them at session start only).
 */
function auth_session_boot()
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    // Reject session IDs that the server never issued (session fixation).
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $params = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        // Only mark Secure when actually on HTTPS - forcing it on plain HTTP
        // (this app's local/XAMPP deployment) would stop the cookie being sent
        // at all and break login entirely.
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,   // JavaScript (incl. XSS payloads) cannot read it
        'samesite' => 'Lax',  // not sent on cross-site POSTs
    ]);

    session_start();
}

/**
 * Destroy the current session completely (data + cookie).
 * Shared by every logout endpoint so they cannot drift apart.
 */
function auth_destroy_session()
{
    auth_session_boot();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => isset($p['samesite']) && $p['samesite'] !== '' ? $p['samesite'] : 'Lax',
        ]);
    }

    session_destroy();
}

/**
 * Establish a freshly authenticated session for $userId.
 * Called by login only, after the password has been verified.
 */
function auth_login_session($userId, $role, $fullName)
{
    auth_session_boot();

    // New session ID on privilege change defeats session fixation: any ID an
    // attacker planted in the victim's browser before login is discarded here.
    session_regenerate_id(true);

    $_SESSION['user_id']     = (int) $userId;
    $_SESSION['role']        = $role;      // convenience/UX only - never trusted for authz
    $_SESSION['fullName']    = $fullName;
    $_SESSION['created_at']  = time();
    $_SESSION['last_active'] = time();
}

/**
 * Emit a denial and stop. Never leaks why beyond the coarse reason.
 *
 * @param int    $code     401 (not authenticated) or 403 (wrong role)
 * @param string $mode     'api' => JSON body; 'page' => redirect to login
 * @param string $loginUrl Redirect target for 'page' mode. Resolved by the
 *                         BROWSER relative to the requested URL, so callers
 *                         must pass a path correct for their own URL depth.
 */
function auth_deny($code, $mode = 'api', $loginUrl = null)
{
    if ($mode === 'page') {
        header('Location: ' . ($loginUrl ?: '/'));
        exit;
    }

    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    $message = $code === 403 ? 'Forbidden' : 'Unauthorized';
    // Several existing front-end handlers read `status`, others read `success`
    // or `message`; emit all three so denials render sanely everywhere.
    echo json_encode([
        'success' => false,
        'status'  => 'error',
        'error'   => $message,
        'message' => $message,
    ]);
    exit;
}

/**
 * Resolve the authenticated user from the session, re-validated against the DB.
 *
 * @return array|null The users row, or null if not authenticated / expired /
 *                    the account no longer exists.
 */
function auth_user()
{
    static $cached = null;
    static $resolved = false;
    if ($resolved) {
        return $cached;
    }
    $resolved = true;

    auth_session_boot();

    if (empty($_SESSION['user_id'])) {
        return $cached = null;
    }

    $now = time();

    // Idle timeout.
    if (isset($_SESSION['last_active']) && ($now - $_SESSION['last_active']) > AUTH_IDLE_TIMEOUT) {
        auth_destroy_session();
        return $cached = null;
    }

    // Absolute lifetime.
    if (isset($_SESSION['created_at']) && ($now - $_SESSION['created_at']) > AUTH_ABSOLUTE_TIMEOUT) {
        auth_destroy_session();
        return $cached = null;
    }

    // Sessions created before this guard existed have no timestamps; adopt them
    // rather than logging everyone out on deploy.
    if (!isset($_SESSION['created_at'])) {
        $_SESSION['created_at'] = $now;
    }
    $_SESSION['last_active'] = $now;

    // The authoritative identity+role check: the account must still exist.
    $db = new DBController();
    $rows = $db->executeSelectPrepared(
        "SELECT id, role, fullName, email FROM users WHERE id = ?",
        "i",
        [(int) $_SESSION['user_id']]
    );

    if (empty($rows)) {
        // Deleted user holding a live session.
        auth_destroy_session();
        return $cached = null;
    }

    // Keep the convenience copy in step with the database.
    $_SESSION['role'] = $rows[0]['role'];

    return $cached = $rows[0];
}

/** @return int|null Authenticated user id, or null. */
function auth_user_id()
{
    $u = auth_user();
    return $u ? (int) $u['id'] : null;
}

/** @return string|null Authenticated role read from the DB, or null. */
function auth_role()
{
    $u = auth_user();
    return $u ? $u['role'] : null;
}

/**
 * Require any authenticated user. Denies with 401 / login redirect otherwise.
 * @return array The authenticated user row.
 */
function auth_require_login($mode = 'api', $loginUrl = null)
{
    $user = auth_user();
    if (!$user) {
        auth_deny(401, $mode, $loginUrl);
    }
    return $user;
}

/**
 * Require an authenticated user holding one of $roles.
 *
 * 401 when not logged in, 403 when logged in as the wrong role - the
 * distinction matters so a legitimate user with an expired session gets sent
 * back to login instead of a dead end.
 *
 * @param string|string[] $roles
 * @return array The authenticated user row.
 */
function auth_require_role($roles, $mode = 'api', $loginUrl = null)
{
    $user = auth_require_login($mode, $loginUrl);

    $allowed = is_array($roles) ? $roles : [$roles];
    if (!in_array($user['role'], $allowed, true)) {
        auth_deny(403, $mode, $loginUrl);
    }

    return $user;
}
