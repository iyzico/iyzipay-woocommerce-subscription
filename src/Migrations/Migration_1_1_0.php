<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Migrations;

defined('ABSPATH') || exit;

/**
 * Ek migration - v1.1.0
 * Ödeme log tablosu ve isteğe bağlı saklı kartlar tablosu oluşturur
 */
class Migration_1_1_0 implements MigrationInterface {
    public function up(): void {
        $this->create_payments_table();
        $this->create_saved_cards_table();
    }

    public function down(): void {
        global $wpdb;
        $payments = esc_sql($wpdb->prefix . 'iyzico_subscription_payments');
        $cards = esc_sql($wpdb->prefix . 'iyzico_saved_cards');
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
        $wpdb->query("DROP TABLE IF EXISTS `{$payments}`");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
        $wpdb->query("DROP TABLE IF EXISTS `{$cards}`");
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
    private function create_payments_table(): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'iyzico_subscription_payments';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            subscription_id BIGINT(20) UNSIGNED NOT NULL,
            order_id BIGINT(20) UNSIGNED NULL,
            iyzico_payment_id VARCHAR(255) NULL,
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) NOT NULL DEFAULT 'TRY',
            status VARCHAR(20) NOT NULL,
            error_code VARCHAR(50) NULL,
            error_message TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY subscription_id (subscription_id),
            KEY status (status)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        dbDelta($sql);
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
    private function create_saved_cards_table(): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'iyzico_saved_cards';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            card_user_key VARCHAR(255) NOT NULL,
            card_token VARCHAR(255) NOT NULL,
            card_alias VARCHAR(255) NULL,
            bin_number VARCHAR(10) NULL,
            last_four VARCHAR(4) NULL,
            card_type VARCHAR(30) NULL,
            card_association VARCHAR(30) NULL,
            card_family VARCHAR(60) NULL,
            card_bank_name VARCHAR(100) NULL,
            card_bank_code INT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_token (user_id, card_token),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        dbDelta($sql);
    }
}

