<?php

defined('ABSPATH') || exit;

/**
 * Plugin bootstrap dosyası
 *
 * @param string $root_dir Plugin kök dizini
 * @return object Container objesi
 */
return function ($root_dir) {
    $container = new stdClass();
    
    $plugin_data = get_file_data(__DIR__ . '/iyzico-subscription.php', [
        'Version' => 'Version'
    ]);
    $version = $plugin_data['Version'] ?? '1.0.0';
    
    $container->{'iyzico-subscription.plugin'} = new class($version) {
        private $version;
        
        public function __construct($version) {
            $this->version = $version;
        }
        
        public function getVersion() {
            return $this->version;
        }
    };
    
    $container->{'iyzico-subscription.root_dir'} = $root_dir;
    
    return $container;
};
