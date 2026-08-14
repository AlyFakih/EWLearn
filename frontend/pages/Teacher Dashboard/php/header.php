<?php
// Server-side gate for every Teacher Dashboard page. Enforces authentication,
// the instructor role (re-read from the database, never from client state),
// session idle/absolute expiry, and deleted-account revocation.
// "../loginRegister.html" is resolved by the BROWSER against the requested
// page URL (all teacher pages live one level below pages/), not against this file.
require_once __DIR__ . "/../../../core/auth_guard.php";
auth_require_role("instructor", "page", "../loginRegister.html");

// Include database controller and required classes
require_once __DIR__ . "/../../../core/DBController.php";
require_once __DIR__ . "/../../common/notifications.php";
require_once __DIR__ . "/../../common/calendar.php";

$db_handle = new DBController();
$notification_manager = new NotificationManager($db_handle);
$calendar_manager = new CalendarManager($db_handle);

// Get teacher information
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ? AND role = 'instructor'";
$result = $db_handle->executeSelectPrepared($query, "i", [$user_id]);

if (empty($result)) {
    header("Location: ../loginRegister.html");
    exit();
}

$teacher = $result[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Teacher Dashboard'; ?> - EWLearn</title>

  <!-- Shared design system -->
  <link rel="stylesheet" href="./css/dashboard-theme.css">
  <?php if (isset($page_css) && !empty($page_css)): ?>
  <link rel="stylesheet" href="./css/<?php echo htmlspecialchars($page_css); ?>.css">
  <?php endif; ?>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">

  <!-- Include notification and calendar styles (also loads jQuery) -->
  <?php include_once __DIR__ . "/../../common/header_includes.php"; ?>
</head>
<body>
  <div class="app-shell">
    <?php include __DIR__ . "/../components/sidebar.php"; ?>
    <div class="main-col">
      <?php include __DIR__ . "/../components/header.php"; ?>
      <main class="page-content">
