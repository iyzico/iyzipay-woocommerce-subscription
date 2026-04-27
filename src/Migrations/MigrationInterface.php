<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Migrations;

defined('ABSPATH') || exit;

/**
 * Migration interface'i
 * Tüm migration sınıfları bu interface'i implement etmeli
 */
interface MigrationInterface {
    
    /**
     * Migration'ı çalıştır (yukarı)
     */
    public function up(): void;
    
    /**
     * Migration'ı geri al (aşağı)
     */
    public function down(): void;
}
