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
use api\model\Picture;
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
$brand = new Brand($db);

// get posted data
//$data = json_decode(file_get_contents("php://input"));
$data = json_decode(json_encode($_POST));
$data->compress = ((isset($data->compress) && $data->compress == "on") ? true : false);

if(
    isset($data->id) && 
    !empty($data->id)
){
    if($payload)
    {
        // set ID property of product to be edited
        $brand->id = $data->id;
        
        // set product property values
        $brand->name = (isset($data->name) && !empty($data->name))? $data->name : null;
        $brand->modified = date('Y-m-d H:i:s');

        // update the product
        if($brand->update()){
            // initialize Picture object for upload picture and insert picture path data
            $picture = new Picture($db,"b");
            $picture->target_id = $brand->id;
            $picture->pictures = (isset($_FILES['files']['tmp_name']) && is_uploaded_file($_FILES['files']['tmp_name'][0]))? $_FILES['files']['tmp_name'] : null;
            $picture->compress = $data->compress;
            if(!is_null($picture->pictures))
                $picture->readAllRemovePicture();
                
            if($picture->update())
            {
                $redisInstance = Redis::getInstance();
                if($redisInstance != null && $redisInstance::$redis->hexists('brands',$brand->id))
                {
                    $brand->readOne();
                    $brand_arr = Array(
                        "id" => $brand->id,
                        "name" => $brand->name,
                        "picture" => $brand->picture,
                    );
                    $redisInstance::$redis->hset("brands", $brand->id, serialize($brand_arr));
                }
                // set response code - 200 ok
                http_response_code(200);
            
                // tell the user
                echo json_encode(array("message" => "Brand was updated."));
            }
            else
            {
                // set response code - 503 service unavailable
                http_response_code(503);
            
                // tell the user
                echo json_encode(array("message" => "Unable to update Brand."));
            }
            
        }
        // if unable to update the product, tell the user
        else{
            // set response code - 503 service unavailable
            http_response_code(503);
        
            // tell the user
            echo json_encode(array("message" => "Unable to update Brand."));
        }
    }
}
else{
    // set response code - 400 bad request
    http_response_code(400);
  
    // tell the user
    echo json_encode(array("message" => "Unable to update brand. Data is incomplete."));
}

?>