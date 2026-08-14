<?php
// Server-side admin gate. Must be the first thing on the page so no markup or
// data is emitted before the caller is proven to be a logged-in admin.
// Login lives at ../loginRegister.html: the browser resolves this Location
// against the REQUESTED URL, not this file, so the depth is one level up.
require_once __DIR__ . "/../../core/auth_guard.php";
auth_require_role("admin", "page", "../loginRegister.html");
?>
<link rel="stylesheet" href="../../styles/DashBoards/messages.css" />
<div id="boxNotifi"></div>
<script>
  $("#boxNotifi").each(function () {
    $(this).load("./NotifiAdmin.php");
  });
</script>

<div class="container conMessage">
  <div class="message-card">
    <div class="logo-img">
      <img
        src="../../assets/images/ph-student-fill.png"
        alt="logo-img"
        width="150px"
      />
    </div>
    <div class="title">
      <h1>Email Users</h1>
    </div>
    <form class="formMessage" action="" method="post">
      <div class="Choose">
        <select name="role" class="selectRole">
          <option value="allUsers">All Users</option>
          <option value="student">Student</option>
          <option value="instructor">Instructor</option>
          <option value="staff">Staff</option>
          <option value="admin">Admin</option>
        </select>
        <input
          type="text"
          name="search"
          id="search"
          placeholder="Search"
          autocomplete="off"
        />
      </div>
      <div class="twotype">
        <div class="chooseEmail">
          <h3>Choose Email To Send Message:</h3>
          <select
            name="selectedEmails[]"
            id="selectedEmails"
            multiple
            required
          >
          </select>
        </div>

        <div class="allInputs">
          <input
            type="text"
            name="subject"
            id="subject"
            placeholder="Subject"
            autocomplete="off"
            required
          />
          <textarea
            name="message"
            id="message"
            cols="30"
            rows="4"
            placeholder="Send A Message"
            autocomplete="off"
          ></textarea>
          <button id="send">Send Message</button>
          <p style="text-align: center" id="sendMessgeEmail"></p>
        </div>
      </div>
    </form>
  </div>
</div>
<script src="https://smtpjs.com/v3/smtp.js"></script>
