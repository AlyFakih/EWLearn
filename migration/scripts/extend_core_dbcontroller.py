from pathlib import Path


file = Path(
"frontend/core/DBController.php"
)


content = file.read_text(
    encoding="utf-8",
    errors="ignore"
)


insert = r'''

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

'''


position = content.rfind("}")


content = (
    content[:position]
    +
    insert
    +
    "\n"
    +
    content[position:]
)


file.write_text(
    content,
    encoding="utf-8"
)


print("Core DBController extended")
