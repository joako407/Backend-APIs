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
use api\model\Type;
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
$category = new Category($db);

// get posted data
//$data = json_decode(file_get_contents("php://input"));
$data = json_decode(json_encode($_POST));
$data->compress = ((isset($data->compress) && $data->compress == "on") ? true : false);

// make sure data is not empty
if(
    !empty($data->name) &&
    !empty($data->description) &&
    isset($_FILES['files']['tmp_name']) &&
    is_uploaded_file($_FILES['files']['tmp_name'][0])
){
    if($payload)
    {
        // set product property values
        $category->name = $data->name;
        $category->description = $data->description;
        $category->created = date('Y-m-d H:i:s');
        //var_dump($_FILES['files2']);
        // create the product
        if($category->create()){

            $flag = false;
            $flag1 = false;
            $flag2 = false;
            // initialize Picture object for upload picture and insert picture path data
            $picture = new Picture($db,"c");
            $picture->target_id = $category->id;
            $picture->pictures = $_FILES['files']['tmp_name'];
            $picture->compress = $data->compress;
            $flag = $picture->create();

            if(isset($data->types) && !empty($data->types))
            {
                foreach($data->types as $key=>$x)
                {
                    $type = new Type($db);
                    $type->category_id = $category->id;
                    $type->name = $x;
                    $type->created = date('Y-m-d H:i:s');
                    $flag1 = $type->create();

                    $picture = new Picture($db,"t");
                    $picture->target_id = $type->id;
                    $picture->pictures = array($_FILES['files2']['tmp_name'][$key]);
                    $picture->compress = $data->compress;
                    $flag2 = $picture->create();
                }
            }
            else{
                $flag1 = true;
                $flag2 = true;
            }

            if($flag && $flag1 && $flag2){

                $redisInstance = Redis::getInstance();
                if($redisInstance != null && !$redisInstance::$redis->hexists('categories', $category->id))
                {
                    $category->readOne();
                    $category_arr = Array(
                        "id" => $category->id,
                        "name" => $category->name,
                        "description" => $category->description,
                        "picture" => $category->picture,
                        "type" => $category->types
                    );
                    $redisInstance::$redis->hset("categories", $category->id, serialize($category_arr));
                }


                // set response code - 201 created
                http_response_code(201);
        
                // tell the user
                echo json_encode(array("message" => "Category was created."));
            }
            else{
                // set response code - 503 service unavailable
                http_response_code(503);
        
                // tell the user
                echo json_encode(array("message" => "Unable to create category."));
            }
            
        }
    
        // if unable to create the product, tell the user
        else{
            // set response code - 503 service unavailable
            http_response_code(503);
    
            // tell the user
            echo json_encode(array("message" => "Unable to create category."));
        }
    }
    
}
// tell the user data is incomplete
else{
    
    // set response code - 400 bad request
    http_response_code(400);
  
    // tell the user
    echo json_encode(array("message" => "Unable to create category. Data is incomplete."));
}
?>