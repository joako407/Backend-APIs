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
//print_r($data->types);
// make sure data is not empty
if($payload)
{
    // set product property values
    $promotion->name = $data->name;
    $promotion->description = $data->description;
    $promotion->discount = $data->discount;
    $promotion->discount_type = $data->discount_type;
    $promotion->date_begin = $data->date_begin;
    $promotion->date_end = $data->date_end;
    $promotion->status = $data->status;
    $promotion->created = date('Y-m-d H:i:s');
    
    //var_dump($_FILES['files2']);
    // create the product
    if($promotion->create()){
        // initialize Picture object for upload picture and insert picture path data
        $redisInstance = Redis::getInstance();
        if($redisInstance != null && !$redisInstance::$redis->hexists('promotions', $promotion->id))
        {
            $promotion_arr = getUpdatedData($promotion);
            $redisInstance::$redis->hset('promotions',$promotion->id, serialize($promotion_arr));
        }
        // set response code - 201 created
        http_response_code(201);

        // tell the user
        echo json_encode(array("message" => "Promotion was created."));
    }

    // if unable to create the product, tell the user
    else{
        // set response code - 503 service unavailable
        http_response_code(503);

        // tell the user
        echo json_encode(array("message" => "Unable to create promotion."));
    }
}else{
    // set response code - 503 service unavailable
    http_response_code(401);

    // tell the user
    echo json_encode(array("message" => "Unauthorized action."));
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