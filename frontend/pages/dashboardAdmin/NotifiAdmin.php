<?php
// Server-side admin gate. Must be the first thing on the page so no markup or
// data is emitted before the caller is proven to be a logged-in admin.
// Login lives at ../loginRegister.html: the browser resolves this Location
// against the REQUESTED URL, not this file, so the depth is one level up.
require_once __DIR__ . "/../../core/auth_guard.php";
auth_require_role("admin", "page", "../loginRegister.html");
?>
<div class="title-info">
  <p>DashBoard</p>
  <div class="icon">
    <i class="fa-solid fa-bell"></i>
    <span id="notifiCount">...</span>
  </div>
  <div class="notifi-box" id="box" style="display: none">
    <h2>Notification: <span id="notifiCount2">...</span></h2>
    <div id="notifiItems">
      <p class="loading-text">Loading...</p>
    </div>
  </div>
</div>
<script src="../../javascript/dashboard/NotifiAdmin.js"></script>
