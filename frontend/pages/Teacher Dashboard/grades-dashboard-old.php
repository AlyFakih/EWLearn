<?php
require_once "php/dbcontroller.php";
$db_handle = new DBController();

$sql = "SELECT
G.studentID AS StudentID,
G.fullName AS Name,
G.grade AS Grade
FROM
grades G;";
$gradesResult = $db_handle->readData($sql);
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./css/dashboard-menu.css">
  <link rel="stylesheet" href="./css/grades-dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
  <title>Grades</title>
  <style>
 

    /*-------------------------
  About Section begin
---------------------------*/

    /*-------------------------------
Doctor's onLeave Section starts
---------------------------------*/
    .doctor-leave-announcements {
      margin-top:30px;
      margin-left: 10px;
      
      border: 4px solid #810000;
      border-radius: 10px;
      padding-left: 10px;
      width: 250px;
      height: 470px;
    }

    .announcements {
      display: flex;
      flex-direction: column;
    }

    .doctor-leave-announcements .message {
      flex-grow: 1;
      margin-bottom: 15px;
    }

    .doctor-leave-announcements .message-container {
      display: flex;
      align-items: center;
    }

    img {
      width: 70px;
      height: 70px;
      border-radius: 50%;
      margin-right: 10px;
    }

    .doctor-leave-announcements h4 {
      margin-top: 0.9rem;
      margin-bottom: 1.2rem;
      color: #810000;
      text-align: center;
    }

    .doctor-leave-announcements .message p {
      gap: 1rem;
      line-height: 1.5;
      padding: 0.5rem 0;
      color: #810000;
    }

    .doctor-leave-announcements .message .text-muted {
      color: #888;
      display: block;
      text-align: center;
      margin-top: 5px;
    }

    /*-------------------------
  about Section ends
---------------------------*/
  </style>


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

  <div class="cont-skill">
    <aside>
      <div class="profilee">
        

        <div class="about">

          <div class="doctor-leave-announcements">
            <h4>Reminders</h4>
            <div class="announcements">

              <div class="message">
                <div class="message-container">
                  <img src="./images/member-10.jpg" alt="abbass saleh" />
                  <p>
                    <b>Abbass Saleh</b> will receive an extra 5 points for completing additional assignments.
                  </p>

                </div>

              </div>

              <div class="message">
                <div class="message-container">
                  <img src="./images/logo.jpg" alt="Hussein Fakih" />
                  <p>
                    <b>Hussein Fakih</b> will receive a bonus for successfully reaching a project milestone ahead of schedule.
                  </p>
                </div>
              </div>

              <div class="message">
                <div class="message-container">
                  <img src="./images/teacher.jpg" alt="Ali Fakih" />
                  <p>
                    Kudos to <b>Ali Fakih</b> for outstanding teamwork on the project!
                  </p>

                </div>
                
              </div>

            </div>

          </div>

        </div>

      </div>
    </aside>
  </div>


  <section class="main">
    <section class="table__header">
      <h1>Students</h1>
      <div class="input-group">
        <input type="search" id="searchinstructor" placeholder="Search Data..." />
        <i class="fa-solid fa-magnifying-glass fa-lg" style="color: #000000"></i>
      </div>

    </section>

    <!-- first section for the first table  -->
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
                <th>Grade<span class="icon-arrow">&UpArrow;</span></th>
                <th>Action <span class="icon-arrow">&UpArrow;</span></th>
              </tr>
            </thead>

            <tbody id="ajax-response">
              <!-- Display existing rows here -->
              <?php
              if (!empty($gradesResult)) {
                foreach ($gradesResult as $v) {
              ?>
                  <tr>
                    <td data-id="student_id"><?php echo $v['StudentID']; ?></td>
                    <td data-id="student_name"><?php echo $v['Name']; ?></td>
                    <td data-id="student_grade"><?php echo $v['Grade']; ?></td>
                    <td>
                      <button class="edit"><i class="fas fa-pencil" aria-hidden="true"></i></button>
                      <button class="save" style="display:none;" data-id="<?php echo $v['StudentID']; ?>"><i class="fas fa-check" aria-hidden="true"></i></button>
                      <button class="cancel" style="display:none;"><i class="fas fa-times" aria-hidden="true"></i></button>
                      <button class="del" data-id="<?php echo $v['StudentID']; ?>"><i class="fas fa-trash" aria-hidden="true"></i></button>
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



    <div id="addModal" class="modal">
      <h2>Add New Grade</h2>
      <form id="addForm" method="post">

        <div>
          <label for="newstudentID">ID:</label>
          <input type="number" id="newstudentID" name="newstudentID" required />
          <label for="newName">Name:</label>
          <input type="text" id="newName" name="newName" required />
          <label for="newGrade">Grade:</label>
          <input type="text" id="newGrade" name="newGrade" required />
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
  <script src="./js/grades.js"></script>

</body>

</html>