<?php
$servername = "localhost"; // localhost for XAMPP
$username = "root"; // default XAMPP username
$password = ""; // default XAMPP password is empty
$dbname = "student_management"; 

// create connection
$conn = mysqli_connect($servername, $username, $password,$dbname) ;

// check connection
if (!$conn) {
 die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");
// echo "Connected successfully";
?>