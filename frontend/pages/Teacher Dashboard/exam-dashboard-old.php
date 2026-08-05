<?php
require_once "php/dbcontroller.php";
$db_handle = new DBController();

$sql = "SELECT `id`, `date`, `time`, `courseID`, `subject`, `room` FROM `exam`;";
$studentResult = $db_handle->readData($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="./css/exam-dashboard.css" />
  <link rel="stylesheet" href="./css/dashboard-menu.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <!-- <script src="./js/exam.js"></script> -->
  <title>Exams</title>

  <style>
    .title {
      margin-top: 20px;
      
      margin-left: 20px;
    }

    .title h2 {
      color: #810000;
    }

    .main .table__header {
      width: 100%;
      height: 7%;
      background-color: #fff4;
      padding: 0.5rem 0.8rem;

      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .main .table__header .addbtn button {
      padding: 7px;
      background-color: #810000;
      color: white;
      font-size: 18px;
      font-weight: bold;
      text-align: center;
      width: 80px;
      border-radius: 10px 5px;
    }

    .main .table__header .addbtn button i {
      margin-left: 5px;
      color: #000000;
      font-weight: bold;
    }

    .main .table__header h1 {
      font-weight: bold;
      color: #810000;
      font-size: 29px;
    }

    .main .table__header h1 i {
      color: #000000;
      margin-right: 5px;
    }

    .main .table__header .log {
      padding: 10px;
      width: 90px;
      font-size: 18px;
      font-weight: bold;
      border: 1px solid #fff4;
      border-radius: 10px 20px 10px 20px;
      /* font-weight: 800; */
      color: #000000;
      background-color: #800000;
      margin-right: 30px;
    }

    .main .table__header .input-group {
      width: 35%;
      height: 100%;
      background-color: #fff5;
      padding: 0 0.8rem;
      border-radius: 2rem;
      padding: 9px;
      display: flex;
      justify-content: center;
      align-items: center;

      transition: 0.2s;
    }

    .main .table__header .input-group:hover {
      width: 45%;
      background-color: #fff8;
      box-shadow: 0 0.1rem 0.4rem #0002;
    }

    .main .table__header .input-group img {
      width: 1.2rem;
      height: 1.2rem;
    }

    .main .table__header .input-group input {
      width: 100%;
      padding: 0 0.5rem 0 0.3rem;
      background-color: transparent;
      border: none;
      outline: none;
      color: #000000;
      font-size: 20px;
    }

    /* ----------------------------------------------------------- */
    /* Style for the delete button */

    .save i {
      color: green;

    }

    .cancel i {
      color: darkblue;

    }

    .save i,
    .cancel i,
    .del i,
    .edit i {
      font-weight: bold;
      font-size: 20px;
      background-color: #001;
      padding: 7px;
      border-top: 0;
      border-left: 0;
      border-right: 0;
      border-bottom: 0;
      border: none;
    }

    .del i {
      color: darkred;

    }

    .del:hover {
      background-color: gray;
      /* Gray color on hover */
      color: gray;
    }

    /* ---------------------------------------------------------------- */

    /*---------------------------------------------
 Add a new row Section starts 
------------------------------------------------*/
    .modal {
      display: none;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      padding: 20px;
      background-color: darkgray;
      z-index: 1000;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
    }

    .modal h2 {
      margin-bottom: 10px;
      text-align: center;
      color: #001;
    }

    .addModal {
      display: none;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      padding: 20px;
      background-color: darkgray;
      z-index: 1000;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
    }

    .addModal h2 {
      margin-bottom: 10px;
      text-align: center;
      color: #001;
    }

    /* Classes for the divs inside the form */
    .first,
    .second {
      margin-bottom: 15px;
    }

    /* Form styles */
    #addForm {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 10px;
    }

    #addForm label {
      font-weight: bold;
      grid-column: span 2;
      color: #810000;
      /* Updated color */
    }

    #addForm input,
    #addForm select {

      width: 100%;
      padding: 8px;
      box-sizing: border-box;
      margin-top: 5px;
      border: 4px solid #001;
      border-radius: 2px 20px 2px 20px;
      text-align: center;
      color: #001;
    }

    #addForm select option {
      background-color: #001;
      color: #810000;
      /* Updated color */
    }

    #addForm .buttons {
      grid-column: span 2;
      display: flex;
      justify-content: center;
    }

    #addForm button {
      background-color: #4caf50;
      color: white;
      border: none;
      padding: 10px 15px;
      text-align: center;
      text-decoration: none;
      display: inline-block;
      font-size: 16px;
      cursor: pointer;
      margin-right: 10px;
    }

    #addForm button:hover {
      background-color: #45a049;
    }

    /* Close button styles */
    #addModal button[type="button"] {
      background-color: #f44336;
      color: white;
      border: none;
      padding: 10px 15px;
      text-align: center;
      text-decoration: none;
      display: inline-block;
      font-size: 16px;
      cursor: pointer;
      margin-top: 10px;
    }

    #addModal button[type="button"]:hover {
      background-color: #123;
    }


    /*---------------------------------------------
   Table Section ends 
------------------------------------------------*/
    /*------------------------------------------------------------------------------------ */

    @media (max-width: 1000px) {
      td:not(:first-of-type) {
        min-width: 12.1rem;
      }
    }

    thead th span.icon-arrow {
      display: inline-block;
      width: 1.3rem;
      height: 1.3rem;
      border-radius: 50%;
      border: 1.4px solid transparent;

      text-align: center;
      font-size: 1rem;

      margin-left: 0.5rem;
      transition: 0.2s ease-in-out;
    }

    thead th:hover span.icon-arrow {
      border: 1.4px solid #ff0000;
    }

    thead th:hover {
      color: #ff0000;
    }

    thead th.active span.icon-arrow {
      background-color: #000000;
      color: #fff;
    }

    thead th.asc span.icon-arrow {
      transform: rotate(180deg);
    }

    thead th.active,
    tbody td.active {
      color: #6c00bd;
    }





    /* Modal Content/Box */
    .modal-content {
      background-color: #000000;
      margin: 5% auto 15% auto;
      /* 5% from the top, 15% from the bottom and centered */
      border: 1px solid #ff0000;
      width: 50%;
      /* Could be more or less, depending on screen size */
      border-radius: 30px;
    }

    /* Add Zoom Animation */
    .animate {
      -webkit-animation: animatezoom 0.6s;
      animation: animatezoom 0.6s;
    }

    @-webkit-keyframes animatezoom {
      from {
        -webkit-transform: scale(0);
      }

      to {
        -webkit-transform: scale(1);
      }
    }

    @keyframes animatezoom {
      from {
        transform: scale(0);
      }

      to {
        transform: scale(1);
      }
    }

    /* Change styles for span and cancel button on extra small screens */
    @media screen and (max-width: 300px) {
      span.psw {
        display: block;
        float: none;
      }

      .cancelbtn {
        width: 100%;
      }
    }




    @keyframes colorChange {
      0% {
        color: rgb(255, 0, 0);
      }

      25% {
        color: rgb(255, 255, 255);
      }

      50% {
        color: rgb(65, 0, 0);
      }

      75% {
        color: rgb(255, 238, 0);
      }

      100% {
        color: rgb(0, 0, 0);
      }
    }

    @keyframes vibration {
      0% {
        transform: translateX(0);
      }

      25% {
        transform: translateX(-3px) translateY(-3px);
      }

      50% {
        transform: translateX(3px) translateY(3px);
      }

      75% {
        transform: translateX(-3px) translateY(-3px);
      }

      100% {
        transform: translateX(3px) translateY(3px);
      }
    }

    .color-animation {
      animation: colorChange 5s infinite, vibration 0.3s;
      /* Adjust the durations as needed */
    }




    /*--------------------------------------------------------------------------------------*/
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

  <section class="main" style="margin-left:20px;">

          <section class="table__header" style="margin-top:10px;">
      <h1><i class="fas fa-chart-bar"></i>Exams</h1>
      <div class="input-group">
        <input type="search" id="searchinstructor" placeholder="Search Data..." />
        <i class="fa-solid fa-magnifying-glass fa-lg" style="color: #000000"></i>
      </div>
      <div class="addbtn">
        <a href="#" class="customButton" id="showForm">
          <button>Add<i class="fas fa-plus"></i></button>
        </a>
      </div>
          </section>

    <div class="title">
      <h2></i>Available Exams</h2>
    </div>


    <section class="attendance">

      <div class="attendance-list">


        <div id="list-product">

          <table id="table1" cellpadding="10" cellspacing="1" class="table-attend">

            <thead>
              <tr>
                <th>Course ID<span class="icon-arrow">&UpArrow;</span></th>
                <th>Subject<span class="icon-arrow">&UpArrow;</span></th>
                <th>Date <span class="icon-arrow">&UpArrow;</span></th>
                <th>Time<span class="icon-arrow">&UpArrow;</span></th>
                <th>Room<span class="icon-arrow">&UpArrow;</span></th>
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
                    <td data-id="exam_courseID"><?php echo $v['courseID']; ?></td>
                    <td data-id="exam_subject"><?php echo $v['subject']; ?></td>
                    <td data-id="exam_date"><?php echo $v['date']; ?></td>
                    <td data-id="exam_time"><?php echo $v['time']; ?></td>
                    <td data-id="exam_room"><?php echo $v['room']; ?></td>
                    <td>
                      <button class="edit"><i class="fas fa-pencil" aria-hidden="true"></i></button>
                      <button class="save" style="display:none;" data-id="<?php echo $v['id']; ?>"><i class="fas fa-check" aria-hidden="true"></i></button>
                      <button class="cancel" style="display:none;"><i class="fas fa-times" aria-hidden="true"></i></button>
                      <button class="del" data-id="<?php echo $v['id']; ?>"><i class="fas fa-trash" aria-hidden="true"></i></button>
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


    <div id="addModal" class="addmodal" style="display: none;">
      <h2>Add New Exam</h2>
      <form id="addForm" method="post">
        <div>
          <label for="newcourseID">ID:</label>
          <input type="number" id="newcourseID" name="newcourseID" required />
          <label for="newSubject">Subject:</label>
          <input type="text" id="newSubject" name="newSubject" required />
          <label for="newDate">Date:</label>
          <input type="date" id="newDate" name="newDate" required />
        </div>
        <div>
          <label for="newTime">Time:</label>
          <input type="time" id="newTime" name="newTime" required />
          <label for="newRoom">Room:</label>
          <input type="text" id="newRoom" name="newRoom" required />
        </div>
        <div class="buttons">
          <button type="button" id="adddata" name="adddata">Add</button>
          <button type="button" id="closeForm" name="closeForm">Cancel</button>
        </div>
      </form>
    </div>


  </section>
  








  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script src="./js/exam.js"></script>
</body>

</html>