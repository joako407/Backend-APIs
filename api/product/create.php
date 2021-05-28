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
use api\model\Picture;
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
$data->compress = ((isset($data->compress) && $data->compress == "on") ? true : false);
// make sure data is not empty
if(
    !empty($data->name) &&
    !empty($data->description) &&
    !empty($data->specification) &&
    isset($data->label) &&
    !empty($data->brand_id) &&
    !empty($data->category_id) &&
    isset($_FILES['files']['tmp_name']) &&
    is_uploaded_file($_FILES['files']['tmp_name'][0])
){
    if($payload){
        // set product property values
        $product->name = $data->name;
        $product->description = $data->description;
        $product->specification = $data->specification;
        $product->label = $data->label;
        $product->brand_id = $data->brand_id;
        $product->category_id = $data->category_id;
        $product->created = date('Y-m-d H:i:s');
        $product->type_id = (isset($data->type_id) && !empty($data->type_id))? $data->type_id : null; 
        $product->series_id = (isset($data->series_id) && !empty($data->series_id))? $data->series_id : null; 
        $product->release_date = (isset($data->release_date) && !empty($data->release_date))? $data->release_date : null;

        // create the product
        if($product->create()){

            // initialize Picture object for upload picture and insert picture path data
            $picture = new Picture($db, "p");
            $picture->target_id = $product->id;
            $picture->pictures = $_FILES['files']['tmp_name'];
            $picture->compress = $data->compress;
            $flag = $picture->create();

            $picture = new Picture($db, "d");
            $picture->target_id = $product->id;
            $picture->pictures = (isset($_FILES['files2']['tmp_name']) && is_uploaded_file($_FILES['files2']['tmp_name'][0]))? $_FILES['files2']['tmp_name'] : null;
            $picture->compress = $data->compress;
            $flag1 = $picture->create();


            if($flag && $flag1){

                $promotion = new Promotion($db);
                $redisInstance = Redis::getInstance();
                if($redisInstance != null && !$redisInstance::$redis->hexists('allproducts', $product->id))
                {
                    $product_arr = getUpdatedData($product, $promotion);
                    $redisInstance::$redis->hset('allproducts',$product->id, serialize($product_arr));
                }
                // set response code - 201 created
                http_response_code(201);
        
                // tell the user
                echo json_encode(array("message" => "Product was created."));
            }else{
                // set response code - 503 service unavailable
                http_response_code(503);
        
                // tell the user
                echo json_encode(array("message" => "Unable to create product."));
            }

            
        }
        // if unable to create the product, tell the user
        else{
            // set response code - 503 service unavailable
            http_response_code(503);
    
            // tell the user
            echo json_encode(array("message" => "Unable to create product."));
        }
    }
    
}
// tell the user data is incomplete
else{
  
    // set response code - 400 bad request
    http_response_code(400);
  
    // tell the user
    echo json_encode(array("message" => "Unable to create product. Data is incomplete."));
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
        "label" => $product->label,
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