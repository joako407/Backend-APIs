<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
date_default_timezone_set("Asia/Kuala_Lumpur");
define("SITE_URL", "localhost");

require_once '../../vendor/autoload.php';
use api\config\Database;
use api\model\Category;
use utility\RequestBlocker\GetBlocker;
use utility\Redis\Redis;
use utility\AuthorizationHandler\AuthHandler;

new GetBlocker();

$action = basename($_SERVER["SCRIPT_FILENAME"], '.php');
$module = basename(__DIR__);
AuthHandler::AuthorizationGuard($module, $action);

// database connection will be here
$database = new Database();
$db = $database->getConnection();

// initialize object
$category = new Category($db);

$redisInstance = Redis::getInstance();
if($redisInstance == null || !$redisInstance::$redis->exists('categories'))
{
    // query products
    $stmt = $category->read();
    $num = $stmt->rowCount();
    // check if more than 0 record found
    $temp=0;
    if($num>0){
    
        // categories array
        $categories_arr=array();
        $categories_arr["records"]=array();
        // retrieve our table contents
        // fetch() is faster than fetchAll()
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            // extract row
            // this will make $row['name'] to
            // just $name only
            extract($row);
            if($temp != $categoryID)
            {
                $category_item=array(
                    "id" => $categoryID,
                    "name" => $name,
                    "description" => html_entity_decode($description),
                    "picture" => $pic_path,
                    "type"=> array(array("id"=>$typeID,"name"=>$typeName,"picture"=>$typePic))
                );

                array_push($categories_arr["records"], $category_item);
            }
            else{
                $key = count($categories_arr['records']);
                $categories_arr['records'][$key-1]['type'][] = array("id"=>$typeID,"name"=>$typeName,"picture"=>$typePic);
            }
            $temp = $categoryID;
        }

        if($redisInstance != null)
        {
            foreach($categories_arr["records"] as $key=>$val)
            {
                $redisInstance::$redis->hset("categories", $val['id'], serialize($val));
            }
        }
        
    
        // set response code - 200 OK
        http_response_code(200);
    
        // show products data in json format
        echo json_encode($categories_arr);
    }
    else{
        // set response code - 404 Not found
        http_response_code(404);
    
        // tell the user no products found
        echo json_encode(
            array("message" => "No categories found.")
        );
    }
}
else{
    $arr = $redisInstance::$redis->hgetall("categories");
    $categories_arr = Array();
    $categories_arr["records"]=array();
    foreach($arr as $key=>$val)
    {
        array_push($categories_arr['records'],unserialize($val));
    }

    if(count($categories_arr['records']) > 0)
    {
        http_response_code(200);

        // show products data in json format
        echo json_encode($categories_arr);
    }
    else
    {
        http_response_code(404);

        // tell the user no products found
        echo json_encode(
            array("message" => "No products found.")
        );
    }
}

?>