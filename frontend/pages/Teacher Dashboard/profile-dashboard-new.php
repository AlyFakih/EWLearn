<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has teacher role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 1) {
    header("Location: ../../login.php");
    exit;
}

// Include the database controller
require_once "php/dbcontroller.php";
$db_handle = new DBController();
$conn = $db_handle->connectDB();

// Get teacher information
$teacher_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT fullName, image, country, email, mobile FROM users WHERE id = ? AND role = 1");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $teacher = $result->fetch_assoc();
    $fullName = $teacher['fullName'];
    $image = $teacher['image'];
    $country = $teacher['country'];
    $email = $teacher['email'];
    $mobile = $teacher['mobile'];
} else {
    // Default values if teacher not found
    $fullName = "Teacher";
    $image = "default.jpg";
    $country = "Unknown";
    $email = "unknown@example.com";
    $mobile = "N/A";
}
$stmt->close();

// Get skills
$stmt = $conn->prepare("SELECT id, subjectIcon, subjectName, progressPercentage, experience FROM instructor_skills WHERE instructorID = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

$skillsData = array();
while ($row = $result->fetch_assoc()) {
    $skillsData[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Dashboard</title>
    
    <!-- Include common header resources -->
    <?php include_once "components/header_includes.php"; ?>
    
    <!-- Page specific CSS -->
    <link rel="stylesheet" href="./css/profile-dashboard.css">
</head>
<body>
    <!-- Include the common header -->
    <?php include_once "components/header.php"; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar with calendar -->
                <div class="col-md-3 col-lg-2">
                    <?php include_once "components/sidebar.php"; ?>
                </div>
                
                <!-- Main content area -->
                <div class="col-md-9 col-lg-10">
                    <div class="content-wrapper">
                        <div class="content-header">
                            <div class="container-fluid">
                                <div class="row mb-2">
                                    <div class="col-sm-6">
                                        <h1 class="m-0">Teacher Profile</h1>
                                    </div>
                                    <div class="col-sm-6">
                                        <ol class="breadcrumb float-sm-right">
                                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                                            <li class="breadcrumb-item active">Profile</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <section class="content">
                            <div class="container-fluid">
                                <div class="row">
                                    <!-- Teacher Profile Info -->
                                    <div class="col-md-4">
                                        <div class="card card-primary card-outline">
                                            <div class="card-body box-profile">
                                                <div class="text-center">
                                                    <img class="profile-user-img img-fluid img-circle" 
                                                         src="<?php echo (!empty($image) && $image !== 'NULL') ? $image : './images/avatar.png'; ?>" 
                                                         alt="User profile picture">
                                                </div>
                                                <h3 class="profile-username text-center"><?php echo $fullName; ?></h3>
                                                <p class="text-muted text-center">Teacher</p>

                                                <ul class="list-group list-group-unbordered mb-3">
                                                    <li class="list-group-item">
                                                        <b>Email</b> <a class="float-right"><?php echo $email; ?></a>
                                                    </li>
                                                    <li class="list-group-item">
                                                        <b>Mobile</b> <a class="float-right"><?php echo $mobile; ?></a>
                                                    </li>
                                                    <li class="list-group-item">
                                                        <b>Country</b> <a class="float-right"><?php echo $country; ?></a>
                                                    </li>
                                                </ul>
                                                
                                                <a href="#" class="btn btn-primary btn-block" id="editProfileBtn">
                                                    <b>Edit Profile</b>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Skills Section -->
                                    <div class="col-md-8">
                                        <div class="card">
                                            <div class="card-header">
                                                <h3 class="card-title">Skills</h3>
                                                <div class="card-tools">
                                                    <button type="button" class="btn btn-primary btn-sm" id="showForm">
                                                        <i class="fas fa-plus"></i> Add Skill
                                                    </button>
                                                    <button type="button" class="btn btn-info btn-sm" id="updateButton">
                                                        <i class="fas fa-edit"></i> Update
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm" id="deleteButton">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="subjects row" id="subjectContainer">
                                                    <?php
                                                    // Loop through the fetched data and display subjects dynamically
                                                    if (!empty($skillsData)) {
                                                        foreach ($skillsData as $skill) {
                                                            $iconClass = "";
                                                            switch ($skill['subjectIcon']) {
                                                                case 'code': 
                                                                    $iconClass = "fas fa-code"; 
                                                                    break;
                                                                case 'database': 
                                                                    $iconClass = "fas fa-database"; 
                                                                    break;
                                                                case 'laptop': 
                                                                    $iconClass = "fas fa-laptop-code"; 
                                                                    break;
                                                                case 'computer': 
                                                                    $iconClass = "fas fa-desktop"; 
                                                                    break;
                                                                case 'network': 
                                                                    $iconClass = "fas fa-network-wired"; 
                                                                    break;
                                                                case 'drawing': 
                                                                    $iconClass = "fas fa-pen"; 
                                                                    break;
                                                                case 'security': 
                                                                    $iconClass = "fas fa-shield-alt"; 
                                                                    break;
                                                                default: 
                                                                    $iconClass = "fas fa-graduation-cap"; 
                                                                    break;
                                                            }
                                                    ?>
                                                    <div class="col-md-6 col-lg-4 mb-4">
                                                        <div class="card skill-card" data-id="<?php echo $skill['id']; ?>">
                                                            <div class="progress-circle">
                                                                <svg>
                                                                    <circle class="bg" cx="57" cy="57" r="52"></circle>
                                                                    <circle class="meter-<?php echo $skill['id']; ?>" cx="57" cy="57" r="52" 
                                                                            style="--percent: <?php echo $skill['progressPercentage']; ?>"></circle>
                                                                </svg>
                                                                <div class="number">
                                                                    <h3><?php echo $skill['progressPercentage']; ?><span>%</span></h3>
                                                                </div>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="subject">
                                                                    <div class="left">
                                                                        <i class="<?php echo $iconClass; ?>"></i>
                                                                        <h5><?php echo $skill['subjectName']; ?></h5>
                                                                    </div>
                                                                </div>
                                                                <div class="exp"><?php echo $skill['experience']; ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php
                                                        }
                                                    } else {
                                                        echo "<div class='col-12'><p>No skills found. Add your first skill!</p></div>";
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Skill Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Add New Skill</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="cardForm">
                        <div class="form-group">
                            <label for="subjectIcon">Subject Icon:</label>
                            <select id="subjectIcon" class="form-control" required>
                                <option value="code"><i class="fas fa-code"></i> Programming</option>
                                <option value="database"><i class="fas fa-database"></i> Database</option>
                                <option value="laptop"><i class="fas fa-laptop-code"></i> IT</option>
                                <option value="computer"><i class="fas fa-desktop"></i> Computer Science</option>
                                <option value="network"><i class="fas fa-network-wired"></i> Networking</option>
                                <option value="drawing"><i class="fas fa-pen"></i> Drawing</option>
                                <option value="security"><i class="fas fa-shield-alt"></i> Security</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="subjectName">Title:</label>
                            <input type="text" class="form-control" id="subjectName" required>
                        </div>
                        <div class="form-group">
                            <label for="progressPercentage">Strength % (0-100):</label>
                            <input type="number" class="form-control" id="progressPercentage" min="0" max="100" required>
                        </div>
                        <div class="form-group">
                            <label for="experience">Experience:</label>
                            <input type="text" class="form-control" id="experience" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="addCard()">Add Skill</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Update Skill Modal -->
    <div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateModalLabel">Update Skill</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="updateForm">
                        <input type="hidden" id="update_skill_id">
                        <div class="form-group">
                            <label for="updatedIcon">Subject Icon:</label>
                            <select id="updatedIcon" class="form-control" required>
                                <option value="code"><i class="fas fa-code"></i> Programming</option>
                                <option value="database"><i class="fas fa-database"></i> Database</option>
                                <option value="laptop"><i class="fas fa-laptop-code"></i> IT</option>
                                <option value="computer"><i class="fas fa-desktop"></i> Computer Science</option>
                                <option value="network"><i class="fas fa-network-wired"></i> Networking</option>
                                <option value="drawing"><i class="fas fa-pen"></i> Drawing</option>
                                <option value="security"><i class="fas fa-shield-alt"></i> Security</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="updatedTitle">Title:</label>
                            <input type="text" class="form-control" id="updatedTitle" required>
                        </div>
                        <div class="form-group">
                            <label for="updatedProgress">Strength % (0-100):</label>
                            <input type="number" class="form-control" id="updatedProgress" min="0" max="100" required>
                        </div>
                        <div class="form-group">
                            <label for="updatedDuration">Experience:</label>
                            <input type="text" class="form-control" id="updatedDuration" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="updateCard()">Update Skill</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include the common footer -->
    <?php include_once "components/footer.php"; ?>

    <!-- Page specific scripts -->
    <script src="js/profile-new.js"></script>
</body>
</html>
