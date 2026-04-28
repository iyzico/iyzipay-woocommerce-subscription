<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Services;

defined('ABSPATH') || exit;

use Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces\TemplateServiceInterface;

class TemplateService implements TemplateServiceInterface
{
    private string $template_path;

    public function __construct() {
        $this->template_path = plugin_dir_path(dirname(__DIR__, 1)) . 'templates/emails/';
    }

    public function loadTemplate(string $template_name, array $data): string
    {
        $template_file = $this->template_path . $template_name . '.php';
        
        if (file_exists($template_file)) {
            ob_start();
            extract($data);
            include $template_file;
            return ob_get_clean();
        }
        
        return $this->getFallbackTemplate($template_name, $data);
    }

    public function getFallbackTemplate(string $template_name, array $data): string
    {
        $br = "<br>\n";

        switch ($template_name) {
            case 'subscription-created':
                return sprintf(
                    /* translators: 1: user name, 2: product name, 3: amount, 4: period, 5: start date, 6: next payment date, 7: line break */
                    __('Merhaba %1$s,%7$s%7$s%2$s aboneliğiniz başarıyla oluşturuldu.%7$s%7$sTutar: %3$s%7$sPeriyot: %4$s%7$sBaşlangıç: %5$s%7$sSonraki Ödeme: %6$s%7$s%7$sTeşekkürler!', 'iyzipay-woocommerce-subscription'),
                    $data['user_name'],
                    $data['product_name'],
                    $data['amount'],
                    $data['period'],
                    $data['start_date'],
                    $data['next_payment'],
                    $br
                );

            case 'renewal-success':
                return sprintf(
                    /* translators: 1: user name, 2: product name, 3: amount, 4: payment date, 5: next payment, 6: line break */
                    __('Merhaba %1$s,%6$s%6$s%2$s aboneliğiniz başarıyla yenilendi.%6$s%6$sTutar: %3$s%6$sÖdeme Tarihi: %4$s%6$sSonraki Ödeme: %5$s%6$s%6$sTeşekkürler!', 'iyzipay-woocommerce-subscription'),
                    $data['user_name'],
                    $data['product_name'],
                    $data['amount'],
                    $data['payment_date'],
                    $data['next_payment'],
                    $br
                );

            case 'renewal-failed':
                return sprintf(
                    /* translators: 1: user name, 2: product name, 3: error message, 4: retry date, 5: account url, 6: line break */
                    __('Merhaba %1$s,%6$s%6$s%2$s aboneliğinizin ödemesi başarısız oldu.%6$s%6$sHata: %3$s%6$sYeniden deneme: %4$s%6$s%6$sLütfen ödeme bilgilerinizi kontrol edin: %5$s%6$s%6$sTeşekkürler!', 'iyzipay-woocommerce-subscription'),
                    $data['user_name'],
                    $data['product_name'],
                    $data['error_message'],
                    $data['retry_date'],
                    $data['account_url'],
                    $br
                );

            case 'subscription-cancelled':
                return sprintf(
                    /* translators: 1: user name, 2: product name, 3: cancellation date, 4: line break */
                    __('Merhaba %1$s,%4$s%4$s%2$s aboneliğiniz iptal edildi.%4$s%4$sİptal Tarihi: %3$s%4$s%4$sBizi tercih ettiğiniz için teşekkürler!', 'iyzipay-woocommerce-subscription'),
                    $data['user_name'],
                    $data['product_name'],
                    $data['cancellation_date'],
                    $br
                );

            case 'subscription-suspended':
                return sprintf(
                    /* translators: 1: user name, 2: product name, 3: failed payments count, 4: suspension date, 5: account url, 6: line break */
                    __('Merhaba %1$s,%6$s%6$s%2$s aboneliğiniz askıya alındı.%6$s%6$sSebep: %3$d başarısız ödeme%6$sTarih: %4$s%6$s%6$sHesabınızdan yeniden aktifleştirebilirsiniz: %5$s', 'iyzipay-woocommerce-subscription'),
                    $data['user_name'],
                    $data['product_name'],
                    $data['failed_payments'],
                    $data['suspension_date'],
                    $data['account_url'],
                    $br
                );

            case 'subscription-expiring':
                return sprintf(
                    /* translators: 1: user name, 2: product name, 3: expiry date, 4: days remaining, 5: renewal url, 6: line break */
                    __('Merhaba %1$s,%6$s%6$s%2$s aboneliğiniz yakında sona eriyor.%6$s%6$sBitiş Tarihi: %3$s%6$sKalan Gün: %4$d%6$s%6$sYenilemek için: %5$s', 'iyzipay-woocommerce-subscription'),
                    $data['user_name'],
                    $data['product_name'],
                    $data['expiry_date'],
                    $data['days_remaining'],
                    $data['renewal_url'],
                    $br
                );
        }

        return '';
    }

    public function getPeriodLabel(string $period): string
    {
        $labels = [
            'day' => __('Günlük', 'iyzipay-woocommerce-subscription'),
            'week' => __('Haftalık', 'iyzipay-woocommerce-subscription'),
            'month' => __('Aylık', 'iyzipay-woocommerce-subscription'),
            'year' => __('Yıllık', 'iyzipay-woocommerce-subscription'),
        ];
        
        return $labels[$period] ?? $period;
    }

    public function getDaysUntilExpiry(string $end_date): int
    {
        $now = new \DateTime();
        $expiry = new \DateTime($end_date);
        $diff = $now->diff($expiry);
        
        return $diff->days;
    }

    public function loadSubscriptionTemplate(string $template, string $template_name, array $args, string $template_path, string $default_path): string
    {
        global $product;

        if (!is_object($product) || !method_exists($product, 'get_type') || $product->get_type() !== 'subscription') {
            return $template;
        }

        if ($template_name === 'single-product/add-to-cart/simple.php') {
            $custom_template = plugin_dir_path(dirname(__DIR__, 1)) . 'templates/single-product/add-to-cart/subscription.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }

        return $template;
    }

    public function locateSubscriptionTemplate(string $template, string $template_name, string $template_path): string
    {
        global $product;

        if (!is_object($product) || !method_exists($product, 'get_type') || $product->get_type() !== 'subscription') {
            return $template;
        }

        if ($template_name === 'single-product/add-to-cart/simple.php') {
            $custom_template = plugin_dir_path(dirname(__DIR__, 1)) . 'templates/single-product/add-to-cart/subscription.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }

        return $template;
    }

    public function displaySubscriptionAddToCart(): void
    {
        global $product;

        if (!is_object($product) || !method_exists($product, 'get_type') || $product->get_type() !== 'subscription') {
            return;
        }

        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);

        $template_path = plugin_dir_path(dirname(__DIR__, 1)) . 'templates/single-product/add-to-cart/subscription.php';
        if (file_exists($template_path)) {
            include $template_path;
        }
    }
}
