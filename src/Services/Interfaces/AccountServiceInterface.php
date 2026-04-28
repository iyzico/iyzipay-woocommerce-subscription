<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces;

defined('ABSPATH') || exit;

interface AccountServiceInterface
{
    public function addAccountEndpoints(): void;
    public function addAccountMenuItems(array $items): array;
    public function renderSubscriptionsAccountPage(): void;
    public function renderSavedCardsAccountPage(): void;
    public function ajaxListSavedCards(): void;
    public function ajaxDeleteSavedCard(): void;
    public function getStatusLabel(string $status): string;
    public function renderSubscriptionActions(object $subscription): void;
}
