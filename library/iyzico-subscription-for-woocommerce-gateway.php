<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Iyzico_Subscription_For_WooCommerce_Gateway extends WC_Payment_Gateway {

    public function __construct() {

        $this->id = 'iyzico_subscription';
        $this->iyziV = '1.0.3';
        $this->method_title = __('iyzico Subscription Pay', 'woocommerce-iyzico-subscription');
        $this->method_description = __('The iyzico subscription API provides the opportunity to quickly create subscriptions to merchants that can receive online payments.','woocommerce-iyzico-subscription');
        $this->has_fields = true;
        $this->order_button_text = __('Pay With Card', 'woocommerce-iyzico-subscription');

        $this->has_fields = true;
        $this->supports = array('products');

        $this->init_form_fields();
        $this->init_settings();



        $this->title        = __($this->get_option( 'title' ),'woocommerce-iyzico-subscription');
        $this->description  = __($this->get_option( 'description'),'woocommerce-iyzico-subscription');
        $this->enabled      = $this->get_option( 'enabled' );
        $this->icon         = plugins_url().IYZICO_PLUGIN_NAME.'/image/newcards.png';



        add_action('init', array(&$this, 'iyzico_response'));
        add_action('woocommerce_api_wc_gateway_iyzico', array($this, 'iyzico_response'));


        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
            $this,
            'process_admin_options',
        ) );

        add_action('woocommerce_receipt_iyzico_subscription', array($this, 'iyzico_subscription_form'));

        if(isset($_GET['section']) && $_GET['section'] == 'iyzico') {

            $this->valid_js();
        }

    }

    public function admin_options() {
      ob_start();
      parent::admin_options();
      $parent_options = ob_get_contents();
      ob_end_clean();

      echo $parent_options;
      $pluginUrl = plugins_url().IYZICO_PLUGIN_NAME;

      $html = '<style scoped>@media (max-width:768px){.iyziBrand{position:fixed;bottom:0;top:auto!important;right:0!important}}</style><div class="iyziBrandWrap"><div class="iyziBrand" style="clear:both;position:absolute;right: 50px;top:440px;display: flex;flex-direction: column;justify-content: center;"><img src='.$pluginUrl.'/image/iyzico_logo.png style="width: 250px;margin-left: auto;"><p style="text-align:center;"><strong>V: </strong>'.$this->iyziV.'</p></div></div>';

      echo $html;
  }
     public function valid_js() {
       wp_enqueue_script('script', plugins_url().IYZICO_PLUGIN_NAME.'/media/js/valid_api.js',true,'1.0','all');

      }

    public function init_form_fields() {

        $this->form_fields = Iyzico_Subscription_For_WooCommerce_Fields::iyzicoAdminFields();

    }


    private function setcookieSameSite($name, $value, $expire, $path, $domain, $secure, $httponly) {

      if (PHP_VERSION_ID < 70300) {

          setcookie($name, $value, $expire, "$path; samesite=None", $domain, $secure, $httponly);
      }
      else {
          setcookie($name, $value, [
              'expires' => $expire,
              'path' => $path,
              'domain' => $domain,
              'samesite' => 'None',
              'secure' => $secure,
              'httponly' => $httponly,
          ]);


      }
  }

    public function process_payment($order_id) {

        $order = wc_get_order($order_id);

        return array(
          'result'   => 'success',
          'redirect' => $order->get_checkout_payment_url(true)
        );

    }

    public function iyzico_subscription_form($order_id) {

       $wooCommerceCookieKey = 'wp_woocommerce_session_';

       foreach ($_COOKIE as $name => $value) {
         if (stripos($name,$wooCommerceCookieKey) === 0) {
             $wooCommerceCookieKey = $name;
         }
        }
        $setCookie = $this->setcookieSameSite($wooCommerceCookieKey,$_COOKIE[$wooCommerceCookieKey], time() + 86400, "/", $_SERVER['SERVER_NAME'],true, true);

        $this->versionCheck();

        global $woocommerce;

        $getOrder                  = new WC_Order($order_id);
        $customerCart              = $woocommerce->cart->get_cart();

        $apiKey                    = $this->settings['api_key'];
        $secretKey                 = $this->settings['secret_key'];
        $baseUrl                   = $this->settings['api_type'];
        $rand                      = uniqid();


        $formObjectGenerate        = new Iyzico_Subscription_For_WooCommerce_FormObjectGenerate();
        $iyzicoRequest             = new Iyzico_Subscription_For_WooCommerce_Request();
        $hashV2Builder             = new Iyzico_Subscription_For_WooCommerce_Authorization();

        /* V2 */
        $v2Request          = new stdClass();
        $v2Request->locale  = 'tr';

        $subsUrl =  $baseUrl."/v2/subscription/checkoutform/initialize?";

        $iyzico                            = $formObjectGenerate->subscripotionObjectGenerate($getOrder,$customerCart);
        $iyzico->customer                  = $formObjectGenerate->subscriptionCustomerObjectGenerate($getOrder);
        $iyzico->customer->billingAddress  = $formObjectGenerate->subscriptionBillingAddressGenerate($getOrder);
        $iyzicoJson                        = json_encode($iyzico,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $authorizationDataV2               = $hashV2Builder->generateAuthV2Content($subsUrl,$apiKey,$secretKey,$rand,$iyzicoJson);

        $requestResponse                   = $iyzicoRequest->iyzicoSubscriptionRequest($subsUrl,$iyzicoJson,$authorizationDataV2);


        $className                = $this->settings['form_class'];

        if ($className == 'popup'){
          $message                  =  __("Thank you for your order, please click the button below to pay with iyzico checkout.", 'woocommerce-iyzico-subscription') . '</p>';
         }

        else{
          $message                  =   __('Thank you for your order, please enter your card information in the payment form below to pay with iyzico checkout.', 'woocommerce-iyzico-subscription') . '</p>';
         }



        echo '<script>jQuery(window).on("load", function(){document.getElementById("loadingBar").style.display="none",document.getElementById("infoBox").style.display="block",document.getElementById("iyzipay-checkout-form").style.display="block"});</script>';


        if(isset($requestResponse->status)) {
            if($requestResponse->status == 'success') {
                echo $message;

                echo '<div id="iyzipay-checkout-form" class='.$className.'>' . $requestResponse->checkoutFormContent . '</div>';
                echo '<p style="display:none;" id="iyziVersion">'.$this->iyziV.'</p>';
            } else {
                echo $requestResponse->errorMessage;
            }

        } else {
            echo 'Not Connection...';
        }
        echo $requestResponse->tokenExpireTime;

    }

    public function iyzico_response($order_id) {

        global $woocommerce;

        $token           = $_POST['token'];

        $conversationId  = "";
        $apiKey          = $this->settings['api_key'];
        $secretKey       = $this->settings['secret_key'];
        $baseUrl         = $this->settings['api_type'];
        $user            = wp_get_current_user();
        $rand            = rand(1,99999);

        if(!$token) {
            $entityBody = file_get_contents("php://input");
            $subscriptionNotification = $this->notificationListener($entityBody,$order_id);
        }

        $formObjectGenerate        = new Iyzico_Subscription_For_WooCommerce_FormObjectGenerate();
        $pkiBuilder                = new Iyzico_Subscription_For_WooCommerce_PkiBuilder();
        $iyzicoRequest             = new Iyzico_Subscription_For_WooCommerce_Request();
        $hashV2Builder             = new Iyzico_Subscription_For_WooCommerce_Authorization();


        $tokenDetailObject         = $formObjectGenerate->generateTokenDetailObject($conversationId,$token);
        $pkiString                 = $pkiBuilder->pkiStringGenerate($tokenDetailObject);
        $authorizationData         = $pkiBuilder->authorizationGenerate($pkiString,$apiKey,$secretKey,$rand);
        $tokenDetailObject         = json_encode($tokenDetailObject,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $requestResponse           = $iyzicoRequest->iyzicoCheckoutFormDetailRequest($baseUrl,$tokenDetailObject,$authorizationData);




        if($requestResponse->paymentStatus != 'SUCCESS' || $requestResponse->status != 'success') {
          if($requestResponse->status == 'success' && $requestResponse->paymentStatus == 'FAILURE') {
                throw new Exception('Kartınız için 3D  güvenliği onaylanmamıştır.');

            }
            /* Redirect Error */
            $errorMessage = isset($requestResponse->errorMessage) ? $requestResponse->errorMessage : 'Failed';
            throw new \Exception($errorMessage);
        }

        $paymentId = (int) $requestResponse->paymentId;
        $referenceCode = esc_sql($requestResponseSubs->data->referenceCode);
        $orderAwaitingPaymentId = (int) $woocommerce->session->order_awaiting_payment;
        $userId = (int) $user->ID;

        $order = new WC_Order($woocommerce->session->order_awaiting_payment);

        if($baseUrl == 'https://sandbox-api.iyzipay.com') {

            $orderMessage = '<strong><p style="color:red">TEST SUBSCRIPTION STARTED</a></strong>';
            $order->add_order_note($orderMessage,0,true);
        } else {

            $orderMessage = '<strong><p style="color:red">SUBSCRIPTION STARTED</a></strong>';
            $order->add_order_note($orderMessage,0,true);

        }

        $order->payment_complete();

            /* Order Status */
        $orderStatus = $this->get_option('order_status');
        if($orderStatus != 'default' && !empty($orderStatus)) {
              $order->update_status($orderStatus);
          }


        $orderMessage = 'Payment ID: '.$paymentId;
        $order->add_order_note($orderMessage,0,true);
        $currency = get_option('woocommerce_currency');

        $orderMessage = __("Amount Withdrawn", 'woocommerce-iyzico-subscription').'<b> : '.$requestResponse->price.' '.$currency.'</b>';
        $order->add_order_note($orderMessage,0,true);





        $subsRetrieveUrl           = $baseUrl."/v2/subscription/checkoutform/".$token."?=locale=tr";
        $authorizationDataV2       = $hashV2Builder->generateAuthV2Content($subsRetrieveUrl,$apiKey,$secretKey,$rand,null);
        $requestResponseSubs       = $iyzicoRequest->iyzicoSubscriptionRetrieveRequest($subsRetrieveUrl,$tokenDetailObject,$authorizationDataV2);

        $subscriptionIyziModel       = new Iyzico_Subscription_For_WooCommerce_Model();

        $addSubsObject = new stdClass();
        $addSubsObject->referenceCode = $referenceCode;
        $addSubsObject->orderId = $orderAwaitingPaymentId;
        $addSubsObject->userId = $userId;

        $addSubscription  = $subscriptionIyziModel->addSubscription($addSubsObject);

        $order->payment_complete();

        $order->update_status('processing');

        $woocommerce->cart->empty_cart();

        $checkoutOrderUrl = $order->get_checkout_order_received_url();

        $redirectUrl = add_query_arg(array('msg' => 'Thank You', 'type' => 'woocommerce-message'), $checkoutOrderUrl);

        wp_redirect($redirectUrl);

    }

    private function notificationListener($entityBody,$order_id) {

        $entityBody = json_decode($entityBody);
        $subscriptionReferenceCode = esc_sql($entityBody->subscriptionReferenceCode);

        $subscriptionIyziModel  = new Iyzico_Subscription_For_WooCommerce_Model();

        $findSubscription = $subscriptionIyziModel->findSubscription($subscriptionReferenceCode);

        if($findSubscription->subscription_reference_code == $subscriptionReferenceCode) {



                echo 'SUCCESS';
                exit;
        }

            echo 'FAILURE';
            exit;
    }

    public static function pricingPlanMethod() {

        global $post;
        $postId = (int) $post->ID;
        $pricingPlanCode        = 'pricing_plan_code_'.$postId;
        $pricingPlanCodeOption  = get_option($pricingPlanCode);
        if(get_locale() == 'tr_TR') {
        echo '<h1 style="margin-left:10px;">iyzico Abonelik</h1>';
        ?>
            <div class="options_group pricing show_if_simple show_if_external hidden" style="display: block;">
                <p class="form-field _regular_price_field ">

                    <label for="_regular_price">Pricing Plan Code: </label>
                    <input type="text" class="short wc_input_text" name="pricingPlanReferenceCode" value="<?php echo $pricingPlanCodeOption; ?>" />
                <p><a href ="https://merchant.iyzipay.com">https://merchant.iyzipay.com </a> veya <a href ="https://sandbox-merchant.iyzipay.com">https://sandbox-merchant.iyzipay.com</a> adresi üzerinden müşteri bilgileriniz ile giriş yapınız. Panele eriştiğiniz sırada sağ tarafta bulunan  “Abonelikler” menüsüne tıklayınız. Bu alanda yeni abonelik oluşturabilir veya eski abonelik işlemine devam edebilirsiniz. Ürün ve ödeme planı oluşturduktan sonra referans kodunu buraya yazınız.</p>
                <p>* Ürün fiyat , para birimi , çekilecek tutarın oluşturulan ödeme planını karşıladığına dikkat ediniz. </p>

            </div>

        <?php
      }else {
        echo '<h1 style="margin-left:10px;">iyzico Subscription</h1>';
        ?>
            <div class="options_group pricing show_if_simple show_if_external hidden" style="display: block;">
                <p class="form-field _regular_price_field ">

                    <label for="_regular_price">Pricing Plan Code: </label>
                    <input type="text" class="short wc_input_text" name="pricingPlanReferenceCode" value="<?php echo $pricingPlanCodeOption; ?>" />
                <p><a href ="https://merchant.iyzipay.com">https://merchant.iyzipay.com </a> or<a href ="https://sandbox-merchant.iyzipay.com">https://sandbox-merchant.iyzipay.com</a> Log in with your customer information. When you access the panel, click on the “Subscriptions” menu on the right. In this area, you can create a new subscription or continue the old subscription process. After creating the product and payment plan, write the reference code here.</p>

            </div>

        <?php
      }
    }

    public static function pricingPlanMethodSave() {

        global $post;

        $postId = (int) $post->ID;

        if(!empty($_POST['pricingPlanReferenceCode'])) {
            $pricingPlanReferenceCode = $_POST['pricingPlanReferenceCode'];
        } else {
            $pricingPlanReferenceCode = false;
        }

        $createOrUpdateControl = false;


        $pricingPlanReferenceCode = esc_sql($pricingPlanReferenceCode);

        $pricingPlanReferenceCodeField = 'pricing_plan_code_'.$postId;
        $pricingPlanReferenceCodeOption = get_option($pricingPlanReferenceCodeField);

        /* Empty Post  Control */
        if(empty($pricingPlanReferenceCode)) {

            delete_option($pricingPlanReferenceCodeField);
            return;
        }

        /* Sleeping Data Control */
        if($pricingPlanReferenceCode == $pricingPlanReferenceCodeOption) {

            return;
        }

        if(!empty($pricingPlanReferenceCodeOption)) {

            $createOrUpdateControl = true;
        }

        if(empty($createOrUpdateControl)) {

            add_option($pricingPlanReferenceCodeField,$pricingPlanReferenceCode,'','no');

        } else {

            update_option($pricingPlanReferenceCodeField,$pricingPlanReferenceCode);
        }

        return;

    }


    private function versionCheck() {

      $phpVersion = phpversion();
      $requiredPhpVersion = 5.4;
      $helper = new Iyzico_Subscription_For_WooCommerce_Helper();
      $locale = $helper->cutLocale(get_locale());

      /* Required PHP */
      $warningMessage = 'Required PHP 5.4 and greater for iyzico WooCommerce Payment Gateway';
      if($locale == 'tr') {
          $warningMessage = 'iyzico WooCommerce eklentisini çalıştırabilmek için, PHP 5.4 veya üzeri versiyonları kullanmanız gerekmektedir. ';
      }

      if($phpVersion < $requiredPhpVersion) {
          echo $warningMessage;
          exit;
      }

      /* Required WOOCOMMERCE */
      $wooCommerceVersion = WOOCOMMERCE_VERSION;
      $requiredWoocommerceVersion = 3.0;

      $warningMessage = 'Required WooCommerce 3.0 and greater for iyzico WooCommerce Payment Gateway';

      if($locale == 'tr') {
          $warningMessage = 'iyzico WooCommerce eklentisini çalıştırabilmek için, WooCommerce 3.0 veya üzeri versiyonları kullanmanız gerekmektedir. ';
      }

      if($wooCommerceVersion < $requiredWoocommerceVersion) {
          echo $warningMessage;
          exit;
      }

      /* Required TLS */
      $tlsUrl = 'https://api.iyzipay.com';
      $tlsVersion = get_option('iyziTLS');

      if(!$tlsVersion) {

          $result = $this->verifyTLS($tlsUrl);
          if($result) {
              add_option('iyziTLS',1.2,'','no');
              $tlsVersion = get_option('iyziTLS');
          }

      } elseif($tlsVersion != 1.2) {

          $result = $this->verifyTLS($tlsUrl);
          if($result) {
              update_option('iyziTLS',1.2);
              $tlsVersion = get_option('iyziTLS');
          }
      }


      $requiredTlsVersion = 1.2;

      $warningMessage = 'WARNING! Minimum TLS v1.2 will be supported after March 2018. Please upgrade your openssl version to minimum 1.0.1.';

      if($locale == 'tr') {
          $warningMessage = "UYARI! Ödeme formunuzu görüntüleyebilmeniz için, TLS versiyonunuzun minimum TLS v1.2 olması gerekmektedir. Lütfen servis sağlayıcınız ile görüşerek openssl versiyonunuzu minimum 1.0.1'e yükseltin.";
      }

      if($tlsVersion < $requiredTlsVersion) {
          echo $warningMessage;
          exit;
      }
  }

  private function verifyTLS($url) {

      $curl = curl_init();

      curl_setopt_array($curl, array(
          CURLOPT_RETURNTRANSFER => 1,
          CURLOPT_URL => $url,
      ));

      $response = curl_exec($curl);

      curl_close($curl);

      return $response;
  }

}
