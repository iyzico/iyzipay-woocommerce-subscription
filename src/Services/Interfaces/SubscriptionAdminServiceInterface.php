<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces;

defined('ABSPATH') || exit;

interface SubscriptionAdminServiceInterface
{
    public function performAction(int $subscription_id, string $action): bool;
    public function getSubscriptionsData(array $filters = []): array;
    public function getSubscriptionById(int $subscription_id): ?object;
}
