<?php
include "./config.php";

function userExists($conn, $email){
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s",$email);
            $stmt->execute();
                $stmt->store_result();
                    return $stmt->num_rows > 0;
                    }
                    
function insertUser($conn,$role,$name,$country,$email,$mobile,$blood,$gender,$password,$image){

    if(userExists($conn,$email)){
            echo "Skipped: $email already exists<br>";
                    return;
                        }
                        
    $hash=password_hash($password,PASSWORD_DEFAULT);
    
    $stmt=$conn->prepare("
            INSERT INTO users
                    (role,fullName,country,email,mobile,blood,gender,password,image)
                            VALUES (?,?,?,?,?,?,?,?,?)
                                ");
                                
    $stmt->bind_param(
            "sssssssss",
                    $role,
                            $name,
                                    $country,
                                            $email,
                                                    $mobile,
                                                            $blood,
                                                                    $gender,
                                                                            $hash,
                                                                                    $image
                                                                                        );
                                                                                        
    $stmt->execute();
    
    echo "Inserted: $name<br>";
    }
    
/*
|--------------------------------------------------------------------------
| Base Accounts
|--------------------------------------------------------------------------
*/

insertUser(
$conn,
"admin",
"Dr. Hasan Al-Hassan",
"Lebanon",
"alif75760@gmail.com",
"70100001",
"A+",
"Male",
"***REMOVED***",
"default.png"
);

insertUser(
$conn,
"instructor",
"Dr. Ali Fakkeh",
"Lebanon",
"alyfakkeh@gmail.com",
"70100002",
"O+",
"Male",
"***REMOVED***",
"default.png"
);

insertUser(
$conn,
"student",
"Ahmad Saleh",
"Lebanon",
"ahmadsaleh@gmail.com",
"70100003",
"B+",
"Male",
"***REMOVED***",
"default.png"
);

/*
|--------------------------------------------------------------------------
| More users will be added in the next step
|--------------------------------------------------------------------------
*/

echo "<h2>Seeder completed.</h2>";

$conn->close();
?>