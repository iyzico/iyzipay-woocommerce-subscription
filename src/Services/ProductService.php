<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Services;

defined('ABSPATH') || exit;

use Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces\ProductServiceInterface;

class ProductService implements ProductServiceInterface
{
    public function addSubscriptionProductType(array $types): array
    {
        $types['subscription'] = __('Abonelik', 'iyzipay-woocommerce-subscription');
        return $types;
    }

    public function addSubscriptionProductTab(array $tabs): array
    {
        $tabs['subscription'] = [
            'label' => __('Abonelik Ayarları', 'iyzipay-woocommerce-subscription'),
            'target' => 'subscription_product_data',
            'class' => ['show_if_subscription'],
            'priority' => 21,
        ];
        return $tabs;
    }

    public function addSubscriptionProductFields(): void
    {
        ?>
        <div id="subscription_product_data" class="panel woocommerce_options_panel">
            <?php
            woocommerce_wp_text_input([
                'id' => '_regular_price',
                'label' => __('Normal Fiyat', 'iyzipay-woocommerce-subscription') . ' (' . get_woocommerce_currency_symbol() . ')',
                'type' => 'number',
                'custom_attributes' => [
                    'step' => 'any',
                    'min' => '0',
                ],
            ]);

            woocommerce_wp_select([
                'id' => '_subscription_period',
                'label' => __('Abonelik Periyodu', 'iyzipay-woocommerce-subscription'),
                'options' => [
                    'day' => __('Günlük', 'iyzipay-woocommerce-subscription'),
                    'week' => __('Haftalık', 'iyzipay-woocommerce-subscription'),
                    'month' => __('Aylık', 'iyzipay-woocommerce-subscription'),
                    'year' => __('Yıllık', 'iyzipay-woocommerce-subscription'),
                ],
            ]);

            woocommerce_wp_text_input([
                'id' => '_subscription_length',
                'label' => __('Abonelik Süresi', 'iyzipay-woocommerce-subscription'),
                'description' => __('Abonelik süresi (0 = süresiz)', 'iyzipay-woocommerce-subscription'),
                'type' => 'number',
                'custom_attributes' => [
                    'step' => '1',
                    'min' => '0',
                ],
            ]);

            woocommerce_wp_text_input([
                'id' => '_subscription_trial_days',
                'label' => __('Deneme Süresi (gün)', 'iyzipay-woocommerce-subscription'),
                'description' => __('Ücretsiz deneme süresi (0 = deneme yok)', 'iyzipay-woocommerce-subscription'),
                'type' => 'number',
                'custom_attributes' => [
                    'step' => '1',
                    'min' => '0',
                ],
            ]);
            ?>
        </div>
        <?php
    }

    public function saveSubscriptionProductFields(int $post_id): void
    {
        if (!current_user_can('edit_product', $post_id)) {
            return;
        }
        if (isset($_POST['woocommerce_meta_nonce']) && !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['woocommerce_meta_nonce'])), 'woocommerce_save_data')) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $product_type = isset($_POST['product-type']) ? sanitize_text_field(wp_unslash($_POST['product-type'])) : '';
        if ($product_type !== 'subscription') {
            return;
        }

        wp_set_object_terms($post_id, 'subscription', 'product_type');

        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $regular_price = isset($_POST['_regular_price']) ? sanitize_text_field(wp_unslash($_POST['_regular_price'])) : '';
        $regular_price = wc_clean($regular_price);
        update_post_meta($post_id, '_regular_price', $regular_price);
        update_post_meta($post_id, '_price', $regular_price);

        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $period = isset($_POST['_subscription_period']) ? sanitize_text_field(wp_unslash($_POST['_subscription_period'])) : 'month';
        $period = wc_clean($period);
        if (!in_array($period, ['day', 'week', 'month', 'year'], true)) {
            $period = 'month';
        }
        update_post_meta($post_id, '_subscription_period', $period);

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $length = isset($_POST['_subscription_length']) ? absint(wp_unslash($_POST['_subscription_length'])) : 0;
        update_post_meta($post_id, '_subscription_length', $length);

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $trial = isset($_POST['_subscription_trial_days']) ? absint(wp_unslash($_POST['_subscription_trial_days'])) : 0;
        update_post_meta($post_id, '_subscription_trial_days', $trial);

        update_post_meta($post_id, '_is_subscription', 'yes');
        update_post_meta($post_id, '_subscription_price', $regular_price);

        clean_post_cache($post_id);
    }

    public function hideGeneralTabForSubscription(array $tabs): array
    {
        global $post;
        if ($post && get_post_type($post) === 'product') {
            $product = wc_get_product($post->ID);
            if ($product && $product->get_type() === 'subscription') {
                unset($tabs['general']);
            }
        }
        return $tabs;
    }

    public function addSubscriptionProductJs(): void
    {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('select#product-type').on('change', function() {
                    var type = $(this).val();
                    
                    if (type === 'subscription') {
                        $('.options_group.pricing').show();
                        $('.options_group.subscription_pricing').show();
                        
                        $('.general_options').hide();
                        $('.general_tab').hide();
                        
                        $('.subscription_tab').addClass('active');
                        $('.subscription_options').show();
                    } else {
                        $('.options_group.pricing').show();
                        $('.options_group.subscription_pricing').hide();
                        $('.general_options').show();
                        $('.general_tab').show();
                        $('.subscription_tab').removeClass('active');
                        $('.subscription_options').hide();
                    }
                });

                if ($('select#product-type').val() === 'subscription') {
                    $('.options_group.pricing').show();
                    $('.options_group.subscription_pricing').show();
                    $('.general_options').hide();
                    $('.general_tab').hide();
                    $('.subscription_tab').addClass('active');
                    $('.subscription_options').show();
                }
            });
        </script>
        <?php
    }

    // ponytail: no scalar type hints — WooCommerce passes false/null here (new product screen has no ID yet)
    public function setSubscriptionProductClass($classname, $product_type)
    {
        if ($product_type === 'subscription') {
            return 'Iyzico\IyzipayWoocommerceSubscription\Product\WC_Product_Subscription';
        }
        return $classname;
    }

    public function setSubscriptionProductType($type, $product_id)
    {
        $product_id = absint($product_id);
        if (!$product_id) {
            return $type;
        }
        if (get_post_meta($product_id, '_product_type', true) === 'subscription') {
            return 'subscription';
        }
        return $type;
    }
}
