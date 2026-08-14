<?php
// Server-side admin gate. Must be the first thing on the page so no markup or
// data is emitted before the caller is proven to be a logged-in admin.
// Login lives at ../loginRegister.html: the browser resolves this Location
// against the REQUESTED URL, not this file, so the depth is one level up.
require_once __DIR__ . "/../../core/auth_guard.php";
auth_require_role("admin", "page", "../loginRegister.html");
?>
<div id="boxNotifi"></div>
<script>
  $("#boxNotifi").each(function () {
    $(this).load("./NotifiAdmin.php");
  });
</script>
<div class="datainfo">
  <div class="box">
    <i class="fa-solid fa-book-open-reader"></i>
    <div class="data">
      <p>Students</p>
      <span id="statStudents">...</span>
    </div>
  </div>
  <div class="box">
    <i class="fa-solid fa-chalkboard-user"></i>
    <div class="data">
      <p>Teachers</p>
      <span id="statTeachers">...</span>
    </div>
  </div>
  <div class="box">
    <i class="fa-sharp fa-solid fa-people-group"></i>
    <div class="data">
      <p>Staff</p>
      <span id="statStaff">...</span>
    </div>
  </div>
  <div class="box">
    <i class="fa-solid fa-user"></i>
    <div class="data">
      <p>User</p>
      <span id="statTotalUsers">...</span>
    </div>
  </div>
</div>
<div class="charts">
  <div class="chart ch1">
    <h2>New Enrollments (Past 12 months)</h2>
    <canvas id="lineChart"></canvas>
  </div>
  <div class="chart" id="doughnut-chart">
    <h2>Employess</h2>
    <canvas id="doughnut"></canvas>
  </div>
</div>
