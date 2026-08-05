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

// Set the instructor ID based on your application logic
$instructorID = 1; // Replace with the actual instructor ID

// CRUD Operations


// Create (Add) Operation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $subjectIcon = $_POST['subjectIcon'];
    $instructorID = isset($_POST['instructorID']) ? intval($_POST['instructorID']) : null; // Validate as integer
    $subjectName = $_POST['subjectName'];
    $progressPercentage = $_POST['progressPercentage'];
    $experience = $_POST['experience'];

    $sqlInsert = "INSERT INTO instructor_skills (instructorID, subjectIcon, subjectName, progressPercentage, experience) VALUES (?, ?, ?, ?, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("issis", $instructorID, $subjectIcon, $subjectName, $progressPercentage, $experience);

    if ($stmtInsert->execute()) {
        header("Location: profile-dashboard.php");
        exit();
    } else {
        die("Error in SQL query execution: " . $stmtInsert->error);
    }
}

// Ensure $stmtInsert is always defined before trying to close it
if (isset($stmtInsert)) {
    $stmtInsert->close();  // Close the statement outside the conditional block
}




// Read Operation
$sqlSkills = "SELECT  `id`, `subjectIcon`, `subjectName`, `progressPercentage`, `experience` FROM `instructor_skills`;";
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

// Update Operation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update') {
    $subjectIcon = $_POST['subjectIcon'];
    $subjectName = $_POST['subjectName'];
    $progressPercentage = $_POST['progressPercentage'];
    $experience = $_POST['experience'];

    $sqlUpdate = "UPDATE instructor_skills SET subjectIcon=?, subjectName=?, progressPercentage=?, experience=? WHERE id=?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("ssisi", $subjectIcon, $subjectName, $progressPercentage, $experience, $_POST['id']);

    if ($stmtUpdate->execute()) {
        echo "Update card success"; // Send a success message to the client
    } else {
        echo "Failed to update the current card: "; // Send an error message to the client
    }

    $stmtUpdate->close();
}

// Delete Operation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $sqlDelete = "DELETE FROM instructor_skills WHERE id=?";
    $stmtDelete = $conn->prepare($sqlDelete);
    $stmtDelete->bind_param("i", $_POST['id']);

    if ($stmtDelete->execute()) {
        echo "delete the card success"; // Send a success message to the client
    } else {
        echo "Failed to delete the card: "; // Send an error message to the client
    }

    $stmtDelete->close();
}

// Fetch user data based on the provided user ID (in this case, 4)
$userID = 3;
$sql = "SELECT `fullName`, `image`, `country`, `email`, `mobile` FROM `users` WHERE id = $userID";
$result = $conn->query($sql);

// Initialize variables with defaults
$fullName = "User";
$image = "./images/logo.jpg";
$country = "Not specified";
$email = "No email";
$mobile = "No mobile";

if ($result->num_rows > 0) {
    // Output data of each row
    $row = $result->fetch_assoc();
    
    // Only override defaults if values are set
    if (isset($row["fullName"])) $fullName = $row["fullName"];
    if (isset($row["image"])) $image = $row["image"];
    if (isset($row["country"])) $country = $row["country"];
    if (isset($row["email"])) $email = $row["email"];
    if (isset($row["mobile"])) $mobile = $row["mobile"];
} else {
    echo "<div class='alert alert-warning'>User data not found. Displaying defaults.</div>";
}


// Close the database connection
$conn->close();
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/dashboard-menu.css">
    <link rel="stylesheet" href="./css/profile-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <title>Profile</title>
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
            <aside>

                <!-----------------------   Profle Section starts    -------------------------------------------------------------------------------->
                <div class="profiles">
                    <div class="top">
                        <div class="profiles-photo">
                            <img src="<?php echo $image; ?>" alt="<?php echo $fullName; ?>">
                            <i class="fas fa-pencil-alt"></i>
                        </div>
                    </div>

                    <div class="about">
                        <h5>Name</h5>
                        <p><?php echo $fullName; ?> <i class="fas fa-pencil-alt"></i></p>

                        <h5>Contact</h5>
                        <p><?php echo $mobile; ?> <i class="fas fa-pencil-alt"></i></p>
                        <h5>Email</h5>
                        <p><?php echo $email; ?> <i class="fas fa-pencil-alt"></i></p>
                        <h5>Country</h5>
                        <p><?php echo $country; ?> <i class="fas fa-pencil-alt"></i></p>
                    </div>
                </div>
            </aside>

            <main>
                <div class="title">
                    <h2 id="#skill">Skills</h2>
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
                            echo "<div class='eg' data-id='" . $rowSkills['id'] . "' onclick='selectCard(this)'>";
                            echo "<span class='material-icons-sharp'>" . $rowSkills['subjectIcon'] . "</span>";
                            echo "<h3>" . $rowSkills['subjectName'] . "</h3>";
                            echo "<div class='progress'>";
                            echo "<svg><circle cx='38' cy='38' r='36'></circle></svg>";
                            echo "<div class='number'><p>" . $rowSkills['progressPercentage'] . "%</p></div>";
                            echo "</div>";
                            echo "<small class='text-muted'>" . $rowSkills['experience'] . "</small>";
                            echo "</div>";
                        }
                    } else {
                        echo "<p>No results found</p>";
                    }
                    ?>
                </div>





                <div id="popup" class="popup">
                    <span class="close" onclick="closePopup()">&times;</span>
                    <h3>Add New Card</h3>
                    <form id="cardForm">
                        <label for="subjectIcon">Subject Icon:</label>
                        <select id="subjectIcon" required>
                            <option value="code"><i class="fas fa-code"></i> Programming</option>
                            <option value="database"><i class="fas fa-database"></i> Database</option>
                            <option value="laptop"><i class="fas fa-laptop-code"></i> IT</option>
                            <option value="computer"><i class="fas fa-desktop"></i> Computer Science</option>
                            <option value="network"><i class="fas fa-network-wired"></i> Networking</option>
                            <option value="drawing"><i class="fas fa-pen"></i> Drawing</option>
                            <option value="security"><i class="fas fa-shield-alt"></i> Security</option>
                        </select>

                        <!-- Hidden input for instructorID (manually enterable) -->
                        <label for="instructorID">Instructor ID:</label>
                        <input type="text" id="instructorID" name="instructorID" required>

                        <label for="subjectName">Title :</label>
                        <input type="text" id="subjectName" required>

                        <label for="progressPercentage">Strength %:</label>
                        <input type="text" id="progressPercentage" required>

                        <label for="experience">Experience:</label>
                        <input type="text" id="experience" required>

                        <button type="button" onclick="addCard()">Add Card</button>
                    </form>

                </div>

                <div class="card-form" id="updateForm">
                    <h3>Update Card</h3>
                    <div class="form-design">
                        <label for="updatedIcon">Icon:</label>
                        <select id="updatedIcon">
                            <option value="code"><i class="fas fa-code"></i> Programming</option>
                            <option value="database"><i class="fas fa-database"></i> Database</option>
                            <option value="laptop"><i class="fas fa-laptop-code"></i> IT</option>
                            <option value="computer"><i class="fas fa-desktop"></i> Computer Science</option>
                            <option value="network"><i class="fas fa-network-wired"></i> Networking</option>
                            <option value="drawing"><i class="fas fa-pen"></i> Drawing</option>
                            <option value="security"><i class="fas fa-shield-alt"></i> Security</option>
                        </select>


                    </div>

                    <div class="form-design">
                        <label for="updatedTitle"> Title:</label>
                        <input type="text" id="updatedTitle">
                    </div>
                    <div class="form-design">
                        <label for="updatedProgress"> Strenght %:</label>
                        <input type="number" id="updatedProgress" min="0" max="100">
                    </div>
                    <div class="form-design">
                        <label for="updatedDuration">Experience:</label>
                        <input type="text" id="updatedDuration">
                    </div>
                    <div class="button-container">
                        <button onclick="updateCard()">Update</button>
                        <button onclick="hideUpdateForm()">Cancel</button>
                    </div>
                </div>



            </main>


        </div>

    </div>

    <script src="./js/profile.js"></script>

</body>

</html>