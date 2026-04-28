<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces;

defined('ABSPATH') || exit;

interface TemplateServiceInterface
{
    public function loadTemplate(string $template_name, array $data): string;
    public function getFallbackTemplate(string $template_name, array $data): string;
    public function getPeriodLabel(string $period): string;
    public function getDaysUntilExpiry(string $end_date): int;
    public function loadSubscriptionTemplate(string $template, string $template_name, array $args, string $template_path, string $default_path): string;
    public function locateSubscriptionTemplate(string $template, string $template_name, string $template_path): string;
    public function displaySubscriptionAddToCart(): void;
}
