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




    public function connectDB(){

        $database = new Database();

        $this->connection = $database->connect();

        return $this->connection;
    }


    public function executeQuery($query){

        return $this->connection->query($query);

    }


    public function executeInsert($query){

        if($this->connection->query($query)){
            return $this->connection->insert_id;
        }

        return false;
    }


    public function readData($query){

        $result = $this->connection->query($query);

        $data=[];

        if($result){

            while($row=$result->fetch_assoc()){

                $data[]=$row;

            }

        }

        return $data;
    }


    public function cleanData($data){

        return htmlspecialchars(
            stripslashes(trim($data))
        );

    }


    public function escapeString($value){

        return $this->connection->real_escape_string($value);

    }


    public function getLastInsertId(){

        return $this->connection->insert_id;

    }


    public function beginTransaction(){

        return $this->connection->begin_transaction();

    }


    public function commitTransaction(){

        return $this->connection->commit();

    }


    public function rollbackTransaction(){

        return $this->connection->rollback();

    }


    /**
     * The instructor-course relationship is stored in `instructorcourse`,
     * keyed by users.fullName / courses.courseTitle (legacy schema) rather
     * than a courses.teacher_id column, which does not exist in the database.
     */
    public function isCourseOwnedByTeacher($course_id, $teacher_user_id){

        $query = "SELECT c.id FROM courses c
                  JOIN instructorcourse ic ON ic.courseID = c.courseTitle
                  JOIN users tu ON tu.fullName = ic.userInstructorID
                  WHERE c.id = ? AND tu.id = ?";

        $result = $this->executeSelectPrepared($query, "ii", [$course_id, $teacher_user_id]);

        return !empty($result);

    }


    public function getTeacherCourseIds($teacher_user_id){

        $query = "SELECT c.id FROM courses c
                  JOIN instructorcourse ic ON ic.courseID = c.courseTitle
                  JOIN users tu ON tu.fullName = ic.userInstructorID
                  WHERE tu.id = ?";

        $rows = $this->executeSelectPrepared($query, "i", [$teacher_user_id]);

        return array_column($rows, 'id');

    }

}


?>
