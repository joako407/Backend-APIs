<?php
session_start();
// required headers
header("Access-Control-Allow-Origin: *");
//header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
date_default_timezone_set("Asia/Kuala_Lumpur");
define("SITE_URL", "localhost");

require_once '../../vendor/autoload.php';
use api\config\Database;
use api\model\User;
use utility\RequestBlocker\PostBlocker;
use utility\AuthorizationHandler\AuthHandler;


new PostBlocker();

$action = basename($_SERVER["SCRIPT_FILENAME"], '.php');
$module = basename(__DIR__);
$payload = AuthHandler::AuthorizationGuard($module, $action);

// database connection will be here
$database = new Database();
$db = $database->getConnection();

// initialize object
$user = new User($db);

// get posted data
//$data = json_decode(file_get_contents("php://input"));
$data = json_decode(json_encode($_POST));
//print_r($data->types);
// make sure data is not empty
if(
    !empty($data->username) &&
    !empty($data->firstname) &&
    !empty($data->lastname) &&
    !empty($data->gender) &&
    !empty($data->dob) &&
    !empty($data->email) &&
    !empty($data->pwd) &&
    !empty($data->type)
)
{
    if($payload){
        // set product property values
        $user->username = strtoupper($data->username);
        $user->firstname = $data->firstname;
        $user->lastname = $data->lastname;
        $user->gender = $data->gender;
        $user->dob = $data->dob;
        $user->email = $data->email;
        $user->password = $data->pwd;
        $user->type = $data->type;
        $user->status = 1;
        $user->created = date('Y-m-d H:i:s');
        if($user->create()){
            // set response code - 201 created
            http_response_code(201);

            // tell the user
            echo json_encode(array("message" => "User was created."));
        }
        else{
            // set response code - 503 service unavailable
            http_response_code(503);

            // tell the user
            echo json_encode(array("message" => "Unable to create user."));
        }
    }
    else{
        // set response code - 503 service unavailable
        http_response_code(401);
    
        // tell the user
        echo json_encode(array("message" => "Unauthorized action."));
    }
}else{
    // set response code - 400 bad request
    http_response_code(400);
  
    // tell the user
    echo json_encode(array("message" => "Unable to create user. Data is incomplete."));
}


?>