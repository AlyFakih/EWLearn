<?php
require_once __DIR__ . '/load_env.php';
$env = load_env(__DIR__ . '/.env');

$servername = $env['DB_HOST'] ?? '';
$username = $env['DB_USERNAME'] ?? '';
$password = $env['DB_PASSWORD'] ?? '';
$dbname = $env['DB_NAME'] ?? '';

// Validate environment variables
if (empty($servername) || empty($username) || empty($dbname)) {
    error_log("Missing database configuration in .env file");
    throw new Exception("Database configuration error: missing credentials in .env");
}

// Create connection - mysqli_connect throws mysqli_sql_exception in PHP 8.0+
try {
    $conn = @mysqli_connect($servername, $username, $password, $dbname);

    if (!$conn) {
        $error = mysqli_connect_error();
        error_log("Database connection failed: " . $error);
        throw new Exception("Database connection failed: " . $error);
    }

    // Set charset to UTF-8
    if (!mysqli_set_charset($conn, "utf8")) {
        $error = mysqli_error($conn);
        error_log("Failed to set charset: " . $error);
        throw new Exception("Charset configuration failed: " . $error);
    }
} catch (mysqli_sql_exception $e) {
    error_log("Database connection exception: " . $e->getMessage());
    throw new Exception("Database connection error: " . $e->getMessage());
}
?>