<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Services;

defined('ABSPATH') || exit;

use Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces\SubscriptionAdminServiceInterface;
use Iyzico\IyzipayWoocommerceSubscription\Models\Interfaces\SubscriptionRepositoryInterface;

class SubscriptionAdminService implements SubscriptionAdminServiceInterface
{
    private SubscriptionRepositoryInterface $subscriptionRepository;

    public function __construct(SubscriptionRepositoryInterface $subscriptionRepository) {
        $this->subscriptionRepository = $subscriptionRepository;
    }

    public function performAction(int $subscription_id, string $action): bool {
        $subscription = $this->subscriptionRepository->find($subscription_id);
        if (!$subscription) {
            return false;
        }

        switch ($action) {
            case 'suspend':
                return $this->subscriptionRepository->updateStatus($subscription_id, 'suspended');
            case 'cancel':
                return $this->subscriptionRepository->updateStatus($subscription_id, 'cancelled');
            case 'reactivate':
                return $this->subscriptionRepository->updateStatus($subscription_id, 'active');
            default:
                return false;
        }
    }

    public function getSubscriptionsData(array $filters = []): array {
        $hasFilters = false;
        foreach (['status', 'customer_search', 'date_from', 'date_to'] as $key) {
            if (!empty($filters[$key])) { $hasFilters = true; break; }
        }

        $subscriptions = $hasFilters
            ? $this->subscriptionRepository->findByFilters($filters)
            : $this->subscriptionRepository->findAll();
        $stats = $this->subscriptionRepository->getSubscriptionStats();

        return [
            'subscriptions' => $subscriptions,
            'stats' => $stats,
            'filters' => $filters
        ];
    }

    public function getSubscriptionById(int $subscription_id): ?object {
        return $this->subscriptionRepository->find($subscription_id);
    }
}
