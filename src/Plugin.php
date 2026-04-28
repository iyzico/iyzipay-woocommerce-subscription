<?php

namespace Iyzico\IyzipayWoocommerceSubscription;

defined('ABSPATH') || exit;

use Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces\HookServiceInterface;

class Plugin {
    private static $instance = null;
    private static $container = null;
    
    private HookServiceInterface $hookService;

    public function __construct() {
        if (self::$instance !== null) {
            throw new \Exception('Plugin sınıfı singleton olmalıdır.');
        }
        self::$instance = $this;
    }

    public static function init($container = null): void
    {
        if ($container) {
            self::$container = $container;
        }
        
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        self::$instance->initInstance();
    }

    public static function container()
    {
        return self::$container;
    }

    public function initInstance(): void
    {
        $this->initHookService();

        $this->hookService->registerPluginHooks();
        $this->hookService->registerProductHooks();
        $this->hookService->registerTemplateHooks();
        $this->hookService->registerAccountHooks();
        $this->hookService->registerPaymentHooks();
        $this->hookService->registerAdminHooks();
        $this->hookService->registerAjaxHooks();

        \Iyzico\IyzipayWoocommerceSubscription\Models\SubscriptionFactory::createPrivacyService()->register();
        
        \Iyzico\IyzipayWoocommerceSubscription\Models\SubscriptionFactory::createCheckoutFieldService()->register();
    }

    private function initHookService(): void
    {
        $this->hookService = \Iyzico\IyzipayWoocommerceSubscription\Models\SubscriptionFactory::createHookService();
    }
} 
