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
use api\model\Series;
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
//print_r($data->types);
// make sure data is not empty
if(!empty($data->name) && 
    isset($_FILES['files']['tmp_name']) &&
    is_uploaded_file($_FILES['files']['tmp_name'][0])
){
    if($payload)
    {
        // set product property values
        $brand->name = $data->name;
        $brand->created = date('Y-m-d H:i:s');
        
        //var_dump($_FILES['files2']);
        // create the product
        if($brand->create()){
            
            $flag = false;
            $flag1 = false;
            // initialize Picture object for upload picture and insert picture path data
            $picture = new Picture($db,"b");
            $picture->target_id = $brand->id;
            $picture->pictures = $_FILES['files']['tmp_name'];
            $picture->compress = $data->compress;
            $flag = $picture->create();

            if(isset($data->series) && !empty($data->series))
            {
                foreach($data->series as $key=>$x)
                {
                    $series = new Series($db);
                    $series->brand_id = $brand->id;
                    $series->name = $x;
                    $series->created = date('Y-m-d H:i:s');
                    $flag1 = $series->create();
                }
            }
            else{
                $flag1 = true;
            }
            
            if($flag && $flag1){
                $redisInstance = Redis::getInstance();
                if($redisInstance != null && !$redisInstance::$redis->hexists("brands", $brand->id))
                {
                    $brand->readOne();
                    $brand_arr = Array(
                        "id" => $brand->id,
                        "name" => $brand->name,
                        "picture" => $brand->picture,
                        "series" => $brand->series,
                    ); 
                    $redisInstance::$redis->hset("brands", $brand->id, serialize($brand_arr));
                }

                // set response code - 201 created
                http_response_code(201);
        
                // tell the user
                echo json_encode(array("message" => "Brand was created."));
            }
            else{
                // set response code - 503 service unavailable
                http_response_code(503);
        
                // tell the user
                echo json_encode(array("message" => "Unable to create brand."));
            }
            
        }
    
        // if unable to create the product, tell the user
        else{
            // set response code - 503 service unavailable
            http_response_code(503);
    
            // tell the user
            echo json_encode(array("message" => "Unable to create brand."));
        }
    }
}
// tell the user data is incomplete
else{
  
    // set response code - 400 bad request
    http_response_code(400);
  
    // tell the user
    echo json_encode(array("message" => "Unable to create brand. Data is incomplete."));
}
?>