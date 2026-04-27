<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Migrations;

defined('ABSPATH') || exit;

/**
 * İlk migration - v1.0.0
 * Temel tabloları oluşturur
 */
class Migration_1_0_0 implements MigrationInterface {
    
    public function up(): void {
        $this->create_subscriptions_table();
        $this->create_notifications_table();
    }

    public function down(): void {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
        $wpdb->query('DROP TABLE IF EXISTS `' . esc_sql($wpdb->prefix . 'iyzico_subscriptions') . '`');
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
        $wpdb->query('DROP TABLE IF EXISTS `' . esc_sql($wpdb->prefix . 'iyzico_subscription_notifications') . '`');
    }

    /**
     * Abonelikler tablosunu oluştur
     */
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
    private function create_subscriptions_table(): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'iyzico_subscriptions';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            order_id bigint(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            iyzico_subscription_id varchar(255) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            amount decimal(10,2) NOT NULL,
            currency varchar(3) NOT NULL DEFAULT 'TRY',
            period varchar(10) NOT NULL,
            period_interval int(11) NOT NULL DEFAULT 1,
            start_date datetime NOT NULL,
            next_payment datetime NOT NULL,
            end_date datetime NULL,
            trial_end_date datetime NULL,
            payment_method varchar(50) NOT NULL,
            billing_cycles int(11) DEFAULT 0,
            completed_cycles int(11) DEFAULT 0,
            failed_payments int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY next_payment (next_payment)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        dbDelta($sql);
    }

    /**
     * Bildirimler tablosunu oluştur
     */
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
    private function create_notifications_table(): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'iyzico_subscription_notifications';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            subscription_id mediumint(9) NOT NULL,
            notification_type varchar(50) NOT NULL,
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY subscription_id (subscription_id),
            KEY notification_type (notification_type)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        dbDelta($sql);
    }
}
