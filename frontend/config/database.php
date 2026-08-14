<?php

require_once __DIR__ . '/../../backend/load_env.php';

class Database {

    private $host;
    private $username;
    private $password;
    private $database;

    public function __construct() {
        $env = load_env(__DIR__ . '/../../backend/.env');
        $this->host = $env['DB_HOST'] ?? '';
        $this->username = $env['DB_USERNAME'] ?? '';
        $this->password = $env['DB_PASSWORD'] ?? '';
        $this->database = $env['DB_NAME'] ?? '';
    }

    public function connect(){

        $conn = new mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->database
        );

        if($conn->connect_error){
            die("Database connection failed: " . $conn->connect_error);
        }

        $conn->set_charset("utf8mb4");

        return $conn;
    }
}

?>
