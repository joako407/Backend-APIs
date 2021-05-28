<?php
session_start();

header("Access-Control-Allow-Origin: *");
//header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
date_default_timezone_set("Asia/Kuala_Lumpur");
define("SITE_URL", "localhost");

require_once '../../vendor/autoload.php';
require_once '../../vendor/karyamedia/ipay88/src/IPay88/Security/Signature.php';

use api\config\Database;
use api\model\TestOrder;



// database connection will be here
$database = new Database();
$db = $database->getConnection();

$testorder = new TestOrder($db);

$res['merchantcode'] = $_REQUEST["MerchantCode"];
$res['paymentid'] = $_REQUEST["PaymentId"];
$res['refno'] = $_REQUEST["RefNo"];
$res['amount'] = $_REQUEST["Amount"];
$res['ecurrency'] = $_REQUEST["Currency"];
$res['remark'] = $_REQUEST["Remark"];
$res['transid'] = $_REQUEST["TransId"];
$res['authcode'] = $_REQUEST["AuthCode"];
$res['estatus'] = $_REQUEST["Status"];
$res['errdesc'] = $_REQUEST["ErrDesc"];
$res['signature'] = $_REQUEST["Signature"];
$res['ccname'] = $_REQUEST["CCName"];
$res['ccno'] = $_REQUEST["CCNo"];
$res['s_bankname'] = $_REQUEST["S_bankname"];
$res['s_country'] = $_REQUEST["S_country"];

if($res['estatus'] == 1){
    $mySignature = Signature::generateSignature(
		"tpjBsmUDe5",
		$res['merchantcode'],
		$res['paymentid'],
		$res['refno'],
		preg_replace('/[\.\,]/', '', $res['amount']), //clear ',' and '.'
		$res['ecurrency'],
		$res['estatus']
	);
    
	if($mySignature == $res['signature']){
	    $testorder->status = 'success1';
        $testorder->create();
	}
	else{
	    $testorder->status = 'pass1';
        $testorder->create();
	}
}
else{
    $testorder->status = 'failed1';
    $testorder->create();
    
}
header("Location: https://cheannyong.com.my");
