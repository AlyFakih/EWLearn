<?php
$page_title = "Profile";
$current_page = "profile";
$page_css = "profile-dashboard";
$page_js = "profile";

include_once "php/header.php";
// $db_handle, $user_id, $teacher (row from users) are now provided by header.php

// Create (Add) skill
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $subjectIcon = $_POST['subjectIcon'];
    $subjectName = $_POST['subjectName'];
    $progressPercentage = $_POST['progressPercentage'];
    $experience = $_POST['experience'];

    $db_handle->executeUpdatePrepared(
        "INSERT INTO instructor_skills (instructorID, subjectIcon, subjectName, progressPercentage, experience) VALUES (?, ?, ?, ?, ?)",
        "issis",
        [$user_id, $subjectIcon, $subjectName, $progressPercentage, $experience]
    );
    header("Location: profile-dashboard.php");
    exit();
}

// Update skill
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update') {
    $subjectIcon = $_POST['subjectIcon'];
    $subjectName = $_POST['subjectName'];
    $progressPercentage = $_POST['progressPercentage'];
    $experience = $_POST['experience'];
    $skill_id = (int)$_POST['id'];

    $affected = $db_handle->executeUpdatePrepared(
        "UPDATE instructor_skills SET subjectIcon=?, subjectName=?, progressPercentage=?, experience=? WHERE id=? AND instructorID=?",
        "ssisii",
        [$subjectIcon, $subjectName, $progressPercentage, $experience, $skill_id, $user_id]
    );
    echo $affected > 0 ? "Update card success" : "Failed to update the current card";
    exit();
}

// Delete skill
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $skill_id = (int)$_POST['id'];
    $affected = $db_handle->executeUpdatePrepared(
        "DELETE FROM instructor_skills WHERE id=? AND instructorID=?",
        "ii",
        [$skill_id, $user_id]
    );
    echo $affected > 0 ? "delete the card success" : "Failed to delete the card";
    exit();
}

// Read skills for this instructor
$skillsData = $db_handle->executeSelectPrepared(
    "SELECT id, subjectIcon, subjectName, progressPercentage, experience FROM instructor_skills WHERE instructorID = ?",
    "i",
    [$user_id]
);

$fullName = $teacher['fullName'] ?: 'User';
// users.image is stored relative to frontend/images/ (matches the Student
// Dashboard's equivalent), not this page's own directory
$image = !empty($teacher['image']) ? '../../images/' . $teacher['image'] : './images/logo.jpg';
$country = $teacher['country'] ?: 'Not specified';
$email = $teacher['email'] ?: 'No email';
$mobile = $teacher['mobile'] ?: 'No mobile';
?>

<div class="profile-grid">
    <div class="card profile-card">
        <div class="profile-photo">
            <img id="profileImage" src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($fullName); ?>">
            <label for="profileImageInput" class="photo-edit-btn" title="Change profile photo">
                <i class="fas fa-pencil-alt"></i>
            </label>
            <input type="file" id="profileImageInput" accept="image/jpeg,image/png,image/gif,image/webp" hidden>
        </div>
        <div class="about">
            <h5>Name</h5>
            <p data-field="fullName"><span class="field-value"><?php echo htmlspecialchars($fullName); ?></span> <i class="fas fa-pencil-alt"></i></p>

            <h5>Contact</h5>
            <p data-field="mobile"><span class="field-value"><?php echo htmlspecialchars($mobile); ?></span> <i class="fas fa-pencil-alt"></i></p>

            <h5>Email</h5>
            <p data-field="email"><span class="field-value"><?php echo htmlspecialchars($email); ?></span> <i class="fas fa-pencil-alt"></i></p>

            <h5>Country</h5>
            <p data-field="country"><span class="field-value"><?php echo htmlspecialchars($country); ?></span> <i class="fas fa-pencil-alt"></i></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Skills &amp; Subjects</h2>
            <div class="card-actions">
                <button type="button" class="btn btn-primary" onclick="showPopup()"><i class="fas fa-plus"></i> Add</button>
                <button type="button" class="btn btn-secondary" onclick="showUpdateForm()"><i class="fas fa-edit"></i> Update</button>
                <button type="button" class="btn btn-danger" id="deleteButton" onclick="deleteSelectedCard()"><i class="fas fa-trash"></i> Delete</button>
            </div>
        </div>

        <div class="skills-grid" id="subjectContainer">
            <?php if (!empty($skillsData)): ?>
                <?php foreach ($skillsData as $rowSkills): ?>
                    <div class="eg" data-id="<?php echo (int)$rowSkills['id']; ?>" onclick="selectCard(this)">
                        <span class="material-icons-sharp"><?php echo htmlspecialchars($rowSkills['subjectIcon']); ?></span>
                        <h3><?php echo htmlspecialchars($rowSkills['subjectName']); ?></h3>
                        <div class="progress">
                            <svg><circle cx="38" cy="38" r="36" style="--pct: <?php echo (int)$rowSkills['progressPercentage']; ?>;"></circle></svg>
                            <div class="number"><p><?php echo (int)$rowSkills['progressPercentage']; ?>%</p></div>
                        </div>
                        <small class="text-muted"><?php echo htmlspecialchars($rowSkills['experience']); ?></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-graduation-cap empty-icon"></i>
                    <h3>No skills added yet</h3>
                    <p>Showcase what you teach by adding a skill card.</p>
                </div>
            <?php endif; ?>
        </div>

        <div id="popup" class="modal-overlay">
            <div class="modal-panel">
                <span class="close" onclick="closePopup()">&times;</span>
                <h3>Add New Skill</h3>
                <form id="cardForm">
                    <div class="form-group">
                        <label for="subjectIcon">Subject Icon</label>
                        <select id="subjectIcon" required>
                            <option value="code">Programming</option>
                            <option value="database">Database</option>
                            <option value="laptop">IT</option>
                            <option value="computer">Computer Science</option>
                            <option value="network">Networking</option>
                            <option value="drawing">Drawing</option>
                            <option value="security">Security</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="subjectName">Title</label>
                        <input type="text" id="subjectName" required>
                    </div>
                    <div class="form-group">
                        <label for="progressPercentage">Strength %</label>
                        <input type="number" id="progressPercentage" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label for="experience">Experience</label>
                        <input type="text" id="experience" required>
                    </div>
                    <div class="card-actions">
                        <button type="button" class="btn btn-primary" onclick="addCard()">Add Card</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-overlay" id="updateForm">
            <div class="modal-panel">
                <h3>Update Skill</h3>
                <div class="form-group">
                    <label for="updatedIcon">Icon</label>
                    <select id="updatedIcon">
                        <option value="code">Programming</option>
                        <option value="database">Database</option>
                        <option value="laptop">IT</option>
                        <option value="computer">Computer Science</option>
                        <option value="network">Networking</option>
                        <option value="drawing">Drawing</option>
                        <option value="security">Security</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="updatedTitle">Title</label>
                    <input type="text" id="updatedTitle">
                </div>
                <div class="form-group">
                    <label for="updatedProgress">Strength %</label>
                    <input type="number" id="updatedProgress" min="0" max="100">
                </div>
                <div class="form-group">
                    <label for="updatedDuration">Experience</label>
                    <input type="text" id="updatedDuration">
                </div>
                <div class="card-actions">
                    <button type="button" class="btn btn-primary" onclick="updateCard()">Update</button>
                    <button type="button" class="btn btn-secondary" onclick="hideUpdateForm()">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once "php/footer.php";
?>
