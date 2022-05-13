<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Iyzico_Subscription_For_WooCommerce_Model {

	public function __construct() {
		
		$this->database = $GLOBALS['wpdb'];
	}


	public function addSubscription($subscription) {

		$addSubscription = $this->database->insert( 
			$this->database->prefix.'subscription_iyzico', 
			array( 
				'subscription_reference_code' 	=> $subscription->referenceCode, 
				'order_id' 		=> $subscription->orderId, 
				'user_id' 		=> $subscription->userId, 
			), 
			array( 
				'%s', 
				'%d',
				'%d'
			)
		);

		return $addSubscription;

	}


	public function findSubscription($subscriptionReferenceCode) {

	$tableName = $this->database->prefix .'subscription_iyzico';
 
	$query = $this->database->prepare("
			 	SELECT * FROM {$tableName} 
			 	WHERE subscription_reference_code = %s 
			 	ORDER BY subscription_iyzico_id DESC LIMIT 1;
				",$subscriptionReferenceCode
			);

	$result = $this->database->get_row($query);


		if(isset($result->subscription_iyzico_id)) {

			return $result;

		} else {

			return '';
		}

	}



}