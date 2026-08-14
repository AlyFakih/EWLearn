<?php
// Server-side admin gate. Must be the first thing on the page so no markup or
// data is emitted before the caller is proven to be a logged-in admin.
// The redirect target must be an absolute path: this page is also served at
// the clean URL /admin (and /admin/<section>) via an .htaccess internal
// rewrite, and auth_guard.php resolves it as the browser would - relative to
// the REQUESTED URL, not this file's real location. A relative "../..." path
// works when requested at the real nested path but 404s when requested at
// the shallow clean URL, since "../" then resolves against the site root.
require_once __DIR__ . "/../../core/auth_guard.php";
auth_require_role("admin", "page", "/login");
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Also served at the clean URLs /admin and /admin/<section> via an
         .htaccess internal rewrite. Without this, every relative
         href/src below - AND dash.js's own relative AJAX calls like
         "students.php" - resolve against the shallow clean URL instead
         of this file's real location, 404ing everything including
         dash.js itself. Must stay before every relative href/src below. -->
    <base href="/frontend/pages/dashboardAdmin/" />
    <title>DashBoard</title>
    <!-- <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65"
      crossorigin="anonymous"
    /> -->

    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
      integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA=="
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../../styles/DashBoards/adminstyle.css" />
    <link rel="stylesheet" href="../../styles/DashBoards/teacher.css" />
    <link rel="stylesheet" href="../../styles/DashBoards/admin-enhance.css" />
    <script src="../../javascript/dashboard/dash.js"></script>
  </head>
  <body>
    <!-- ! Menu -------------- -->
    <div class="menu">
      <ul>
        <li class="profile">
          <div class="img-pro">
            <img src="../../assets/images/member1.jpg" alt="profileImages" />
          </div>
          <h2>AliMallah</h2>
        </li>

        <li data-section="home">
          <a href="" aria-label="Home"
            ><i class="fa-solid fa-house"></i>
            <p>Home</p></a
          >
        </li>
        <li data-section="dash">
          <a class="active" href="" aria-label="DashBoard"
            ><i class="fa-solid fa-chart-line"></i>
            <p>DashBoard</p></a
          >
        </li>
        <li data-section="teacher">
          <a href="" aria-label="Instructor"
            ><i class="fa-solid fa-chalkboard-user"></i>
            <p>Instructor</p></a
          >
        </li>
        <li data-section="students">
          <a href="" aria-label="Students"
            ><i class="fa-solid fa-book-open-reader"></i>
            <p>Students</p></a
          >
        </li>
        <li data-section="coursesAdmin">
          <a href="" aria-label="Courses"
            ><i class="fa-solid fa-book"></i>
            <p>Courses</p></a
          >
        </li>

        <li data-section="eventsAdmin">
          <a href="" aria-label="Events"
            ><i class="fa-solid fa-calendar-days"></i>
            <p>Events</p></a
          >
        </li>
        <li data-section="staff">
          <a href="" aria-label="Staff"
            ><i class="fa-sharp fa-solid fa-people-group"></i>
            <p>Staff</p></a
          >
        </li>
        <li data-section="messages">
          <a href="" aria-label="Message"
            ><i class="fa-solid fa-message"></i>
            <p>Message</p></a
          >
        </li>
        <li class="logout-item">
          <a class="logout" href="" onClick="removeLoginState(); return false;" aria-label="Log out"
            ><i class="fa-solid fa-right-from-bracket"></i>
            <p>Logout</p></a
          >
        </li>
      </ul>
    </div>

    <!-- ! Header & Dashboard Content -------------- -->
    <div class="bodydash" id="dashboard-content">
      <!-- Content will be dynamically loaded here -->
    </div>

    <!-- !  000000000 -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  </body>
</html>
