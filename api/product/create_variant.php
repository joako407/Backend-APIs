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
use api\model\Product;
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
$product = new Product($db);

// get posted data
//$data = json_decode(file_get_contents("php://input"));
$data = json_decode(json_encode($_POST));

//var_dump($data);
if(
    !empty($data->id) &&
    count($data->optName) > 0 &&
    count($data->optValue) > 0 &&
    count($data->chkVariant) > 0 &&
    count($data->variant) > 0 &&
    count($data->sku) > 0 &&
    count($data->price) > 0 &&
    count($data->qty) > 0
){
    if($payload){
        if(count($data->optName) == count($data->optValue)){
            $product->id = $data->id;

            //for update product version
            $product->readVersion();
            $product->version += 1; // increase 1
            $product->update();
            
            $product->deleteVaraint();
            for($i = 0; $i < count($data->optName); $i++)
            {
                $product->variant[] = Array("id"=>null,"name"=>$data->optName[$i],"list"=>null);
                $optValue = explode(",",$data->optValue[$i]);
                foreach($optValue as $x){
                    $product->variant[$i]['list'][] = Array("v_id"=>null, "value"=>$x);
                }
            }
            $product->createVariant();
            //var_dump($product->variant);
            foreach($data->sku as $k => $x)
            {
                if(!empty($data->sku[$k]))
                {
                    $product->combination[] = Array(
                        "id"=>null,
                        "sku"=> $data->sku[$k],
                        "price"=> $data->price[$k],
                        "quantity"=> $data->qty[$k],
                        "items" => null
                    );
                    $combValue = explode("*",$data->variant[$k]);
                    $i = count($product->combination)-1; // get the current position of combination
                    foreach($combValue as $l => $y){
                        $index = array_search($y, array_column($product->variant[$l]['list'],'value')); //search index by option value
                        $o_id = $product->variant[$l]['id'];
                        $v_id = $product->variant[$l]['list'][$index]['v_id'];
                        $product->combination[$i]['items'][] = Array("o_id"=>$o_id,"v_id"=>$v_id, "value"=>$y);
                    }
                }
            }
            $product->createCombination();

            $redisInstance = Redis::getInstance();
            if($redisInstance != null && $redisInstance::$redis->hexists("products",$product->id))
            {
                $promotion = new Promotion($db);
                $product->variant = null;
                $product->combination = null;
                $product_arr = getUpdatedData($product, $promotion);
                $redis->hset('products',$product->id, serialize($product_arr)); 
            }
            if($redisInstance != null && $redisInstance::$redis->hexists("allproducts",$product->id))
            {
                $promotion = new Promotion($db);
                $product->variant = null;
                $product->combination = null;
                $product_arr = getUpdatedData($product, $promotion);
                $redis->hset('allproducts',$product->id, serialize($product_arr)); 
            }
            //var_dump($product->combination);

            // set response code - 200 ok
            http_response_code(200);
            // tell the user
            echo json_encode(array("message" => "Variants was created."));
        }
    }
}

function getUpdatedData($product, $promotion)
{
    
    $promotion->readActivePromotion($product);
    $product->readOne();

    // create array
    $product_arr = array(
        "id" =>  $product->id,
        "name" => $product->name,
        "description" => $product->description,
        "specification" => $product->specification,
        "price" => $product->price,
        "quantity" => $product->quantity,
        "brand_id" => $product->brand_id,
        "brand_name" => $product->brand_name,
        "category_id" => $product->category_id,
        "category_name" => $product->category_name,
        "type_id" => $product->type_id,
        "type_name" => $product->type_name,
        "release_date" => $product->release_date,
        "status" => $product->status,
        "variants" => $product->variant,
        "combinations" => $product->combination,
        "promotion" => [],
        "pictures" => $product->pictures,
        "feature_pictures" => $product->feature_pictures
    );
    $product_arr['promotion'] = $promotion->getPromotionsToProduct($product_arr);
    return $product_arr;
    
}
?>