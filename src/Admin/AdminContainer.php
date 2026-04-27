<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Admin;

defined('ABSPATH') || exit;

use Iyzico\IyzipayWoocommerceSubscription\Admin\Views\SubscriptionAdminView;
use Iyzico\IyzipayWoocommerceSubscription\Models\SubscriptionFactory;

class AdminContainer
{
    private static array $instances = [];

    public static function get_subscription_admin_controller(): SubscriptionAdminController
    {
        if (!isset(self::$instances['subscription_admin_controller'])) {
            self::$instances['subscription_admin_controller'] = new SubscriptionAdminController(
                self::get_subscription_admin_view(),
                SubscriptionFactory::createSubscriptionAdminService(),
                SubscriptionFactory::createRenewalService()
            );
        }

        return self::$instances['subscription_admin_controller'];
    }

    private static function get_subscription_admin_view(): SubscriptionAdminView
    {
        if (!isset(self::$instances['subscription_admin_view'])) {
            self::$instances['subscription_admin_view'] = new SubscriptionAdminView(
                SubscriptionFactory::createSubscriptionAdminService(),
                SubscriptionFactory::createSubscriptionRepository()
            );
        }

        return self::$instances['subscription_admin_view'];
    }

    public static function clear_instances(): void
    {
        self::$instances = [];
    }
}
