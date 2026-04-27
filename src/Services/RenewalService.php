<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Services;

defined('ABSPATH') || exit;

use Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces\RenewalServiceInterface;
use Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces\EmailServiceInterface;
use Iyzico\IyzipayWoocommerceSubscription\Models\Interfaces\SubscriptionRepositoryInterface;
use Iyzico\IyzipayWoocommerceSubscription\Models\Interfaces\SavedCardRepositoryInterface;
use Iyzico\IyzipayWoocommerceSubscription\Gateway\IyzicoGateway;

class RenewalService implements RenewalServiceInterface
{
    private SubscriptionRepositoryInterface $subscriptionRepository;
    private IyzicoGateway $gateway;
    private EmailServiceInterface $emailService;
    private SavedCardRepositoryInterface $savedCardRepository;

    public function __construct(
        SubscriptionRepositoryInterface $subscriptionRepository,
        IyzicoGateway $gateway,
        EmailServiceInterface $emailService,
        SavedCardRepositoryInterface $savedCardRepository
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->gateway = $gateway;
        $this->emailService = $emailService;
        $this->savedCardRepository = $savedCardRepository;
        
        add_action('wp', [$this, 'scheduleRenewalCheck']);
        add_action('iyzico_subscription_renewal_check', [$this, 'processRenewals']);
        add_action('iyzico_subscription_retry_failed', [$this, 'retryFailedPayments']);
    }

    public function scheduleRenewalCheck(): void {
        if (!wp_next_scheduled('iyzico_subscription_renewal_check')) {
            wp_schedule_event(time(), 'hourly', 'iyzico_subscription_renewal_check');
        }
        
        if (!wp_next_scheduled('iyzico_subscription_retry_failed')) {
            wp_schedule_event(time(), 'daily', 'iyzico_subscription_retry_failed');
        }
    }

    public function processRenewals(): void {
        $due_subscriptions = $this->subscriptionRepository->findDueRenewals();
        
        foreach ($due_subscriptions as $subscription) {
            $this->processSingleRenewal($subscription);
        }
    }

    public function processSingleRenewal(object $subscription): bool {
        try {
            $payment_result = $this->processPayment($subscription);
            
            if ($payment_result['success']) {
                $this->subscriptionRepository->processSuccessfulPayment($subscription->id);
                
                $this->createRenewalOrder($subscription);
                
                $this->emailService->sendRenewalSuccessEmail($subscription);
                
                do_action('iyzico_subscription_renewal_success', $subscription);
                $this->logPaymentAttempt($subscription->id, $subscription->amount, $subscription->currency ?? 'TRY', 'success', $payment_result['payment_id'] ?? null);
                return true;
                
            } else {
                $this->subscriptionRepository->incrementFailedPayments($subscription->id);
                
                $this->emailService->sendRenewalFailedEmail($subscription, $payment_result['error']);
                
                do_action('iyzico_subscription_renewal_failed', $subscription, $payment_result['error']);
                $this->logPaymentAttempt($subscription->id, $subscription->amount, $subscription->currency ?? 'TRY', 'failed', null, null, $payment_result['error']);
                return false;
            }
            
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('iyzico-subscription renewal error: ' . $e->getMessage());
            }
            $this->subscriptionRepository->incrementFailedPayments($subscription->id);
            $this->logPaymentAttempt($subscription->id, $subscription->amount, $subscription->currency ?? 'TRY', 'failed', null, null, $e->getMessage());
            return false;
        }
    }

    private function processPayment(object $subscription): array {
        try {
            $options = new \Iyzipay\Options();
            $options->setApiKey($this->gateway->api_key);
            $options->setSecretKey($this->gateway->secret_key);
            $options->setBaseUrl($this->gateway->sandbox === 'yes' ? 'https://sandbox-api.iyzipay.com' : 'https://api.iyzipay.com');

            $card_user_key = $this->savedCardRepository->getCardUserKey((int) $subscription->user_id);
            $card_token = $this->savedCardRepository->getCardToken((int) $subscription->user_id);

            if (empty($card_user_key) || empty($card_token)) {
                return ['success' => false, 'error' => __('Kullanıcı için kayıtlı kart bulunamadı.', 'iyzipay-woocommerce-subscription')];
            }

            $request = new \Iyzipay\Request\CreatePaymentRequest();
            $request->setLocale(\Iyzipay\Model\Locale::TR);
            $request->setConversationId($subscription->id . '_renewal_' . time());
            $request->setPrice($subscription->amount);
            $request->setPaidPrice($subscription->amount);
            $request->setCurrency($this->mapCurrency((string) ($subscription->currency ?? 'TRY')));
            $request->setInstallment(1);
            $request->setBasketId((string) $subscription->id);
            $request->setPaymentChannel(\Iyzipay\Model\PaymentChannel::WEB);
            $request->setPaymentGroup(\Iyzipay\Model\PaymentGroup::SUBSCRIPTION);

            $buyer = $this->buildBuyerFromSubscription($subscription);
            $request->setBuyer($buyer);

            $address = $this->buildAddressFromSubscription($subscription);
            if ($address) {
                $request->setShippingAddress($address);
                $request->setBillingAddress($address);
            }

            $basketItem = new \Iyzipay\Model\BasketItem();
            $basketItem->setId((string) $subscription->product_id);
            $basketItem->setName(get_the_title($subscription->product_id) ?: 'Abonelik');
            $basketItem->setCategory1('Abonelik');
            $basketItem->setItemType(\Iyzipay\Model\BasketItemType::VIRTUAL);
            $basketItem->setPrice($subscription->amount);
            $request->setBasketItems([$basketItem]);

            $paymentCard = new \Iyzipay\Model\PaymentCard();
            $paymentCard->setCardUserKey($card_user_key);
            $paymentCard->setCardToken($card_token);
            $request->setPaymentCard($paymentCard);

            $payment = \Iyzipay\Model\Payment::create($request, $options);

            if ($payment->getStatus() === 'success') {
                return ['success' => true, 'payment_id' => $payment->getPaymentId()];
            }

            return ['success' => false, 'error' => $payment->getErrorMessage()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function mapCurrency(string $code): string {
        $code = strtoupper($code);
        $map = [
            'TRY' => \Iyzipay\Model\Currency::TL,
            'TL'  => \Iyzipay\Model\Currency::TL,
            'USD' => \Iyzipay\Model\Currency::USD,
            'EUR' => \Iyzipay\Model\Currency::EUR,
            'GBP' => \Iyzipay\Model\Currency::GBP,
            'IRR' => \Iyzipay\Model\Currency::IRR,
            'CHF' => \Iyzipay\Model\Currency::CHF,
            'NOK' => \Iyzipay\Model\Currency::NOK,
            'RUB' => \Iyzipay\Model\Currency::RUB,
        ];
        return $map[$code] ?? \Iyzipay\Model\Currency::TL;
    }

    private function buildBuyerFromSubscription(object $subscription): \Iyzipay\Model\Buyer {
        $user = get_user_by('id', (int) $subscription->user_id);
        $order = !empty($subscription->order_id) ? wc_get_order((int) $subscription->order_id) : null;

        $first_name = $order ? $order->get_billing_first_name() : ($user->first_name ?? '');
        $last_name = $order ? $order->get_billing_last_name() : ($user->last_name ?? '');
        $email = $order ? $order->get_billing_email() : ($user->user_email ?? '');
        $address = $order ? $order->get_billing_address_1() : '';
        $city = $order ? $order->get_billing_city() : '';
        $country = $order ? $order->get_billing_country() : 'Türkiye';

        if (empty($first_name)) { $first_name = $user->display_name ?? 'Müşteri'; }
        if (empty($last_name)) { $last_name = '-'; }
        if (empty($address)) { $address = '-'; }
        if (empty($city)) { $city = '-'; }
        if (empty($country)) { $country = 'Türkiye'; }

        $identity = (string) get_user_meta((int) $subscription->user_id, '_iyzico_identity_number', true);
        if (empty($identity)) {
            $identity = '11111111111';
        }

        $ip = (string) (get_user_meta((int) $subscription->user_id, '_iyzico_last_ip', true) ?: '127.0.0.1');

        $buyer = new \Iyzipay\Model\Buyer();
        $buyer->setId((string) $subscription->user_id);
        $buyer->setName($first_name);
        $buyer->setSurname($last_name);
        $buyer->setEmail($email);
        $buyer->setIdentityNumber($identity);
        $buyer->setRegistrationAddress($address);
        $buyer->setCity($city);
        $buyer->setCountry($country);
        $buyer->setIp($ip);

        return $buyer;
    }

    private function buildAddressFromSubscription(object $subscription): ?\Iyzipay\Model\Address {
        $order = !empty($subscription->order_id) ? wc_get_order((int) $subscription->order_id) : null;
        if (!$order) {
            return null;
        }

        $contactName = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        if (empty($contactName)) {
            $contactName = 'Müşteri';
        }

        $address = new \Iyzipay\Model\Address();
        $address->setContactName($contactName);
        $address->setCity($order->get_billing_city() ?: '-');
        $address->setCountry($order->get_billing_country() ?: 'Türkiye');
        $address->setAddress($order->get_billing_address_1() ?: '-');

        return $address;
    }

    private function createRenewalOrder(object $subscription): object {
        $order = wc_create_order();
        $product = wc_get_product($subscription->product_id);

        if ($product) {
            $order->add_product($product, 1);
        }
        $order->set_customer_id((int) $subscription->user_id);
        $order->set_payment_method('iyzico_subscription');
        $order->set_payment_method_title(__('iyzico Abonelik', 'iyzipay-woocommerce-subscription'));
        $order->set_currency((string) ($subscription->currency ?? 'TRY'));

        $parent_order = !empty($subscription->order_id) ? wc_get_order((int) $subscription->order_id) : null;
        if ($parent_order) {
            $order->set_address([
                'first_name' => $parent_order->get_billing_first_name(),
                'last_name'  => $parent_order->get_billing_last_name(),
                'company'    => $parent_order->get_billing_company(),
                'email'      => $parent_order->get_billing_email(),
                'phone'      => $parent_order->get_billing_phone(),
                'address_1'  => $parent_order->get_billing_address_1(),
                'address_2'  => $parent_order->get_billing_address_2(),
                'city'       => $parent_order->get_billing_city(),
                'state'      => $parent_order->get_billing_state(),
                'postcode'   => $parent_order->get_billing_postcode(),
                'country'    => $parent_order->get_billing_country(),
            ], 'billing');
        }

        $order->add_meta_data('_subscription_renewal', true);
        $order->add_meta_data('_parent_subscription_id', (int) $subscription->id);

        $order->calculate_totals();
        $order->set_status('completed', __('Abonelik yenileme ödemesi başarıyla tamamlandı.', 'iyzipay-woocommerce-subscription'));
        $order->save();

        return $order;
    }

    public function retryFailedPayments(): void {
        $failed_subscriptions = $this->subscriptionRepository->findByStatus('suspended');
        
        foreach ($failed_subscriptions as $subscription) {
            $suspended_date = new \DateTime($subscription->updated_at);
            $now = new \DateTime();
            $diff = $now->diff($suspended_date);
            
            if ($diff->days >= 3 && $subscription->failed_payments < 5) {
                $this->processSingleRenewal($subscription);
            }
        }
    }

    

    public function cancelSubscription(int $subscription_id): bool {
        $subscription = $this->subscriptionRepository->find($subscription_id);
        if (!$subscription) {
            return false;
        }

        $this->subscriptionRepository->cancel($subscription_id);

        $this->cleanupSavedCardIfOrphan((int) $subscription->user_id);

        do_action('iyzico_subscription_cancelled', $subscription);
        return true;
    }

    public function suspendSubscription(int $subscription_id): bool {
        $subscription = $this->subscriptionRepository->find($subscription_id);
        if ($subscription) {
            $this->subscriptionRepository->suspend($subscription_id);
            do_action('iyzico_subscription_suspended', $subscription);
            return true;
        }
        return false;
    }

    public function reactivateSubscription(int $subscription_id): bool {
        $subscription = $this->subscriptionRepository->find($subscription_id);
        if (!$subscription) {
            return false;
        }
        if (in_array($subscription->status, ['suspended', 'cancelled'], true)) {
            $result = $this->processSingleRenewal($subscription);
            if ($result) {
                return $this->subscriptionRepository->reactivate($subscription_id);
            }
            return false;
        }
        return $this->subscriptionRepository->reactivate($subscription_id);
    }

    private function cleanupSavedCardIfOrphan(int $user_id): void {
        if (empty($user_id)) {
            return;
        }

        $remaining = $this->subscriptionRepository->findByUser($user_id);
        foreach ((array) $remaining as $sub) {
            if (in_array($sub->status, ['active', 'pending', 'suspended'], true)) {
                return;
            }
        }

        $card_user_key = $this->savedCardRepository->getCardUserKey($user_id);
        $card_token = $this->savedCardRepository->getCardToken($user_id);

        if (empty($card_user_key) || empty($card_token)) {
            $this->savedCardRepository->purgeForUser($user_id);
            return;
        }

        try {
            $options = new \Iyzipay\Options();
            $options->setApiKey($this->gateway->api_key);
            $options->setSecretKey($this->gateway->secret_key);
            $options->setBaseUrl($this->gateway->sandbox === 'yes' ? 'https://sandbox-api.iyzipay.com' : 'https://api.iyzipay.com');

            $request = new \Iyzipay\Request\CreateCardDeleteRequest();
            $request->setLocale(\Iyzipay\Model\Locale::TR);
            $request->setCardUserKey($card_user_key);
            $request->setCardToken($card_token);

            \Iyzipay\Model\Card::delete($request, $options);
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('iyzico-subscription card delete error: ' . $e->getMessage());
            }
        }

        $this->savedCardRepository->purgeForUser($user_id);
    }

    private function logPaymentAttempt(int $subscription_id, float $amount, string $currency, string $status, ?string $iyzico_payment_id = null, ?string $error_code = null, ?string $error_message = null): void {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->insert(
            $wpdb->prefix . 'iyzico_subscription_payments',
            [
                'subscription_id' => $subscription_id,
                'order_id' => null,
                'iyzico_payment_id' => $iyzico_payment_id,
                'amount' => $amount,
                'currency' => $currency,
                'status' => $status,
                'error_code' => $error_code,
                'error_message' => $error_message,
                'created_at' => current_time('mysql'),
            ],
            [
                '%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s', '%s',
            ]
        );
    }
} 
