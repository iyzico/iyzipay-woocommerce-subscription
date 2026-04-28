<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Admin;

defined('ABSPATH') || exit;

use Iyzico\IyzipayWoocommerceSubscription\Admin\Views\SubscriptionAdminView;
use Iyzico\IyzipayWoocommerceSubscription\Services\SubscriptionAdminService;
use Iyzico\IyzipayWoocommerceSubscription\Services\RenewalService;

class SubscriptionAdminController
{
    private SubscriptionAdminView $view;
    private SubscriptionAdminService $service;
    private RenewalService $renewalService;
    private string $hook_suffix = '';

    public function __construct(
        SubscriptionAdminView $view,
        SubscriptionAdminService $service,
        RenewalService $renewalService
    ) {
        $this->view = $view;
        $this->service = $service;
        $this->renewalService = $renewalService;

        $this->init_hooks();
    }

    private function init_hooks(): void
    {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'handle_actions']);
        add_filter('set-screen-option', [$this, 'set_screen_option'], 10, 3);
    }

    public function add_menu_page(): void
    {
        $this->hook_suffix = (string) add_submenu_page(
            'woocommerce',
            __('iyzico Abonelikler', 'iyzipay-woocommerce-subscription'),
            __('iyzico Abonelikler', 'iyzipay-woocommerce-subscription'),
            'manage_woocommerce',
            'iyzico-subscriptions',
            [$this->view, 'render_subscriptions_page']
        );

        if ($this->hook_suffix) {
            add_action('load-' . $this->hook_suffix, [$this->view, 'register_screen_options']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        }
    }

    public function enqueue_admin_scripts(string $hook): void
    {
        $this->view->enqueue_admin_assets($hook);
    }

    public function set_screen_option($status, $option, $value)
    {
        if ($option === 'iyzico_subscriptions_per_page') {
            return (int) $value;
        }
        return $status;
    }

    public function handle_actions(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_GET['page']) || $_GET['page'] !== 'iyzico-subscriptions') {
            return;
        }

        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (!empty($_POST['action']) || !empty($_POST['action2'])) {
            $this->handle_bulk_action();
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $action = isset($_GET['do']) ? sanitize_key(wp_unslash($_GET['do'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if (empty($action) || empty($id)) {
            return;
        }

        if (!check_admin_referer('iyzico_subscription_action_' . $action . '_' . $id)) {
            return;
        }

        $result = false;
        $success_message = '';
        $error_message = '';

        switch ($action) {
            case 'suspend':
                $result = $this->service->performAction($id, 'suspend');
                $success_message = __('Abonelik askıya alındı.', 'iyzipay-woocommerce-subscription');
                $error_message = __('Abonelik askıya alınamadı.', 'iyzipay-woocommerce-subscription');
                break;

            case 'cancel':
                $result = $this->service->performAction($id, 'cancel');
                $success_message = __('Abonelik iptal edildi.', 'iyzipay-woocommerce-subscription');
                $error_message = __('Abonelik iptal edilemedi.', 'iyzipay-woocommerce-subscription');
                break;

            case 'reactivate':
                $result = $this->service->performAction($id, 'reactivate');
                $success_message = __('Abonelik yeniden aktifleştirildi.', 'iyzipay-woocommerce-subscription');
                $error_message = __('Abonelik yeniden aktifleştirilemedi.', 'iyzipay-woocommerce-subscription');
                break;

            case 'trigger_renewal':
                $subscription = $this->service->getSubscriptionById($id);
                if ($subscription) {
                    $result = $this->renewalService->processSingleRenewal($subscription);
                    $success_message = __('Manuel yenileme ödemesi başarıyla alındı.', 'iyzipay-woocommerce-subscription');
                    $error_message = __('Manuel yenileme ödemesi başarısız oldu. Detaylar için ödeme geçmişine bakınız.', 'iyzipay-woocommerce-subscription');
                }
                break;

            default:
                return;
        }

        $this->add_admin_notice(
            $result ? 'success' : 'error',
            $result ? $success_message : $error_message
        );

        $redirect = $this->build_safe_redirect();
        wp_safe_redirect($redirect);
        exit;
    }

    private function handle_bulk_action(): void
    {
        check_admin_referer('bulk-iyzico_subscriptions');

        $action = !empty($_POST['action']) && $_POST['action'] !== '-1'
            ? sanitize_key(wp_unslash($_POST['action']))
            : sanitize_key(wp_unslash($_POST['action2'] ?? ''));

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $ids = isset($_POST['subscription_ids']) ? array_map('absint', (array) wp_unslash($_POST['subscription_ids'])) : [];

        if (empty($action) || $action === '-1' || empty($ids)) {
            return;
        }

        if (!in_array($action, ['suspend', 'cancel', 'reactivate'], true)) {
            return;
        }

        $success_count = 0;
        foreach ($ids as $id) {
            if ($this->service->performAction((int) $id, $action)) {
                $success_count++;
            }
        }

        if ($success_count > 0) {
            $this->add_admin_notice(
                'success',
                sprintf(
                    /* translators: %d: number of subscriptions affected */
                    _n('%d abonelik üzerinde işlem yapıldı.', '%d abonelik üzerinde işlem yapıldı.', $success_count, 'iyzipay-woocommerce-subscription'),
                    $success_count
                )
            );
        } else {
            $this->add_admin_notice('error', __('Hiçbir abonelik üzerinde işlem yapılamadı.', 'iyzipay-woocommerce-subscription'));
        }

        $redirect = $this->build_safe_redirect();
        wp_safe_redirect($redirect);
        exit;
    }

    private function add_admin_notice(string $type, string $message): void
    {
        $key = 'iyzico_subscription_notice_' . get_current_user_id();
        set_transient($key, ['type' => $type, 'message' => $message], 30);

        add_action('admin_notices', [$this, 'render_flash_notice']);
    }

    public function render_flash_notice(): void
    {
        $key = 'iyzico_subscription_notice_' . get_current_user_id();
        $notice = get_transient($key);
        if (!$notice) {
            return;
        }
        delete_transient($key);

        $class = ($notice['type'] === 'success') ? 'notice-success' : 'notice-error';
        printf(
            '<div class="notice %s is-dismissible"><p>%s</p></div>',
            esc_attr($class),
            esc_html($notice['message'])
        );
    }

    private function build_safe_redirect(): string
    {
        $args = [
            'page' => 'iyzico-subscriptions',
        ];

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!empty($_GET['view']) && $_GET['view'] === 'subscription' && !empty($_GET['id'])) {
            $args['view'] = 'subscription';
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $args['id'] = (int) $_GET['id'];
        }

        foreach (['status', 's', 'date_from', 'date_to', 'paged', 'orderby', 'order'] as $key) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (!empty($_GET[$key])) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $args[$key] = sanitize_text_field(wp_unslash($_GET[$key]));
            }
        }

        return add_query_arg($args, admin_url('admin.php'));
    }
}
