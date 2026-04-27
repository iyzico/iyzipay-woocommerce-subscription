<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Models;

defined('ABSPATH') || exit;

use Iyzico\IyzipayWoocommerceSubscription\Models\Interfaces\SubscriptionCalculatorInterface;

class SubscriptionCalculator implements SubscriptionCalculatorInterface
{
    public function calculateNextPayment(object $subscription): string
    {
        $current_date = new \DateTime($subscription->next_payment);
        
        switch ($subscription->period) {
            case 'day':
                $current_date->add(new \DateInterval('P' . $subscription->period_interval . 'D'));
                break;
            case 'week':
                $current_date->add(new \DateInterval('P' . ($subscription->period_interval * 7) . 'D'));
                break;
            case 'month':
                $current_date->add(new \DateInterval('P' . $subscription->period_interval . 'M'));
                break;
            case 'year':
                $current_date->add(new \DateInterval('P' . $subscription->period_interval . 'Y'));
                break;
            default:
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new \InvalidArgumentException('Invalid period: ' . $subscription->period);
        }

        return $current_date->format('Y-m-d H:i:s');
    }

    public function calculateTrialEndDate(string $start_date, int $trial_days): string
    {
        $date = new \DateTime($start_date);
        $date->add(new \DateInterval('P' . $trial_days . 'D'));
        return $date->format('Y-m-d H:i:s');
    }

    public function calculateEndDate(string $start_date, string $period, int $period_interval, int $billing_cycles): ?string
    {
        if ($billing_cycles <= 0) {
            return null;
        }

        $date = new \DateTime($start_date);
        
        switch ($period) {
            case 'day':
                $date->add(new \DateInterval('P' . ($period_interval * $billing_cycles) . 'D'));
                break;
            case 'week':
                $date->add(new \DateInterval('P' . ($period_interval * $billing_cycles * 7) . 'D'));
                break;
            case 'month':
                $date->add(new \DateInterval('P' . ($period_interval * $billing_cycles) . 'M'));
                break;
            case 'year':
                $date->add(new \DateInterval('P' . ($period_interval * $billing_cycles) . 'Y'));
                break;
            default:
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new \InvalidArgumentException('Invalid period: ' . $period);
        }

        return $date->format('Y-m-d H:i:s');
    }
}
