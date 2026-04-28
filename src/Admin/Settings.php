<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Admin;

defined('ABSPATH') || exit;

/**
 * Plugin ayarları için sınıf
 */
class Settings {
    /**
     * Bağlantı sekmesi ID'si
     */
    public const CONNECTION_TAB_ID = 'connection';
    
    /**
     * Genel sekmesi ID'si
     */
    public const GENERAL_TAB_ID = 'general';
    
    /**
     * Gelişmiş sekmesi ID'si
     */
    public const ADVANCED_TAB_ID = 'advanced';
    
    /**
     * Ayarları al
     */
    public static function get($key, $default = null) {
        return get_option('iyzico_subscription_' . $key, $default);
    }
    
    /**
     * Ayarı kaydet
     */
    public static function set($key, $value) {
        return update_option('iyzico_subscription_' . $key, $value);
    }
    
    /**
     * Ayarı sil
     */
    public static function delete($key) {
        return delete_option('iyzico_subscription_' . $key);
    }
}
