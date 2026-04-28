<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Models\Interfaces;

defined('ABSPATH') || exit;

interface SubscriptionValidatorInterface
{
    public function validateCreateData(array $data): array;
    public function validateUpdateData(array $data): array;
    public function validateStatus(string $status): bool;
    public function validatePeriod(string $period): bool;
}
