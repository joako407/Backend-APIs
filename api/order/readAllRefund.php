<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
date_default_timezone_set("Asia/Kuala_Lumpur");
define("SITE_URL", "localhost");

require_once '../../vendor/autoload.php';
use api\config\Database;
use api\model\Order;
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
$order = new Order($db);
if($payload)
{
    // query products
    $stmt = $order->readAllRefund();
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
                    "order_subtotal" => $order_subtotal,
                    "order_total" => $order_total,
                    "remark" => $remark,
                    "refund" => $refund,
                    "prodDesc" => $prodDesc,
                    "shipping_fee" => $shipping_fee,
                    "order_status" => $order_status,
                    "payment_date" => $payment_date,
                    "user_name" => $user_name,
                    "contact" => $contact,
                    "address" => $address,
                    "postalcode" => $postalcode,
                    "city" => $city,
                    "state" => $state,
                    "transId" => $transId,
                    "payment_amount" => $payment_amount,
                    "authDetail" => $authDetail,
                    "payment_status" => $payment_status,
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
                    "sku" => $sku,
                    "price" => $price,
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