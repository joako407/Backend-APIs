<?php
ini_set('session.cookie_lifetime', 0);
ini_set('session.use_only_cookies', 0);
ini_set('session.gc_maxlifetime', 0);
session_start();
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
date_default_timezone_set("Asia/Kuala_Lumpur");
define("SITE_URL", "localhost");

require_once '../../vendor/autoload.php';

use api\config\Database;
use \Firebase\JWT\JWT;
use api\model\User;
use utility\RequestBlocker\PostBlocker;
use utility\AuthorizationHandler\AuthHandler;

new PostBlocker();

$action = basename($_SERVER["SCRIPT_FILENAME"], '.php');
$module = basename(__DIR__);
AuthHandler::AuthorizationGuard($module, $action);

// database connection will be here
$database = new Database();
$db = $database->getConnection();

// initialize object
$user = new User($db);

// get posted data
//$data = json_decode(file_get_contents("php://input"));
$data = json_decode(json_encode($_POST));

// make sure data is not empty
if(
    (isset($data->un_or_email) && !empty($data->un_or_email)) &&
    (isset($data->password) && !empty($data->password)) 
){
    // set product property values
    if(filter_var($data->un_or_email, FILTER_VALIDATE_EMAIL))
        $user->email = strtolower(trim($data->un_or_email));
    else
        $user->username = strtoupper($data->un_or_email);

    $user->password = $data->password;
    $result = $user->login();
    if($result == 1)
    {
        if($user->status != 1){
            // set response code - 201 created
            http_response_code(401);
        
            // tell the user
            echo json_encode(array("message" => "This account was frozen. Please contact your manager."));
        }
        else{
            // secrect key for jwt
            $key = '73Az)tE:a#$Pkd_b'; 
            // provide authority for valid user
            $payload = array(
                "iss" => "localhost",
                "user_id" => $user->id,
                "loginStatus" => true,
                "type" => $user->type,
                "name" =>$user->username,
                "iat" => time(),
                "nbf" => time(),
                "exp" => time() + 3 * 3600 //3 hours
            );
            // generate jwt
            $token = JWT::encode($payload, $key);
            // set cookie
            setcookie("jwt", $token, $payload['exp'], "/", null,null,true);
            $_SESSION['type'] = $user->type;

            // set response code - 201 created
            http_response_code(200);
        
            // tell the user
            echo json_encode(
                array(
                        "token" => $token,
                        "message" => "Login successful."
                    ));
        }   
    }
    else if($result == 2)
    {
        // set response code - 201 created
        http_response_code(401);
    
        // tell the user
        echo json_encode(array("message" => "Unable to login."));
    }
    else if($result == 3)
    {
        // set response code - 201 created
        http_response_code(401);
    
        // tell the user
        //echo json_encode(array("message" => "Invalid password."));
        echo json_encode(array("message" => "Invalid username & password."));
    }
    else if($result == 4)
    {
        // set response code - 201 created
        http_response_code(401);
    
        // tell the user
        //echo json_encode(array("message" => "The account is not exists in the server!"));
        echo json_encode(array("message" => "Invalid username & password."));
    }
}
// tell the user data is incomplete
else{
  
    // set response code - 400 bad request
    http_response_code(401);
  
    // tell the user
    echo json_encode(array("message" => "Unable to login."));
}
?>