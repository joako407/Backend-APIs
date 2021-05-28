<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');
date_default_timezone_set("Asia/Kuala_Lumpur");
define("SITE_URL", "localhost");

// include database and object files
require_once '../../vendor/autoload.php';
use api\config\Database;
use api\model\Product;
use api\model\Promotion;
use utility\RequestBlocker\GetBlocker;
use utility\Redis\Redis;
use utility\AuthorizationHandler\AuthHandler;

new GetBlocker();

$action = basename($_SERVER["SCRIPT_FILENAME"], '.php');
$module = basename(__DIR__);
AuthHandler::AuthorizationGuard($module, $action);
  
// get database connection
$database = new Database();
$db = $database->getConnection();
  
// prepare product object
$product = new Product($db);
$promotion = new Promotion($db);
  
// set ID property of record to read
$product->id = isset($_GET['id']) ? $_GET['id'] : die();
$product->status = 1;

$redisInstance = Redis::getInstance();
if($redisInstance != null && $redisInstance::$redis->hexists('products', $product->id))
{
    $product_arr = unserialize($redisInstance::$redis->hget('products', $product->id));
    
    http_response_code(200);
    
    // make it json format
    echo json_encode($product_arr);
}
else{
    // query products
    $promotion->readActivePromotion($product);

    $product->readOne();

    if($product->name!=null){
        // create array
        $product_arr = array(
            "id" =>  $product->id,
            "name" => $product->name,
            "description" => html_entity_decode($product->description),
            "specification" => html_entity_decode($product->specification),
            "label" => $product->label,
            "price" => $product->price,
            "quantity" => $product->quantity,
            "brand_id" => $product->brand_id,
            "brand_name" => $product->brand_name,
            "category_id" => $product->category_id,
            "category_name" => $product->category_name,
            "type_id" => $product->type_id,
            "type_name" => $product->type_name,
            "series_id" => $product->series_id,
            "series_name" => $product->series_name,
            "release_date" => $product->release_date,
            "status" => $product->status,
            "version" => $product->version,
            "variants" => $product->variant,
            "combinations" => $product->combination,
            "promotion" => [],
            "pictures" => $product->pictures,
            "feature_pictures" => $product->feature_pictures
        );

        $product_arr['promotion'] = $promotion->getPromotionsToProduct($product_arr);
    
        // set response code - 200 OK
        http_response_code(200);
    
        // make it json format
        echo json_encode($product_arr);
    }
    
    else{
        // set response code - 404 Not found
        http_response_code(404);
    
        // tell the user product does not exist
        echo json_encode(array("message" => "Product does not exist."));
    }
}

?>