<?php
/**
 * Plugin Name: iyzico Subscription WooCommerce

 * Description: iyzico Payment Subscription Gateway for WooCommerce.
 * Author: iyzico
 * Author URI: https://iyzico.com
 * Version: 1.0.0
 * Text Domain: iyzico Subscription WooCommerce
 * Domain Path: /i18n/languages/
 * WC requires at least: 3.0.0
 * WC tested up to: 3.7.1
 */
define('IYZICO_PATH',untrailingslashit( plugin_dir_path( __FILE__ )));
define('IYZICO_LANG_PATH',plugin_basename(dirname(__FILE__)) . '/i18n/languages/');
define('IYZICO_PLUGIN_NAME','/'.plugin_basename(dirname(__FILE__)));


if (!defined('ABSPATH')) {
    exit;
}
if ( ! class_exists( 'Iyzico_Subscription_For_WooCommerce' ) ) {

    class Iyzico_Subscription_For_WooCommerce {

        protected static $instance;

        public static function get_instance() {

            if ( null === self::$instance ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        protected function __construct() {

            add_action('plugins_loaded', array($this,'init'));

        }

        public static function SubscriptionIyzicoActive() {

            global $wpdb;
            $table_name = $wpdb->prefix . 'subscription_iyzico';

            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
                subscription_iyzico_id int(11) NOT NULL AUTO_INCREMENT,
                subscription_reference_code  varchar(45) NOT NULL,
                order_id int(11) NOT NULL,
                user_id int(11) NOT NULL,
                created_at  timestamp DEFAULT current_timestamp,
              PRIMARY KEY (subscription_iyzico_id)
            ) $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta($sql);
        }

        public static function SubscriptionIyzicoDeactive() {

            global $wpdb;

            $table_name = $wpdb->prefix . 'subscription_iyzico';
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "DROP TABLE IF EXISTS $table_name;";
            $wpdb->query($sql);
            flush_rewrite_rules();
        }

        public function init() {

            $this->InitIyzicoPaymentGateway();
        }


        public static function installLanguage() {

          load_plugin_textdomain('woocommerce-iyzico-subscription',false,IYZICO_LANG_PATH);

        }

        public function InitIyzicoPaymentGateway() {

            if ( ! class_exists('WC_Payment_Gateway')) {
                return;
            }

            include_once IYZICO_PATH . '/library/iyzico-subscription-for-woocommerce-gateway.php';
            include_once IYZICO_PATH . '/library/iyzico-subscription-for-woocommerce-gateway-fields.php';
            include_once IYZICO_PATH . '/library/iyzico-subscription-for-woocommerce-gateway-formobjectgenerate.php';
            include_once IYZICO_PATH . '/library/iyzico-subscription-for-woocommerce-gateway-helper.php';
            include_once IYZICO_PATH . '/library/iyzico-subscription-for-woocommerce-gateway-pkibuilder.php';
            include_once IYZICO_PATH . '/library/iyzico-subscription-for-woocommerce-gateway-authorization.php';
            include_once IYZICO_PATH . '/library/iyzico-subscription-for-woocommerce-gateway-request.php';
            include_once IYZICO_PATH . '/library/iyzico-subscription-for-woocommerce-gateway-model.php';


            add_action('woocommerce_payment_gateways',array($this,'AddIyzicoSubscriptionGateway'));

            add_action('woocommerce_product_options_general_product_data',
                        array('Iyzico_Subscription_For_WooCommerce_Gateway',
                        'pricingPlanMethod'));

            add_action('woocommerce_process_product_meta',
                        array('Iyzico_Subscription_For_WooCommerce_Gateway',
                        'pricingPlanMethodSave'));

        }


        public function AddIyzicoSubscriptionGateway($methods) {

            $methods[] = 'Iyzico_Subscription_For_WooCommerce_Gateway';
            return $methods;
        }

    }

Iyzico_Subscription_For_WooCommerce::get_instance();
add_action('plugins_loaded',array('Iyzico_Subscription_For_WooCommerce','installLanguage'));
register_activation_hook(__FILE__, array('Iyzico_Subscription_For_WooCommerce','SubscriptionIyzicoActive'));
register_deactivation_hook(__FILE__,array('Iyzico_Subscription_For_WooCommerce','SubscriptionIyzicoDeactive'));
}
