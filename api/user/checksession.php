<?php
session_start();
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
date_default_timezone_set("Asia/Kuala_Lumpur");
define("SITE_URL", "localhost");

require_once '../../vendor/autoload.php';
use utility\RequestBlocker\GetBlocker;
use utility\AuthorizationHandler\AuthHandler;

new GetBlocker();

$action = basename($_SERVER["SCRIPT_FILENAME"], '.php');
$module = basename(__DIR__);
$payload = AuthHandler::AuthorizationGuard($module, $action);

if($payload){        
    http_response_code(200);
        
    // tell the user
    echo json_encode(
        array(
            "message" => "logined.",
            "type" => $payload->type,
            "name" => $payload->name,
            "loginStatus" => true,
        )
    );
}
else{
    http_response_code(400);
        
    // tell the user
    echo json_encode(array("message" => "not login."));
}