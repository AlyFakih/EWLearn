<?php
// Server-side admin gate. Must be the first thing on the page so no markup or
// data is emitted before the caller is proven to be a logged-in admin.
// Login lives at ../loginRegister.html: the browser resolves this Location
// against the REQUESTED URL, not this file, so the depth is one level up.
require_once __DIR__ . "/../../core/auth_guard.php";
auth_require_role("admin", "page", "../loginRegister.html");
?>
<link rel="stylesheet" href="../../styles/DashBoards/students.css" />
<div id="boxNotifi"></div>
<script>
  $("#boxNotifi").load("./NotifiAdmin.php");
</script>
<div id="id01" class="modal">
  <form id="addstudentsForm" class="modal-content animate" method="post">
    <div class="imgcontainer">
      <h1>ADD Students</h1>
      <span
        onclick="document.getElementById('id01').style.display='none'"
        class="close"
        title="Close Modal"
        >&times;</span
      >
    </div>

    <div class="container contadd">
      <div class="form-row">
        <input type="text" name="full_name" placeholder="Full Name" />
        <select name="blood" class="selectRole">
          <option value="">Choose Blood Type</option>
          <option value="A+">A+</option>
          <option value="A-">A-</option>
          <option value="B+">B+</option>
          <option value="B-">B-</option>
          <option value="AB+">AB+</option>
          <option value="AB-">AB-</option>
          <option value="O+">O+</option>
          <option value="O-">O-</option>
        </select>
      </div>

      <div class="form-row">
        <input type="email" name="email" placeholder="Email" />
        <input type="tel" name="phone_number" placeholder="Phone Number" />
      </div>
      <div class="form-row">
        <select name="role" class="selectRole">
          <option value="student">Student</option>
          <option value="instructor">Instructor</option>
          <option value="admin">Admin</option>
        </select>
        <input type="text" name="country" placeholder="Country" />
      </div>
      <div class="form-row">
        <input type="password" name="password" placeholder="Password" />
        <input
          type="password"
          name="confirm_password"
          placeholder="Confirm Password"
        />
      </div>

      <div class="gender">
        <input type="radio" name="gender" value="male" />
        <label for="male">Male</label>

        <input type="radio" name="gender" value="female" />
        <label for="female">Female</label>
      </div>

      <div class="sub-button">
        <input type="submit" value="Add Instructor" />
        <p id="messageR" style="color: red; margin-top: 10px"></p>
      </div>
    </div>
  </form>
</div>
<div id="id02" class="modal">
  <form
    id="updateSTForm"
    class="modal-content animate"
    action=""
    method="post"
  >
    <div class="imgcontainer">
      <h1>Update Students Details</h1>
      <span
        onclick="document.getElementById('id02').style.display='none'"
        class="close"
        title="Close Modal"
        >&times;</span
      >
    </div>

    <div class="container contadd">
      <div class="form-row">
        <input
          type="text"
          id="namest"
          name="full_name"
          placeholder="Full Name"
        />
        <select name="blood" id="bloodst" class="selectRole">
          <option value="">Choose Blood Type</option>
          <option value="A+">A+</option>
          <option value="A-">A-</option>
          <option value="B+">B+</option>
          <option value="B-">B-</option>
          <option value="AB+">AB+</option>
          <option value="AB-">AB-</option>
          <option value="O+">O+</option>
          <option value="O-">O-</option>
        </select>
      </div>

      <div class="form-row">
        <input type="email" id="emailst" name="email" placeholder="Email" />
        <input
          type="tel"
          id="phonest"
          name="phone_number"
          placeholder="Phone Number"
        />
      </div>
      <div class="form-row">
        <select name="role" id="rolest" class="selectRole">
          <option value="student">Student</option>
          <option value="instructor">Instructor</option>
          <option value="admin">Admin</option>
        </select>
        <input
          type="text"
          id="countryst"
          name="country"
          placeholder="Country"
        />
      </div>

      <div class="sub-button">
        <input type="submit" value="Update Details" />
        <p id="updateSTMessage" style="color: red; margin-top: 10px"></p>
      </div>
    </div>
  </form>
</div>
<div class="table">
  <section class="table__header">
    <h1>Students</h1>
    <div class="input-group">
      <input
        type="search"
        id="searchinstructor"
        placeholder="Search Data..."
      />
      <i
        class="fa-solid fa-magnifying-glass fa-lg"
        style="color: #000000"
      ></i>
    </div>
    <button
      class="log"
      onclick="document.getElementById('id01').style.display='block'"
    >
      ADD
    </button>
  </section>

  <section class="table__body">
    <table>
      <thead>
        <tr>
          <th>Id <span class="icon-arrow">&UpArrow;</span></th>
          <th>Name<span class="icon-arrow">&UpArrow;</span></th>
          <th>Gmail <span class="icon-arrow">&UpArrow;</span></th>
          <th>Number <span class="icon-arrow">&UpArrow;</span></th>
          <th>Country <span class="icon-arrow">&UpArrow;</span></th>
          <th>Action <span class="icon-arrow">&UpArrow;</span></th>
        </tr>
      </thead>
      <tbody id="studentTableBody">
        <div
          id="no-cards-message"
          style="
            display: none;
            text-align: center;
            font-size: 25px;
            color: white;
          "
          class="color-animation"
        >
          This Instructor Not Found
        </div>
      </tbody>
    </table>
  </section>
</div>
<section class="container StudCourse">
  <h1 style="text-align: center; margin-bottom: 20px">
    Choose A Course To Student
  </h1>
  <form id="addCourseStudent" class="addEvent">
    <div class="formChoose">
      <select id="studentName" name="studentName"></select>
      <select id="getInstName" name="instructorName"></select>
      <select id="courseNamestudent" name="courseName"></select>
      <button type="button" onclick="addStudentCourse()">Choose</button>
    </div>
    <div class="formChoose">
      <select id="studentCourseSelect" name="studentCourseSelect">
      </select>
    </div>
    <div class="formButtons">
      <button type="button" onclick="updateStudentCourse()">Update</button>
      <button type="button" onclick="deleteStudentCourse()">Delete</button>
    </div>
  </form>
  <div id="successMessage"></div>
</section>
