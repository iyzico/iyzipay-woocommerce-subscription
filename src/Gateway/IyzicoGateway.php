<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Gateway;

defined('ABSPATH') || exit;

use WC_Payment_Gateway;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\Buyer;
use Iyzipay\Model\CheckoutFormInitialize;
use Iyzipay\Model\Locale;
use Iyzipay\Model\PaymentGroup;
use Iyzipay\Options;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;
use Iyzico\IyzipayWoocommerceSubscription\Models\SavedCardRepository;
use Iyzico\IyzipayWoocommerceSubscription\Models\SubscriptionFactory;

class IyzicoGateway extends WC_Payment_Gateway {
    public $api_key;
    public $secret_key;
    public $sandbox;
    private SavedCardRepository $savedCardRepository;

    public function __construct() {
        $this->id = 'iyzico_subscription';
        $this->icon = plugin_dir_url(__FILE__) . '../../assets/img/cards/mastercard-visa-amex.svg';
        $this->has_fields = true;
        $this->method_title = __('iyzico Abonelik', 'iyzipay-woocommerce-subscription');
        $this->method_description = __('iyzico ile düzenli (abonelik) ödeme tahsilatı', 'iyzipay-woocommerce-subscription');

        $this->supports = [
            'products',
        ];

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->api_key = $this->get_option('api_key');
        $this->secret_key = $this->get_option('secret_key');
        $this->sandbox = $this->get_option('sandbox');

        $this->savedCardRepository = new SavedCardRepository();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

        add_action('woocommerce_api_iyzico_subscription', [$this, 'handle_api_request']);
        add_action('woocommerce_api_iyzico-subscription', [$this, 'handle_api_request']);

        add_action('woocommerce_before_checkout_form', [$this, 'display_checkout_errors']);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_checkout_notice_script']);
    }

    public function enqueue_checkout_notice_script(): void {
        if (!function_exists('is_checkout') || !is_checkout()) {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (empty($_GET['iyzico_error'])) {
            return;
        }

        $script_url = plugin_dir_url(__FILE__) . '../../assets/js/frontend/checkout-notices.js';
        wp_enqueue_script(
            'iyzico-subscription-checkout-notices',
            $script_url,
            ['wp-i18n', 'wp-dom-ready'],
            '1.1.0',
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations(
                'iyzico-subscription-checkout-notices',
                'iyzipay-woocommerce-subscription',
                plugin_dir_path(__FILE__) . '../../languages'
            );
        }
    }

    public function is_available() {
        if ('yes' !== $this->enabled) {
            return false;
        }
        if (is_admin()) {
            return true;
        }
        if (empty($this->api_key) || empty($this->secret_key)) {
            return false;
        }
        if (function_exists('WC') && WC()->cart) {
            foreach (WC()->cart->get_cart() as $cart_item) {
                $product = isset($cart_item['data']) ? $cart_item['data'] : null;
                if ($product && in_array($product->get_type(), ['subscription', 'variable-subscription'], true)) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title' => __('Aktif/Pasif', 'iyzipay-woocommerce-subscription'),
                'type' => 'checkbox',
                'label' => __('iyzico ödeme geçidini aktifleştir', 'iyzipay-woocommerce-subscription'),
                'default' => 'no',
            ],
            'title' => [
                'title' => __('Başlık', 'iyzipay-woocommerce-subscription'),
                'type' => 'text',
                'description' => __('Ödeme sayfasında görünecek başlık', 'iyzipay-woocommerce-subscription'),
                'default' => __('Kredi Kartı ile Güvenli Ödeme', 'iyzipay-woocommerce-subscription'),
                'desc_tip' => true,
            ],
            'description' => [
                'title' => __('Açıklama', 'iyzipay-woocommerce-subscription'),
                'type' => 'textarea',
                'description' => __('Ödeme sayfasında görünecek açıklama', 'iyzipay-woocommerce-subscription'),
                'default' => __('iyzico altyapısı ile kart bilgileriniz güvende. Visa, Mastercard ve Amex ile hızlı ve güvenli ödeme yapın.', 'iyzipay-woocommerce-subscription'),
            ],
            'api_key' => [
                'title' => __('API Anahtarı', 'iyzipay-woocommerce-subscription'),
                'type' => 'text',
                'description' => __('iyzico API anahtarınız', 'iyzipay-woocommerce-subscription'),
            ],
            'secret_key' => [
                'title' => __('Gizli Anahtar', 'iyzipay-woocommerce-subscription'),
                'type' => 'password',
                'description' => __('iyzico gizli anahtarınız', 'iyzipay-woocommerce-subscription'),
            ],
            'sandbox' => [
                'title' => __('Test Modu', 'iyzipay-woocommerce-subscription'),
                'type' => 'checkbox',
                'label' => __('Test modunu aktifleştir', 'iyzipay-woocommerce-subscription'),
                'default' => 'yes',
            ],
        ];
    }

    public function handle_api_request() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_GET['wc-api']) || $_GET['wc-api'] !== 'iyzico_subscription') {
            return;
        }

        try {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $token = isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '';

            if (empty($token)) {
                wc_add_notice(__('Token bulunamadı.', 'iyzipay-woocommerce-subscription'), 'error');
                wp_safe_redirect(wc_get_checkout_url());
                exit;
            }

            $options = new Options();
            $options->setApiKey($this->api_key);
            $options->setSecretKey($this->secret_key);
            $options->setBaseUrl($this->sandbox === 'yes' ? 'https://sandbox-api.iyzipay.com' : 'https://api.iyzipay.com');

            $request = new \Iyzipay\Request\RetrieveCheckoutFormRequest();
            $request->setLocale(\Iyzipay\Model\Locale::TR);
            $request->setConversationId($token);
            $request->setToken($token);

            $checkoutForm = \Iyzipay\Model\CheckoutForm::retrieve($request, $options);

            if ($checkoutForm->getStatus() === 'success' && $checkoutForm->getPaymentStatus() === 'SUCCESS') {
                $basket_id = $checkoutForm->getBasketId();
                $order_id = explode('_', $basket_id)[0];
                $order = wc_get_order($order_id);

                if (!$order) {
                    wc_add_notice(__('Sipariş bulunamadı.', 'iyzipay-woocommerce-subscription'), 'error');
                    wp_safe_redirect(wc_get_checkout_url());
                    exit;
                }

                $card_token = method_exists($checkoutForm, 'getCardToken') ? $checkoutForm->getCardToken() : null;
                $card_user_key = method_exists($checkoutForm, 'getCardUserKey') ? $checkoutForm->getCardUserKey() : null;
                
                if (!$card_token || !$card_user_key) {
                    $cancel_result = $this->cancelIyzicoPayment(
                        $order,
                        (string) $checkoutForm->getPaymentId(),
                        'other',
                        'card_token_missing'
                    );

                    if ($cancel_result['success']) {
                        $order->update_status('cancelled', __('Kart bilgileri alınamadığı için ödeme iptal edildi.', 'iyzipay-woocommerce-subscription'));

                        $checkout_url = add_query_arg([
                            'iyzico_error' => 'card_info_missing',
                            'order_id' => $order->get_id(),
                        ], wc_get_checkout_url());
                    } else {
                        $order->add_order_note(sprintf(
                            /* translators: 1: error code, 2: error message */
                            __('Ödeme iptal edilemedi. Hata Kodu: %1$s, Hata: %2$s', 'iyzipay-woocommerce-subscription'),
                            $cancel_result['code'] ?? '',
                            $cancel_result['error'] ?? ''
                        ));

                        $checkout_url = add_query_arg([
                            'iyzico_error' => 'cancel_failed',
                            'error_code' => $cancel_result['code'] ?? '',
                            'error_message' => rawurlencode((string) ($cancel_result['error'] ?? '')),
                            'order_id' => $order->get_id(),
                        ], wc_get_checkout_url());
                    }

                    wp_safe_redirect($checkout_url);
                    exit;
                }

                if ($card_user_key || $card_token) {
                    $this->persistSavedCard((int) $order->get_customer_id(), (string) $card_user_key, (string) $card_token);
                }

                $customer_id = (int) $order->get_customer_id();
                if ($customer_id) {
                    $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
                    if (!empty($remote_ip)) {
                        update_user_meta($customer_id, '_iyzico_last_ip', $remote_ip);
                    }
                    $order_identity = (string) $order->get_meta('_iyzico_identity_number');
                    if (!empty($order_identity)) {
                        update_user_meta($customer_id, '_iyzico_identity_number', $order_identity);
                    }
                }

                $payment_id = $checkoutForm->getPaymentId();
                $subscription_failed = false;
                $subscription_error = '';

                try {
                    $this->create_subscription($order);
                } catch (\Throwable $sub_e) {
                    $subscription_failed = true;
                    $subscription_error = $sub_e->getMessage();
                    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                        error_log('iyzipay-woocommerce-subscription create_subscription error: ' . $subscription_error);
                    }
                }

                if ($subscription_failed) {
                    $cancel_result = $this->cancelIyzicoPayment($order, $payment_id, 'other', 'subscription_create_failed: ' . $subscription_error);

                    $order->add_order_note(sprintf(
                        /* translators: %s: error message */
                        __('Abonelik kaydı oluşturulamadığı için ödeme iptal işlemi başlatıldı. Hata: %s', 'iyzipay-woocommerce-subscription'),
                        $subscription_error
                    ));

                    $customer_id = (int) $order->get_customer_id();
                    if ($customer_id) {
                        $this->purgeCustomerCardCredentials($customer_id);
                    }

                    if ($cancel_result['success']) {
                        $order->update_status('cancelled', __('Abonelik oluşturulamadı; ödeme iyzico tarafında iptal edildi.', 'iyzipay-woocommerce-subscription'));
                    } else {
                        $order->update_status('on-hold', __('Abonelik oluşturulamadı ve ödeme iptal edilemedi. Manuel inceleme gerekiyor.', 'iyzipay-woocommerce-subscription'));
                        $order->add_order_note(sprintf(
                            /* translators: %s: error message */
                            __('Otomatik iptal başarısız: %s', 'iyzipay-woocommerce-subscription'),
                            $cancel_result['error'] ?? __('Bilinmeyen hata', 'iyzipay-woocommerce-subscription')
                        ));
                    }

                    $checkout_url = add_query_arg([
                        'iyzico_error' => 'subscription_create_failed',
                        'order_id' => $order->get_id(),
                    ], wc_get_checkout_url());

                    wp_safe_redirect($checkout_url);
                    exit;
                }

                $order->payment_complete($payment_id);
                $order->add_order_note(sprintf(
                    /* translators: 1: payment id */
                    __('iyzico ödemesi tamamlandı. Ödeme ID: %1$s', 'iyzipay-woocommerce-subscription'),
                    $payment_id
                ));

                $order->update_status('completed', __('Ödeme başarıyla tamamlandı ve abonelik başlatıldı.', 'iyzipay-woocommerce-subscription'));

                wp_safe_redirect($this->get_return_url($order));
                exit;
            } else {
                $checkout_url = add_query_arg([
                    'iyzico_error' => 'payment_failed'
                ], wc_get_checkout_url());
                
                wp_safe_redirect($checkout_url);
                exit;
            }
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('iyzipay-woocommerce-subscription handle_api_request exception: ' . $e->getMessage());
            }

            if (isset($checkoutForm) && method_exists($checkoutForm, 'getPaymentId') && !empty($checkoutForm->getPaymentId()) && isset($order) && $order instanceof \WC_Order) {
                $this->cancelIyzicoPayment($order, (string) $checkoutForm->getPaymentId(), 'other', 'handle_api_request_exception: ' . $e->getMessage());
                $order->update_status('cancelled', __('İşlem sırasında oluşan bir hata nedeniyle ödeme iptal edildi.', 'iyzipay-woocommerce-subscription'));
            }

            $checkout_url = add_query_arg([
                'iyzico_error' => 'general_error',
                'error_message' => rawurlencode($e->getMessage()),
            ], wc_get_checkout_url());

            wp_safe_redirect($checkout_url);
            exit;
        }
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        $options = $this->buildIyzipayOptions();
        list($basketItems, $total_amount) = $this->buildBasketItemsAndTotal($order);
        $request = $this->buildCheckoutInitializeRequest($order, $basketItems, $total_amount, true);

        $checkoutFormInitialize = CheckoutFormInitialize::create($request, $options);

        if ($checkoutFormInitialize->getStatus() === 'success') {
            return array(
                'result' => 'success',
                'redirect' => $checkoutFormInitialize->getPaymentPageUrl()
            );
        }

        $errorMessage = method_exists($checkoutFormInitialize, 'getErrorMessage') ? $checkoutFormInitialize->getErrorMessage() : __('Ödeme başlatılamadı.', 'iyzipay-woocommerce-subscription');
        $errorCode = method_exists($checkoutFormInitialize, 'getErrorCode') ? $checkoutFormInitialize->getErrorCode() : '';

        if ($errorCode === '3005' || stripos($errorMessage, 'cardUserKey') !== false) {
            $this->purgeCustomerCardCredentials((int) $order->get_customer_id());

            $requestWithoutCardKey = $this->buildCheckoutInitializeRequest($order, $basketItems, $total_amount, false);
            $retryInitialize = CheckoutFormInitialize::create($requestWithoutCardKey, $options);

            if ($retryInitialize->getStatus() === 'success') {
                return array(
                    'result' => 'success',
                    'redirect' => $retryInitialize->getPaymentPageUrl()
                );
            }

            $errorMessage = method_exists($retryInitialize, 'getErrorMessage') ? $retryInitialize->getErrorMessage() : $errorMessage;
            $errorCode = method_exists($retryInitialize, 'getErrorCode') ? $retryInitialize->getErrorCode() : $errorCode;
        }

        wc_add_notice(
            sprintf(
                /* translators: 1: error code, 2: error message */
                __('Ödeme başlatılamadı (%1$s): %2$s', 'iyzipay-woocommerce-subscription'),
                $errorCode,
                $errorMessage
            ),
            'error'
        );
        return array(
            'result' => 'fail',
            'messages' => sprintf(
                /* translators: 1: error code, 2: error message */
                __('Ödeme başlatılamadı (%1$s): %2$s', 'iyzipay-woocommerce-subscription'),
                $errorCode,
                $errorMessage
            ),
            'redirect' => '',
        );
    }

    private function buildIyzipayOptions(): Options {
        $options = new Options();
        $options->setApiKey($this->api_key);
        $options->setSecretKey($this->secret_key);
        $options->setBaseUrl($this->sandbox === 'yes' ? 'https://sandbox-api.iyzipay.com' : 'https://api.iyzipay.com');
        return $options;
    }

    private function buildBasketItemsAndTotal(\WC_Order $order): array {
        $basketItems = array();
        $total_amount = 0;

        foreach ($order->get_items() as $item) {
            $basketItem = new BasketItem();
            $basketItem->setId($item->get_product_id());
            $basketItem->setName($item->get_name());
            $basketItem->setCategory1('Genel');
            $basketItem->setItemType(BasketItemType::PHYSICAL);
            $basketItem->setPrice($item->get_total());
            $basketItems[] = $basketItem;
            $total_amount += $item->get_total();
        }

        if ($order->get_shipping_total() > 0) {
            $shippingItem = new BasketItem();
            $shippingItem->setId('shipping');
            $shippingItem->setName('Kargo Ücreti');
            $shippingItem->setCategory1('Kargo');
            $shippingItem->setItemType(BasketItemType::PHYSICAL);
            $shippingItem->setPrice($order->get_shipping_total());
            $basketItems[] = $shippingItem;
            $total_amount += $order->get_shipping_total();
        }

        if ($order->get_total_tax() > 0) {
            $taxItem = new BasketItem();
            $taxItem->setId('tax');
            $taxItem->setName('Vergi');
            $taxItem->setCategory1('Vergi');
            $taxItem->setItemType(BasketItemType::PHYSICAL);
            $taxItem->setPrice($order->get_total_tax());
            $basketItems[] = $taxItem;
            $total_amount += $order->get_total_tax();
        }

        return array($basketItems, (float) $total_amount);
    }

    private function buildCheckoutInitializeRequest(\WC_Order $order, array $basketItems, float $total_amount, bool $attachCardKey): CreateCheckoutFormInitializeRequest {
        $request = new CreateCheckoutFormInitializeRequest();
        $request->setLocale(Locale::TR);
        $request->setConversationId($order->get_id());
        $request->setBasketItems($basketItems);
        $request->setPrice($total_amount);
        $request->setPaidPrice($total_amount);
        $request->setCurrency($order->get_currency());
        $request->setBasketId($order->get_id() . '_' . time());
        $request->setPaymentGroup(PaymentGroup::PRODUCT);
        $request->setCallbackUrl(add_query_arg('wc-api', 'iyzico_subscription', home_url('/')));
        $request->setEnabledInstallments(array(1));

        if ($attachCardKey) {
            $existing_card_user_key = $this->getCardUserKeyFromStorage((int) $order->get_customer_id());
            if (!empty($existing_card_user_key) && method_exists($request, 'setCardUserKey')) {
                $request->setCardUserKey($existing_card_user_key);
            }
        }

        $identity = $this->resolveIdentityNumber($order);

        $buyer = new Buyer();
        $buyer->setId((string) $order->get_customer_id());
        $buyer->setName($order->get_billing_first_name());
        $buyer->setSurname($order->get_billing_last_name());
        $buyer->setEmail($order->get_billing_email());
        $buyer->setIdentityNumber($identity);
        $buyer->setRegistrationAddress($order->get_billing_address_1() ?: '-');
        $buyer->setCity($order->get_billing_city() ?: '-');
        $buyer->setCountry($order->get_billing_country() ?: 'Türkiye');
        $buyer->setIp(isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '127.0.0.1');
        $request->setBuyer($buyer);

        $shippingAddress = new \Iyzipay\Model\Address();
        $shippingAddress->setContactName($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name());
        $shippingAddress->setCity($order->get_shipping_city());
        $shippingAddress->setCountry($order->get_shipping_country());
        $shippingAddress->setAddress($order->get_shipping_address_1());
        $request->setShippingAddress($shippingAddress);

        $billingAddress = new \Iyzipay\Model\Address();
        $billingAddress->setContactName($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        $billingAddress->setCity($order->get_billing_city());
        $billingAddress->setCountry($order->get_billing_country());
        $billingAddress->setAddress($order->get_billing_address_1());
        $request->setBillingAddress($billingAddress);

        return $request;
    }

    private function cancelIyzicoPayment(\WC_Order $order, string $payment_id, string $reason = 'other', string $description = ''): array {
        if (empty($payment_id)) {
            return ['success' => false, 'error' => 'payment_id is empty'];
        }

        $valid_reasons = ['double_payment', 'buyer_request', 'fraud', 'other'];
        if (!in_array($reason, $valid_reasons, true)) {
            $reason = 'other';
        }

        try {
            $options = $this->buildIyzipayOptions();

            $cancelRequest = new \Iyzipay\Request\CreateCancelRequest();
            $cancelRequest->setLocale(\Iyzipay\Model\Locale::TR);
            $cancelRequest->setConversationId($order->get_id() . '_cancel_' . time());
            $cancelRequest->setPaymentId($payment_id);
            $cancelRequest->setIp(isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '127.0.0.1');
            $cancelRequest->setReason($reason);
            if (!empty($description)) {
                $cancelRequest->setDescription(substr($description, 0, 250));
            }

            $cancel = \Iyzipay\Model\Cancel::create($cancelRequest, $options);

            if ($cancel->getStatus() === 'success') {
                $order->add_order_note(sprintf(
                    /* translators: %s: payment id */
                    __('iyzico ödemesi başarıyla iptal edildi. Ödeme ID: %s', 'iyzipay-woocommerce-subscription'),
                    $payment_id
                ));
                return ['success' => true];
            }

            $error_message = method_exists($cancel, 'getErrorMessage') ? (string) $cancel->getErrorMessage() : '';
            $error_code = method_exists($cancel, 'getErrorCode') ? (string) $cancel->getErrorCode() : '';

            return [
                'success' => false,
                'error' => $error_message,
                'code' => $error_code,
            ];
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('iyzipay-woocommerce-subscription cancelIyzicoPayment exception: ' . $e->getMessage());
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function resolveIdentityNumber(\WC_Order $order): string {
        $identity = (string) $order->get_meta('_iyzico_identity_number');

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (empty($identity) && !empty($_POST['billing_iyzico_identity_number'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $identity = preg_replace('/\D+/', '', sanitize_text_field(wp_unslash($_POST['billing_iyzico_identity_number'])));
        }

        if (empty($identity)) {
            $customer_id = (int) $order->get_customer_id();
            if ($customer_id) {
                $identity = (string) get_user_meta($customer_id, '_iyzico_identity_number', true);
            }
        }

        if (strlen((string) $identity) !== 11) {
            $identity = '11111111111';
        }

        return (string) $identity;
    }

    private function getCardUserKeyFromStorage(int $user_id): string {
        global $wpdb;
        $table = esc_sql($wpdb->prefix . 'iyzico_saved_cards');
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $row = $wpdb->get_row($wpdb->prepare("SELECT card_user_key FROM `{$table}` WHERE user_id = %d ORDER BY id DESC LIMIT 1", $user_id));
        if ($row && !empty($row->card_user_key)) {
            return (string) $row->card_user_key;
        }
        return (string) get_user_meta($user_id, '_iyzico_card_user_key', true);
    }

    private function purgeCustomerCardCredentials(int $user_id): void {
        $this->savedCardRepository->purgeForUser($user_id);
    }

    private function create_subscription($order) {
        $subscriptionService = SubscriptionFactory::createSubscriptionService();
        $subscriptionRepository = SubscriptionFactory::createSubscriptionRepository();

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();

            if (!$product || !in_array($product->get_type(), ['subscription', 'variable-subscription'], true)) {
                continue;
            }

            $period = get_post_meta($product->get_id(), '_subscription_period', true) ?: 'month';
            $length = (int) get_post_meta($product->get_id(), '_subscription_length', true);
            $trial_days = (int) get_post_meta($product->get_id(), '_subscription_trial_days', true);

            $start_date = current_time('mysql');
            $next_payment_date = $this->calculate_next_payment_date($product);

            $subscription_data = [
                'user_id' => (int) $order->get_customer_id(),
                'order_id' => (int) $order->get_id(),
                'product_id' => (int) $product->get_id(),
                'iyzico_subscription_id' => 'iyz_sub_' . $order->get_id() . '_' . time(),
                'status' => 'active',
                'amount' => (float) $item->get_total(),
                'currency' => $order->get_currency(),
                'period' => $period,
                'period_interval' => 1,
                'start_date' => $start_date,
                'next_payment' => $next_payment_date,
                'end_date' => null,
                'trial_end_date' => null,
                'payment_method' => 'iyzico_subscription',
                'billing_cycles' => $length > 0 ? $length : 0,
                'trial_days' => $trial_days > 0 ? $trial_days : 0,
            ];

            $subscription_id = $subscriptionService->createSubscription($subscription_data);

            if ($subscription_id) {
                $subscription = $subscriptionRepository->find((int) $subscription_id);
                $order->add_order_note(
                    sprintf(
                        /* translators: %s: subscription id */
                        __('Abonelik oluşturuldu. Abonelik ID: %s', 'iyzipay-woocommerce-subscription'),
                        $subscription_id
                    )
                );
                if ($subscription) {
                    do_action('iyzico_subscription_created', $subscription);
                }
            } elseif (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('iyzipay-woocommerce-subscription: Abonelik oluşturulamadı. Order ID: ' . $order->get_id());
            }
        }
    }

    

    private function calculate_next_payment_date($product) {
        $period = get_post_meta($product->get_id(), '_subscription_period', true);
        $length = get_post_meta($product->get_id(), '_subscription_length', true);
        
        $next_date = current_time('mysql');
        
        switch ($period) {
            case 'day':
                $next_date = gmdate('Y-m-d H:i:s', strtotime($next_date . ' +1 day'));
                break;
            case 'week':
                $next_date = gmdate('Y-m-d H:i:s', strtotime($next_date . ' +1 week'));
                break;
            case 'month':
                $next_date = gmdate('Y-m-d H:i:s', strtotime($next_date . ' +1 month'));
                break;
            case 'year':
                $next_date = gmdate('Y-m-d H:i:s', strtotime($next_date . ' +1 year'));
                break;
        }
        
        return $next_date;
    }

    public function display_checkout_errors() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (empty($_GET['iyzico_error'])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $error_type = sanitize_text_field(wp_unslash($_GET['iyzico_error']));
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $order_id = isset($_GET['order_id']) ? absint(wp_unslash($_GET['order_id'])) : 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $error_code = isset($_GET['error_code']) ? sanitize_text_field(wp_unslash($_GET['error_code'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $error_message_raw = isset($_GET['error_message']) ? wp_unslash($_GET['error_message']) : '';
        $error_message = sanitize_text_field(urldecode((string) $error_message_raw));

        switch ($error_type) {
            case 'card_info_missing':
                $display_message = __('Kart bilgileri alınamadığı için ödeme iptal edildi. Lütfen tekrar deneyiniz.', 'iyzipay-woocommerce-subscription');
                break;
            case 'cancel_failed':
                $display_message = sprintf(
                    /* translators: 1: error code, 2: error message */
                    __('Ödeme iptal edilemedi. Hata Kodu: %1$s, Hata: %2$s', 'iyzipay-woocommerce-subscription'),
                    $error_code,
                    $error_message
                );
                break;
            case 'subscription_create_failed':
                $display_message = __('Aboneliğiniz oluşturulamadığı için ödemeniz iyzico tarafında otomatik olarak iptal edildi. Hesabınızdan bir tahsilat yapılmamıştır. Lütfen tekrar deneyiniz veya destek ile iletişime geçiniz.', 'iyzipay-woocommerce-subscription');
                break;
            case 'payment_failed':
                $display_message = __('Ödeme başarısız oldu. Lütfen tekrar deneyiniz.', 'iyzipay-woocommerce-subscription');
                break;
            case 'general_error':
                if (!empty($error_message)) {
                    $display_message = sprintf(
                        /* translators: %s: error detail */
                        __('İşlem sırasında bir hata oluştu: %s. Hesabınızdan herhangi bir tahsilat yapılmamıştır. Lütfen tekrar deneyiniz veya destek ile iletişime geçiniz.', 'iyzipay-woocommerce-subscription'),
                        $error_message
                    );
                } else {
                    $display_message = __('İşlem sırasında bir hata oluştu. Hesabınızdan herhangi bir tahsilat yapılmamıştır. Lütfen tekrar deneyiniz veya destek ile iletişime geçiniz.', 'iyzipay-woocommerce-subscription');
                }
                break;
            default:
                $display_message = __('Ödemeniz tamamlanamadı. Hesabınızdan tahsilat yapılmamıştır. Lütfen tekrar deneyiniz.', 'iyzipay-woocommerce-subscription');
                break;
        }

        if ($order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                /* translators: %s: error message */
                $order->add_order_note(
                    sprintf(
                        /* translators: %s: error message */
                        __('iyzico ödeme hatası: %s', 'iyzipay-woocommerce-subscription'),
                        $display_message
                    )
                );
            }
        }

        if (function_exists('wc_add_notice')) {
            wc_add_notice($display_message, 'error');
        }
    }

    private function persistSavedCard(int $user_id, string $card_user_key, string $card_token): void {
        global $wpdb;
        if (empty($user_id) || (empty($card_user_key) && empty($card_token))) {
            return;
        }
        $table = $wpdb->prefix . 'iyzico_saved_cards';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        if (!$table_exists) {
            if (!empty($card_user_key)) {
                update_user_meta($user_id, '_iyzico_card_user_key', $card_user_key);
            }
            if (!empty($card_token)) {
                update_user_meta($user_id, '_iyzico_card_token', $card_token);
            }
            return;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->insert(
            $table,
            [
                'user_id' => $user_id,
                'card_user_key' => $card_user_key,
                'card_token' => $card_token,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );
        $this->savedCardRepository->save($user_id, $card_user_key, $card_token);
    }
}
