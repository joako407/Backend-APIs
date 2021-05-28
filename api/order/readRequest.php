<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
date_default_timezone_set("Asia/Kuala_Lumpur");
define("SITE_URL", "localhost");

require_once '../../vendor/autoload.php';
use api\config\Database;
use api\model\User;
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
$user = new User($db);

if($payload){
    $request_arrs = array();
    $request_arrs['records'] = array();
    
    if (file_exists('request.csv')) {
        $requests = array_map('str_getcsv', file('request.csv'));
        foreach($requests as $req){
            if($payload->type === "STAFF" && $payload->user_id === $req[2]){   
                $user->id = $req[2];
                $user->readOne();
                
                $req_arr = array("id" => $req[0],"order_id"=>$req[1], "user" => "$user->firstname $user->lastname", "status" => $req[3], "cod" => $req[4],"date" => $req[5]);
                $request_arrs['records'][] = $req_arr;
            }
            else if($payload->type !== "STAFF"){
                $user->id = $req[2];
                $user->readOne();
                
                $req_arr = array("id" => $req[0],"order_id"=>$req[1], "user" => "$user->firstname $user->lastname", "status" => $req[3], "cod" => $req[4],"date" => $req[5]);
                $request_arrs['records'][] = $req_arr;
            }
        }
    }

    if(count($request_arrs['records']) > 0){
        // set response code - 200 OK
        http_response_code(200);
        
        // show users data in json format
        echo json_encode($request_arrs);
    }
    else{
        // set response code - 200 OK
        http_response_code(404);
        
        // show users data in json format
        echo json_encode($request_arrs);
    }
    
    
}
else{
    http_response_code(403);
    echo json_encode(array("message" => "403 Forbidden."));
    exit;
}


?>