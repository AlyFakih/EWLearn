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
  $("#boxNotifi").load("./NotifiAdmin.php");
</script>

<section class="container">
  <h1 style="text-align: center; margin-bottom: 20px">Add / Edit Member</h1>
  <form id="addTeamMemberForm" class="addEvent">
    <input type="hidden" id="editingMemberId" value="" />
    <input type="file" id="image" name="image" accept="image/*" />
    <input type="text" id="name" name="name" placeholder="Name" required />
    <input
      type="text"
      id="major"
      name="major"
      placeholder="Major"
      required
    />
    <button type="button" id="btnAddTeamMember" onclick="addTeamMember()">Add Team Member</button>
    <br />
    <select id="deleteMemberSelect" name="deleteMemberSelect">
      <option value="">-- Select a team member --</option>
    </select>

    <button type="button" onclick="editTeamMember()">Edit</button>
    <button type="button" onclick="deleteTeamMember()">Delete</button>
    <button type="button" onclick="cancelEditTeamMember()">Cancel</button>
  </form>
  <div id="successMessage"></div>
</section>
<div class="row" id="teamMembersContainer"></div>
