<?php
/**
 * ACP Configuration Management
 * Professional configuration handling for production environments
 * 
 * @link https://github.com/agentic-commerce-protocol/agentic-commerce-protocol
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ACP Configuration Class
 * 
 * Manages plugin configuration with proper validation,
 * defaults, and production-ready settings.
 * 
 * @since 1.0.0
 * @package WooCommerce_ACP
 */
class ACP_Config {
    
    /**
     * Default configuration
     * 
     * @var array
     */
    private $defaults = array(
        'api_key' => '',
        'webhook_secret' => '',
        'sandbox_mode' => true,
        'enable_feed' => true,
        'enable_webhooks' => true,
        'webhook_url' => '',
        'enable_signature_validation' => true,
        'timestamp_tolerance' => 300,
        'redis_enabled' => false,
        'redis_host' => 'localhost',
        'redis_port' => 6379,
        'redis_password' => '',
        'redis_database' => 0,
        'feed_max_products' => 1000,
        'feed_categories' => '',
        'session_retention_days' => 30,
        'webhook_retention_days' => 30,
        'log_level' => 'info',
        'enable_debug' => false
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init_config'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Initialize configuration
     */
    public function init_config(): void {
        $this->set_defaults();
        $this->validate_config();
    }
    
    /**
     * Set default configuration
     */
    private function set_defaults(): void {
        foreach ($this->defaults as $key => $value) {
            if (get_option("acp_$key") === false) {
                update_option("acp_$key", $value);
            }
        }
    }
    
    /**
     * Validate configuration
     */
    private function validate_config(): void {
        $required_settings = array('api_key', 'webhook_secret');
        
        foreach ($required_settings as $setting) {
            $value = get_option("acp_$setting");
            if (empty($value)) {
                $this->log_config_error("Required setting 'acp_$setting' is empty");
            }
        }
    }
    
    /**
     * Register settings
     */
    public function register_settings(): void {
        // General Settings
        register_setting('acp_settings', 'acp_api_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('acp_settings', 'acp_webhook_secret', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('acp_settings', 'acp_sandbox_mode', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ));
        
        // Security Settings
        register_setting('acp_settings', 'acp_enable_signature_validation', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ));
        
        register_setting('acp_settings', 'acp_timestamp_tolerance', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 300
        ));
        
        // Redis Settings
        register_setting('acp_settings', 'acp_redis_enabled', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ));
        
        register_setting('acp_settings', 'acp_redis_host', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'localhost'
        ));
        
        register_setting('acp_settings', 'acp_redis_port', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 6379
        ));
        
        register_setting('acp_settings', 'acp_redis_password', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('acp_settings', 'acp_redis_database', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 0
        ));
        
        // Feed Settings
        register_setting('acp_settings', 'acp_feed_max_products', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 1000
        ));
        
        register_setting('acp_settings', 'acp_feed_categories', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        // Retention Settings
        register_setting('acp_settings', 'acp_session_retention_days', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 30
        ));
        
        register_setting('acp_settings', 'acp_webhook_retention_days', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 30
        ));
        
        // Logging Settings
        register_setting('acp_settings', 'acp_log_level', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'info'
        ));
        
        register_setting('acp_settings', 'acp_enable_debug', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ));
    }
    
    /**
     * Get configuration value
     */
    public function get(string $key, $default = null) {
        $value = get_option("acp_$key", $default);
        
        // Return default if value is empty and default exists
        if (empty($value) && isset($this->defaults[$key])) {
            return $this->defaults[$key];
        }
        
        return $value;
    }
    
    /**
     * Set configuration value
     */
    public function set(string $key, $value): bool {
        return update_option("acp_$key", $value);
    }
    
    /**
     * Get all configuration
     */
    public function get_all(): array {
        $config = array();
        
        foreach ($this->defaults as $key => $default) {
            $config[$key] = $this->get($key, $default);
        }
        
        return $config;
    }
    
    /**
     * Validate configuration value
     */
    public function validate(string $key, $value): bool {
        switch ($key) {
            case 'api_key':
                return !empty($value) && is_string($value) && strlen($value) >= 32;
            
            case 'webhook_secret':
                return !empty($value) && is_string($value) && strlen($value) >= 32;
            
            case 'timestamp_tolerance':
                return is_numeric($value) && $value >= 60 && $value <= 3600;
            
            case 'redis_port':
                return is_numeric($value) && $value >= 1 && $value <= 65535;
            
            case 'redis_database':
                return is_numeric($value) && $value >= 0 && $value <= 15;
            
            case 'feed_max_products':
                return is_numeric($value) && $value >= 1 && $value <= 10000;
            
            case 'session_retention_days':
            case 'webhook_retention_days':
                return is_numeric($value) && $value >= 1 && $value <= 365;
            
            case 'log_level':
                return in_array($value, array('debug', 'info', 'warning', 'error'));
            
            default:
                return true;
        }
    }
    
    /**
     * Log configuration error
     */
    private function log_config_error(string $message): void {
        $logger = new ACP_Logger();
        $logger->error("Configuration Error: $message");
    }
    
    /**
     * Get production-ready configuration
     */
    public function get_production_config(): array {
        return array(
            'api_key' => $this->get('api_key'),
            'webhook_secret' => $this->get('webhook_secret'),
            'sandbox_mode' => false,
            'enable_signature_validation' => true,
            'timestamp_tolerance' => 300,
            'redis_enabled' => true,
            'log_level' => 'warning',
            'enable_debug' => false
        );
    }
    
    /**
     * Check if configuration is production-ready
     */
    public function is_production_ready(): bool {
        $required_settings = array('api_key', 'webhook_secret');
        
        foreach ($required_settings as $setting) {
            if (empty($this->get($setting))) {
                return false;
            }
        }
        
        return true;
    }
}
