<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Iyzico_Subscription_For_WooCommerce_Request {

	
	public function iyzicoCheckoutFormRequest($baseUrl,$json,$authorizationData) {

			$url = $baseUrl.'/payment/iyzipos/checkoutform/initialize/auth/ecom';
				 
		    return $this->curlPost($json,$authorizationData,$url);

	}

	public function iyzicoSubscriptionRequest($baseUrl,$json,$authorizationData) {

			$url = $baseUrl;
				 
		    return $this->curlPostV2($json,$authorizationData,$url);

	}

	public function iyzicoSubscriptionRetrieveRequest($baseUrl,$json,$authorizationData) {

			$url = $baseUrl;
				 
		    return $this->curlGetV2($json,$authorizationData,$url);

	}

	public function iyzicoCheckoutFormDetailRequest($baseUrl,$json,$authorizationData) {

			$url = $baseUrl.'/payment/iyzipos/checkoutform/auth/ecom/detail';
				 
		    return $this->curlPost($json,$authorizationData,$url);

	}


	public function iyzicoOverlayScriptRequest($baseUrl,$json,$authorizationData) {

			$baseUrl   = "https://iyziup.iyzipay.com/";
			$url   	   = $baseUrl."v1/iyziup/protected/shop/detail/overlay-script";


		    return $this->curlPost($json,$authorizationData,$url);

	}

	public function iyzicoCargoTracking($baseUrl,$json,$authorizationData) {

			$url = $baseUrl.'/v1/iyziup/create-zen-order-shipment-over-plugin-framework';

		    return $this->curlPost($json,$authorizationData,$url);
	}

	public function curlPost($json,$authorizationData,$url) {

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		$content_length = 0;
		if ($json) {
		    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		    curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		curl_setopt($curl, CURLOPT_TIMEOUT, 150);
		
		curl_setopt(        
		    $curl, CURLOPT_HTTPHEADER, array(
		        "Authorization: " .$authorizationData['authorization'],
		        "x-iyzi-rnd:".$authorizationData['rand_value'], 
		        "Content-Type: application/json",
		    )
		);

		$result = json_decode(curl_exec($curl));
		curl_close($curl);

		

		return $result;
	}

	public function curlPostV2($json,$authorizationData,$url) {

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		if ($json) {
		    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		    curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		curl_setopt($curl, CURLOPT_TIMEOUT, 150);
		
		curl_setopt(        
		    $curl, CURLOPT_HTTPHEADER, array(
		        "Content-Type: application/json",
		        "Authorization: " .$authorizationData['authorization'],
		    )
		);

		$result = json_decode(curl_exec($curl));
		curl_close($curl);

		

		return $result;
	}

	public function curlGetV2($json,$authorizationData,$url) {

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		curl_setopt($curl, CURLOPT_TIMEOUT, 150);
		
		curl_setopt(        
		    $curl, CURLOPT_HTTPHEADER, array(
		        "Content-Type: application/json",
		        "Authorization: " .$authorizationData['authorization'],
		    )
		);

		$result = json_decode(curl_exec($curl));
		curl_close($curl);

		return $result;
	}


}