<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Services;

defined('ABSPATH') || exit;

use Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces\SubscriptionServiceInterface;
use Iyzico\IyzipayWoocommerceSubscription\Models\Interfaces\SubscriptionRepositoryInterface;
use Iyzico\IyzipayWoocommerceSubscription\Models\Interfaces\SubscriptionCalculatorInterface;

class SubscriptionService implements SubscriptionServiceInterface
{
    private $repository;
    private $calculator;

    public function __construct(
        SubscriptionRepositoryInterface $repository,
        SubscriptionCalculatorInterface $calculator
    ) {
        $this->repository = $repository;
        $this->calculator = $calculator;
    }

    public function createSubscription(array $data): ?int
    {
        if (isset($data['trial_days']) && $data['trial_days'] > 0) {
            $data['trial_end_date'] = $this->calculator->calculateTrialEndDate(
                $data['start_date'], 
                $data['trial_days']
            );
        }

        if (isset($data['billing_cycles']) && $data['billing_cycles'] > 0) {
            $data['end_date'] = $this->calculator->calculateEndDate(
                $data['start_date'],
                $data['period'],
                $data['period_interval'] ?? 1,
                $data['billing_cycles']
            );
        }

        return $this->repository->create($data);
    }

    public function processRenewal(int $subscription_id): bool
    {
        $subscription = $this->repository->find($subscription_id);
        
        if (!$subscription || $subscription->status !== 'active') {
            return false;
        }

        return $this->repository->processSuccessfulPayment($subscription_id);
    }

    public function handleFailedPayment(int $subscription_id): bool
    {
        $subscription = $this->repository->find($subscription_id);
        
        if (!$subscription) {
            return false;
        }

        $result = $this->repository->incrementFailedPayments($subscription_id);
        
        if ($result && $subscription->failed_payments >= 2) {
            $this->sendFailedPaymentNotification($subscription);
        }
        
        return $result;
    }

    public function cancelSubscription(int $subscription_id, string $reason = ''): bool
    {
        $subscription = $this->repository->find($subscription_id);
        
        if (!$subscription) {
            return false;
        }

        $result = $this->repository->cancel($subscription_id);
        
        if ($result) {
            $this->sendCancellationNotification($subscription, $reason);
        }
        
        return $result;
    }

    public function getActiveSubscriptionsForUser(int $user_id): array
    {
        return $this->repository->findByUser($user_id);
    }

    public function getDueRenewals(): array
    {
        return $this->repository->findDueRenewals();
    }

    public function getSubscriptionAnalytics(string $start_date, string $end_date): array
    {
        $stats = $this->repository->getSubscriptionStats();
        $revenue = $this->repository->getRevenueByPeriod($start_date, $end_date);
        
        return [
            'stats' => $stats,
            'revenue' => $revenue,
            'period' => [
                'start' => $start_date,
                'end' => $end_date
            ]
        ];
    }

    private function sendFailedPaymentNotification(object $subscription): void
    {
        do_action('iyzico_subscription_failed_payment_notification', $subscription);
    }

    private function sendCancellationNotification(object $subscription, string $reason): void
    {
        do_action('iyzico_subscription_cancellation_notification', $subscription, $reason);
    }
}
