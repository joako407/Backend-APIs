<?php
session_start();

// required headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
date_default_timezone_set("Asia/Kuala_Lumpur");
define("SITE_URL", "localhost");

require_once '../../vendor/autoload.php';
use api\config\Database;
use api\model\Order;
use utility\RequestBlocker\GetBlocker;
use utility\JWT\JWTHelper;

new GetBlocker();

// database connection will be here
$database = new Database();
$db = $database->getConnection();

// initialize object
$order = new Order($db);
$payload = JWTHelper::tokenValidate();

if(
    $payload &&
    !empty($payload->user_id) &&
    !empty($payload->type) &&
    !empty($payload->loginStatus) &&
    $payload->type == "CLIENT"
)
{
    $order->user_id = $payload->user_id;
    
    // query products
    $stmt = $order->read();
    $num = $stmt->rowCount();
    // check if more than 0 record found
    if($num>0){
    
        // brands array
        $orders_arr=array();
        $orders_arr["records"]=array();
        // retrieve our table contents
        // fetch() is faster than fetchAll()
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            // extract row
            // this will make $row['name'] to
            // just $name only
            extract($row);
            if(array_search($order_id,array_column($orders_arr['records'],'order_id')) === false){
                $order_item=array(
                    "order_id" => $order_id,
                    "refno" => $refno,
                    "user_id" => $user_id,
                    "order_date" => $order_date,
                    "amount" => $amount,
                    "currency" => $currency,
                    "payment_id" => $payment_id,
                    "prodDesc" => $prodDesc,
                    "order_subtotal" => $order_subtotal,
                    "shipping_fee" => $shipping_fee,
                    "shipping_address" => $shipping_address,
                    "shipping_postalcode" => $shipping_postalcode,
                    "shipping_city" => $shipping_city,
                    "shipping_state" => $shipping_state,
                    "user_name" => $user_name,
                    "user_contact" => $user_contact,
                    "user_email" => $user_email,
                    "order_status" => $order_status,
                    "payment_date" => $payment_date,
                    "order_items" => []
                );
                
                array_push($orders_arr["records"], $order_item);
            }
            
            $index = array_search($order_id, array_column($orders_arr['records'],'order_id'));  
            if(array_search($order_item_id,array_column($orders_arr['records'][$index]['order_items'],'order_item_id')) === false){
                array_push($orders_arr['records'][$index]['order_items'], 
                array(
                    "order_item_id" => $order_item_id, 
                    "product_id" => $product_id,
                    "sku_id" => $sku_id,
                    "quantity" => $quantity,
                    "product_name" => $name,
                    "product_description" => $description,
                    "product_picture" => $pic_path,
                    "variants" => [],
                    "items" => []
                ));              
            }
            $index2 = array_search($order_item_id, array_column($orders_arr['records'][$index]['order_items'],'order_item_id'));
            if(array_search($optionName,array_column($orders_arr['records'][$index]['order_items'][$index2]['variants'],'name')) === false){
                           
                array_push($orders_arr['records'][$index]['order_items'][$index2]['variants'],array("name" => $optionName));
            }
            if(array_search($optionValue,$orders_arr['records'][$index]['order_items'][$index2]['items']) === false){
                           
                array_push($orders_arr['records'][$index]['order_items'][$index2]['items'],$optionValue);
            }
        }         
        // set response code - 200 OK
        http_response_code(200);
    
        // show products data in json format
        echo json_encode($orders_arr);
    }
    else{
        // set response code - 404 Not found
        http_response_code(404);
    
        // tell the user no products found
        echo json_encode(
            array("message" => "No order found.")
        );
    }
}
?>