<?php

/**
 * Plugin Name: iyzico Subscription for WooCommerce
 * Plugin URI: https://www.iyzico.com
 * Description: WooCommerce için iyzico Abonelik Eklentisi
 * Version: 1.1.0
 * Author: iyzico
 * Author URI: https://www.iyzico.com
 * Text Domain: iyzipay-woocommerce-subscription
 * Requires Plugins: woocommerce
 * Domain Path: /languages
 * Requires at least: 6.6
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 9.5
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

use Iyzico\IyzipayWoocommerceSubscription\Plugin;
use Iyzico\IyzipayWoocommerceSubscription\Admin\Settings;

(function () {
    $autoload_filepath = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoload_filepath)) {
        require $autoload_filepath;
    }

    /**
     * Initialize the plugin and its modules.
     */
    function iyzipay_woocommerce_subscription_init(): void
    {
        $root_dir = __DIR__;

        static $initialized;
        if (! $initialized) {
            $bootstrap = require "$root_dir/bootstrap.php";

            $app_container = $bootstrap($root_dir);

            Plugin::init($app_container);

            $initialized = true;
        }
    }

    add_action('plugins_loaded', 'iyzipay_woocommerce_subscription_init');

    register_activation_hook(
        __FILE__,
        function () {
            iyzipay_woocommerce_subscription_init();
            /**
             * The hook fired in register_activation_hook.
             */
            do_action('iyzico_subscription_activate');

            add_rewrite_endpoint('subscriptions', EP_ROOT | EP_PAGES);
            add_rewrite_endpoint('saved-cards', EP_ROOT | EP_PAGES);
            flush_rewrite_rules();
        }
    );
    register_deactivation_hook(
        __FILE__,
        function () {
            iyzipay_woocommerce_subscription_init();
            /**
             * The hook fired in register_deactivation_hook.
             */
            do_action('iyzico_subscription_deactivate');

            wp_clear_scheduled_hook('iyzico_subscription_renewal_check');
            wp_clear_scheduled_hook('iyzico_subscription_retry_failed');

            flush_rewrite_rules();
        }
    );
    add_filter(
        'plugin_action_links_' . plugin_basename(__FILE__),
        /**
         * Add "Settings" link to Plugins screen.
         *
         * @param array $links
         * @return array
         */
        function ($links) {
            if (! iyzipay_woocommerce_subscription_is_wc_activated()) {
                return $links;
            }

            array_unshift(
                $links,
                sprintf(
                    '<a href="%1$s">%2$s</a>',
                    admin_url('admin.php?page=wc-settings&tab=checkout&section=iyzico_subscription&from=WCADMIN_PAYMENT_SETTINGS'),
                    __('Settings', 'iyzipay-woocommerce-subscription')
                )
            );

            return $links;
        }
    );

    add_filter(
        'plugin_row_meta',
        /**
         * Add links below the description on the Plugins page.
         *
         * @param array $links
         * @param string $file
         * @return array
         */
        function ($links, $file) {
            if (plugin_basename(__FILE__) !== $file) {
                return $links;
            }

            return array_merge(
                $links,
                array(
                    sprintf(
                        '<a target="_blank" href="%1$s">%2$s</a>',
                        'https://woocommerce.com/document/iyzico-subscription/',
                        __('Documentation', 'iyzipay-woocommerce-subscription')
                    ),
                    sprintf(
                        '<a target="_blank" href="%1$s">%2$s</a>',
                        'https://woocommerce.com/document/iyzico-subscription/#get-help',
                        __('Get help', 'iyzipay-woocommerce-subscription')
                    ),
                    sprintf(
                        '<a target="_blank" href="%1$s">%2$s</a>',
                        'https://woocommerce.com/feature-requests/iyzico-subscription/',
                        __('Request a feature', 'iyzipay-woocommerce-subscription')
                    ),
                    sprintf(
                        '<a target="_blank" href="%1$s">%2$s</a>',
                        'https://github.com/iyzico/iyzipay-woocommerce-subscription/issues/new?assignees=&labels=type%3A+bug&template=bug_report.md',
                        __('Submit a bug', 'iyzipay-woocommerce-subscription')
                    ),
                )
            );
        },
        10,
        2
    );

    add_action(
        'before_woocommerce_init',
        function () {
            if (class_exists('\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
                /**
                 * Skip WC class check.
                 *
                 * @psalm-suppress UndefinedClass
                 */
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
            }
        }
    );

    /**
     * Check if WooCommerce is active.
     *
     * @return bool true if WooCommerce is active, otherwise false.
     */
    function iyzipay_woocommerce_subscription_is_wc_activated(): bool
    {
        return class_exists('woocommerce');
    }


})();