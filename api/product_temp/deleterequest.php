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
use utility\RequestBlocker\PostBlocker;
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
// make sure data is not empty
if(
    isset($data->id) && 
    !empty($data->id)
){
    if($payload){
        // set product property values
        $product->product_id = $data->id;
        $product->user_id = $payload->user_id;
        $product->created = date('Y-m-d H:i:s');
        $product->copyProductInfo('delete');

        $product->getProductPicture();
        if(!empty($product->pictures)){
            $picture = new Picture($db,"p");
            $picture->pictures = $product->pictures;
            $picture->target_id = $product->id;
            $picture->copyPicture($product->id);
        }
        if(!empty($product->feature_pictures)){  
            $picture = new Picture($db,"d");
            $picture->pictures = $product->feature_pictures;
            $picture->target_id = $product->id;
            $picture->copyPicture($product->id);
        }

        // set response code - 201 created
        http_response_code(201);

        // tell the user
        echo json_encode(array("message" => "Product was created."));
        
    }
    
}
// tell the user data is incomplete
else{
  
    // set response code - 400 bad request
    http_response_code(400);
  
    // tell the user
    echo json_encode(array("message" => "Unable to create product. Data is incomplete."));
}

?>