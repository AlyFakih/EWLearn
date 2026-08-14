<?php
require_once __DIR__ . '/load_env.php';
$env = load_env(__DIR__ . '/.env');

$servername = $env['DB_HOST'] ?? '';
$username = $env['DB_USERNAME'] ?? '';
$password = $env['DB_PASSWORD'] ?? '';
$dbname = $env['DB_NAME'] ?? '';

// create connection
$conn = mysqli_connect($servername, $username, $password,$dbname) ;

// check connection
if (!$conn) {
 die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");
// echo "Connected successfully";
?>