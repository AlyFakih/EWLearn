<?php
// Admin logout. Previously the admin dashboard had NO server-side logout at
// all - "Logout" only cleared a localStorage flag, leaving the PHP session
// fully alive, so anyone returning to the dashboard URL was still admin.
// Deliberately does not require a live session: logout must always succeed.
require_once __DIR__ . "/../../core/auth_guard.php";

auth_destroy_session();

// Resolved by the browser against the requested URL (.../dashboardAdmin/logout.php)
header("Location: ../loginRegister.html");
exit();
