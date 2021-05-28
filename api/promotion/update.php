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
use api\model\Promotion;
use utility\RequestBlocker\PostBlocker;
use utility\Redis\Redis;
use utility\AuthorizationHandler\AuthHandler;

new PostBlocker();

$action = basename($_SERVER["SCRIPT_FILENAME"], '.php');
$module = basename(__DIR__);
$payload = AuthHandler::AuthorizationGuard($module, $action);

// database connection will be here
$database = new Database();
$db = $database->getConnection();

// initialize object
$promotion = new Promotion($db);

// get posted data
//$data = json_decode(file_get_contents("php://input"));
$data = json_decode(json_encode($_POST));

if(
    isset($data->id) && 
    !empty($data->id)
){
    if($payload)
    {
        // set ID property of promotion to be edited
        $promotion->id = $data->id;
        
        // set product property values
        $promotion->name = (isset($data->name) && !empty($data->name))? $data->name : null;
        $promotion->description = (isset($data->description) && !empty($data->description))? $data->description : null;
        $promotion->discount = (isset($data->discount) && !empty($data->discount))? $data->discount : null;
        $promotion->discount_type = (isset($data->discount_type) && !empty($data->discount_type))? $data->discount_type : null;
        $promotion->date_begin = (isset($data->date_begin) && !empty($data->date_begin))? $data->date_begin : null;
        $promotion->date_end = (isset($data->date_end) && !empty($data->date_end))? $data->date_end : null;
        $promotion->status = (isset($data->status) && $data->status !=="")? $data->status : null; 
        $promotion->modified = date('Y-m-d H:i:s');
        var_dump($promotion);
        // update the promotion
        if($promotion->update()){

            $redisInstance = Redis::getInstance();
            if($redisInstance != null && $redisInstance::$redis->hexists("promotions",$promotion->id))
            {
                $promotion_arr = getUpdatedData($promotion);
                $redisInstance::$redis->hset('promotions',$promotion->id, serialize($promotion_arr)); 
            }
            // set response code - 200 ok
            http_response_code(200);
        
            // tell the user
            echo json_encode(array("message" => "Promotion was updated."));
        }
        // if unable to update the product, tell the user
        else{
            // set response code - 503 service unavailable
            http_response_code(503);
        
            // tell the user
            echo json_encode(array("message" => "Unable to update Promotion."));
        }
    }
}
else{
    // set response code - 400 bad request
    http_response_code(400);
  
    // tell the user
    echo json_encode(array("message" => "Unable to update promotion. Data is incomplete."));
}

function getUpdatedData($promotion)
{
    $promotion->readOne();

    // create array
    $promotion_arr = array(
        "id" =>  $promotion->id,
        "name" => $promotion->name,
        "description" => $promotion->description,
        "discount" => $promotion->discount,
        "discount_type" => $promotion->discount_type,
        "date_begin" => $promotion->date_begin,
        "date_end" => $promotion->date_end,
        "item" => $promotion->item,
        "status" => $promotion->status
    );
    return $promotion_arr;
    
}

?>