<?php

require_once __DIR__ . '/../config/database.php';


class DBController {


    private $connection;


    public function __construct(){

        $database = new Database();

        $this->connection = $database->connect();

    }


    public function executeSelectPrepared($query,$types="",$params=[]){

        $stmt=$this->connection->prepare($query);

        if(!$stmt){
            die($this->connection->error);
        }


        if(!empty($params)){
            $stmt->bind_param($types,...$params);
        }


        $stmt->execute();

        $result=$stmt->get_result();


        return $result->fetch_all(MYSQLI_ASSOC);

    }



    public function executeUpdatePrepared($query,$types="",$params=[]){

        $stmt=$this->connection->prepare($query);


        if(!empty($params)){
            $stmt->bind_param($types,...$params);
        }


        $stmt->execute();


        return $stmt->affected_rows;

    }


    public function getConnection(){

        return $this->connection;

    }


}


?>
