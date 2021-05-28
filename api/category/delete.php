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
use api\model\Category;
use api\model\Picture;
use api\model\Type;
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
$category = new Category($db);

// get posted data
$data = json_decode(json_encode($_POST));

// make sure data is not empty
if(
    isset($data->id) &&
    !empty($data->id)
){
    if($payload)
    {
        $flag = false;
        $flag1 = false;
        $flag2 = false;

        // set product id to be deleted
        $category->id = $data->id;

        //delete category's type
        $type = new Type($db);
        $type->category_id = $category->id;
        $type->readAllRemoveID();
        $flag = $type->delete();

        if(isset($type->id) && is_array($type->id) && !empty($type->id))
        {
            foreach($type->id as $x)
            {
                $picture = new Picture($db,"t");
                $picture->target_id = $x;
                $picture->readAllRemovePicture(); // assign remove pictures to picture object
                $flag1 = $picture->delete();
            }
            
        }
        else
            $flag1 = true;
        
        // delete the product
        if($category->delete()){
            // initialize Picture object for upload picture and insert picture path data
            $picture = new Picture($db,"c");
            $picture->target_id = $category->id;
            $picture->readAllRemovePicture(); // assign remove pictures to picture object
            $flag2 = $picture->delete();

            if($flag && $flag1 && $flag2)
            {
                $redisInstance = Redis::getInstance();
                if($redisInstance != null && $redisInstance::$redis->hexists("categories", $category->id))
                {
                    $redisInstance::$redis->hdel("categories", $category->id);
                }
                // set response code - 200 ok
                http_response_code(200);
            
                // tell the user
                echo json_encode(array("message" => "Category was deleted."));
            }
            else
            {
                // set response code - 503 service unavailable
                http_response_code(503);
            
                // tell the user
                echo json_encode(array("message" => "Unable to delete Category."));
            }
        
            
        }
        
        // if unable to delete the product
        else{
        
            // set response code - 503 service unavailable
            http_response_code(503);
        
            // tell the user
            echo json_encode(array("message" => "Unable to delete Category."));
        }
    }
}
else{
    // set response code - 400 bad request
    http_response_code(400);
  
    // tell the user
    echo json_encode(array("message" => "Unable to delete category. Data is incomplete."));
}


?>