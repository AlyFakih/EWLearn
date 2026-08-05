<?php
class DBController
{
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $dbname = "student_management";
    private $conn;

    function __construct()
    {
        $this->conn = $this->connectDB();
    }

    function connectDB()
    {
        $conn = mysqli_connect($this->host, $this->username, $this->password, $this->dbname);
        return $conn;
    }

    function readData($query)
    {
        $result = mysqli_query($this->conn, $query);
        $resultset = array();
        while ($row = mysqli_fetch_array($result)) {
            $resultset[] = $row;
        }
        return $resultset;
    }

    function numRows($query)
    {
        $result = mysqli_query($this->conn, $query);
        $rowcount = mysqli_num_rows($result);
        return $rowcount;
    }

    function executeInsert($query)
    {
        $result = mysqli_query($this->conn, $query);
        if ($result) {
            $insert_id = mysqli_insert_id($this->conn);
            return $insert_id;
        } else {
            return false;
        }
    }

    function cleanData($data)
    {
        $data = mysqli_real_escape_string($this->conn, strip_tags($data));
        return $data;
    }
}
?>
