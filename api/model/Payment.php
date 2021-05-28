<?php

namespace api\model;
require_once '../../vendor/karyamedia/ipay88/src/IPay88/Payment/Request.php';
require_once '../../vendor/karyamedia/ipay88/src/IPay88/Payment/Response.php';
//require '../../vendor/autoload.php';
//use IPay88\Payment\Request;
use IPay88\Payment\Response;


class Payment {

	protected $_merchantCode;
	protected $_merchantKey;
	protected $_paymentId;
	protected $_refno;
	protected $_amount;
	protected $_currency;
	protected $_prodDesc;
	protected $_userName;
	protected $_userEmail;
	protected $_userContact;
	protected $_remark;


	public function __construct()
	{
		//parent::__construct();
		$this->_merchantCode = 'M31032'; //MerchantCode confidential
        $this->_merchantKey = 'tpjBsmUDe5'; //MerchantKey confidential
	}

	

	public function request()
	{
		$request = new \Request($this->_merchantKey);
		/*$this->_data = array(
			'merchantCode' => $request->setMerchantCode($this->_merchantCode),
			'paymentId' =>  $request->setPaymentId(55),
			'refNo' => $request->setRefNo('EXAMPLE0002'),
			'amount' => $request->setAmount('1.00'),
			'currency' => $request->setCurrency('MYR'),
			'prodDesc' => $request->setProdDesc('Testing'),
			'userName' => $request->setUserName('Your name'),
			'userEmail' => $request->setUserEmail('yungdev8@gmail.com'),
			'userContact' => $request->setUserContact('0123456789'),
			'remark' => $request->setRemark('Some remarks here..'),
			'lang' => $request->setLang('UTF-8'),
			'signature' => $request->getSignature(),
			'responseUrl' => $request->setResponseUrl('https://cheannyong.com.my/payment/response.php'),
			'backendUrl' => $request->setBackendUrl('https://cheannyong.com.my/payment/backend_response.php')
			);*/
			
		$this->_data = array(
			'merchantCode' => $request->setMerchantCode($this->_merchantCode),
			'paymentId' =>  $request->setPaymentId($this->_paymentId),
			'refNo' => $request->setRefNo($this->_refno),
			'amount' => $request->setAmount($this->_amount),
			'currency' => $request->setCurrency($this->_currency),
			'prodDesc' => $request->setProdDesc($this->_prodDesc),
			'userName' => $request->setUserName($this->_userName),
			'userEmail' => $request->setUserEmail($this->_userEmail),
			'userContact' => $request->setUserContact($this->_userContact),
			'remark' => $request->setRemark($this->_remark),
			'lang' => $request->setLang('UTF-8'),
			'signature' => $request->getSignature(),
			'responseUrl' => $request->setResponseUrl('https://cheannyong.com.my/api/payment/response.php'),
			'backendUrl' => $request->setBackendUrl('https://cheannyong.com.my/api/payment/backend_response.php')
			);
		\Request::make($this->_merchantKey, $this->_data);
	}
	
	public function response()
	{	
		$response = (new IPay88\Payment\Response)->init($this->_merchantCode);
		echo "<pre>";
		print_r($response);
	}
	
	public function setPaymentId($id)
	{
	    $this->_paymentId = $id;
	}
	
	public function setRefNo($refno)
	{
	    $this->_refno = $refno;
	}
	
	public function setAmount($amount)
	{
	    $this->_amount = $amount;
	}
	
	public function setCurrency($currency)
	{
	    $this->_currency = $currency;
	}
	
	public function setProdDesc($desc)
	{
	    $this->_prodDesc = $desc;
	}
	
	public function setUserName($username)
	{
	    $this->_userName = $username;
	}
	
	public function setUserEmail($email)
	{
	    $this->_userEmail = $email;
	}
	
	public function setUserContact($contact)
	{
	    $this->_userContact = $contact;
	}
	
	public function setRemark($remark)
	{
	    $this->_remark = $remark;
	}
	
	public function getMerchantCode()
	{
	    return $this->_merchantCode;
	}
	
	public function getMerchantKey()
	{
	    return $this->_merchantKey;
	}
}