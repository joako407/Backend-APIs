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
use api\model\Brand;
use api\model\Series;
use api\model\Picture;
use utility\RequestBlocker\PostBlocker;
use utility\JWT\JWTHelper;
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
$brand = new Brand($db);
$payload = JWTHelper::tokenValidate();

// get posted data
$data = json_decode(json_encode($_POST));
  
if(
    isset($data->id) && 
    !empty($data->id)
){
    if($payload)
    {
        // set product id to be deleted
        $brand->id = $data->id;
        
        $series = new Series($db);
        $series->brand_id = $brand->id;
        $series->readAllRemoveID();
        $flag = $series->delete();
        
        // delete the product
        if($brand->delete()){

            // initialize Picture object for upload picture and insert picture path data
            $picture = new Picture($db,"b");
            $picture->target_id = $brand->id;
            $picture->readAllRemovePicture(); // assign remove pictures to picture object
            
            if($picture->delete())
            {
                $redisInstance = Redis::getInstance();
                if($redisInstance != null && $redisInstance::$redis->hexists("brands", $brand->id))
                {
                    $redisInstance::$redis->hdel("brands", $brand->id);
                }
                // set response code - 200 ok
                http_response_code(200);
            
                // tell the user
                echo json_encode(array("message" => "Brand was deleted."));
            }
            else
            {
                // set response code - 503 service unavailable
                http_response_code(503);
            
                // tell the user
                echo json_encode(array("message" => "Unable to delete Brand."));
            }      
        }
        // if unable to delete the product
        else{
            // set response code - 503 service unavailable
            http_response_code(503);
        
            // tell the user
            echo json_encode(array("message" => "Unable to delete brand."));
        }
    }
}
else{
    // set response code - 400 bad request
    http_response_code(400);
  
    // tell the user
    echo json_encode(array("message" => "Unable to delete brand. Data is incomplete."));
}

?>