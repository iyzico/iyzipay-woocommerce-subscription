<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Iyzico_Subscription_For_WooCommerce_Authorization {


	public function generateAuthV2Content($uri, $apiKey, $secretKey, $randomString, $request = null) {

	    $hashStr = "apiKey:" . $apiKey . "&randomKey:" . $randomString ."&signature:" . $this->getHmacSHA256Signature($uri, $secretKey, $randomString, $request);

	    $hashStr = base64_encode($hashStr);

	    $hashStr = "IYZWSv2 ".$hashStr;

	    $authorization_data = array(
			'authorization' => $hashStr,
			'rand_value' 	=> $randomString
		);

	    return $authorization_data;
	 
	}

	private function getHmacSHA256Signature($uri, $secretKey, $randomString, $request = null)
	{
	    $dataToEncrypt = $randomString . $this->getPayload($uri, $request);

	    $hash = hash_hmac('sha256', $dataToEncrypt, $secretKey, true);
	    $token = bin2hex($hash);

	    return $token;
	}

	private function getPayload($uri, $request = null)
	{

	    $startNumber  = strpos($uri, '/v2');
	    $endNumber    = strpos($uri, '?');
	    $endNumber-=  $startNumber;

	    $uriPath      =  substr($uri, $startNumber, $endNumber);

	   	if (!empty($request) && $request != '[]')
	    	$uriPath = $uriPath.$request;

	    return $uriPath;
	}

}