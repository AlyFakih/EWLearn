<?php
require_once "dbcontroller.php";
$db_handle = new DBController();

// Handle the form submission from the new form
if (isset($_POST["newstudentID"])) {
    // Extract and clean the form data
    $newstudentID = $db_handle->cleanData($_POST['newstudentID']);
    $newName = $db_handle->cleanData($_POST['newName']);
    $newEmail = $db_handle->cleanData($_POST['newEmail']);
    $newMobile = $db_handle->cleanData($_POST['newMobile']);
    $newCountry = $db_handle->cleanData($_POST['newCountry']);

    // Insert the new assignment into the database
    $sql = "INSERT INTO users (id, role, fullName, country, email, mobile)
    VALUES ('$newstudentID', '0', '$newName', '$newCountry', '$newEmail', '$newMobile');";
    $insertedId = $db_handle->executeInsert($sql);

    // Output the newly inserted row
    echo '<tr>';
    echo '<td data-id="student_id">' . $newstudentID . '</td>';
    echo '<td data-id="student_name">' . $newName . '</td>';
    echo '<td data-id="student_email">' . $newEmail . '</td>';
    echo '<td data-id="student_mobile">' . $newMobile. '</td>';
    echo '<td data-id="student_country">' . $newCountry . '</td>';
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
    $student_email = $db_handle->cleanData($_POST['student_email']);
    $student_mobile = $db_handle->cleanData($_POST['student_mobile']);
    $student_country = $db_handle->cleanData($_POST['student_country']);

    // Update or insert into the database
    if (isset($_POST['id'])) {
        $sql = "UPDATE users
                SET 
                    `id` = '$student_id',
                    `role` = '0',
                    `fullName` = '$student_name',
                    `country` = '$student_country',
                    `email` = '$student_email',
                    `mobile` = '$student_mobile'
                WHERE
                    `users`.`id` = '$student_id'";
        
        $db_handle->executeInsert($sql);
    } else {
        $sql = "INSERT INTO users (id, role, fullName, country, email, mobile)
        VALUES ('$newstudentID', '0', '$newName', '$newCountry', '$newEmail', '$newMobile');";

        $db_handle->executeInsert($sql);
    }

    // Fetch all rows to send them back to the client after update
    $sql = "SELECT u.id AS ID, u.fullName AS NAME, u.email AS EMAIL, u.mobile AS MOBILE, u.country AS COUNTRY
    FROM users u 
    WHERE u.role = 0";
    $allRows = $db_handle->readData($sql);
}

// Output all rows after update
if (!empty($allRows)) {
    $html = '';
    foreach ($allRows as $row) {
        $html .= '<tr>';
        $html .= '<td data-id="student_id">' . $row['ID'] . '</td>';
        $html .= '<td data-id="student_name">' . $row['NAME'] . '</td>';
        $html .= '<td data-id="student_email">' . $row['EMAIL'] . '</td>';
        $html .= '<td data-id="student_mobile">' . $row['MOBILE'] . '</td>';
        $html .= '<td data-id="student_country">' . $row['COUNTRY'] . '</td>';
        $html .= '<td>';
        $html .= '<button class="edit btn btn-success"><i class="fas fa-pencil" aria-hidden="true"></i></button>';
        $html .= '<button class="save btn btn-success" style="display:none;" data-id="' . $row['ID'] . '"><i class="fas fa-check" aria-hidden="true"></i></button>';
        $html .= '<button class="cancel btn btn-danger" style="display:none;"><i class="fas fa-times" aria-hidden="true"></i></button>';
        $html .= '<button class="del btn btn-warning" data-id="' . $row['ID'] . '"><i class="fas fa-trash" aria-hidden="true"></i></button>';
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
        $sql = "DELETE FROM users WHERE ID = '$id'";
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
