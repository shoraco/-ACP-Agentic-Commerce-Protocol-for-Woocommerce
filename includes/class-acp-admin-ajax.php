<?php
/**
 * ACP Admin AJAX Handlers
 * Professional admin interface AJAX functionality
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class ACP_Admin_Ajax {
    
    public function __construct() {
        add_action('wp_ajax_acp_generate_api_key', array($this, 'generate_api_key'));
        add_action('wp_ajax_acp_generate_webhook_secret', array($this, 'generate_webhook_secret'));
        add_action('wp_ajax_acp_get_logs', array($this, 'get_logs'));
        add_action('wp_ajax_acp_clear_logs', array($this, 'clear_logs'));
        add_action('wp_ajax_acp_download_logs', array($this, 'download_logs'));
        add_action('wp_ajax_acp_get_stats', array($this, 'get_stats'));
        add_action('wp_ajax_acp_test_connection', array($this, 'test_connection'));
    }
    
    /**
     * Generate new API key
     */
    public function generate_api_key(): void {
        check_ajax_referer('acp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Unauthorized');
        }
        
        $auth = new ACP_Auth();
        $api_key = $auth->generate_api_key();
        
        update_option('acp_api_key', $api_key);
        
        wp_send_json_success($api_key);
    }
    
    /**
     * Generate new webhook secret
     */
    public function generate_webhook_secret(): void {
        check_ajax_referer('acp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Unauthorized');
        }
        
        $auth = new ACP_Auth();
        $webhook_secret = $auth->generate_webhook_secret();
        
        update_option('acp_webhook_secret', $webhook_secret);
        
        wp_send_json_success($webhook_secret);
    }
    
    /**
     * Get logs
     */
    public function get_logs(): void {
        check_ajax_referer('acp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Unauthorized');
        }
        
        $logger = new ACP_Logger();
        $log_file = $logger->get_log_file();
        
        if (file_exists($log_file)) {
            $logs = file_get_contents($log_file);
            // Get last 1000 lines
            $lines = explode("\n", $logs);
            $logs = implode("\n", array_slice($lines, -1000));
        } else {
            $logs = 'No logs found.';
        }
        
        wp_send_json_success($logs);
    }
    
    /**
     * Clear logs
     */
    public function clear_logs(): void {
        check_ajax_referer('acp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Unauthorized');
        }
        
        $logger = new ACP_Logger();
        $result = $logger->clear_logs();
        
        if ($result) {
            wp_send_json_success('Logs cleared successfully');
        } else {
            wp_send_json_error('Failed to clear logs');
        }
    }
    
    /**
     * Download logs
     */
    public function download_logs(): void {
        check_ajax_referer('acp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Unauthorized');
        }
        
        $logger = new ACP_Logger();
        $log_file = $logger->get_log_file();
        
        if (file_exists($log_file)) {
            $filename = 'acp-logs-' . date('Y-m-d-H-i-s') . '.log';
            
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($log_file));
            
            readfile($log_file);
            exit;
        } else {
            wp_die('Log file not found');
        }
    }
    
    /**
     * Get statistics
     */
    public function get_stats(): void {
        check_ajax_referer('acp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Unauthorized');
        }
        
        $session_stats = ACP_Model::get_session_stats();
        $webhook_stats = (new ACP_Webhook())->get_webhook_stats();
        $cron_status = (new ACP_Cron())->get_cron_status();
        
        $stats = [
            'sessions' => [
                'total' => $session_stats->total ?? 0,
                'pending' => $session_stats->pending ?? 0,
                'completed' => $session_stats->completed ?? 0,
                'failed' => $session_stats->failed ?? 0,
                'cancelled' => $session_stats->cancelled ?? 0,
                'total_amount' => $session_stats->total_amount ?? 0
            ],
            'webhooks' => [
                'total' => $webhook_stats->total ?? 0,
                'sent' => $webhook_stats->sent ?? 0,
                'failed' => $webhook_stats->failed ?? 0,
                'pending' => $webhook_stats->pending ?? 0
            ],
            'cron' => $cron_status,
            'log_size' => (new ACP_Logger())->get_log_size()
        ];
        
        wp_send_json_success($stats);
    }
    
    /**
     * Test connection
     */
    public function test_connection(): void {
        check_ajax_referer('acp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Unauthorized');
        }
        
        $tests = [
            'woocommerce_active' => class_exists('WooCommerce'),
            'database_tables' => $this->test_database_tables(),
            'api_endpoints' => $this->test_api_endpoints(),
            'redis_connection' => $this->test_redis_connection(),
            'webhook_url' => $this->test_webhook_url()
        ];
        
        $all_passed = !in_array(false, $tests, true);
        
        wp_send_json_success([
            'tests' => $tests,
            'overall_status' => $all_passed ? 'passed' : 'failed'
        ]);
    }
    
    /**
     * Test database tables
     */
    private function test_database_tables(): bool {
        global $wpdb;
        
        $sessions_table = $wpdb->prefix . 'acp_sessions';
        $webhooks_table = $wpdb->prefix . 'acp_webhooks';
        
        $sessions_exists = $wpdb->get_var("SHOW TABLES LIKE '$sessions_table'") === $sessions_table;
        $webhooks_exists = $wpdb->get_var("SHOW TABLES LIKE '$webhooks_table'") === $webhooks_table;
        
        return $sessions_exists && $webhooks_exists;
    }
    
    /**
     * Test API endpoints
     */
    private function test_api_endpoints(): bool {
        $routes = rest_get_server()->get_routes();
        
        $required_routes = [
            '/acp/v1/checkout_sessions',
            '/acp/v1/checkout_sessions/(?P<session_id>[a-zA-Z0-9_-]+)',
            '/acp/v1/checkout_sessions/(?P<session_id>[a-zA-Z0-9_-]+)/complete',
            '/acp/v1/checkout_sessions/(?P<session_id>[a-zA-Z0-9_-]+)/cancel'
        ];
        
        foreach ($required_routes as $route) {
            if (!isset($routes[$route])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Test Redis connection
     */
    private function test_redis_connection(): bool {
        if (!get_option('acp_redis_enabled', false)) {
            return true; // Redis not enabled, so test passes
        }
        
        try {
            $auth = new ACP_Auth();
            $redis = $auth->get_redis_connection();
            return $redis !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Test webhook URL
     */
    private function test_webhook_url(): bool {
        $webhook_url = get_option('acp_webhook_url');
        
        if (empty($webhook_url)) {
            return true; // No webhook URL configured, so test passes
        }
        
        // Test if URL is reachable
        $response = wp_remote_get($webhook_url, array(
            'timeout' => 10,
            'sslverify' => false
        ));
        
        return !is_wp_error($response);
    }
}
