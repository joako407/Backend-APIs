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
$series = new Series($db);

// get posted data
//$data = json_decode(file_get_contents("php://input"));
$data = json_decode(json_encode($_POST));
if($payload ){
    $flag = false;
    $flag1 = false;
    $flag2 = false;
    //for update current type
    if(isset($data->id) && !empty($data->id))
    {
        foreach($data->id as $key => $x)
        {
            $series->id = $x;
            $series->name = (isset($data->name[$key]) && !empty($data->name[$key]))? $data->name[$key] : null;
            $series->modified = date('Y-m-d H:i:s');
            $flag = $series->update();
        }
    }else{
        $flag = true;
    }
    
    //for new added types
    if(isset($data->series) && 
        !empty($data->series) && 
        isset($data->brandID) &&
        !empty($data->brandID)
    ){
        foreach($data->series as $key=>$x)
        {
            $series = new Series($db);
            $series->brand_id = $data->brandID;
            $series->name = $x;
            $flag1 = $series->create();
        }
    }else{
        $flag1 = true;
    }
    //for remove types
    if(isset($data->remove_series) && !empty($data->remove_series))
    {
        $data->remove_series = json_decode($data->remove_series);
        foreach($data->remove_series as $key=>$x)
        {
            $series = new Series($db);
            $series->id = $x;
            $flag2 = $series->delete();
        }
    }else{
        $flag2 = true;
    }
    
    if($flag && $flag1 && $flag2)
    {   $brand = new Brand($db);
        $brand->id = $data->brandID;

        $redisInstance = Redis::getInstance();
        if($redisInstance != null && $redisInstance::$redis->hexists('brands', $brand->id))
        {
            $brand->readOne();
            $brand_item=array(
                "id" => $brand->id,
                "name" => $brand->name,
                "picture" => $brand->pic_path,
                "series" => $brand->series
            );
            
            $redisInstance::$redis->hset("brands", $brand->id, serialize($brand_item));
        }
        // set response code - 200 ok
        http_response_code(200);
        
        // tell the user
        echo json_encode(array("message" => "Series was updated."));
    }
}



?>