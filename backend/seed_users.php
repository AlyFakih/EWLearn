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
/*
|--------------------------------------------------------------------------
| Generate Additional Instructors
|--------------------------------------------------------------------------
*/

$instructors = [

["Dr. Hussein Khalil","hussein.khalil@ewlearn.edu.lb"],
["Dr. Omar Hamdan","omar.hamdan@ewlearn.edu.lb"],
["Dr. Mahmoud Diab","mahmoud.diab@ewlearn.edu.lb"],
["Dr. Jad Karam","jad.karam@ewlearn.edu.lb"],
["Dr. Bilal Nasser","bilal.nasser@ewlearn.edu.lb"],
["Dr. Hassan Farhat","hassan.farhat@ewlearn.edu.lb"],
["Dr. Samer Issa","samer.issa@ewlearn.edu.lb"],
["Dr. Wael Sabbagh","wael.sabbagh@ewlearn.edu.lb"],
["Dr. Karim Moussa","karim.moussa@ewlearn.edu.lb"],
["Dr. Tarek Haydar","tarek.haydar@ewlearn.edu.lb"],
["Dr. Rami Daher","rami.daher@ewlearn.edu.lb"],
["Dr. Ziad Sharaf","ziad.sharaf@ewlearn.edu.lb"],
["Dr. Mustafa Amin","mustafa.amin@ewlearn.edu.lb"],
["Dr. Bassam Najjar","bassam.najjar@ewlearn.edu.lb"]

];

$bloodTypes=["A+","A-","B+","B-","AB+","AB-","O+","O-"];

$mobile=70110000;

foreach($instructors as $i){

insertUser(

$conn,
"instructor",
$i[0],
"Lebanon",
$i[1],
(string)$mobile,
$bloodTypes[array_rand($bloodTypes)],
"Male",
"Instructor2026!",
"default.png"

);

$mobile++;

}
/*
|--------------------------------------------------------------------------
| Generate Students
|--------------------------------------------------------------------------
*/

$students=[

["Mohammad Khalil","Male"],
["Ali Hassan","Male"],
["Fatima Mahmoud","Female"],
["Sara Diab","Female"],
["Omar Issa","Male"],
["Nour Hamdan","Female"],
["Yara Saleh","Female"],
["Maya Karam","Female"],
["Hussein Fakkeh","Male"],
["Bilal Farhat","Male"],
["Ahmad Haydar","Male"],
["Lina Moussa","Female"],
["Jad Chriem","Male"],
["Aya Sabbagh","Female"],
["Mahmoud Amin","Male"],
["Zeinab Sharaf","Female"],
["Karim Najjar","Male"],
["Diana Khalil","Female"],
["Rami Hamdan","Male"],
["Hiba Issa","Female"],
["Mariam Daher","Female"],
["Youssef Saleh","Male"],
["Rana Hassan","Female"],
["Mustafa Diab","Male"]

];

$mobile=70200000;

foreach($students as $s){

$email=strtolower(str_replace(" ",".",$s[0]))."@student.ewlearn.edu.lb";

insertUser(

$conn,
"student",
$s[0],
"Lebanon",
$email,
(string)$mobile,
$bloodTypes[array_rand($bloodTypes)],
$s[1],
"Student2026!",
"default.png"

);

$mobile++;

}
$conn->close();
?>
