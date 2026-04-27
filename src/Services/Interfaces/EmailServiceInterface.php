<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces;

defined('ABSPATH') || exit;

interface EmailServiceInterface
{
    public function sendSubscriptionCreatedEmail(object $subscription): void;
    public function sendRenewalSuccessEmail(object $subscription): void;
    public function sendRenewalFailedEmail(object $subscription, string $error): void;
    public function sendCancellationEmail(object $subscription): void;
    public function sendSuspensionEmail(object $subscription): void;
    public function sendExpiringEmail(object $subscription): void;
    public function sendAdminNotification(string $type, object $subscription, ?string $extra_data = null): void;
    public function checkExpiringSubscriptions(): void;
}
