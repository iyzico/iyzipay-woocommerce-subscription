<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Models\Interfaces;

defined('ABSPATH') || exit;

interface SubscriptionCalculatorInterface
{
    public function calculateNextPayment(object $subscription): string;
    public function calculateTrialEndDate(string $start_date, int $trial_days): string;
    public function calculateEndDate(string $start_date, string $period, int $period_interval, int $billing_cycles): ?string;
}
