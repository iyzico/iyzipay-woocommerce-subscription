<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Models;

defined('ABSPATH') || exit;

use Iyzico\IyzipayWoocommerceSubscription\Models\Interfaces\SavedCardRepositoryInterface;

class SavedCardRepository implements SavedCardRepositoryInterface
{
    private $wpdb;
    private string $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'iyzico_saved_cards';
    }

    public function getCardUserKey(int $user_id): ?string
    {
        if ($this->tableExists()) {
            $table = esc_sql($this->table_name);
            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $row = $this->wpdb->get_row(
                $this->wpdb->prepare("SELECT card_user_key FROM `{$table}` WHERE user_id = %d ORDER BY id DESC LIMIT 1", $user_id)
            );
            // phpcs:enable
            if ($row && !empty($row->card_user_key)) {
                return (string) $row->card_user_key;
            }
        }

        $meta = get_user_meta($user_id, '_iyzico_card_user_key', true);
        return $meta !== '' ? (string) $meta : null;
    }

    public function getCardToken(int $user_id): ?string
    {
        if ($this->tableExists()) {
            $table = esc_sql($this->table_name);
            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $row = $this->wpdb->get_row(
                $this->wpdb->prepare("SELECT card_token FROM `{$table}` WHERE user_id = %d ORDER BY id DESC LIMIT 1", $user_id)
            );
            // phpcs:enable
            if ($row && !empty($row->card_token)) {
                return (string) $row->card_token;
            }
        }

        $meta = get_user_meta($user_id, '_iyzico_card_token', true);
        return $meta !== '' ? (string) $meta : null;
    }

    public function save(int $user_id, ?string $card_user_key, ?string $card_token): void
    {
        if (empty($user_id) || (empty($card_user_key) && empty($card_token))) {
            return;
        }

        if (!$this->tableExists()) {
            if (!empty($card_user_key)) {
                update_user_meta($user_id, '_iyzico_card_user_key', $card_user_key);
            }
            if (!empty($card_token)) {
                update_user_meta($user_id, '_iyzico_card_token', $card_token);
            }
            return;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $this->wpdb->insert(
            $this->table_name,
            [
                'user_id' => $user_id,
                'card_user_key' => (string) $card_user_key,
                'card_token' => (string) $card_token,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );
    }

    public function purgeForUser(int $user_id): void
    {
        delete_user_meta($user_id, '_iyzico_card_user_key');
        delete_user_meta($user_id, '_iyzico_card_token');

        if ($this->tableExists()) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $this->wpdb->delete($this->table_name, ['user_id' => $user_id]);
        }
    }

    private function tableExists(): bool
    {
        $sql = $this->wpdb->prepare('SHOW TABLES LIKE %s', $this->table_name);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
        $exists = $this->wpdb->get_var($sql);
        return !empty($exists);
    }
}
