<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Migrations;

defined('ABSPATH') || exit;

/**
 * Migration v1.0.1
 * iyzico hesap eşleştirme için kullanıcı meta alanları (TC kimlik no, son IP) için
 * iyzico_subscriptions tablosuna `notes` kolonu eklenir.
 */
class Migration_1_0_1 implements MigrationInterface {

    public function up(): void {
        global $wpdb;
        $table_name = esc_sql($wpdb->prefix . 'iyzico_subscriptions');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM `{$table_name}` LIKE 'notes'");
        if (empty($column_exists)) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->query("ALTER TABLE `{$table_name}` ADD COLUMN notes TEXT NULL AFTER failed_payments");
        }
    }

    public function down(): void {
        global $wpdb;
        $table_name = esc_sql($wpdb->prefix . 'iyzico_subscriptions');
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
        $wpdb->query("ALTER TABLE `{$table_name}` DROP COLUMN IF EXISTS notes");
    }
}
