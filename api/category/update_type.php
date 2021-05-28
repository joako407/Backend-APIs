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
use api\model\Picture;
use api\model\Category;
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
$type = new Type($db);

// get posted data
//$data = json_decode(file_get_contents("php://input"));
$data = json_decode(json_encode($_POST));
$data->compress = ((isset($data->compress) && $data->compress == "on") ? true : false);
if($payload){
    $flag = false;
    $flag1 = false;
    $flag2 = false;
    //for update current type
    if(isset($data->id) && !empty($data->id))
    {
        foreach($data->id as $key => $x)
        {
            $type->id = $x;
            $type->name = (isset($data->name[$key]) && !empty($data->name[$key]))? $data->name[$key] : null;
            $type->modified = date('Y-m-d H:i:s');
            if($type->update())
            {
                $picture = new Picture($db,"t");
                $picture->target_id = $type->id;
                $picture->pictures = (isset($_FILES['files']['tmp_name'][$key]) && is_uploaded_file($_FILES['files']['tmp_name'][$key]))? array($_FILES['files']['tmp_name'][$key]) : null;
                $picture->compress = $data->compress;
                if(!is_null($picture->pictures))
                {
                    $picture->readAllRemovePicture();
                }
                $flag = $picture->update();
            } 
        }
    }else{
        $flag = true;
    }
    
    //for new added types
    if(isset($data->types) && 
        !empty($data->types) && 
        isset($data->cateID) &&
        !empty($data->cateID) &&
        isset($_FILES['files2']['tmp_name']) && 
        is_uploaded_file($_FILES['files2']['tmp_name'][0])
    ){
        foreach($data->types as $key=>$x)
        {
            $type = new Type($db);
            $type->category_id = $data->cateID;
            $type->name = $x;
            $flag1 = $type->create();

            $picture = new Picture($db,"t");
            $picture->target_id = $type->id;
            $picture->pictures = array($_FILES['files2']['tmp_name'][$key]);
            $picture->compress = $data->compress;
            $flag1 = $picture->create();
        }
    }else{
        $flag1 = true;
    }
    //for remove types
    if(isset($data->remove_types) && !empty($data->remove_types))
    {
        $data->remove_types = json_decode($data->remove_types);
        foreach($data->remove_types as $key=>$x)
        {
            $type = new Type($db);
            $type->id = $x;
            $flag2 = $type->delete();

            $picture = new Picture($db,"t");
            $picture->target_id = $type->id;
            $picture->readAllRemovePicture();
            $flag2 = $picture->delete();
        }
    }else{
        $flag2 = true;
    }
    
    if($flag && $flag1 && $flag2)
    {   $category = new Category($db);
        $category->id = $data->cateID;

        $redisInstance = Redis::getInstance();
        if($redisInstance != null && $redisInstance::$redis->hexists('categories', $category->id))
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
        // set response code - 200 ok
        http_response_code(200);
        
        // tell the user
        echo json_encode(array("message" => "Types was updated."));
    }
}



?>