<?php
/**
 * Plugin Name: ACP for WooCommerce
 * Plugin URI: https://shora.co
 * Description: Commerce infra for the AI era - Agentic Commerce Protocol integration for WooCommerce
 * Version: 1.0.0
 * Author: Shora
 * License: MIT
 * Text Domain: woocommerce-acp
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.6
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 * Requires PHP: 8.0
 * Network: false
 * Update URI: https://github.com/shoraco/-ACP-Agentic-Commerce-Protocol-for-Woocommerce
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WOOCOMMERCE_ACP_VERSION', '1.0.0');
define('WOOCOMMERCE_ACP_PLUGIN_FILE', __FILE__);
define('WOOCOMMERCE_ACP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WOOCOMMERCE_ACP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Check if WooCommerce is active
if (!class_exists('WooCommerce')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>ACP for WooCommerce</strong> requires WooCommerce 8.0 or higher to be installed and active.</p></div>';
    });
    return;
}

// Check WooCommerce version
if (defined('WC_VERSION') && version_compare(WC_VERSION, '8.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>ACP for WooCommerce</strong> requires WooCommerce 8.0 or higher. Current version: ' . WC_VERSION . '</p></div>';
    });
    return;
}

// Check PHP version
if (version_compare(PHP_VERSION, '8.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>ACP for WooCommerce</strong> requires PHP 8.0 or higher. Current version: ' . PHP_VERSION . '</p></div>';
    });
    return;
}

/**
 * Main plugin class
 */
class WooCommerce_ACP {
    
    /**
     * Single instance of the plugin
     */
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 3);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load core classes
        require_once WOOCOMMERCE_ACP_PLUGIN_DIR . 'includes/class-acp-exceptions.php';
        require_once WOOCOMMERCE_ACP_PLUGIN_DIR . 'includes/class-acp-logger.php';
        require_once WOOCOMMERCE_ACP_PLUGIN_DIR . 'includes/class-acp-header-validator.php';
        require_once WOOCOMMERCE_ACP_PLUGIN_DIR . 'includes/class-acp-response-builder.php';
        require_once WOOCOMMERCE_ACP_PLUGIN_DIR . 'includes/class-acp-auth.php';
        require_once WOOCOMMERCE_ACP_PLUGIN_DIR . 'includes/class-acp-api.php';
        require_once WOOCOMMERCE_ACP_PLUGIN_DIR . 'includes/class-acp-feed-generator.php';
        require_once WOOCOMMERCE_ACP_PLUGIN_DIR . 'includes/class-acp-webhook.php';
        require_once WOOCOMMERCE_ACP_PLUGIN_DIR . 'includes/class-acp-model.php';
        require_once WOOCOMMERCE_ACP_PLUGIN_DIR . 'includes/class-acp-database.php';
        require_once WOOCOMMERCE_ACP_PLUGIN_DIR . 'includes/class-acp-cron.php';
        require_once WOOCOMMERCE_ACP_PLUGIN_DIR . 'includes/class-acp-admin.php';
        require_once WOOCOMMERCE_ACP_PLUGIN_DIR . 'includes/class-acp-admin-ajax.php';
        require_once WOOCOMMERCE_ACP_PLUGIN_DIR . 'includes/class-acp-setup-wizard.php';
        require_once WOOCOMMERCE_ACP_PLUGIN_DIR . 'includes/class-acp-test.php';
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('woocommerce-acp', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize components
        new ACP_Database();
        new ACP_API();
        new ACP_Auth();
        new ACP_Feed_Generator();
        new ACP_Webhook();
        new ACP_Cron();
        new ACP_Admin();
        new ACP_Admin_Ajax();
        new ACP_Setup_Wizard();
        new ACP_Test();
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        $api = new ACP_API();
        $api->register_routes();
    }
    
    /**
     * Handle order status changes
     */
    public function handle_order_status_change($order_id, $old_status, $new_status) {
        $webhook = new ACP_Webhook();
        $webhook->handle_order_status_change($order_id, $old_status, $new_status);
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create necessary database tables
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // ACP sessions table
        $table_name = $wpdb->prefix . 'acp_sessions';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            intent_id varchar(255) NOT NULL,
            session_id varchar(255) NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            amount decimal(10,2) NOT NULL,
            currency varchar(3) NOT NULL DEFAULT 'TRY',
            order_id bigint(20) NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY intent_id (intent_id),
            KEY session_id (session_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // ACP webhooks table
        $table_name = $wpdb->prefix . 'acp_webhooks';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            webhook_id varchar(255) NOT NULL,
            event_type varchar(100) NOT NULL,
            order_id bigint(20) NOT NULL,
            payload text NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            attempts int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime NULL,
            PRIMARY KEY (id),
            UNIQUE KEY webhook_id (webhook_id),
            KEY order_id (order_id),
            KEY status (status)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $defaults = array(
            'acp_api_key' => wp_generate_password(32, false),
            'acp_webhook_secret' => wp_generate_password(32, false),
            'acp_sandbox_mode' => true,
            'acp_enable_feed' => true,
            'acp_enable_webhooks' => true,
            'acp_redis_enabled' => false,
            'acp_redis_host' => 'localhost',
            'acp_redis_port' => 6379,
            'acp_redis_password' => '',
            'acp_redis_database' => 0
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
}

// Initialize the plugin
function woocommerce_acp_init() {
    return WooCommerce_ACP::get_instance();
}

// Start the plugin
woocommerce_acp_init();
