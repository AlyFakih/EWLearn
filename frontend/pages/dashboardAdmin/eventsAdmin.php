<?php
// Server-side admin gate. Must be the first thing on the page so no markup or
// data is emitted before the caller is proven to be a logged-in admin.
// Login lives at ../loginRegister.html: the browser resolves this Location
// against the REQUESTED URL, not this file, so the depth is one level up.
require_once __DIR__ . "/../../core/auth_guard.php";
auth_require_role("admin", "page", "../loginRegister.html");
?>
<link rel="stylesheet" href="../../styles/DashBoards/staff.css" />
<div id="boxNotifi"></div>
<script>
  $("#boxNotifi").each(function () {
    $(this).load("./NotifiAdmin.php");
  });
</script>

<section class="container">
  <h1 style="text-align: center; margin-bottom: 20px">Add / Edit Event</h1>
  <form id="addEventForm" class="addEvent">
    <input type="hidden" id="editingEventId" value="" />
    <input type="text" id="eventTitle" name="nameType" placeholder="Event Title" required />
    <input type="text" id="eventDate" name="Date" placeholder="Date (e.g. August 20, 2026)" required />
    <input type="text" id="eventLink" name="linkInfo" placeholder="Short Summary" required />
    <textarea id="eventDescription" name="description" placeholder="Full Description (optional)" rows="4"></textarea>
    <input type="file" id="eventImage" name="image" accept="image/*" />
    <button type="button" id="btnAddEvent" onclick="addEvent()">Add Event</button>
    <br />
    <select id="deleteEventSelect" name="deleteEventSelect">
      <option value="">-- Select an event --</option>
    </select>

    <button type="button" onclick="editEvent()">Edit</button>
    <button type="button" onclick="deleteEvent()">Delete</button>
    <button type="button" onclick="cancelEditEvent()">Cancel</button>
  </form>
  <div id="eventSuccessMessage"></div>
</section>
<div class="row" id="eventsContainer"></div>
