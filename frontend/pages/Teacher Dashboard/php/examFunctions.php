<?php
require_once "dbcontroller.php";
$db_handle = new DBController();

// Handle the form submission from the new form
if (isset($_POST["newcourseID"])) {
    // Extract and clean the form data
    $newcourseID = $db_handle->cleanData($_POST['newcourseID']);
    $newSubject = $db_handle->cleanData($_POST['newSubject']);
    $newDate = $db_handle->cleanData($_POST['newDate']);
    $newTime = $db_handle->cleanData($_POST['newTime']);
    $newRoom = $db_handle->cleanData($_POST['newRoom']);

    // Insert the new assignment into the database
    $sql = "INSERT INTO `exam`(`id`, `date`, `time`, `courseID`, `subject`, `room`)
    VALUES (NULL, '$newDate', '$newTime', '$newcourseID', '$newSubject', '$newRoom')";
    $insertedId = $db_handle->executeInsert($sql);

    // Output the newly inserted row
    echo '<tr>';
    echo '<td data-id="exam_courseID">' . $newcourseID . '</td>';
    echo '<td data-id="exam_subject">' . $newSubject . '</td>';
    echo '<td data-id="exam_date">' . $newDate . '</td>';
    echo '<td data-id="exam_time">' . $newTime. '</td>';
    echo '<td data-id="exam_room">' . $newRoom . '</td>';
    echo '<td>';
    echo '<button class="edit btn btn-success"><i class="fas fa-pencil" aria-hidden="true"></i></button>';
    echo '<button class="save btn btn-success" style="display:none;" data-id="' . $insertedId . '"><i class="fas fa-check" aria-hidden="true"></i></button>';
    echo '<button class="cancel btn btn-danger" style="display:none;"><i class="fas fa-times" aria-hidden="true"></i></button>';
    echo '<button class="del btn btn-warning" data-id="' . $insertedId . '"><i class="fas fa-trash" aria-hidden="true"></i></button>';
    echo '</td>';
    echo '</tr>';
    exit; // Exit to prevent further processing
}

// Handle the form submission for update
elseif (isset($_POST["exam_courseID"])) {
    // Extract and clean the form data
    $id = $db_handle->cleanData($_POST['id']);
    $exam_courseID = $db_handle->cleanData($_POST['exam_courseID']);
    $exam_subject = $db_handle->cleanData($_POST['exam_subject']);
    $exam_date = $db_handle->cleanData($_POST['exam_date']);
    $exam_time = $db_handle->cleanData($_POST['exam_time']);
    $exam_room = $db_handle->cleanData($_POST['exam_room']);

    // Update or insert into the database
    if (isset($_POST['id'])) {
        $sql = "UPDATE `exam`
                SET 
                    `courseID` = '$exam_courseID',
                    `subject` = '$exam_subject',
                    `room` = '$exam_room',
                    `date` = '$exam_date',
                    `time` = '$exam_time'
                WHERE
                    `id` = '$id'";
        
        $db_handle->executeInsert($sql);
    } else {
        $sql = "INSERT INTO `exam`(`id`, `date`, `time`, `courseID`, `subject`, `room`)
        VALUES (NULL, '$exam_date', '$exam_time', '$exam_courseID', '$exam_subject', '$exam_room')";

        $db_handle->executeInsert($sql);
    }

    // Fetch all rows to send them back to the client after update
    $sql = "SELECT `id`, `date`, `time`, `courseID`, `subject`, `room` FROM `exam`";
    $allRows = $db_handle->readData($sql);
}

// Output all rows after update
if (!empty($allRows)) {
    $html = '';
    foreach ($allRows as $row) {
        $html .= '<tr>';
        $html .= '<td data-id="exam_courseID">' . $row['courseID'] . '</td>';
        $html .= '<td data-id="exam_subject">' . $row['subject'] . '</td>';
        $html .= '<td data-id="exam_date">' . $row['date'] . '</td>';
        $html .= '<td data-id="exam_time">' . $row['time'] . '</td>';
        $html .= '<td data-id="exam_room">' . $row['room'] . '</td>';
        $html .= '<td>';
        $html .= '<button class="edit btn btn-success"><i class="fas fa-pencil" aria-hidden="true"></i></button>';
        $html .= '<button class="save btn btn-success" style="display:none;" data-id="' . $row['id'] . '"><i class="fas fa-check" aria-hidden="true"></i></button>';
        $html .= '<button class="cancel btn btn-danger" style="display:none;"><i class="fas fa-times" aria-hidden="true"></i></button>';
        $html .= '<button class="del btn btn-warning" data-id="' . $row['id'] . '"><i class="fas fa-trash" aria-hidden="true"></i></button>';
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
        $sql = "DELETE FROM `exam` WHERE id = '$id'";
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
