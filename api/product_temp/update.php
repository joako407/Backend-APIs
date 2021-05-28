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
$data->compress = ((isset($data->compress) && $data->compress == "on") ? true : false);

if(
    isset($data->id) && 
    !empty($data->id)
){
    if($payload){
        // set ID property of product to be edited
        $product->id = $data->id;
        
        // set product property values
        $product->created = date('Y-m-d H:i:s');
        $product->modified = date('Y-m-d H:i:s');
        $product->name = (isset($data->name) && !empty($data->name))? $data->name : null;
        $product->description = (isset($data->description) && !empty($data->description))? $data->description : null;
        $product->specification = (isset($data->specification) && !empty($data->specification))? $data->specification : null;
        $product->brand_id = (isset($data->brand_id) && !empty($data->brand_id))? $data->brand_id : null;
        $product->category_id = (isset($data->category_id) && !empty($data->category_id))? $data->category_id : null;
        $product->type_id = (isset($data->type_id) && !empty($data->type_id))? $data->type_id : null; 
        $product->series_id = (isset($data->series_id) && !empty($data->series_id))? $data->series_id : null; 
        $product->release_date = (isset($data->release_date) && !empty($data->release_date))? $data->release_date : null;
        $product->status = (isset($data->status) && $data->status !=="")? $data->status : null; 
        $product->user_id = $payload->user_id;
        $product->action = "edit";
        
        // update the product
        if($product->update2()){
        
            // initialize Picture object for upload picture and insert picture path data
            $flag = false;
            $flag1 = false;
            $flag2 = false;
            $flag3 = false;

            //copy pictures from original product.
            if($product->action_mode === 1)
            {
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
                $product->cloneVariant();
                
            }
        

            //for replace current picture of product
            if(
                isset($_FILES['c_files']['tmp_name']) && is_uploaded_file($_FILES['c_files']['tmp_name'][0]) &&
                isset($data->replace_pic) && !empty($data->replace_pic)
            ){
                $replace_files = json_decode($data->replace_pic);
                $temp_files = [];
                foreach($replace_files as $r_f){
                    $file_name = basename($r_f);
                    if(strpos($file_name, 'temp') !== false){
                        $temp_file_name = $file_name;
                    }
                    else{  
                        $temp_file_name = "temp_".$product->id."_".$file_name;
                    }
                    $path = str_replace($file_name, "", $r_f);
                    $temp_files[] = $path.$temp_file_name;
                }
                
                $data->replace_pic = $temp_files;
                $_FILES['c_files']['tmp_name'] = array_filter($_FILES['c_files']['tmp_name']);
                if(count($data->replace_pic) == count($_FILES['c_files']['tmp_name']))
                {
                    $picture = new Picture($db,"p");
                    $picture->target_id = $product->id;
                    $picture->pictures = (isset($_FILES['c_files']['tmp_name']) && is_uploaded_file($_FILES['c_files']['tmp_name'][0]))? $_FILES['c_files']['tmp_name'] : null;
                    $picture->compress = $data->compress;
                    $picture->remove_pic = (isset($data->replace_pic) && !empty($data->replace_pic))? $data->replace_pic : null; 
                    $flag = $picture->update2($product->id);
                }
            }else{
                $flag = true;
            }

            //for replace current picture of feature product
            if(
                isset($_FILES['c_files2']['tmp_name']) && is_uploaded_file($_FILES['c_files2']['tmp_name'][0]) &&
                isset($data->replace_pic2) && !empty($data->replace_pic2)
            ){
                $replace_files2 = json_decode($data->replace_pic2);
                $temp_files2 = [];
                foreach($replace_files2 as $r_f){
                    $file_name = basename($r_f);
                    if(strpos($file_name, 'temp') !== false){
                        $temp_file_name = $file_name;
                    }
                    else{  
                        $temp_file_name = "temp_".$product->id."_".$file_name;
                    }
                    $path = str_replace($file_name, "", $r_f);
                    $temp_files2[] = $path.$temp_file_name;
                }
                
                $data->replace_pic2 = $temp_files2;
                $_FILES['c_files2']['tmp_name'] = array_filter($_FILES['c_files2']['tmp_name']);
                if(count($data->replace_pic2) == count($_FILES['c_files2']['tmp_name']))
                {
                    $picture = new Picture($db,"d");
                    $picture->target_id = $product->id;
                    $picture->pictures = (isset($_FILES['c_files2']['tmp_name']) && is_uploaded_file($_FILES['c_files2']['tmp_name'][0]))? $_FILES['c_files2']['tmp_name'] : null;
                    $picture->compress = $data->compress;
                    $picture->remove_pic = (isset($data->replace_pic2) && !empty($data->replace_pic2))? $data->replace_pic2 : null; 
                    $flag1 = $picture->update2($product->id);
                    
                }
            }else{
                $flag1 = true;
            }

            //for additional picture of product
            if(
                (isset($_FILES['files']['tmp_name']) && is_uploaded_file($_FILES['files']['tmp_name'][0])) ||
                (isset($data->remove_pic) && !empty($data->remove_pic))    
            ){
                if(isset($data->remove_pic)){
                    $remove_files = json_decode($data->remove_pic);
                    $temp_files = [];
                    foreach($remove_files as $r_f){
                        $file_name = basename($r_f);
                        if(strpos($file_name, 'temp') !== false){
                            $temp_file_name = $file_name;
                        }
                        else{  
                            $temp_file_name = "temp_".$product->id."_".$file_name;
                        }
                        $path = str_replace($file_name, "", $r_f);
                        $temp_files[] = $path.$temp_file_name;
                    }
                    
                    $data->remove_pic = $temp_files;
                }
                $picture = new Picture($db,"p");
                $picture->target_id = $product->id;
                $picture->pictures = (isset($_FILES['files']['tmp_name']) && is_uploaded_file($_FILES['files']['tmp_name'][0]))? $_FILES['files']['tmp_name'] : null;
                $picture->compress = $data->compress;
                $picture->remove_pic = (isset($data->remove_pic) && !empty($data->remove_pic))? $data->remove_pic : null; 
                $flag2 = $picture->update2($product->id);
            }
            else
                $flag2 = true;
            
            //for addtional picture of product feature
            if(
                (isset($_FILES['files2']['tmp_name']) && is_uploaded_file($_FILES['files2']['tmp_name'][0])) ||
                (isset($data->remove_pic2) && !empty($data->remove_pic2))   
            ){
                if(isset($data->remove_pic2)){                   
                    $remove_files2 = json_decode($data->remove_pic2);
                    $temp_files2 = [];
                    foreach($remove_files2 as $r_f){
                        $file_name = basename($r_f);
                        if(strpos($file_name, 'temp') !== false){
                            $temp_file_name = $file_name;
                        }
                        else{  
                            $temp_file_name = "temp_".$product->id."_".$file_name;
                        }
                        $path = str_replace($file_name, "", $r_f);
                        $temp_files2[] = $path.$temp_file_name;
                    }        
                    $data->remove_pic2 = $temp_files2;
                }
                $picture = new Picture($db,"d");
                $picture->target_id = $product->id;
                $picture->pictures = (isset($_FILES['files2']['tmp_name']) && is_uploaded_file($_FILES['files2']['tmp_name'][0]))? $_FILES['files2']['tmp_name'] : null;
                $picture->compress = $data->compress;
                $picture->remove_pic = (isset($data->remove_pic2) && !empty($data->remove_pic2))? $data->remove_pic2 : null; 
                $flag3 = $picture->update2($product->id);
            }   
            else
                $flag3 = true;

            if($flag && $flag1 && $flag2 && $flag3){

                // set response code - 200 ok
                http_response_code(200);
            
                // tell the user
                echo json_encode(array("message" => "Product was updated."));
            }
            else{
                // set response code - 503 service unavailable
                http_response_code(503);
            
                // tell the user
                echo json_encode(array("message" => "Unable to update product."));
            }
            
        }
        
        // if unable to update the product, tell the user
        else{
        
            // set response code - 503 service unavailable
            http_response_code(503);
        
            // tell the user
            echo json_encode(array("message" => "Unable to update product."));
        }
    }
    else{
        // set response code - 503 service unavailable
        http_response_code(503);
        
        // tell the user
        echo json_encode(array("message" => "Unable to update product."));
    }
}
else
{
    // set response code - 400 bad request
    http_response_code(400);
  
    // tell the user
    echo json_encode(array("message" => "Unable to update product. Data is incomplete."));
}

?>