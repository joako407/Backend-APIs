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

if($payload){
    // set ID property of product to be edited
    $user->id = $data->id;

    // set product property values
    $user->modified = date('Y-m-d H:i:s');
    $user->firstname = (isset($data->firstname) && !empty($data->firstname))? $data->firstname : null;
    $user->lastname = (isset($data->lastname) && !empty($data->lastname))? $data->lastname : null;
    $user->email = (isset($data->email) && !empty($data->email))? $data->email : null;
    $user->gender = (isset($data->gender) && !empty($data->gender))? $data->gender : null;
    $user->dob = (isset($data->dob) && !empty($data->dob))? $data->dob : null;
    $user->type = (isset($data->type) && !empty($data->type))? $data->type : null;
    $user->status = (isset($data->status))? $data->status : null; 

    if($payload->user_id == $data->id){
        // update the user
        if($user->update()){
            // set response code - 200 ok
            http_response_code(200);
        
            // tell the user
            echo json_encode(array("message" => "User profile was updated."));
        }
        // if unable to update the product, tell the user
        else{
        
            // set response code - 503 service unavailable
            http_response_code(503);
        
            // tell the user
            echo json_encode(array("message" => "Unable to update user profile."));
        }
    }
    else{
        if($payload->type == "SUPERUSER" || $payload->type == "ADMIN"){
            // update the product
            if($user->update()){
                
                // set response code - 200 ok
                http_response_code(200);
            
                // tell the user
                echo json_encode(array("message" => "User profile was updated."));
            }
            
            // if unable to update the product, tell the user
            else{
            
                // set response code - 503 service unavailable
                http_response_code(503);
            
                // tell the user
                echo json_encode(array("message" => "Unable to update user profile."));
            }
        }
    }
}
else
{
    // set response code - 400 bad request
    http_response_code(400);
  
    // tell the user
    echo json_encode(array("message" => "Unable to update user. Data is incomplete."));
}

?>