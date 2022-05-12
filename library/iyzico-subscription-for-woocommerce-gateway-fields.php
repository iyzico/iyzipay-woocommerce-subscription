<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Iyzico_Subscription_For_WooCommerce_Fields {

	public static function iyzicoAdminFields() {
		//dil ayarları gözden geçirilecek

		return $form_fields = array(
			'callback' => array(
					'description' => __('<b>*</b> Do not forget to add the reference code to the Product > Edit > Pricing Plan Code section.' ,'woocommerce-iyzico-subscription' ),
					'type' => 'title'
			),
			 'api_type' => array(
		        'title' 	=> __('Api Type', 'woocommerce-iyzico-subscription'),
		        'type' 		=> 'select',
		        'required'  => true,
		        'default' 	=> 'popup',
		        'options' 	=>
		        	array(
		        	 'https://api.iyzipay.com'    => __('Live', 'woocommerce-iyzico-subscription'),
		        	 'https://sandbox-api.iyzipay.com' => __('Sandbox / Test', 'woocommerce-iyzico-subscription')
		     )),
		     'api_key' => array(
		         'title' => __('Api Key', 'woocommerce-iyzico-subscription'),
		         'type' => 'text'
		     ),
		     'secret_key' => array(
		         'title' => __('Secret Key', 'woocommerce-iyzico-subscription'),
		         'type' => 'text'
		     ),
		    'title' => array(
		        'title' => __('Payment Value', 'woocommerce-iyzico-subscription'),
		        'type' => 'text',
		        'description' => __('This message will show to the user during checkout.', 'woocommerce-iyzico-subscription'),
		        'default' => __('Online Payment - iyzico Subscription', 'woocommerce-iyzico-subscription')
		    ),
		    'description' => array(
		        'title' => __('Payment Form Description Value', 'woocommerce-iyzico-subscription'),
		        'type' => 'text',
		        'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-iyzico-subscription'),
		        'default' => __('Pay with your credit card via iyzico.', 'woocommerce-iyzico-subscription'),
		        'desc_tip' => true,
		    ),
				'form_class' => array(
					 'title' => __('Payment Form Design', 'woocommerce-iyzico-subscription'),
					 'type' => 'select',
					 'default' => 'popup',
					 'options' => array('responsive' => __('Responsive', 'woocommerce-iyzico-subscription'), 'popup' => __('Popup', 'woocommerce-iyzico-subscription'))
			 ),

			 'order_status' => array(
						'title' => __('Order Status', 'woocommerce-iyzico-subscription'),
						'type' => 'select',
						'description' => __('Recommended, Default', 'woocommerce-iyzico-subscription'),
						'default' => 'default',
						'options' => array('default' => __('Default', 'woocommerce-iyzico-subscription'),
										 'pending' => __('Pending', 'woocommerce-iyzico-subscription'),
										 'processing' => __('Processing', 'woocommerce-iyzico-subscription'),
										 'on-hold' => __('On-Hold', 'woocommerce-iyzico-subscription'),
										 'completed' => __('Completed', 'woocommerce-iyzico-subscription'),
										 'cancelled' => __('Cancelled', 'woocommerce-iyzico-subscription'),
										 'refunded' => __('Refunded', 'woocommerce-iyzico-subscription'),
										 'failed' => __('Failed', 'woocommerce-iyzico-subscription'))
			 ),

		    'enabled' => array(
		        'title' => __('Enable/Disable', 'woocommerce-iyzico-subscription'),
		        'label' => __('Enable iyzico checkout', 'woocommerce-iyzico-subscription'),
		        'type' => 'checkbox',
		        'default' => 'yes'
		    ),
		);
	}
}
