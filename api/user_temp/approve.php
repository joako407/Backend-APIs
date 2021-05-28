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
if(
    isset($data->id) && 
    !empty($data->id)
){
    if($payload){
        // set product id
        $user->id = $data->id;

        // 1 is not found
        // 2 and 3 is existed
        if($user->checkExists() != 1){
            if($user->action == "edit"){
                if($user->checkExistsById() > 0){
                    
                    // clone the new product info then replace the old product info
                    if($user->cloneForUpdate()){
                        deleteRequest($data->id, $db);
                        
                        // set response code - 400 bad request
                        http_response_code(200);
                    
                        // tell the user
                        echo json_encode(array("message" => "Request was approved successfully."));
                    }
                    else{
                        // set response code - 400 bad request
                        http_response_code(400);
                    
                        // tell the user
                        echo json_encode(array("message" => "Unable to approve request."));
                    }

                }
                else{
                    // set response code - 400 bad request
                    http_response_code(400);
                
                    // tell the user
                    echo json_encode(array("message" => "Unable to approve request. Product doesn't exists."));
                    
                }
            }
            else if($user->action == "create"){
                if($user->cloneForCreate()){
                    deleteRequest($data->id, $db);
                    
                    // set response code - 400 bad request
                    http_response_code(200);
                
                    // tell the user
                    echo json_encode(array("message" => "Request was approved successfully."));
                }
                else{
                    // set response code - 400 bad request
                    http_response_code(400);
                
                    // tell the user
                    echo json_encode(array("message" => "Unable to approve request."));
                }
            }  
        }   
        else{
            // set response code - 400 bad request
            http_response_code(400);
        
            // tell the user
            echo json_encode(array("message" => "Unable to approve request."));
        }
    }
}
else{
    // set response code - 400 bad request
    http_response_code(400);
  
    // tell the user
    echo json_encode(array("message" => "Unable to delete product. Data is incomplete."));
}

function deleteRequest($id, $db)
{
    $user = new User($db);
    $user->id = $id;
    $user->delete2();
}

?>