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
  
if(
    isset($data->id) && 
    !empty($data->id)
){
    if($payload){
        // set product id to be deleted
        $product->id = $data->id;

        //delete the product variant
        $product->deleteVaraint2();

        // delete the product
        if($product->delete2()){
        
            $flag = false;
            $flag1 = false;
            // initialize Picture object for upload picture and insert picture path data
            $picture = new Picture($db,"p");
            $picture->target_id = $product->id;
            $picture->readAllRemovePicture2(); // assign remove pictures to picture object
            $flag = $picture->delete2();

            $picture = new Picture($db,"d");
            $picture->target_id = $product->id;
            $picture->readAllRemovePicture2(); // assign remove pictures to picture object
            $flag1 = $picture->delete2();

            if($flag && $flag1)
            {
                // set response code - 200 ok
                http_response_code(200);
            
                // tell the user
                echo json_encode(array("message" => "Product was deleted."));
            }
            else
            {
                // set response code - 503 service unavailable
                http_response_code(503);
            
                // tell the user
                echo json_encode(array("message" => "Unable to delete product.1"));
            }  
        }
        
        // if unable to delete the product
        else{
        
            // set response code - 503 service unavailable
            http_response_code(503);
        
            // tell the user
            echo json_encode(array("message" => "Unable to delete product.2"));
        }
    }
}
else{
    // set response code - 400 bad request
    http_response_code(400);
  
    // tell the user
    echo json_encode(array("message" => "Unable to delete product. Data is incomplete."));
}

?>