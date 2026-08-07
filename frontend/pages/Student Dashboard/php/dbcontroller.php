<?php
class StudentDBController {
    private $host = "localhost";
    private $user = "root";
    private $password = ""; // Use the same password you set in config.php
    private $database = "student_management";
    private $conn;
    
    function __construct() {
        $this->conn = $this->connectDB();
    }
    
    function connectDB() {
        $conn = mysqli_connect($this->host, $this->user, $this->password, $this->database);
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }
        mysqli_set_charset($conn, "utf8");
        return $conn;
    }
    
    function readData($query) {
        $result = mysqli_query($this->conn, $query);
        $resultArray = array();
        
        if ($result) {
            while($row = mysqli_fetch_assoc($result)) {
                $resultArray[] = $row;
            }
        }
        
        return $resultArray;
    }
    
    /**
     * Execute a prepared statement with parameters
     * @param string $query SQL query with placeholders (?, ?, ...)
     * @param string $types Types of parameters (i: integer, s: string, d: double, b: blob)
     * @param array $params Array of parameters to bind
     * @return array Result array of associative arrays
     */
    function executeSelectPrepared($query, $types = null, $params = []) {
        $stmt = $this->conn->prepare($query);
        $resultArray = [];
        
        if ($stmt) {
            if ($types && $params) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $resultArray[] = $row;
                }
            }
            
            $stmt->close();
        }
        
        return $resultArray;
    }
    
    /**
     * Execute an INSERT, UPDATE or DELETE with prepared statement
     * @param string $query SQL query with placeholders
     * @param string $types Types of parameters (i: integer, s: string, d: double, b: blob)
     * @param array $params Array of parameters to bind
     * @return int|bool Last inserted ID or TRUE on success, FALSE on failure
     */
    function executeUpdatePrepared($query, $types = null, $params = []) {
        $stmt = $this->conn->prepare($query);
        
        if ($stmt) {
            if ($types && $params) {
                $stmt->bind_param($types, ...$params);
            }
            
            $result = $stmt->execute();
            
            if ($result) {
                if (strpos(strtoupper($query), 'INSERT') === 0) {
                    $insertId = $stmt->insert_id;
                    $stmt->close();
                    return $insertId;
                } else {
                    $affectedRows = $stmt->affected_rows;
                    $stmt->close();
                    return $affectedRows;
                }
            }
            
            $stmt->close();
            return false;
        }
        
        return false;
    }
    
    function executeQuery($query) {
        $result = mysqli_query($this->conn, $query);
        return $result;
    }
    
    function executeInsert($query) {
        if (mysqli_query($this->conn, $query)) {
            return mysqli_insert_id($this->conn);
        } else {
            return false;
        }
    }
    
    function numRows($query) {
        $result = mysqli_query($this->conn, $query);
        $rowcount = mysqli_num_rows($result);
        return $rowcount;
    }
    
    function escapeString($value) {
        return mysqli_real_escape_string($this->conn, $value);
    }
    
    /**
     * Clean input data to prevent XSS attacks
     * @param mixed $data Data to clean
     * @return mixed Cleaned data
     */
    function cleanData($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->cleanData($value);
            }
        } else {
            $data = $this->escapeString(trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8')));
        }
        return $data;
    }
}
?>
