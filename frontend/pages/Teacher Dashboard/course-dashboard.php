<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../../login.php");
    exit;
}
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
  $ID = isset($_POST['id']) ? intval($_POST['id']) : null;
  $courseTitle = isset($_POST['courseTitle']) ? $_POST['courseTitle'] : null;
  $description = isset($_POST['description']) ? $_POST['description'] : null;


  // Check if the file was uploaded without errors
  if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
    $tempName = $_FILES['image']['tmp_name'];
    $imageName = $_FILES['image']['name'];
    $image = 'images/' . $imageName;

    // Move the uploaded file to the "images" folder
    if (move_uploaded_file($tempName, $image)) {
      $sqlInsert = "INSERT INTO courses (id, courseTitle, image, description) VALUES (?, ?, ?, ?)";
      $stmtInsert = $conn->prepare($sqlInsert);
      $stmtInsert->bind_param("isss", $ID, $courseTitle, $image, $description);
      
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
    echo "Failed to upload file: " . $_FILES['image']['error'];
  }
}






// Update Operation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update') {
  $ID = isset($_POST['id']) ? intval($_POST['id']) : null;
  $courseTitle = isset($_POST['courseTitle']) ? $_POST['courseTitle'] : null;
  $description = isset($_POST['description']) ? $_POST['description'] : null;


  $sqlUpdate = "UPDATE courses SET courseTitle=?, description=?";

  // Check if an image is provided
  if (!empty($_FILES['image']['name'])) {
    $tempName = $_FILES['image']['tmp_name'];
    $imageName = $_FILES['image']['name'];
    $image = 'images/' . $imageName;

    // Move the uploaded file to the "images" folder
    if (move_uploaded_file($tempName, $image)) {
      // If the move is successful, update the image column as well
      $sqlUpdate .= ", image=?";
    } else {
      echo "Failed to move the uploaded file.";
      exit();
    }
  }

  $sqlUpdate .= " WHERE id=?";
  $stmtUpdate = $conn->prepare($sqlUpdate);

  if (!empty($_FILES['image']['name'])) {
    $stmtUpdate->bind_param("sssi", $courseTitle, $description, $image, $ID);
  } else {
    $stmtUpdate->bind_param("ssi", $courseTitle, $description, $ID);
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
  $ID = isset($_POST['id']) ? intval($_POST['id']) : null;

  $sqlDelete = "DELETE FROM courses WHERE id=?";
  $stmtDelete = $conn->prepare($sqlDelete);
  $stmtDelete->bind_param("i", $ID);

  if ($stmtDelete->execute()) {
    echo "Delete card success";
  } else {
    echo "Failed to delete the card: " . $stmtDelete->error;
  }

  $stmtDelete->close();
}
$sqlSkills = "SELECT id AS ID, courseTitle AS Title, description AS Description, image AS Image FROM courses";
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



<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./css/course-dashboard.css">
  <link rel="stylesheet" href="./css/dashboard-menu.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
  <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
  <title>Courses</title>

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

  <div class="container">
    <div class="cont-skill">

    

      <main>
      <div class="title" style="background-color: #810000; width: 100%; padding: 10px; border-radius:10px; ">
          <h2 id="#skill">Courses</h2>
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
              echo "<h4>" . $rowSkills['Title'] . "</h4>";
              echo "<p>" . $rowSkills['Description'] . "</p>";
              echo "<div class='progress'>";
              echo "<img src='" . $rowSkills['Image'] . "' alt='Progress Image'>";
              echo "</div>";
              echo "</div>";
            }
          } else {
            echo "<p>No results found</p>";
          }
          ?>
        </div>


        <div id="popup" class="popup">
          <span class="close" onclick="closePopup()">&times;</span>
          <h3>Add New Course</h3>
          <form id="cardForm">
            <!-- Hidden input for studentID (manually enterable) -->
            <label for="id">ID:</label>
            <input type="text" id="id" name="id" required>

            <label for="image">Image:</label>
            <input type="file" id="image" name="image" required>

            <label for="courseTitle">Name :</label>
            <input type="text" id="courseTitle" name="courseTitle" required>

            <label for="description">Description :</label>
            <input type="text" id="description" name="description" required>

            <button type="button" onclick="addCard()">Add Card</button>
          </form>

        </div>

        <div class="card-form" id="updateForm">
          <h3>Update Course Card</h3>
          <div class="form-design">
            <label for="updateImage">Image :</label>
            <input type="file" id="updateImage" name="updateImage">
          </div>
          <div class="form-design">
            <label for="updateCourseTitle">Title :</label>
            <input type="text" id="updateCourseTitle" name="updateCourseTitle">
          </div>
          <div class="form-design">
            <label for="updateDescription">Description :</label>
            <input type="text" id="updateDescription" name="updateDescription">
          </div>

          <div class="button-container">
            <button onclick="updateCard()">Update</button>
            <button onclick="hideUpdateForm()">Cancel</button>
          </div>
        </div>



      </main>






    </div>



  </div>


  <script src="./js/course.js"></script>



</body>

</html>