<?php
/**
 * ACP Database Management
 * Professional database schema following ACP specification
 * 
 * @link https://github.com/agentic-commerce-protocol/agentic-commerce-protocol
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ACP Database Class
 * 
 * Manages database tables for ACP sessions, webhooks, and logs.
 * Implements proper database schema following WordPress standards
 * and ACP specification requirements.
 * 
 * @since 1.0.0
 * @package WooCommerce_ACP
 */
class ACP_Database {
    
    /**
     * Database version
     * 
     * @var string
     */
    private $db_version = '1.0.0';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'check_database_version'));
        register_activation_hook(WOOCOMMERCE_ACP_PLUGIN_FILE, array($this, 'create_tables'));
        register_deactivation_hook(WOOCOMMERCE_ACP_PLUGIN_FILE, array($this, 'cleanup'));
    }
    
    /**
     * Check database version and update if needed
     */
    public function check_database_version(): void {
        $installed_version = get_option('acp_db_version', '0.0.0');
        
        if (version_compare($installed_version, $this->db_version, '<')) {
            $this->create_tables();
            update_option('acp_db_version', $this->db_version);
        }
    }
    
    /**
     * Create database tables
     */
    public function create_tables(): void {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // ACP Sessions table
        $sessions_table = $wpdb->prefix . 'acp_sessions';
        $sessions_sql = "CREATE TABLE $sessions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            intent_id varchar(255) NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            amount decimal(10,2) NOT NULL DEFAULT 0.00,
            currency varchar(3) NOT NULL DEFAULT 'USD',
            buyer_id varchar(255) DEFAULT NULL,
            buyer_name varchar(255) DEFAULT NULL,
            buyer_email varchar(255) DEFAULT NULL,
            line_items longtext,
            metadata longtext,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY intent_id (intent_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // ACP Webhooks table
        $webhooks_table = $wpdb->prefix . 'acp_webhooks';
        $webhooks_sql = "CREATE TABLE $webhooks_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            webhook_id varchar(255) NOT NULL,
            event_type varchar(100) NOT NULL,
            session_id varchar(255) DEFAULT NULL,
            order_id bigint(20) DEFAULT NULL,
            payload longtext NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            attempts int(11) NOT NULL DEFAULT 0,
            max_attempts int(11) NOT NULL DEFAULT 3,
            next_retry_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY webhook_id (webhook_id),
            KEY event_type (event_type),
            KEY session_id (session_id),
            KEY order_id (order_id),
            KEY status (status),
            KEY next_retry_at (next_retry_at)
        ) $charset_collate;";
        
        // ACP Logs table
        $logs_table = $wpdb->prefix . 'acp_logs';
        $logs_sql = "CREATE TABLE $logs_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context longtext,
            session_id varchar(255) DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY level (level),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sessions_sql);
        dbDelta($webhooks_sql);
        dbDelta($logs_sql);
        
        // Create indexes for performance
        $this->create_indexes();
    }
    
    /**
     * Create database indexes for performance
     */
    private function create_indexes(): void {
        global $wpdb;
        
        $sessions_table = $wpdb->prefix . 'acp_sessions';
        $webhooks_table = $wpdb->prefix . 'acp_webhooks';
        $logs_table = $wpdb->prefix . 'acp_logs';
        
        // Additional indexes for performance
        $wpdb->query("CREATE INDEX IF NOT EXISTS idx_sessions_buyer_email ON $sessions_table (buyer_email)");
        $wpdb->query("CREATE INDEX IF NOT EXISTS idx_sessions_amount ON $sessions_table (amount)");
        $wpdb->query("CREATE INDEX IF NOT EXISTS idx_webhooks_created_at ON $webhooks_table (created_at)");
        $wpdb->query("CREATE INDEX IF NOT EXISTS idx_logs_level_created ON $logs_table (level, created_at)");
    }
    
    /**
     * Cleanup database tables
     */
    public function cleanup(): void {
        // Keep data for 30 days after deactivation
        $this->schedule_cleanup();
    }
    
    /**
     * Schedule cleanup task
     */
    private function schedule_cleanup(): void {
        if (!wp_next_scheduled('acp_cleanup_old_data')) {
            wp_schedule_single_event(time() + (30 * DAY_IN_SECONDS), 'acp_cleanup_old_data');
        }
    }
    
    /**
     * Get database statistics
     */
    public function get_stats(): array {
        global $wpdb;
        
        $sessions_table = $wpdb->prefix . 'acp_sessions';
        $webhooks_table = $wpdb->prefix . 'acp_webhooks';
        $logs_table = $wpdb->prefix . 'acp_logs';
        
        return array(
            'sessions' => array(
                'total' => $wpdb->get_var("SELECT COUNT(*) FROM $sessions_table"),
                'pending' => $wpdb->get_var("SELECT COUNT(*) FROM $sessions_table WHERE status = 'pending'"),
                'completed' => $wpdb->get_var("SELECT COUNT(*) FROM $sessions_table WHERE status = 'completed'"),
                'cancelled' => $wpdb->get_var("SELECT COUNT(*) FROM $sessions_table WHERE status = 'cancelled'")
            ),
            'webhooks' => array(
                'total' => $wpdb->get_var("SELECT COUNT(*) FROM $webhooks_table"),
                'pending' => $wpdb->get_var("SELECT COUNT(*) FROM $webhooks_table WHERE status = 'pending'"),
                'sent' => $wpdb->get_var("SELECT COUNT(*) FROM $webhooks_table WHERE status = 'sent'"),
                'failed' => $wpdb->get_var("SELECT COUNT(*) FROM $webhooks_table WHERE status = 'failed'")
            ),
            'logs' => array(
                'total' => $wpdb->get_var("SELECT COUNT(*) FROM $logs_table"),
                'errors' => $wpdb->get_var("SELECT COUNT(*) FROM $logs_table WHERE level = 'error'"),
                'warnings' => $wpdb->get_var("SELECT COUNT(*) FROM $logs_table WHERE level = 'warning'")
            )
        );
    }
    
    /**
     * Cleanup old data
     */
    public function cleanup_old_data(): void {
        global $wpdb;
        
        $sessions_table = $wpdb->prefix . 'acp_sessions';
        $webhooks_table = $wpdb->prefix . 'acp_webhooks';
        $logs_table = $wpdb->prefix . 'acp_logs';
        
        // Cleanup old sessions (older than 90 days)
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $sessions_table WHERE created_at < %s",
            date('Y-m-d H:i:s', time() - (90 * DAY_IN_SECONDS))
        ));
        
        // Cleanup old webhooks (older than 30 days)
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $webhooks_table WHERE created_at < %s",
            date('Y-m-d H:i:s', time() - (30 * DAY_IN_SECONDS))
        ));
        
        // Cleanup old logs (older than 30 days)
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $logs_table WHERE created_at < %s",
            date('Y-m-d H:i:s', time() - (30 * DAY_IN_SECONDS))
        ));
    }
}
