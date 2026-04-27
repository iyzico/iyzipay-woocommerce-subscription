<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces;

defined('ABSPATH') || exit;

interface ProductServiceInterface
{
    public function addSubscriptionProductType(array $types): array;
    public function addSubscriptionProductTab(array $tabs): array;
    public function addSubscriptionProductFields(): void;
    public function saveSubscriptionProductFields(int $post_id): void;
    public function hideGeneralTabForSubscription(array $tabs): array;
    public function addSubscriptionProductJs(): void;
    public function setSubscriptionProductClass(string $classname, string $product_type): string;
    public function setSubscriptionProductType(string $type, int $product_id): string;
}
