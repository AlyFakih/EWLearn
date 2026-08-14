<?php
// Server-side authorization gate: instructor only. Runs before any other
// logic so nothing is emitted to an unauthenticated or wrong-role caller.
require_once __DIR__ . '/../../../core/auth_guard.php';
auth_require_role('instructor');

require_once "../../../core/DBController.php";
$db_handle = new DBController();

// Handle the form submission from the new form
if (isset($_POST["newstudentID"])) {
    // Extract and clean the form data
    $newstudentID = $db_handle->cleanData($_POST['newstudentID']);
    $newName = $db_handle->cleanData($_POST['newName']);
    $newGrade = $db_handle->cleanData($_POST['newGrade']);


    // Insert the new grade into the database
    $sql = "INSERT INTO course_grades (student_id, course_id, grade, term)
    VALUES ('$newstudentID', '1', '$newGrade', 'Fall 2025');";
    $insertedId = $db_handle->executeInsert($sql);

    // Output the newly inserted row
    echo '<tr>';
    echo '<td data-id="student_id">' . $newstudentID . '</td>';
    echo '<td data-id="student_name">' . $newName . '</td>';
    echo '<td data-id="student_grade">' . $newGrade . '</td>';
    echo '<td>';
    echo '<button class="edit btn btn-success"><i class="fas fa-pencil" aria-hidden="true"></i></button>';
    echo '<button class="save btn btn-success" style="display:none;" data-id="' . $newstudentID . '"><i class="fas fa-check" aria-hidden="true"></i></button>';
    echo '<button class="cancel btn btn-danger" style="display:none;"><i class="fas fa-times" aria-hidden="true"></i></button>';
    echo '<button class="del btn btn-warning" data-id="' . $newstudentID . '"><i class="fas fa-trash" aria-hidden="true"></i></button>';
    echo '</td>';
    echo '</tr>';
    exit; // Exit to prevent further processing
}

// Handle the form submission for update
elseif (isset($_POST["student_id"])) {
    // Extract and clean the form data
    $student_id = $db_handle->cleanData($_POST['student_id']);
    $student_name = $db_handle->cleanData($_POST['student_name']);
    $student_grade = $db_handle->cleanData($_POST['student_grade']);


    // Update or insert into the database
if (isset($_POST['student_id'])) {
    $sql = "UPDATE course_grades
            SET 
                `student_id` = '$student_id',
                `grade` = '$student_grade'
            WHERE
                `student_id` = '$student_id'";

    $db_handle->executeInsert($sql);
} else {
    $sql = "INSERT INTO course_grades (student_id, course_id, grade, term)
    VALUES ('$student_id', '1', '$student_grade', 'Fall 2025');";

    $db_handle->executeInsert($sql);
}


    // Fetch all rows to send them back to the client after update
    $sql = "SELECT
    cg.student_id AS StudentID,
    u.fullName AS Name,
    cg.grade AS Grade
    FROM
    course_grades cg
    JOIN users u ON cg.student_id = u.id
    WHERE u.role = 'student';";
    $allRows = $db_handle->readData($sql);
}

// Output all rows after update
if (!empty($allRows)) {
    $html = '';
    foreach ($allRows as $row) {
        $html .= '<tr>';
        $html .= '<td data-id="student_id">' . $row['StudentID'] . '</td>';
        $html .= '<td data-id="student_name">' . $row['Name'] . '</td>';
        $html .= '<td data-id="student_grade">' . $row['Grade'] . '</td>';
        $html .= '<td>';
        $html .= '<button class="edit btn btn-success"><i class="fas fa-pencil" aria-hidden="true"></i></button>';
        $html .= '<button class="save btn btn-success" style="display:none;" data-id="' . $row['StudentID'] . '"><i class="fas fa-check" aria-hidden="true"></i></button>';
        $html .= '<button class="cancel btn btn-danger" style="display:none;"><i class="fas fa-times" aria-hidden="true"></i></button>';
        $html .= '<button class="del btn btn-warning" data-id="' . $row['StudentID'] . '"><i class="fas fa-trash" aria-hidden="true"></i></button>';
        $html .= '</td>';
        $html .= '</tr>';
    }
    echo $html;
    exit; // Exit to prevent further processing
}

// Handle delete action
if (isset($_POST['action']) && $_POST['action'] == 'del') {
    $id = $db_handle->cleanData($_POST['id']);
    if ($id > 0) {
        $sql = "DELETE FROM course_grades WHERE student_id = '$id'";
        $result = mysqli_query($db_handle->connectDB(), $sql); // Use mysqli_query for delete operation
        if ($result) {
            echo 1;
            exit;
        } else {
            echo 0;
            exit;
        }
    }
}
