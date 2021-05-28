<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
date_default_timezone_set("Asia/Kuala_Lumpur");
define("SITE_URL", "localhost");

require_once '../../vendor/autoload.php';
use api\config\Database;
use api\model\Brand;
use utility\RequestBlocker\GetBlocker;
use utility\Redis\Redis;
use utility\AuthorizationHandler\AuthHandler;

new GetBlocker();

$action = basename($_SERVER["SCRIPT_FILENAME"], '.php');
$module = basename(__DIR__);
$payload = AuthHandler::AuthorizationGuard($module, $action);

// database connection will be here
$database = new Database();
$db = $database->getConnection();

// initialize object
$brand = new Brand($db);

if($payload)
{
    // query products
    $stmt = $brand->getAll();
    $num = $stmt->rowCount();
    // check if more than 0 record found
    if($num>0){

        // brands array
        $brands_arr=array();
        $brands_arr["records"]=array();
        // retrieve our table contents
        // fetch() is faster than fetchAll()
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            // extract row
            // this will make $row['name'] to
            // just $name only
            extract($row);
            if(array_search($brandID,array_column($brands_arr['records'],'id')) === false){
                $brand_item=array(
                    "id" => $brandID,
                    "name" => $name,
                    "picture" => $pic_path,
                    "series" => array(array("id"=>$seriesID, "name" => $seriesName)),
                    "categories" => array(array("id"=>$categoryID, "name" => $categoryName)),
                );
                array_push($brands_arr["records"], $brand_item);
                
            }
            $index = array_search($brandID, array_column($brands_arr['records'],'id'));
            if(array_search($seriesID,array_column($brands_arr['records'][$index]['series'],'id')) === false){
                array_push($brands_arr['records'][$index]['series'],array("id"=>$seriesID,"name"=>$seriesName));
            }
            if(array_search($categoryID,array_column($brands_arr['records'][$index]['categories'],'id')) === false){
                array_push($brands_arr['records'][$index]['categories'],array("id"=>$categoryID,"name"=>$categoryName));
            }
        
        }
        
        // set response code - 200 OK
        http_response_code(200);

        // show products data in json format
        echo json_encode($brands_arr);
    }
    else{
        // set response code - 404 Not found
        http_response_code(404);

        // tell the user no products found
        echo json_encode(
            array("message" => "No brands found.")
        );
    }
}





?>