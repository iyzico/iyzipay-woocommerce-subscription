<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Models\Interfaces;

defined('ABSPATH') || exit;

interface SubscriptionRepositoryInterface
{
    public function create(array $data): ?int;
    public function update(int $id, array $data): bool;
    public function find(int $id): ?object;
    public function findByUser(int $user_id): array;
    public function findAll(): array;
    public function findByStatus(string $status): array;
    public function findByFilters(array $filters): array;
    public function countByFilters(array $filters): int;
    public function findDueRenewals(): array;
    public function updateStatus(int $id, string $status): bool;
    public function suspend(int $id): bool;
    public function cancel(int $id): bool;
    public function reactivate(int $id): bool;
    public function incrementFailedPayments(int $id): bool;
    public function processSuccessfulPayment(int $id): bool;
    public function getSubscriptionStats(): object;
    public function getRevenueByPeriod(string $start_date, string $end_date): array;
}
