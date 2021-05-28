<?php
session_start();
// required headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');
date_default_timezone_set("Asia/Kuala_Lumpur");
define("SITE_URL", "localhost");

require_once '../../vendor/autoload.php';
use api\config\Database;
use api\model\User;
use utility\RequestBlocker\GetBlocker;
use utility\AuthorizationHandler\AuthHandler;

new GetBlocker();

$action = basename($_SERVER["SCRIPT_FILENAME"], '.php');
$module = basename(__DIR__);
$payload = AuthHandler::AuthorizationGuard($module, $action);

// database connection will be here
$database = new Database();
$db = $database->getConnection();

// initialize object
$user = new User($db);
// set ID property of record to read

$data = json_decode(json_encode($_POST));

if($payload){
    if($payload->type == "STAFF")
    {
        http_response_code(403);
        echo json_encode(array("message" => "403 Forbidden."));
        exit;
    }
    else{
        $user->executor_id = $payload->user_id;
        // read the details of product to be edited
        $stmt = $user->read2();
        $num = $stmt->rowCount();
        if($num > 0)
        {
            $users_arr=array();
            $users_arr["records"]=array();
            // retrieve our table contents
            // fetch() is faster than fetchAll()
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                // extract row
                // this will make $row['name'] to
                // just $name only
                extract($row);
                
                $user_arr = array(
                    "id" =>  $id,
                    "user_id" =>  $user_id,
                    "username" => $username,
                    "firstname" => $fname,
                    "lastname" => $lname,
                    "gender" => $gender,
                    "dob" => $dob,
                    "type" => $type,
                    "email" => $email,
                    "action" => $action,
                    "fullname" => $firstname." ".$lastname,
                    "status" => $status,
                    "created" => $created
                );    
                array_push($users_arr["records"], $user_arr);         
            }
            // set response code - 200 OK
            http_response_code(200);
    
            // show users data in json format
            echo json_encode($users_arr);
        }
        else{
            http_response_code(404);
    
            // show users data in json format
            echo json_encode(
                array("message" => "No user found!")
            );
        }
    }

}
else{
    http_response_code(403);
    echo json_encode(array("message" => "403 Forbidden."));
    exit;
}


?>