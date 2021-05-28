<?php
session_start();
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
date_default_timezone_set("Asia/Kuala_Lumpur");
define("SITE_URL", "localhost");

require_once '../../vendor/autoload.php';
use api\config\Database;
use api\model\Product;
use utility\RequestBlocker\GetBlocker;
use utility\AuthorizationHandler\AuthHandler;

new GetBlocker();

$action = basename($_SERVER["SCRIPT_FILENAME"], '.php');
$module = basename(__DIR__);
$payload = AuthHandler::AuthorizationGuard($module, $action);

// database connection will be here
$database = new Database();
$db = $database->getConnection();

// initialize object
$product = new Product($db);

// set additonal constraint property of record to read
$product->limit = isset($_GET['limit']) ? $_GET['limit'] : null;
$product->keyword = isset($_GET['keyword']) ? $_GET['keyword'] : null;
$product->order_by_name = isset($_GET['order_by_name']) ? $_GET['order_by_name'] : null;
$product->order_by_price = isset($_GET['order_by_price']) ? $_GET['order_by_price'] : null;
$product->order_by_release_date = isset($_GET['order_by_release_date']) ? $_GET['order_by_release_date'] : null;
$product->brand_id = isset($_GET['brand_id']) ? $_GET['brand_id'] : null;
$product->category_id = isset($_GET['category_id']) ? $_GET['category_id'] : null;
$product->type_id = isset($_GET['type_id']) ? $_GET['type_id'] : null;
$product->series_id = isset($_GET['series_id']) ? $_GET['series_id'] : null;


if($payload){
    // query products
    $product->user_id = $payload->user_id;
    $stmt = $product->read2();
    $num = $stmt->rowCount();
    // check if more than 0 record found
    
    if($num>0){
        // products array
        $products_arr=array();
        $products_arr["records"]=array();
        // retrieve our table contents
        // fetch() is faster than fetchAll()
        $temp=0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            // extract row
            // this will make $row['name'] to
            // just $name only
            extract($row);
            if(array_search($id,array_column($products_arr['records'],'id')) === false)
            {
                if(isset($product->limit) && !empty($product->limit))
                {
                    if(count($products_arr['records']) == $product->limit)
                    {
                        break;
                    }
                }
                $product->id = $id;
                $product->readOption2();
                $product->readCombination2();
                $product_item=array(
                    "id" => $id,
                    "name" => $name,
                    "description" => html_entity_decode($description),
                    "specification" => html_entity_decode($specification),
                    "price" => $product->price,
                    "quantity" => $product->quantity,
                    "brand_id" => $brand_id,
                    "brand_name" => $brand_name,
                    "category_id" => $category_id,
                    "category_name" => $category_name,
                    "type_id" => $type_id,
                    "type_name" => $type_name,
                    "series_id" => $series_id,
                    "series_name" => $series_name,
                    "release_date" => $release_date,
                    "user_id" => $user_id,
                    "action" => $action,
                    "status" => $status,
                    "created" => $created,
                    "variants" => (empty($product->variant)? null: $product->variant),
                    "combinations" => (empty($product->combination)? null: $product->combination),
                    "pictures" => [],
                    "feature_pictures" => []
                );
                array_push($products_arr["records"], $product_item);
                unset($product->variant);
                unset($product->combination);
            }
            $index = array_search($id, array_column($products_arr['records'],'id')); 
            if($type=='p' && array_search($pic_path, $products_arr['records'][$index]['pictures'], true) === false)
                $products_arr['records'][$index]['pictures'][] = $pic_path;
            else if($type=='d' && array_search($pic_path, $products_arr['records'][$index]['feature_pictures'], true) === false)
                $products_arr['records'][$index]['feature_pictures'][] = $pic_path;   
            
        }

        // set response code - 200 OK
        http_response_code(200);
    
        // show products data in json format
        echo json_encode($products_arr);
    }
    else{
        
        // set response code - 404 Not found
        http_response_code(404);
    
        // tell the user no products found
        echo json_encode(
            array("message" => "No products found.")
        );
    }
    
}
?>