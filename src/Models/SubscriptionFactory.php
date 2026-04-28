<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Models;

defined('ABSPATH') || exit;

use Iyzico\IyzipayWoocommerceSubscription\Services\SubscriptionService;
use Iyzico\IyzipayWoocommerceSubscription\Services\TemplateService;
use Iyzico\IyzipayWoocommerceSubscription\Services\EmailService;
use Iyzico\IyzipayWoocommerceSubscription\Services\RenewalService;
use Iyzico\IyzipayWoocommerceSubscription\Services\SubscriptionAdminService;
use Iyzico\IyzipayWoocommerceSubscription\Services\ProductService;
use Iyzico\IyzipayWoocommerceSubscription\Services\AccountService;
use Iyzico\IyzipayWoocommerceSubscription\Services\PluginService;
use Iyzico\IyzipayWoocommerceSubscription\Services\HookService;
use Iyzico\IyzipayWoocommerceSubscription\Services\PrivacyService;
use Iyzico\IyzipayWoocommerceSubscription\Services\CheckoutFieldService;
use Iyzico\IyzipayWoocommerceSubscription\Gateway\IyzicoGateway;
use Iyzico\IyzipayWoocommerceSubscription\Models\SavedCardRepository;

/**
 * Service container — singleton instance'ları tutar.
 *
 * Önemli: RenewalService ve EmailService constructor'ları add_action çağırıyor.
 * Her çağrıda yeni instance üretirsek hook'lar duplicate kayıt edilir.
 * Bu sınıf tüm servisleri lazy-singleton olarak yönetir.
 */
class SubscriptionFactory
{
    /** @var array<string, object> */
    private static array $instances = [];

    public static function reset(): void
    {
        self::$instances = [];
    }

    public static function createSubscriptionValidator(): SubscriptionValidator
    {
        return self::$instances['validator'] ??= new SubscriptionValidator();
    }

    public static function createSubscriptionCalculator(): SubscriptionCalculator
    {
        return self::$instances['calculator'] ??= new SubscriptionCalculator();
    }

    public static function createSubscriptionRepository(): SubscriptionRepository
    {
        return self::$instances['repository'] ??= new SubscriptionRepository(
            self::createSubscriptionValidator(),
            self::createSubscriptionCalculator()
        );
    }

    public static function createSavedCardRepository(): SavedCardRepository
    {
        return self::$instances['saved_card_repository'] ??= new SavedCardRepository();
    }

    public static function createSubscriptionService(): SubscriptionService
    {
        return self::$instances['subscription_service'] ??= new SubscriptionService(
            self::createSubscriptionRepository(),
            self::createSubscriptionCalculator()
        );
    }

    public static function createTemplateService(): TemplateService
    {
        return self::$instances['template_service'] ??= new TemplateService();
    }

    public static function createEmailTemplateService(): TemplateService
    {
        return self::createTemplateService();
    }

    public static function createEmailService(): EmailService
    {
        return self::$instances['email_service'] ??= new EmailService(self::createTemplateService());
    }

    public static function createIyzicoGateway(): IyzicoGateway
    {
        return self::$instances['iyzico_gateway'] ??= new IyzicoGateway();
    }

    public static function createRenewalService(): RenewalService
    {
        return self::$instances['renewal_service'] ??= new RenewalService(
            self::createSubscriptionRepository(),
            self::createIyzicoGateway(),
            self::createEmailService(),
            self::createSavedCardRepository()
        );
    }

    public static function createSubscriptionAdminService(): SubscriptionAdminService
    {
        return self::$instances['subscription_admin_service'] ??= new SubscriptionAdminService(
            self::createSubscriptionRepository()
        );
    }

    public static function createProductService(): ProductService
    {
        return self::$instances['product_service'] ??= new ProductService();
    }

    public static function createAccountService(): AccountService
    {
        return self::$instances['account_service'] ??= new AccountService(
            self::createSubscriptionRepository()
        );
    }

    public static function createPluginService(): PluginService
    {
        return self::$instances['plugin_service'] ??= new PluginService();
    }

    public static function createHookService(): HookService
    {
        return self::$instances['hook_service'] ??= new HookService(
            self::createProductService(),
            self::createTemplateService(),
            self::createAccountService(),
            self::createPluginService(),
            self::createRenewalService()
        );
    }

    public static function createPrivacyService(): PrivacyService
    {
        return self::$instances['privacy_service'] ??= new PrivacyService(
            self::createSubscriptionRepository(),
            self::createSavedCardRepository()
        );
    }

    public static function createCheckoutFieldService(): CheckoutFieldService
    {
        return self::$instances['checkout_field_service'] ??= new CheckoutFieldService();
    }
}
