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
use api\model\Order;
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
$order = new Order($db);

// get posted data
//$data = json_decode(file_get_contents("php://input"));
$data = json_decode(json_encode($_POST));

if(
    isset($data->id) && 
    !empty($data->id)
){
    if($payload){
        $flag = false;
        if($payload->type !== "STAFF"){
            
            // set ID property of cart to be edited
            $order->id = $data->id;
    
            // set product property values
            $order->status = 4;
            $order->refund_date = date('Y-m-d H:i:s');
            $flag = $order->update();
        }
        
        // update the product
        if($flag){
            // set response code - 200 ok
            http_response_code(200);
            
            // tell the user
            echo json_encode(array("message" => "Order status was update successfully."));
        }
        else{ 
            // set response code - 503 service unavailable
            http_response_code(503);
            
            // tell the user
            echo json_encode(array("message" => "Unable to update order status."));
        }
        
    }
}
else
{
   // set response code - 400 bad request
   http_response_code(400);
  
   // tell the user
   echo json_encode(array("message" => "Unable to update order status. Data is incomplete."));
}
?>