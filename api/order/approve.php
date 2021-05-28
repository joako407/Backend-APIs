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
use utility\RequestBlocker\PostBlocker;
use api\model\Order;
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
$data = json_decode(json_encode($_POST));
if(
    isset($data->id) && 
    !empty($data->id)
){
    if($payload)
    {
        if (file_exists('request.csv')) {
            $requests = array_map('str_getcsv', file('request.csv'));
            if (($key = array_search($data->id, array_column($requests, 0))) !== false) {
                $req = $requests[$key];
                var_dump($req);
                // set product property values
                $order->id = $req[1];
                $order->status = $req[3];
                $order->modified = date('Y-m-d H:i:s');
                if($order->update()){
                    unset($requests[$key]);
                    $fp = fopen('request.csv', 'w');
                    foreach($requests as $req){
                        fputcsv($fp, $req);
                    }
                    fclose($fp);
                    // set response code - 200 ok
                    http_response_code(200);
                    
                    // tell the user
                    echo json_encode(array("message" => "Request was approved successfully."));
                }
                else{
                    // set response code - 503 service unavailable
                    http_response_code(503);
                    
                    // tell the user
                    echo json_encode(array("message" => "Unable to approve request."));
                }
            }
        }
        else{
            // set response code - 503 service unavailable
            http_response_code(503);
            
            // tell the user
            echo json_encode(array("message" => "Unable to aprove request."));
        }
        
    }
}
else{
    // set response code - 400 bad request
    http_response_code(400);
  
    // tell the user
    echo json_encode(array("message" => "Unable to delete request. Data is incomplete."));
}

?>