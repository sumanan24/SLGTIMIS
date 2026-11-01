<?php

/* This php file contains the methods which access the ESMS web services
*Author: Izzath Dilshana
*
*
*=================sample code for sending SMS===========================================
*
*$session=createSession('','username','password','');
*sendMessages($session,'alias','message text',array('71xxxxxxx','71xxxxxxx'),1); // 1 for promotional messages, 0 for normal message 
*closeSession($session);
*
*=======================================================================================
*
*
*==============sample code for retrieving SMS===========================================
*
*$session=createSession('','username','password','');
*getMessagesFromShortCode($session,"shortcode");
*getMessagesFromLongNumber($session,"longnum");
*closeSession($session);
*
*=======================================================================================
*
*
*==============sample code for retrieving Delivery Reports===========================================
*
*$session=createSession('','username','password','');
*getDeliveryReports($session,"alias")
*closeSession($session);
*
*=======================================================================================

**/

//====================================ESMS WEB SERVCIES START ================================

/**
 * The gateway stalls PHP's own HTTP client. Curl with this agent gets a response.
 */
function mobitelSoapTransport($request, $location, $action, $version, $oneWay)
{
	if (!function_exists('curl_init')) {
		throw new SoapFault('HTTP', 'Error Fetching http headers');
	}
	static $ch = null;
	$headers = array(
		'Content-Type: text/xml; charset=utf-8',
		'SOAPAction: "' . $action . '"',
		'Connection: keep-alive',
	);
	$response = false;
	$errno = 0;
	for ($attempt = 1; $attempt <= 4; $attempt++) {
		$open = is_resource($ch) || (class_exists('CurlHandle', false) && $ch instanceof CurlHandle);
		if (!$open) {
			$ch = curl_init($location);
			$options = array(
				CURLOPT_POST => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_USERAGENT => 'curl/7.68.0',
				CURLOPT_TIMEOUT => 4,
				CURLOPT_CONNECTTIMEOUT => 4,
			);
			if (defined('CURL_HTTP_VERSION_1_1')) {
				$options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
			}
			$ca = '/etc/ssl/certs/ca-certificates.crt';
			if (is_file($ca)) {
				$options[CURLOPT_CAINFO] = $ca;
			}
			if (defined('CURLSSLOPT_NATIVE_CA')) {
				$options[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NATIVE_CA;
			}
			curl_setopt_array($ch, $options);
		}
		curl_setopt($ch, CURLOPT_URL, $location);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$response = curl_exec($ch);
		$errno = curl_errno($ch);
		$http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($response !== false && $errno === 0 && $http !== 0) {
			break;
		}
		curl_close($ch);
		$ch = null;
	}
	if ($response === false || $errno !== 0) {
		throw new SoapFault('HTTP', 'Error Fetching http headers');
	}
	if ($oneWay) {
		return '';
	}
	return $response;
}

if (PHP_VERSION_ID >= 80000) {
	class MobitelCurlSoapClient extends SoapClient
	{
		public function __doRequest(string $request, string $location, string $action, int $version, bool $one_way = false): ?string
		{
			return mobitelSoapTransport($request, $location, $action, $version, $one_way);
		}
	}
} else {
	class MobitelCurlSoapClient extends SoapClient
	{
		public function __doRequest($request, $location, $action, $version, $one_way = 0)
		{
			return mobitelSoapTransport($request, $location, $action, $version, $one_way);
		}
	}
}

function mobitelWsdlPath()
{
	$bundled = __DIR__ . DIRECTORY_SEPARATOR . 'mSMSEnterpriseAPI.wsdl';
	if (class_exists('EnvLoader', false)) {
		$configured = trim((string) EnvLoader::get('MOBITEL_SMS_WSDL', ''));
		if ($configured !== '' && !preg_match('#^https?://#i', $configured) && is_file($configured)) {
			return $configured;
		}
	}
	return $bundled;
}

//create soap client
function getClient()
{
	static $extended = false;
	if (!$extended && function_exists('set_time_limit')) {
		@set_time_limit(60);
		$extended = true;
	}
	ini_set("soap.wsdl_cache_enabled", "0");
	$client = new MobitelCurlSoapClient(mobitelWsdlPath(), array(
		'exceptions' => true,
		'cache_wsdl' => WSDL_CACHE_NONE,
		'connection_timeout' => 20,
		'user_agent' => 'curl/7.68.0',
	));

	return $client;
}


//serviceTest
function serviceTest($id,$username,$password,$customer)
{

	$client = getClient();

	$user = new stdClass();
	$user->id = '';
	$user->username = $username;
	$user->password = $password;
	$user->customer = '';

	$serviceTest = new stdClass();
	$serviceTest->arg0 = $user;

	return $client->serviceTest($serviceTest);

}

//create session
function createSession($id,$username,$password,$customer)
{

	$client = getClient();

	$user = new stdClass();
	$user->id = $id;
	$user->username = $username;
	$user->password = $password;
	$user->customer = $customer;

	$createSession = new stdClass();
	$createSession->user = $user;

	$createSessionResponse = new stdClass();
	$createSessionResponse = $client->createSession($createSession);

	return $createSessionResponse->return;

}

//check if session is valid
function isSession($session)
{

	$client = getClient();

	$isSession = new stdClass();
	$isSession->session = $session;

	$isSessionResponse = new stdClass();
	$isSessionResponse = $client->isSession($isSession);

	return $isSessionResponse->return;
}

//send SMS to recipients
function sendMessages($session,$alias,$message,$recipients,$messageType)
{
	$client=getClient();

	$smsMessage= new stdClass();
	$smsMessage->message=$message;
	$smsMessage->messageId="";
	$smsMessage->recipients=$recipients;
	$smsMessage->retries="";
	$smsMessage->sender=$alias;
	$smsMessage->messageType=$messageType;
	$smsMessage->sequenceNum="";
	$smsMessage->status="";
	$smsMessage->time="";
	$smsMessage->type="";
	$smsMessage->user="";

	$sendMessages = new stdClass();
	$sendMessages->session = $session;
	$sendMessages->smsMessage = $smsMessage;

	$sendMessagesResponse = new stdClass();
	$sendMessagesResponse = $client->sendMessages($sendMessages);

	return $sendMessagesResponse->return;
}

//send Unicoded SMS to recipients
function sendMessagesMultiLang($session,$alias,$message,$recipients,$messageType)
{
	$client=getClient();

	$smsMessageMultiLang = new stdClass();
	$smsMessageMultiLang->message=$message;
	$smsMessageMultiLang->messageId="";
	$smsMessageMultiLang->recipients=$recipients;
	$smsMessageMultiLang->retries="";
	$smsMessageMultiLang->sender=$alias;
	$smsMessageMultiLang->messageType=$messageType;
	$smsMessageMultiLang->sequenceNum="";
	$smsMessageMultiLang->status="";
	$smsMessageMultiLang->time="";
	$smsMessageMultiLang->type="";
	$smsMessageMultiLang->user="";

	$sendMessagesMultiLang = new stdClass();
	$sendMessagesMultiLang->session = $session;
	$sendMessagesMultiLang->smsMessageMultiLang = $smsMessageMultiLang;

	$sendMessagesMultiLangResponse = new stdClass();
	$sendMessagesMultiLangResponse = $client->sendMessagesMultiLang($sendMessagesMultiLang);

	return $sendMessagesMultiLangResponse->return;
}

//send Campaign SMS to recipients
function sendCampaignMessages($session,$alias,$message,$recipients,$datetime,$multilanguage,$messageType)
{
	$client=getClient();

	$smsCampaignMessage = new stdClass();
	$smsCampaignMessage->message = $message;
	$smsCampaignMessage->messageId = "";
	$smsCampaignMessage->recipients = $recipients;
	$smsCampaignMessage->retries = "";
	$smsCampaignMessage->sender = $alias;
	$smsCampaignMessage->messageType=$messageType;
	$smsCampaignMessage->sequenceNum = "";
	$smsCampaignMessage->status = "";
	$smsCampaignMessage->time = $datetime;
	$smsCampaignMessage->type = "";
	$smsCampaignMessage->user = "";
	$smsCampaignMessage->esmClass = $multilanguage;
	
	$sendCampaignMessages=new stdClass();
	$sendCampaignMessages->session=$session;
	$sendCampaignMessages->smsCampaignMessage=$smsCampaignMessage;
	

	$sendCampaignMessagesResponse = new stdClass();
	$sendCampaignMessagesResponse = $client->sendCampaignMessages($sendCampaignMessages);

	return $sendCampaignMessagesResponse->return;
}

//renew session 
function renewSession($session)
{

	$client = getClient();

	$renewSession = new stdClass();
	$renewSession->session = $session;

	$renewSessionResponse = new stdClass();
	$renewSessionResponse = $client->renewSession($renewSession);

	return $renewSessionResponse->return;

}


//close session
function closeSession($session)
{

	$client = getClient();

	$closeSession = new stdClass();
	$closeSession->session = $session;

	$client->closeSession($closeSession);

}

//retrieve messages from shortcode
function getMessagesFromShortCode($session,$shortCode)
{

	$client = getClient();

	$getMessagesFromShortCode = new stdClass();
	$getMessagesFromShortCode->session = $session;
	$getMessagesFromShortCode->shortcode = $shortCode;

	$getMessagesFromShortcodeResponse = new stdClass();
	$getMessagesFromShortcodeResponse->return = "";
	$getMessagesFromShortcodeResponse = $client->getMessagesFromShortcode($getMessagesFromShortCode);
	
	if(property_exists($getMessagesFromShortcodeResponse,'return'))
	return $getMessagesFromShortcodeResponse->return;
	
	else return NULL;

}

//retrieve delivery report
function getDeliveryReports($session,$alias)
{

	$client = getClient();

	$getDeliveryReports = new stdClass();
	$getDeliveryReports->session = $session;
	$getDeliveryReports->alias = $alias;

	$getDeliveryReportsResponse = new stdClass();
	$getDeliveryReportsResponse->return = "";
	$getDeliveryReportsResponse = $client->getDeliveryReports($getDeliveryReports);
	
	if(property_exists($getDeliveryReportsResponse,'return'))
	return $getDeliveryReportsResponse->return;
	
	else return NULL;

}

//retrieve messages from longnumber
function getMessagesFromLongNumber($session,$longNumber)
{

	$client = getClient();

	$getMessagesFromLongNumber = new stdClass();
	$getMessagesFromLongNumber->session = $session;
	$getMessagesFromLongNumber->longNumber=$longNumber;

	$getMessagesFromLongNumberResponse = new stdClass();
	$getmessagesFromLongNumberResponse->return = "";
	$getMessagesFromLongNumberResponse = $client->getMessagesFromLongNumber($getMessagesFromLongNumber);
	
	if(property_exists($getMessagesFromLongNumberResponse,'return'))
	return $getMessagesFromLongNumberResponse->return;
	
	else return NULL;
	
}




//==================================ESMS WEB SERVICE END=============================================================



?>
