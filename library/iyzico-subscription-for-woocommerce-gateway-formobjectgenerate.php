<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Iyzico_Subscription_For_WooCommerce_FormObjectGenerate {

	public function __construct() {

		$this->helper = new Iyzico_Subscription_For_WooCommerce_Helper();
	}


	public function subscripotionObjectGenerate($orderData,$customerCart) {

		$product_id = 0;
		$customerCartCheck = count($customerCart);
		/* Quantity Check */

		if($customerCartCheck == 1) {
			foreach ($customerCart as $key => $cart) {

				if($cart['quantity'] > 1) {
					echo 'Birden fazla ürün ile abonelik başlatamazsınız.';
					exit;
				}
				$product_id = $cart['product_id'];
			}
		} else {

			echo 'Birden fazla ürün ile abonelik başlatamazsınız.';
			exit;
		}

		$pricingPlanCode        = 'pricing_plan_code_'.$product_id;
        $pricingPlanCodeOption  = get_option($pricingPlanCode);


		$subscriptionObject = new stdClass();
		$subscriptionObject->callbackUrl 					= add_query_arg('wc-api', 'WC_Gateway_Iyzico', $orderData->get_checkout_order_received_url());
		$subscriptionObject->locale 						= $this->helper->cutLocale(get_locale());
		$subscriptionObject->conversationId 				= $orderData->get_id();
		$subscriptionObject->subscriptionInitialStatus		= "ACTIVE";
		$subscriptionObject->pricingPlanReferenceCode 		= $pricingPlanCodeOption;

		return $subscriptionObject;

	}


	public function subscriptionCustomerObjectGenerate($orderData) {

		$subscriptionCustomerObject = new stdClass();

		$subscriptionCustomerObject->name 			= $this->helper->dataCheck($orderData->get_billing_first_name());
		$subscriptionCustomerObject->surname 		= $this->helper->dataCheck($orderData->get_billing_last_name());
		$subscriptionCustomerObject->email 			= $this->helper->dataCheck($orderData->get_billing_email());
		$subscriptionCustomerObject->gsmNumber 		= "+9".$this->helper->dataCheck($orderData->get_billing_phone());
		$subscriptionCustomerObject->identityNumber = "11111111111";

		return $subscriptionCustomerObject;
	}


	public function subscriptionBillingAddressGenerate($orderData) {

		$subscriptionBilingAddressObject = new stdClass();

		$address = $this->helper->trimString($orderData->get_billing_address_1(),$orderData->get_billing_address_2());

		$subscriptionBilingAddressObject->address 		= $this->helper->dataCheck($address);
		$subscriptionBilingAddressObject->zipCode 		= $this->helper->dataCheck($orderData->get_billing_postcode());
 		$subscriptionBilingAddressObject->contactName   = $this->helper->dataCheck($orderData->get_billing_first_name().$orderData->get_billing_last_name());
		$subscriptionBilingAddressObject->city          = $this->helper->dataCheck(WC()->countries->states[$orderData->get_billing_country()][$orderData->get_billing_state()]);
		$subscriptionBilingAddressObject->country       = $this->helper->dataCheck(WC()->countries->countries[$orderData->get_billing_country()]);

		return $subscriptionBilingAddressObject;
	}

	public function generateTokenDetailObject($conversationId,$token) {

		$tokenDetail = new stdClass();

		$tokenDetail->locale 			= $this->helper->cutLocale(get_locale());
		$tokenDetail->conversationId 	= $conversationId;
		$tokenDetail->token 			= $token;

		return $tokenDetail;

	}
}
