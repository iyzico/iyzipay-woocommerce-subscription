<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Models;

defined('ABSPATH') || exit;

use Iyzico\IyzipayWoocommerceSubscription\Models\Interfaces\SubscriptionValidatorInterface;

class SubscriptionValidator implements SubscriptionValidatorInterface
{
    private const VALID_STATUSES = ['pending', 'active', 'suspended', 'cancelled', 'completed'];
    private const VALID_PERIODS = ['day', 'week', 'month', 'year'];

    public function validateCreateData(array $data): array
    {
        $errors = [];

        if (!isset($data['user_id']) || !is_numeric($data['user_id'])) {
            $errors[] = 'user_id is required and must be numeric';
        }

        if (empty($data['order_id']) || !is_numeric($data['order_id'])) {
            $errors[] = 'order_id is required and must be numeric';
        }

        if (empty($data['product_id']) || !is_numeric($data['product_id'])) {
            $errors[] = 'product_id is required and must be numeric';
        }

        if (empty($data['iyzico_subscription_id'])) {
            $errors[] = 'iyzico_subscription_id is required';
        }

        if (!isset($data['amount']) || !is_numeric($data['amount']) || (float) $data['amount'] < 0) {
            $errors[] = 'amount is required and must be numeric';
        }

        if (empty($data['period']) || !$this->validatePeriod($data['period'])) {
            $errors[] = 'period is required and must be valid';
        }

        if (empty($data['start_date'])) {
            $errors[] = 'start_date is required';
        }

        if (empty($data['next_payment'])) {
            $errors[] = 'next_payment is required';
        }

        if (empty($data['payment_method'])) {
            $errors[] = 'payment_method is required';
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }

        return $data;
    }

    public function validateUpdateData(array $data): array
    {
        $errors = [];
        
        if (isset($data['status']) && !$this->validateStatus($data['status'])) {
            $errors[] = 'Invalid status provided';
        }
        
        if (isset($data['period']) && !$this->validatePeriod($data['period'])) {
            $errors[] = 'Invalid period provided';
        }
        
        if (isset($data['amount']) && !is_numeric($data['amount'])) {
            $errors[] = 'amount must be numeric';
        }
        
        if (!empty($errors)) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new \InvalidArgumentException(implode(', ', $errors));
        }
        
        return $data;
    }

    public function validateStatus(string $status): bool
    {
        return in_array($status, self::VALID_STATUSES);
    }

    public function validatePeriod(string $period): bool
    {
        return in_array($period, self::VALID_PERIODS);
    }
}
