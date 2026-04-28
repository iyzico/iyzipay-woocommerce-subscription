<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Services;

defined('ABSPATH') || exit;

use Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces\HookServiceInterface;
use Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces\ProductServiceInterface;
use Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces\TemplateServiceInterface;
use Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces\AccountServiceInterface;
use Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces\PluginServiceInterface;
use Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces\RenewalServiceInterface;

class HookService implements HookServiceInterface
{
    private ProductServiceInterface $productService;
    private TemplateServiceInterface $templateService;
    private AccountServiceInterface $accountService;
    private PluginServiceInterface $pluginService;
    private RenewalServiceInterface $renewalService;

    public function __construct(
        ProductServiceInterface $productService,
        TemplateServiceInterface $templateService,
        AccountServiceInterface $accountService,
        PluginServiceInterface $pluginService,
        RenewalServiceInterface $renewalService
    ) {
        $this->productService = $productService;
        $this->templateService = $templateService;
        $this->accountService = $accountService;
        $this->pluginService = $pluginService;
        $this->renewalService = $renewalService;
    }

    public function registerProductHooks(): void
    {
        add_filter('product_type_selector', [$this->productService, 'addSubscriptionProductType']);
        add_filter('woocommerce_product_data_tabs', [$this->productService, 'addSubscriptionProductTab']);
        add_action('woocommerce_product_data_panels', [$this->productService, 'addSubscriptionProductFields']);
        add_action('woocommerce_process_product_meta', [$this->productService, 'saveSubscriptionProductFields']);
        add_filter('woocommerce_product_data_tabs', [$this->productService, 'hideGeneralTabForSubscription']);
        add_action('admin_footer', [$this->productService, 'addSubscriptionProductJs']);
        
        add_filter('woocommerce_product_class', [$this->productService, 'setSubscriptionProductClass'], 10, 2);
        add_filter('woocommerce_product_type_query', [$this->productService, 'setSubscriptionProductType'], 10, 2);
    }

    public function registerTemplateHooks(): void
    {
        add_filter('wc_get_template', [$this->templateService, 'loadSubscriptionTemplate'], 10, 5);
        add_filter('woocommerce_locate_template', [$this->templateService, 'locateSubscriptionTemplate'], 10, 3);
        add_filter('woocommerce_locate_core_template', [$this->templateService, 'locateSubscriptionTemplate'], 10, 3);
        
        add_action('woocommerce_single_product_summary', [$this->templateService, 'displaySubscriptionAddToCart'], 30);
    }

    public function registerAccountHooks(): void
    {
        add_action('init', [$this->accountService, 'addAccountEndpoints']);
        add_filter('woocommerce_account_menu_items', [$this->accountService, 'addAccountMenuItems']);
        add_action('woocommerce_account_subscriptions_endpoint', [$this->accountService, 'renderSubscriptionsAccountPage']);
        add_action('woocommerce_account_saved-cards_endpoint', [$this->accountService, 'renderSavedCardsAccountPage']);
    }

    public function registerPaymentHooks(): void
    {
        add_filter('woocommerce_payment_gateways', [$this->pluginService, 'addIyzicoGateway']);
        
        $this->pluginService->addWooCommerceBlocksSupport();
    }

    public function registerAdminHooks(): void
    {
        if (is_admin()) {
            \Iyzico\IyzipayWoocommerceSubscription\Admin\AdminContainer::get_subscription_admin_controller();
        }
    }

    public function registerAjaxHooks(): void
    {
        add_action('wp_ajax_iyzico_subscription_action', [$this, 'handleSubscriptionAction']);

        add_action('wp_ajax_iyzico_list_saved_cards', [$this->accountService, 'ajaxListSavedCards']);
        add_action('wp_ajax_iyzico_delete_saved_card', [$this->accountService, 'ajaxDeleteSavedCard']);
    }

    public function handleSubscriptionAction(): void
    {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'iyzico_subscription_action')) {
            wp_send_json_error(['message' => __('Güvenlik doğrulaması başarısız.', 'iyzipay-woocommerce-subscription')]);
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Bu işlem için giriş yapmalısınız.', 'iyzipay-woocommerce-subscription')]);
        }

        $subscription_id = isset($_POST['subscription_id']) ? intval($_POST['subscription_id']) : 0;
        $action = isset($_POST['subscription_action']) ? sanitize_text_field(wp_unslash($_POST['subscription_action'])) : '';

        if (!$subscription_id || !$action) {
            wp_send_json_error(['message' => __('Geçersiz istek.', 'iyzipay-woocommerce-subscription')]);
        }

        $repository = \Iyzico\IyzipayWoocommerceSubscription\Models\SubscriptionFactory::createSubscriptionRepository();
        $subscription = $repository->find($subscription_id);

        if (!$subscription) {
            wp_send_json_error(['message' => __('Abonelik bulunamadı.', 'iyzipay-woocommerce-subscription')]);
        }

        $current_user_id = get_current_user_id();
        $is_owner = ((int) $subscription->user_id === (int) $current_user_id);
        $is_admin = current_user_can('manage_woocommerce');

        if (!$is_owner && !$is_admin) {
            wp_send_json_error(['message' => __('Bu abonelik üzerinde işlem yapma yetkiniz yok.', 'iyzipay-woocommerce-subscription')]);
        }

        $result = false;

        switch ($action) {
            case 'suspend':
                $result = $this->renewalService->suspendSubscription($subscription_id);
                break;
            case 'cancel':
                $result = $this->renewalService->cancelSubscription($subscription_id);
                break;
            case 'reactivate':
                $result = $this->renewalService->reactivateSubscription($subscription_id);
                break;
        }

        if ($result) {
            wp_send_json_success(['message' => __('İşlem başarıyla tamamlandı.', 'iyzipay-woocommerce-subscription')]);
        } else {
            wp_send_json_error(['message' => __('İşlem başarısız oldu.', 'iyzipay-woocommerce-subscription')]);
        }
    }

    public function registerPluginHooks(): void
    {
        add_action('iyzico_subscription_activate', [$this->pluginService, 'createDatabaseTables']);
        add_action('plugins_loaded', [$this->pluginService, 'createDatabaseTables']);
    }
}
