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
    isset($data->id) &&
    !empty($data->id)
)
{
    if($payload){
        // set product property values
        $user->id = $data->id;
        if($user->delete2()){
            // set response code - 201 created
            http_response_code(201);

            // tell the user
            echo json_encode(array("message" => "Request was deleted."));
        }
        else{
            // set response code - 503 service unavailable
            http_response_code(503);

            // tell the user
            echo json_encode(array("message" => "Unable to delete request."));
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
    echo json_encode(array("message" => "Unable to delete request. Data is incomplete."));
}


?>