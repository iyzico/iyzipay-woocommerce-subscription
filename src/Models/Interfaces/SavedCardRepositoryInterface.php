<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Models\Interfaces;

defined('ABSPATH') || exit;

interface SavedCardRepositoryInterface
{
    public function getCardUserKey(int $user_id): ?string;

    public function getCardToken(int $user_id): ?string;

    public function save(int $user_id, ?string $card_user_key, ?string $card_token): void;

    public function purgeForUser(int $user_id): void;
}

