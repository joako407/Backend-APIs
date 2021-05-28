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
if($payload)
{
    $user->id = $payload->user_id;
    // read the details of product to be edited
    $user->readOne();

    // check if more than 0 record found
    if($user->username!=null){
    
        // create array
        $user_arr = array(
            "id" =>  $user->id,
            "username" => $user->username,
            "firstname" => $user->firstname,
            "lastname" => $user->lastname,
            "gender" => $user->gender,
            "dob" => $user->dob,
            "email" => $user->email,
            "status" => $user->status
        );
    
        // set response code - 200 OK
        http_response_code(200);
    
        // show products data in json format
        echo json_encode($user_arr);
    }
}
?>