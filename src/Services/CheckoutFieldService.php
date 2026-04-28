<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Services;

defined('ABSPATH') || exit;

class CheckoutFieldService
{
    public function register(): void
    {
        add_filter('woocommerce_checkout_fields', [$this, 'addIdentityField']);
        add_action('woocommerce_checkout_process', [$this, 'validateIdentityField']);
        add_action('woocommerce_checkout_update_user_meta', [$this, 'saveIdentityToUserMeta'], 10, 2);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'saveIdentityToOrderMeta'], 10, 2);

        add_action('woocommerce_before_checkout_form', [$this, 'displayLoginRequiredNotice'], 5);
        add_action('woocommerce_check_cart_items', [$this, 'forceLoginForSubscriptions']);
        add_action('woocommerce_checkout_process', [$this, 'validateLoggedInForSubscriptions']);
        add_filter('woocommerce_checkout_registration_required', [$this, 'requireRegistrationForSubscriptions']);
        add_filter('woocommerce_checkout_registration_enabled', [$this, 'enableRegistrationForSubscriptions']);
    }

    public function addIdentityField(array $fields): array
    {
        if (!$this->isIyzicoAvailable()) {
            return $fields;
        }

        $existing = '';
        $user_id = get_current_user_id();
        if ($user_id) {
            $existing = (string) get_user_meta($user_id, '_iyzico_identity_number', true);
        }

        $fields['billing']['billing_iyzico_identity_number'] = [
            'type' => 'text',
            'label' => __('TC Kimlik Numarası', 'iyzipay-woocommerce-subscription'),
            'placeholder' => '00000000000',
            'required' => true,
            'class' => ['form-row-wide'],
            'priority' => 25,
            'default' => $existing,
            'custom_attributes' => [
                'maxlength' => 11,
                'pattern' => '\d{11}',
                'inputmode' => 'numeric',
            ],
            'description' => __('iyzico için TC kimlik numaranız (zorunludur).', 'iyzipay-woocommerce-subscription'),
        ];

        return $fields;
    }

    public function validateIdentityField(): void
    {
        if (!$this->isIyzicoAvailable()) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $value = isset($_POST['billing_iyzico_identity_number'])
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            ? preg_replace('/\D+/', '', sanitize_text_field(wp_unslash($_POST['billing_iyzico_identity_number'])))
            : '';

        if (strlen($value) !== 11 || !$this->isValidTurkishIdentity($value)) {
            wc_add_notice(__('Lütfen geçerli bir TC kimlik numarası giriniz (11 haneli).', 'iyzipay-woocommerce-subscription'), 'error');
        }
    }

    public function saveIdentityToUserMeta(int $customer_id, array $data): void
    {
        if (!$customer_id) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $value = isset($_POST['billing_iyzico_identity_number'])
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            ? preg_replace('/\D+/', '', sanitize_text_field(wp_unslash($_POST['billing_iyzico_identity_number'])))
            : '';

        if (!empty($value)) {
            update_user_meta($customer_id, '_iyzico_identity_number', $value);
        }
    }

    public function saveIdentityToOrderMeta(int $order_id, array $data): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $value = isset($_POST['billing_iyzico_identity_number'])
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            ? preg_replace('/\D+/', '', sanitize_text_field(wp_unslash($_POST['billing_iyzico_identity_number'])))
            : '';

        if (!empty($value)) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->update_meta_data('_iyzico_identity_number', $value);
                $order->save();
            }
        }
    }

    private function isIyzicoAvailable(): bool
    {
        if (!function_exists('WC') || !WC()->cart) {
            return false;
        }

        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = isset($cart_item['data']) ? $cart_item['data'] : null;
            if ($product && in_array($product->get_type(), ['subscription', 'variable-subscription'], true)) {
                return true;
            }
        }

        return false;
    }

    public function displayLoginRequiredNotice(): void
    {
        if (is_user_logged_in() || !$this->isIyzicoAvailable()) {
            return;
        }

        $login_url = wp_login_url(wc_get_checkout_url());
        $register_url = wc_get_page_permalink('myaccount');

        ?>
        <div class="woocommerce-info iyzico-subscription-login-required" style="border-left-color:#FF6B00;">
            <p style="margin:0 0 10px;font-weight:600;">
                <?php esc_html_e('Aboneliklerinizi yönetebilmek için üye girişi yapmanız gerekmektedir.', 'iyzipay-woocommerce-subscription'); ?>
            </p>
            <p style="margin:0 0 12px;">
                <?php esc_html_e('Aboneliklerin otomatik yenilenmesi, kayıtlı kart yönetimi ve iptal/askıya alma işlemleri için bir hesabınız olmalıdır. Misafir olarak abonelik satın alınamaz.', 'iyzipay-woocommerce-subscription'); ?>
            </p>
            <p style="margin:0;">
                <a href="<?php echo esc_url($login_url); ?>" class="button" style="margin-right:8px;">
                    <?php esc_html_e('Giriş Yap', 'iyzipay-woocommerce-subscription'); ?>
                </a>
                <a href="<?php echo esc_url($register_url); ?>" class="button">
                    <?php esc_html_e('Hesap Oluştur', 'iyzipay-woocommerce-subscription'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    public function forceLoginForSubscriptions(): void
    {
        if (is_user_logged_in() || !$this->isIyzicoAvailable()) {
            return;
        }

        if (function_exists('is_cart') && is_cart() && function_exists('wc_print_notice')) {
            $notices = function_exists('wc_get_notices') ? wc_get_notices() : [];
            $existing = isset($notices['notice']) ? $notices['notice'] : [];
            $message = __('Sepetinizde abonelik ürünü bulunmaktadır. Devam etmek için giriş yapmanız veya hesap oluşturmanız gerekmektedir.', 'iyzipay-woocommerce-subscription');

            $already_added = false;
            foreach ($existing as $n) {
                $text = is_array($n) ? ($n['notice'] ?? '') : (string) $n;
                if (strpos($text, $message) !== false) {
                    $already_added = true;
                    break;
                }
            }
            if (!$already_added) {
                wc_add_notice($message, 'notice');
            }
        }
    }

    public function validateLoggedInForSubscriptions(): void
    {
        if (is_user_logged_in() || !$this->isIyzicoAvailable()) {
            return;
        }

        wc_add_notice(
            __('Abonelik ürünleri için üye girişi yapmanız gerekmektedir. Lütfen giriş yapın veya bir hesap oluşturun.', 'iyzipay-woocommerce-subscription'),
            'error'
        );
    }

    public function requireRegistrationForSubscriptions(bool $required): bool
    {
        if ($this->isIyzicoAvailable()) {
            return true;
        }
        return $required;
    }

    public function enableRegistrationForSubscriptions(bool $enabled): bool
    {
        if ($this->isIyzicoAvailable()) {
            return true;
        }
        return $enabled;
    }

    private function isValidTurkishIdentity(string $id): bool
    {
        if (strlen($id) !== 11 || $id[0] === '0') {
            return false;
        }

        if (!ctype_digit($id)) {
            return false;
        }

        $digits = str_split($id);
        $digits = array_map('intval', $digits);

        $oddSum = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8];
        $evenSum = $digits[1] + $digits[3] + $digits[5] + $digits[7];

        $tenth = (($oddSum * 7) - $evenSum) % 10;
        if ($tenth < 0) {
            $tenth += 10;
        }
        if ($tenth !== $digits[9]) {
            return false;
        }

        $sumFirst10 = array_sum(array_slice($digits, 0, 10));
        if (($sumFirst10 % 10) !== $digits[10]) {
            return false;
        }

        return true;
    }
}
