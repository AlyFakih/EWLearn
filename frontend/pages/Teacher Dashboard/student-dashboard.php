<?php
require_once "php/dbcontroller.php";
$db_handle = new DBController();

$sql = "SELECT u.id AS ID, u.fullName AS NAME, u.email AS EMAIL, u.mobile AS MOBILE, u.country AS COUNTRY
FROM users u 
WHERE u.role = 0";
$studentResult = $db_handle->readData($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./css/student-dashboard.css">
  <link rel="stylesheet" href="./css/dashboard-menu.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
  <title>Student</title>

 
</head>

<body>

  <div class="menu">
    <ul>

      <li class="profile">
        <div class="img-box">
          <img src="./images/logo.jpg" alt="logo image">
        </div>
        <h2>Ali Fakih</h2>
      </li>
      <li>
        <a class="active" href="./profile-dashboard.php">
          <i class="fas fa-home"></i>
          <p>Profile</p>
        </a>
      </li>

      <li>
        <a href="./course-dashboard.php">
          <i class="fas fa-book"></i>
          <p>Courses</p>
        </a>
      </li>

      <li>
        <a href="./student-dashboard.php">
          <i class="fas fa-user-group"></i>
          <p>Students</p>
        </a>
      </li>
      <li>
        <a href="./exam-dashboard.php">
          <i class="fas fa-pencil-alt"></i>
          <p>Exams</p>
        </a>
      </li>
      <li>
        <a href="./grades-dashboard.php">
          <i class="fas fa-graduation-cap"></i>
          <p>Grades</p>
        </a>
      </li>
      <li>
        <a href="./assignment-dashboard.php">
          <i class="fas fa-tasks"></i>
          <p>Assignemts</p>
        </a>
      </li>
      <li>
        <a href="./attendence-dashboard.php">
          <i class="fas fa-user-check"></i>
          <p>Attendance</p>
        </a>
      </li>
      <li class="log-out">
        <a href="#">
          <i class="fas fa-sign-out"></i>
          <p>Log Out</p>
        </a>
      </li>
    </ul>
  </div>


  <section class="main" style="margin-left:20px;">

    <section class="table__header">
      <h1>Students</h1>
      <div class="input-group">
        <input type="search" id="searchinstructor" placeholder="Search Data..." />
        <i class="fa-solid fa-magnifying-glass fa-lg" style="color: #000000"></i>
      </div>

    </section>

    <section class="attendance">
      <div class="attendance-list">
        <div class="cont">
        <h1>MIS</h1>
          <a href="#" class="customButton" id="showForm">
            <button>Add<i class="fas fa-plus"></i></button>
          </a>
        </div>



        <div id="list-product">

          <table id="table1" cellpadding="10" cellspacing="1" class="table-attend">

            <thead>
              <tr>
                <th>Id <span class="icon-arrow">&UpArrow;</span></th>
                <th>Name<span class="icon-arrow">&UpArrow;</span></th>
                <th>Email <span class="icon-arrow">&UpArrow;</span></th>
                <th>Number <span class="icon-arrow">&UpArrow;</span></th>
                <th>Country <span class="icon-arrow">&UpArrow;</span></th>
                <th>Action <span class="icon-arrow">&UpArrow;</span></th>
              </tr>
            </thead>

            <tbody id="ajax-response">
              <!-- Display existing rows here -->
              <?php
              if (!empty($studentResult)) {
                foreach ($studentResult as $v) {
              ?>
                  <tr>
                    <td data-id="student_id"><?php echo $v['ID']; ?></td>
                    <td data-id="student_name"><?php echo $v['NAME']; ?></td>
                    <td data-id="student_email"><?php echo $v['EMAIL']; ?></td>
                    <td data-id="student_mobile"><?php echo $v['MOBILE']; ?></td>
                    <td data-id="student_country"><?php echo $v['COUNTRY']; ?></td>
                    <td>
                      <button class="edit"><i class="fas fa-pencil" aria-hidden="true"></i></button>
                      <button class="save" style="display:none;" data-id="<?php echo $v['ID']; ?>"><i class="fas fa-check" aria-hidden="true"></i></button>
                      <button class="cancel" style="display:none;"><i class="fas fa-times" aria-hidden="true"></i></button>
                      <button class="del" data-id="<?php echo $v['ID']; ?>"><i class="fas fa-trash" aria-hidden="true"></i></button>
                    </td>
                  </tr>
              <?php
                }
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>


    <div id="addModal" class="addmodal">
      <h2>Add New Student</h2>
      <form id="addForm" method="post">

        <div>
          <label for="newstudentID">ID:</label>
          <input type="number" id="newstudentID" name="newstudentID" required />
          <label for="newName">Name:</label>
          <input type="text" id="newName" name="newName" required />
          <label for="newEmail">Email:</label>
          <input type="text" id="newEmail" name="newEmail" required />
        </div>
        <div>
          <label for="newMobile">Mobile:</label>
          <input type="number" id="newMobile" name="newMobile" required />
          <label for="newCountry">Country:</label>
          <input type="text" id="newCountry" name="newCountry" required />
        </div>
        <div class="buttons">
          <button type="button" id="adddata" name="adddata">Add</button>
          <button type="button" id="closeForm" name="closeForm">Cancel</button>
        </div>
      </form>
    </div>
  </section>

  </div>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script src="./js/student.js"></script>
</body>

</html>