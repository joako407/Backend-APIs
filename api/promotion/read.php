<?php
session_start();
// required headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');
date_default_timezone_set("Asia/Kuala_Lumpur");
define("SITE_URL", "localhost");

require_once '../../vendor/autoload.php';
use api\config\Database;
use api\model\Promotion;
use utility\RequestBlocker\GetBlocker;
use utility\Redis\Redis;
use utility\AuthorizationHandler\AuthHandler;

new GetBlocker();

$action = basename($_SERVER["SCRIPT_FILENAME"], '.php');
$module = basename(__DIR__);
$payload = AuthHandler::AuthorizationGuard($module, $action);

// database connection will be here
$database = new Database();
$db = $database->getConnection();

// initialize object
$promotion = new Promotion($db);
// set ID property of record to read
if($payload){
    
    // read the details of product to be edited
    $redisInstance = Redis::getInstance();
    if($redisInstance == null || !$redisInstance::$redis->exists('promotions'))
    {
        $stmt = $promotion->read();
        $num = $stmt->rowCount();
        $temp=0;

        if($num > 0)
        {
            $promotions_arr=array();
            $promotions_arr["records"]=array();
            // retrieve our table contents
            // fetch() is faster than fetchAll()
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                // extract row
                // this will make $row['name'] to
                // just $name only
                extract($row);
                if(array_search($id,array_column($promotions_arr['records'],'id')) === false){
                    $promotion_arr = array(
                        "id" =>  $id,
                        "name" => $name,
                        "description" => $description,
                        "discount" => $discount,
                        "discount_type" => $discount_type,
                        "date_begin" => $date_begin,
                        "date_end" => $date_end,
                        "item" => [],
                        "status" => $status
                    );  
                    array_push($promotions_arr["records"], $promotion_arr); 
                }
                $index = array_search($id, array_column($promotions_arr['records'],'id'));
                if(array_search($target_id,$promotions_arr['records'][$index]['item']) === false){
                    array_push($promotions_arr['records'][$index]['item'],$target_id);
                }
            }

            if($redisInstance != null)
            {
                foreach($promotions_arr['records'] as $key=>$val)
                {
                    $redisInstance::$redis->hset('promotions',$val['id'], serialize($val));
                }
            }

            // set response code - 200 OK
            http_response_code(200);

            // show users data in json format
            echo json_encode($promotions_arr);
        }
        else{
            http_response_code(404);
    
            // show users data in json format
            echo json_encode(
                array("message" => "No promotion found!")
            );
        }
    }
    else{
        $arr = $redisInstance::$redis->hgetall("promotions");
        $promotions_arr = Array();
        $promotions_arr["records"]=array();
        foreach($arr as $key=>$val)
        {
            array_push($promotions_arr['records'],unserialize($val));
        }
        // set response code - 200 OK
        http_response_code(200);
        
        // show products data in json format
        echo json_encode($promotions_arr);
    }
}
else{
    http_response_code(401);
    
    // tell the user
    echo json_encode(array("message" => "Unauthorized action!"));
}
?>