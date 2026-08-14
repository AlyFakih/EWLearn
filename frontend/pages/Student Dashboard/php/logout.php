<?php
// Full server-side session teardown (data + cookie), shared with every other
// logout endpoint so they cannot drift apart. Deliberately does NOT require a
// live session: logging out must always succeed, even from an expired one.
require_once __DIR__ . "/../../../core/auth_guard.php";

auth_destroy_session();

// Resolved by the browser against the requested URL (.../Student Dashboard/php/logout.php)
header("Location: ../../loginRegister.html");
exit();
