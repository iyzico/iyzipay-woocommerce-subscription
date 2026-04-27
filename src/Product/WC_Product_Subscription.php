<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Product;

defined('ABSPATH') || exit;

class WC_Product_Subscription extends \WC_Product {
    public function __construct($product) {
        $this->data['type'] = 'subscription';
        parent::__construct($product);
    }

    public function get_type() {
        return 'subscription';
    }

    public function is_type($type) {
        return $type === 'subscription' || parent::is_type($type);
    }

    public function is_purchasable() {
        return true;
    }

    public function is_virtual() {
        return true;
    }

    public function is_downloadable() {
        return false;
    }

    public function needs_shipping() {
        return false;
    }

    public function single_add_to_cart_text() {
        return __('Abonelik Başlat', 'iyzipay-woocommerce-subscription');
    }

    public function add_to_cart_text() {
        return __('Abonelik Başlat', 'iyzipay-woocommerce-subscription');
    }

    public function get_subscription_period() {
        return get_post_meta($this->get_id(), '_subscription_period', true);
    }

    public function get_subscription_length() {
        return get_post_meta($this->get_id(), '_subscription_length', true);
    }

    public function get_subscription_price() {
        return get_post_meta($this->get_id(), '_subscription_price', true);
    }

    public function get_price_html($deprecated = '') {
        $price = $this->get_price();
        $period = $this->get_subscription_period();
        $length = (int) $this->get_subscription_length();

        if ($price === '') {
            return '';
        }

        $period_labels = [
            'day' => __('günlük', 'iyzipay-woocommerce-subscription'),
            'week' => __('haftalık', 'iyzipay-woocommerce-subscription'),
            'month' => __('aylık', 'iyzipay-woocommerce-subscription'),
            'year' => __('yıllık', 'iyzipay-woocommerce-subscription'),
        ];
        $period_text = $period_labels[$period] ?? '';

        if ($length > 0) {
            /* translators: 1: length, 2: period text */
            $length_text = sprintf(__(' (%1$d %2$s)', 'iyzipay-woocommerce-subscription'), $length, $period_text);
        } else {
            /* translators: 1: period text */
            $length_text = sprintf(__(' (Süresiz %1$s)', 'iyzipay-woocommerce-subscription'), $period_text);
        }

        return wp_kses_post(wc_price($price)) . esc_html($length_text);
    }
} 
