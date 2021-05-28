<?php
session_start();

header("Access-Control-Allow-Origin: *");
//header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
date_default_timezone_set("Asia/Kuala_Lumpur");
define("SITE_URL", "localhost");

require_once '../../vendor/autoload.php';

use api\config\Database;
use api\model\Payment;
use api\model\Order;
use utility\RequestBlocker\PostBlocker;
use utility\JWT\JWTHelper;

//request method type handler
new PostBlocker();

// database connection will be here
$database = new Database();
$db = $database->getConnection();

//initialize instance of object 
$order = new Order($db);
$payment = new Payment();
$payload = JWTHelper::tokenValidate();

//get data from post request.
$data = json_decode(json_encode($_POST));

// make sure data is not empty
if(
    !empty($data->currency) &&
    !empty($data->prodDesc) &&
    !empty($data->paymentId) &&
    !empty($data->orderSubtotal) &&
    !empty($data->amount) &&
    !empty($data->shippingFee) &&
    !empty($data->shippingAddress) &&
    !empty($data->shippingPostalCode) &&
    !empty($data->shippingCity) &&
    !empty($data->shippingState) &&
    !empty($data->shippingUserName) &&
    !empty($data->userContact) &&
    !empty($data->userEmail) &&
    !empty($data->orderItems)
){
    if(
        $payload &&
        !empty($payload->user_id) &&
        !empty($payload->type) &&
        !empty($payload->loginStatus) &&
        $payload->type == "CLIENT"
    ){
        $order->refno = refNoGenerator($payload->user_id); 
        $order->user_id = $payload->user_id; 
        $order->order_date = date('Y-m-d H:i:s'); 
        $order->currency = $data->currency; 
        $order->prodDesc = $data->prodDesc; 
        $order->payment_id = $data->paymentId; 
        $order->order_subtotal = $data->orderSubtotal; 
        $order->amount = $data->amount; 
        $order->shipping_fee = $data->shippingFee; 
        $order->shipping_address = $data->shippingAddress; 
        $order->shipping_postalcode = $data->shippingPostalCode; 
        $order->shipping_city = $data->shippingCity; 
        $order->shipping_state = $data->shippingState; 
        $order->user_name = $data->shippingUserName; 
        $order->user_contact = $data->userContact; 
        $order->user_email = $data->userEmail; 
        $order->status = 0; //default 0 0=>to paid , 1=>to delivery, 2=>to receive
        $order->created = date('Y-m-d H:i:s'); 
        $order->order_items = json_decode($data->orderItems); 
        
        $data->refno = $order->refno;
           
        $order->create();
        paymentHandler($payment, $data);
    }
}

function refNoGenerator($userId) {
    $time = time();
    return "{$time}{$userId}";
}


function paymentHandler($payment, $data){
    if(isset($data->paymentId)){
        $payment->setPaymentId($data->paymentId);
    }
    if(isset($data->refno)){
        $payment->setRefNo($data->refno);
    }
    if(isset($data->amount)){
        //$payment->setAmount($data->amount);
        $payment->setAmount("1.00");
    }
    if(isset($data->currency)){
        $payment->setCurrency($data->currency);
    }
    if(isset($data->prodDesc)){
        $payment->setProdDesc($data->prodDesc);
    }
    if(isset($data->shippingUserName)){
        $payment->setUserName($data->shippingUserName);
    }
    if(isset($data->userEmail)){
        $payment->setUserEmail($data->userEmail);
    }
    if(isset($data->userContact)){
        $payment->setUserContact($data->userContact);
    }
    if(isset($data->remark)){
        $payment->setRemark($data->remark);
    }  
    $payment->request();
}