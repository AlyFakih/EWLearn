<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "student_management";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// CRUD Operations

// Create (Add) Operation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
  $studentID = isset($_POST['studentID']) ? intval($_POST['studentID']) : null;
  $studentName = isset($_POST['studentName']) ? $_POST['studentName'] : null;
  $studentYear = isset($_POST['studentYear']) ? $_POST['studentYear'] : null;
  $studentMajor = isset($_POST['studentMajor']) ? $_POST['studentMajor'] : null;

  // Check if the file was uploaded without errors
  if (isset($_FILES['studentImage']) && $_FILES['studentImage']['error'] == UPLOAD_ERR_OK) {
    $tempName = $_FILES['studentImage']['tmp_name'];
    $imageName = $_FILES['studentImage']['name'];
    $studentURLImage = 'images/' . $imageName;

    // Move the uploaded file to the "images" folder
    if (move_uploaded_file($tempName, $studentURLImage)) {
      $sqlInsert = "INSERT INTO attendance (studentID, studentName, studentURLImage, year, major) VALUES (?, ?, ?, ?, ?)";
      $stmtInsert = $conn->prepare($sqlInsert);
      $stmtInsert->bind_param("issss", $studentID, $studentName, $studentURLImage, $studentYear, $studentMajor);

      if ($stmtInsert->execute()) {
        echo "Add card success";
      } else {
        echo "Failed to add the card: " . $stmtInsert->error;
      }

      $stmtInsert->close();
    } else {
      echo "Failed to move the uploaded file.";
    }
  } else {
    // Handle file upload error
    echo "Failed to upload file: " . $_FILES['studentImage']['error'];
  }
}






// Update Operation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update') {
  $studentID = isset($_POST['studentID']) ? intval($_POST['studentID']) : null;
  $studentName = isset($_POST['studentName']) ? $_POST['studentName'] : null;
  $year = isset($_POST['studentYear']) ? $_POST['studentYear'] : null;
  $major = isset($_POST['studentMajor']) ? $_POST['studentMajor'] : null;

  $sqlUpdate = "UPDATE attendance SET studentName=?, year=?, major=?";

  // Check if an image is provided
  if (!empty($_FILES['studentImage']['name'])) {
    $tempName = $_FILES['studentImage']['tmp_name'];
    $imageName = $_FILES['studentImage']['name'];
    $studentURLImage = 'images/' . $imageName;

    // Move the uploaded file to the "images" folder
    if (move_uploaded_file($tempName, $studentURLImage)) {
      // If the move is successful, update the image column as well
      $sqlUpdate .= ", studentURLImage=?";
    } else {
      echo "Failed to move the uploaded file.";
      exit();
    }
  }

  $sqlUpdate .= " WHERE studentID=?";
  $stmtUpdate = $conn->prepare($sqlUpdate);

  if (!empty($_FILES['studentImage']['name'])) {
    $stmtUpdate->bind_param("ssssi", $studentName, $year, $major, $studentURLImage, $studentID);
  } else {
    $stmtUpdate->bind_param("sssi", $studentName, $year, $major, $studentID);
  }

  if ($stmtUpdate->execute()) {
    echo "Update card success";
  } else {
    echo "Failed to update the card: " . $stmtUpdate->error;
  }

  $stmtUpdate->close();
}



// Delete Operation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete') {
  $studentID = isset($_POST['studentID']) ? intval($_POST['studentID']) : null;

  $sqlDelete = "DELETE FROM attendance WHERE studentID=?";
  $stmtDelete = $conn->prepare($sqlDelete);
  $stmtDelete->bind_param("i", $studentID);

  if ($stmtDelete->execute()) {
    echo "Delete card success";
  } else {
    echo "Failed to delete the card: " . $stmtDelete->error;
  }

  $stmtDelete->close();
}
$sqlSkills = "SELECT studentID AS ID, studentName AS Name, studentURLImage AS Image, year AS Year, major AS Major FROM attendance";
$stmtSkills = $conn->prepare($sqlSkills);

if ($stmtSkills === false) {
  die("Error in SQL query preparation: " . $conn->error);
}

$resultSkills = $stmtSkills->execute();

if ($resultSkills === false) {
  die("Error in SQL query execution: " . $stmtSkills->error);
}

$resultSkills = $stmtSkills->get_result();

$skillsData = array();
while ($rowSkills = $resultSkills->fetch_assoc()) {
  $skillsData[] = $rowSkills;
}

$stmtSkills->close();

// Close the database connection
$conn->close();
?>

<?php
require_once "php/dbcontroller.php";
$db_handle = new DBController();

$sql = "SELECT `studentID` AS ID , `studentName` AS Name, `major` AS Major , `date` AS Date, `status` AS Status, `notes` AS Notes FROM `attendance`;";
$attendanceResult = $db_handle->readData($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="./css/attendence-dashboard.css">
  <link rel="stylesheet" href="./css/dashboard-menu.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <title>Attendance</title>

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

  <main>
    <div class="title">
      <h1><i class="fas fa-user-group"></i>Students</h1>
      <div>
        <button onclick="showPopup()">Add</button>
        <button onclick="showUpdateForm()">Update</button>
        <button type="button" id="deleteButton" onclick="deleteSelectedCard()">Delete</button>
      </div>
    </div>



    <div class="subjects" id="subjectContainer">
      <?php
      // Loop through the fetched data and display subjects dynamically
      if (!empty($skillsData)) {
        foreach ($skillsData as $rowSkills) {
          echo "<div class='eg' data-id='" . $rowSkills['ID'] . "' onclick='selectCard(this)'>";
          echo "<div class='progress'>";
          echo "<img src='" . $rowSkills['Image'] . "' alt='Progress Image'>";
          echo "</div>";
          echo "<h4>" . $rowSkills['Name'] . "</h4>";
          echo "<p>" . $rowSkills['Major'] . "</p>";
          echo "<small class='text-muted'>" . $rowSkills['Year'] . "</small>";
          echo "<br>";
          echo "<a href='#'><button>Profile</button></a>";
          echo "</div>";
        }
      } else {
        echo "<p>No results found</p>";
      }
      ?>
    </div>


    <div id="popup" class="popup">
      <span class="close" onclick="closePopup()">&times;</span>
      <h3>Add New Student</h3>
      <form id="cardForm">
        <!-- Hidden input for studentID (manually enterable) -->
        <label for="studentID">Student ID:</label>
        <input type="text" id="studentID" name="studentID" required>

        <label for="studentImage">Image:</label>
        <input type="file" id="studentImage" name="studentImage" required>

        <label for="studentName">Name :</label>
        <input type="text" id="studentName" name="studentName" required>

        <label for="studentMajor">Major :</label>
        <input type="text" id="studentMajor" name="studentMajor" required>


        <label for="studentYear">Year:</label>
        <input type="text" id="studentYear" name="studentYear" required>

        <button type="button" onclick="addCard()">Add Card</button>
      </form>

    </div>

    <div class="card-form" id="updateForm">
      <h3>Update Student Card</h3>
      <div class="form-design">
        <label for="updateStudentImage">Image :</label>
        <input type="file" id="updateStudentImage" name="updateStudentImage">
      </div>

      <div class="form-design">
        <label for="updateStudentName">Name :</label>
        <input type="text" id="updateStudentName" name="updateStudentName">
      </div>
      <div class="form-design">
        <label for="updateStudentMajor">Major :</label>
        <input type="text" id="updateStudentMajor" name="updateStudentMajor">
      </div>
      <div class="form-design">
        <label for="updateStudentYear">Year :</label>
        <input type="text" id="updateStudentYear" name="updateStudentYear">
      </div>
      <div class="button-container">
        <button onclick="updateCard()">Update</button>
        <button onclick="hideUpdateForm()">Cancel</button>
      </div>
    </div>



    <section class="main">
      <div class="main-top">
        <h1><i class="fas fa-user-group"></i>Attendance</h1>
      </div>

      <section class="attendance">
        <div class="attendance-list">
          <div class="cont">
            <h1>Attendance</h1>
            <a href="#" class="customButton" id="showForm">
              <button>Add<i class="fas fa-plus"></i></button>
            </a>
          </div>



          <div id="list-product">

            <table id="table1" cellpadding="10" cellspacing="1" class="table-attend">

              <thead>

                <tr>
                  <th><strong>ID</strong></th>
                  <th><strong>Name</strong></th>
                  <th><strong>Course</strong></th>
                  <th><strong>Date</strong></th>
                  <th><strong>Status</strong></th>
                  <th><strong>Notes/Comments</strong></th>
                  <th><strong>Actions</strong></th>
                </tr>
              </thead>

              <tbody id="ajax-response">
                <!-- Display existing rows here -->
                <?php
                if (!empty($attendanceResult)) {
                  foreach ($attendanceResult as $v) {
                ?>
                    <tr>
                      <td data-id="student_id"><?php echo $v['ID']; ?></td>
                      <td data-id="student_name"><?php echo $v['Name']; ?></td>
                      <td data-id="student_major"><?php echo $v['Major']; ?></td>
                      <td data-id="student_date"><?php echo $v['Date']; ?></td>
                      <td data-id="student_status"><?php echo $v['Status']; ?></td>
                      <td data-id="student_notes"><?php echo $v['Notes']; ?></td>
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


      <div id="addModal" class="modal">
        <h2>Add New Assignment</h2>
        <form id="addForm" method="post">

          <div>
            <label for="newstudentID">ID:</label>
            <input type="number" id="newstudentID" name="newstudentID" required />

            <label for="newName">Name:</label>
            <input type="text" id="newName" name="newName" required />
          </div>

          <div>
            <label for="newMajor">Major:</label>
            <input type="text" id="newMajor" name="newMajor" required />

            <label for="newDate">Date:</label>
            <input type="date" id="newDate" name="newDate" required />
          </div>

          <div>
            <label for="newStatus">Status:</label>
            <select id="newStatus" name="newStatus">
              <option value="pending">Pending</option>
              <option value="ongoing">Ongoing</option>
              <option value="finished">Finished</option>
              <option value="freezed">Freezed</option>
            </select>

            <label for="newNotes">Notes:</label>
            <input type="text" id="newNotes" name="newNotes" required />
          </div>

          <div class="buttons">
            <button type="button" id="adddata" name="adddata">Add</button>
            <button type="button" id="closeForm" name="closeForm">Cancel</button>
          </div>
        </form>
      </div>
    </section>

  </main>

  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script src="./js/attendence.js"></script>
</body>

</html>