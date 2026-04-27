<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces;

defined('ABSPATH') || exit;

interface RenewalServiceInterface
{
    public function scheduleRenewalCheck(): void;
    public function processRenewals(): void;
    public function processSingleRenewal(object $subscription): bool;
    public function retryFailedPayments(): void;
    public function cancelSubscription(int $subscription_id): bool;
    public function suspendSubscription(int $subscription_id): bool;
    public function reactivateSubscription(int $subscription_id): bool;
}
