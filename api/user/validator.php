<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
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
AuthHandler::AuthorizationGuard($module, $action);

// database connection will be here
$database = new Database();
$db = $database->getConnection();


// initialize object
$user = new User($db);

// get posted data
//$data = json_decode(file_get_contents("php://input"));
$data = json_decode(json_encode($_POST));

// make sure data is not empty
if(
    (isset($data->username) && !empty($data->username))
){
    $user->username = strtolower(trim($data->username));
    if($user->checkExistUser() == 0 )
    {
        http_response_code(200);
        echo json_encode(array("message" => "Valid username!"));
    }
    else{
        http_response_code(400);
        echo json_encode(array("message" => "Username is already taken."));
    }
}

if(
    (isset($data->email) && !empty($data->email))
){
    // set product property values
    if(filter_var($data->email, FILTER_VALIDATE_EMAIL))
    {
        $user->email = strtolower(trim($data->email));
        if($user->checkExistUser() == 0 )
        {
            http_response_code(200);
            echo json_encode(array("message" => "Valid email!"));
        }
        else{
            http_response_code(400);
            echo json_encode(array("message" => "Email is already taken."));
        }
    }
    else{
        http_response_code(400);
        echo json_encode(array("message" => "Invalid email format."));
        
    }
}

?>