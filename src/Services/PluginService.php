<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Services;

defined('ABSPATH') || exit;

use Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces\PluginServiceInterface;
use Iyzico\IyzipayWoocommerceSubscription\Migrations\MigrationManager;

class PluginService implements PluginServiceInterface
{
    public function loadPluginTextdomain(): void
    {
        // WordPress.org üzerinde 4.6+ için otomatik yüklenir; manuel yükleme gereksizdir.
        // Bu metod kasıtlı olarak boş bırakıldı.
    }

    public function createDatabaseTables(): void
    {
        $migration_manager = new MigrationManager();
        $migration_manager->run_migrations();
    }

    public function addIyzicoGateway(array $gateways): array
    {
        $gateways[] = 'Iyzico\IyzipayWoocommerceSubscription\Gateway\IyzicoGateway';
        return $gateways;
    }

    public function addWooCommerceBlocksSupport(): void
    {
        static $blocksSupportHookAdded = false;
        if ($blocksSupportHookAdded) {
            return;
        }
        $blocksSupportHookAdded = true;

        add_action('woocommerce_blocks_payment_method_type_registration', function ($payment_method_registry) {
            if (! class_exists('Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType')) {
                return;
            }
            if (! class_exists('Iyzico\\IyzipayWoocommerceSubscription\\Gateway\\IyzicoBlocksSupport')) {
                require_once plugin_dir_path(__FILE__) . '../Gateway/IyzicoBlocksSupport.php';
            }
            if ($payment_method_registry instanceof \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry) {
                $payment_method_registry->register(new \Iyzico\IyzipayWoocommerceSubscription\Gateway\IyzicoBlocksSupport());
            }
        });
    }
}
