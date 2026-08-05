<?php
require_once "dbcontroller.php";
$db_handle = new DBController();

// Handle the form submission from the new form
if (isset($_POST["newstudentID"])) {
    // Extract and clean the form data
    $newstudentID = $db_handle->cleanData($_POST['newstudentID']);
    $newName = $db_handle->cleanData($_POST['newName']);
    $newCourse = $db_handle->cleanData($_POST['newCourse']);
    $newDate = $db_handle->cleanData($_POST['newDate']);
    $newStatus = $db_handle->cleanData($_POST['newStatus']);

    // Insert the new assignment into the database
    $sql = "INSERT INTO assignment_submissions (student_id, assignment_id, content, status, submitted_at)
            VALUES ('$newstudentID', '$newName', '$newCourse', '$newStatus', '$newDate')";
    $insertedId = $db_handle->executeInsert($sql);

    // Output the newly inserted row
    echo '<tr>';
    echo '<td data-id="student_id">' . $newstudentID . '</td>';
    echo '<td data-id="product_name">' . $newName . '</td>';
    echo '<td data-id="product_course">' . $newCourse . '</td>';
    echo '<td data-id="product_date">' . $newDate . '</td>';
    echo '<td data-id="product_status" style="text-align: right;">' . $newStatus . '</td>';
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
    $product_id = $db_handle->cleanData($_POST['student_id']);
    $product_name = $db_handle->cleanData($_POST['product_name']);
    $product_course = $db_handle->cleanData($_POST['product_course']);
    $product_date = $db_handle->cleanData($_POST['product_date']);
    $product_status = $db_handle->cleanData($_POST['product_status']);

    // Update or insert into the database
    if (isset($_POST['id'])) {
        $sql = "UPDATE assignment_submissions
                SET 
                    `content` = '$product_name',
                    `assignment_id` = '$product_course',
                    `submitted_at` = '$product_date',
                    `status` = '$product_status'
                WHERE
                    `student_id` = '$product_id'";
        
        $db_handle->executeInsert($sql);
    } else {
        $sql = "INSERT INTO assignment_submissions (student_id, assignment_id, content, submitted_at, status)
                VALUES ('$product_id', '$product_course', '$product_name', '$product_date', '$product_status')";

        $db_handle->executeInsert($sql);
    }

    // Fetch all rows to send them back to the client after update
    $sql = "SELECT s.student_id as studentID, s.content as studentName, s.assignment_id as courseName, 
            s.submitted_at as date, s.status 
            FROM assignment_submissions s 
            JOIN assignment a ON s.assignment_id = a.id";
    $allRows = $db_handle->readData($sql);
}

// Output all rows after update
if (!empty($allRows)) {
    $html = '';
    foreach ($allRows as $row) {
        $html .= '<tr>';
        $html .= '<td data-id="student_id">' . $row['studentID'] . '</td>';
        $html .= '<td data-id="product_name">' . $row['studentName'] . '</td>';
        $html .= '<td data-id="product_course">' . $row['courseName'] . '</td>';
        $html .= '<td data-id="product_date">' . $row['date'] . '</td>';
        $html .= '<td data-id="product_status" style="text-align: right;">' . $row['status'] . '</td>';
        $html .= '<td>';
        $html .= '<button class="edit btn btn-success"><i class="fas fa-pencil" aria-hidden="true"></i></button>';
        $html .= '<button class="save btn btn-success" style="display:none;" data-id="' . $row['studentID'] . '"><i class="fas fa-check" aria-hidden="true"></i></button>';
        $html .= '<button class="cancel btn btn-danger" style="display:none;"><i class="fas fa-times" aria-hidden="true"></i></button>';
        $html .= '<button class="del btn btn-warning" data-id="' . $row['studentID'] . '"><i class="fas fa-trash" aria-hidden="true"></i></button>';
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
        $sql = "DELETE FROM assignment_submissions WHERE student_id = '$id'";
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
?>
