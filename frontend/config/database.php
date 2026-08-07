<?php

class Database {

    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "student_management";

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
