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
//var_dump($data);
if(
    !empty($data->id) &&
    !empty($data->mode) &&
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
            if($data->mode === "PL"){
                
                $product->product_id = $data->id;
                
                $product->user_id = $payload->user_id;
                $product->created = date('Y-m-d H:i:s');
                $product->copyProductInfo('edit');
    
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
            }

            $product->deleteVaraint2();
            for($i = 0; $i < count($data->optName); $i++)
            {
                $product->variant[] = Array("id"=>null,"name"=>$data->optName[$i],"list"=>null);
                $optValue = explode(",",$data->optValue[$i]);
                foreach($optValue as $x){
                    $product->variant[$i]['list'][] = Array("v_id"=>null, "value"=>$x);
                }
            }
            $product->createVariant2();
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
            $product->createCombination2();

            //var_dump($product->combination);

            // set response code - 200 ok
            http_response_code(200);
            // tell the user
            echo json_encode(array("message" => "Variants was created."));
        }
    }
}
?>