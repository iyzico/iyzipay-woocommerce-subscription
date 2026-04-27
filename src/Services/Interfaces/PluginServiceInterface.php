<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces;

defined('ABSPATH') || exit;

interface PluginServiceInterface
{
    public function loadPluginTextdomain(): void;
    public function createDatabaseTables(): void;
    public function addIyzicoGateway(array $gateways): array;
    public function addWooCommerceBlocksSupport(): void;
}
