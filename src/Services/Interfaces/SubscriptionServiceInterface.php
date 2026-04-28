<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces;

defined('ABSPATH') || exit;

interface SubscriptionServiceInterface
{
    public function createSubscription(array $data): ?int;
    public function processRenewal(int $subscription_id): bool;
    public function handleFailedPayment(int $subscription_id): bool;
    public function cancelSubscription(int $subscription_id, string $reason = ''): bool;
    public function getActiveSubscriptionsForUser(int $user_id): array;
    public function getDueRenewals(): array;
    public function getSubscriptionAnalytics(string $start_date, string $end_date): array;
}
