<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Migrations;

defined('ABSPATH') || exit;

/**
 * Migration yöneticisi sınıfı
 * WordPress standartlarına uygun versiyon bazlı migration sistemi
 */
class MigrationManager {
    private $migrations = [];
    private $current_version;
    private $option_name = 'iyzico-subscription-db-version';

    public function __construct() {
        $this->current_version = $this->get_plugin_version();
        $this->register_migrations();
    }

    /**
     * Plugin versiyonunu al
     */
    private function get_plugin_version(): string {
        $plugin_data = get_file_data(plugin_dir_path(dirname(dirname(__FILE__))) . 'iyzico-subscription.php', [
            'Version' => 'Version'
        ]);
        return $plugin_data['Version'] ?? '1.0.0';
    }

    /**
     * Migration'ları kaydet
     */
    private function register_migrations(): void {
        $this->migrations = [
            '1.0.0' => new Migration_1_0_0(),
            '1.0.1' => new Migration_1_0_1(),
            '1.1.0' => new Migration_1_1_0(),
        ];
    }

    /**
     * Migration'ları çalıştır
     */
    public function run_migrations(): void {
        $installed_version = get_option($this->option_name, '0.0.0');
        
        if (version_compare($installed_version, $this->current_version, '<')) {
            $this->execute_migrations($installed_version, $this->current_version);
            update_option($this->option_name, $this->current_version);
        }
    }

    /**
     * Belirli versiyon aralığındaki migration'ları çalıştır
     */
    private function execute_migrations(string $from_version, string $to_version): void {
        foreach ($this->migrations as $version => $migration) {
            if (version_compare($from_version, $version, '<') && 
                version_compare($version, $to_version, '<=')) {
                
                try {
                    $migration->up();
                    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                        error_log("iyzico-subscription: Migration {$version} başarıyla çalıştırıldı");
                    }
                } catch (\Exception $e) {
                    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                        error_log("iyzico-subscription: Migration {$version} hatası: " . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Migration durumunu kontrol et
     */
    public function needs_migration(): bool {
        $installed_version = get_option($this->option_name, '0.0.0');
        return version_compare($installed_version, $this->current_version, '<');
    }

    /**
     * Mevcut veritabanı versiyonunu al
     */
    public function get_installed_version(): string {
        return get_option($this->option_name, '0.0.0');
    }
}
