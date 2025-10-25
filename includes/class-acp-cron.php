<?php
/**
 * ACP Cron Jobs
 * Professional maintenance and cleanup tasks
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class ACP_Cron {
    
    public function __construct() {
        add_action('init', array($this, 'schedule_cron_jobs'));
        add_action('acp_cleanup_old_sessions', array($this, 'cleanup_old_sessions'));
        add_action('acp_cleanup_old_webhooks', array($this, 'cleanup_old_webhooks'));
        add_action('acp_retry_failed_webhooks', array($this, 'retry_failed_webhooks'));
        add_action('acp_rotate_logs', array($this, 'rotate_logs'));
        add_action('acp_generate_static_feed', array($this, 'generate_static_feed'));
    }
    
    /**
     * Schedule cron jobs
     */
    public function schedule_cron_jobs(): void {
        // Cleanup old sessions (daily)
        if (!wp_next_scheduled('acp_cleanup_old_sessions')) {
            wp_schedule_event(time(), 'daily', 'acp_cleanup_old_sessions');
        }
        
        // Cleanup old webhooks (daily)
        if (!wp_next_scheduled('acp_cleanup_old_webhooks')) {
            wp_schedule_event(time(), 'daily', 'acp_cleanup_old_webhooks');
        }
        
        // Retry failed webhooks (every 15 minutes)
        if (!wp_next_scheduled('acp_retry_failed_webhooks')) {
            wp_schedule_event(time(), 'acp_15min', 'acp_retry_failed_webhooks');
        }
        
        // Rotate logs (daily)
        if (!wp_next_scheduled('acp_rotate_logs')) {
            wp_schedule_event(time(), 'daily', 'acp_rotate_logs');
        }
        
        // Generate static feed (every 6 hours)
        if (!wp_next_scheduled('acp_generate_static_feed')) {
            wp_schedule_event(time(), 'acp_6hours', 'acp_generate_static_feed');
        }
        
        // Add custom cron intervals
        add_filter('cron_schedules', array($this, 'add_cron_intervals'));
    }
    
    /**
     * Add custom cron intervals
     */
    public function add_cron_intervals(array $schedules): array {
        $schedules['acp_15min'] = array(
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display' => __('Every 15 Minutes', 'woocommerce-acp')
        );
        
        $schedules['acp_6hours'] = array(
            'interval' => 6 * HOUR_IN_SECONDS,
            'display' => __('Every 6 Hours', 'woocommerce-acp')
        );
        
        return $schedules;
    }
    
    /**
     * Cleanup old sessions
     */
    public function cleanup_old_sessions(): void {
        $retention_days = get_option('acp_session_retention_days', 30);
        $deleted_count = ACP_Model::cleanup_old_sessions($retention_days);
        
        $logger = new ACP_Logger();
        $logger->info("Cleaned up {$deleted_count} old sessions");
    }
    
    /**
     * Cleanup old webhooks
     */
    public function cleanup_old_webhooks(): void {
        $retention_days = get_option('acp_webhook_retention_days', 30);
        $deleted_count = ACP_Model::cleanup_old_webhooks($retention_days);
        
        $logger = new ACP_Logger();
        $logger->info("Cleaned up {$deleted_count} old webhooks");
    }
    
    /**
     * Retry failed webhooks
     */
    public function retry_failed_webhooks(): void {
        $webhook = new ACP_Webhook();
        $webhook->retry_failed_webhooks();
        
        $logger = new ACP_Logger();
        $logger->info("Retried failed webhooks");
    }
    
    /**
     * Rotate logs
     */
    public function rotate_logs(): void {
        $logger = new ACP_Logger();
        $logger->rotate_logs();
        
        $logger->info("Log rotation completed");
    }
    
    /**
     * Generate static feed
     */
    public function generate_static_feed(): void {
        if (!get_option('acp_enable_feed', true)) {
            return;
        }
        
        $feed_generator = new ACP_Feed_Generator();
        $products = $feed_generator->get_products();
        $feed_data = $feed_generator->format_feed_data($products);
        
        // Save to static file
        $upload_dir = wp_upload_dir();
        $feed_file = $upload_dir['basedir'] . '/acp-feed.json';
        
        $feed_dir = dirname($feed_file);
        if (!file_exists($feed_dir)) {
            wp_mkdir_p($feed_dir);
        }
        
        file_put_contents($feed_file, json_encode($feed_data, JSON_PRETTY_PRINT));
        
        $logger = new ACP_Logger();
        $logger->info("Generated static feed with " . count($products) . " products");
    }
    
    /**
     * Unschedule cron jobs
     */
    public function unschedule_cron_jobs(): void {
        wp_unschedule_event(wp_next_scheduled('acp_cleanup_old_sessions'), 'acp_cleanup_old_sessions');
        wp_unschedule_event(wp_next_scheduled('acp_cleanup_old_webhooks'), 'acp_cleanup_old_webhooks');
        wp_unschedule_event(wp_next_scheduled('acp_retry_failed_webhooks'), 'acp_retry_failed_webhooks');
        wp_unschedule_event(wp_next_scheduled('acp_rotate_logs'), 'acp_rotate_logs');
        wp_unschedule_event(wp_next_scheduled('acp_generate_static_feed'), 'acp_generate_static_feed');
    }
    
    /**
     * Get cron status
     */
    public function get_cron_status(): array {
        return [
            'cleanup_old_sessions' => [
                'next_run' => wp_next_scheduled('acp_cleanup_old_sessions'),
                'status' => wp_next_scheduled('acp_cleanup_old_sessions') ? 'scheduled' : 'not_scheduled'
            ],
            'cleanup_old_webhooks' => [
                'next_run' => wp_next_scheduled('acp_cleanup_old_webhooks'),
                'status' => wp_next_scheduled('acp_cleanup_old_webhooks') ? 'scheduled' : 'not_scheduled'
            ],
            'retry_failed_webhooks' => [
                'next_run' => wp_next_scheduled('acp_retry_failed_webhooks'),
                'status' => wp_next_scheduled('acp_retry_failed_webhooks') ? 'scheduled' : 'not_scheduled'
            ],
            'rotate_logs' => [
                'next_run' => wp_next_scheduled('acp_rotate_logs'),
                'status' => wp_next_scheduled('acp_rotate_logs') ? 'scheduled' : 'not_scheduled'
            ],
            'generate_static_feed' => [
                'next_run' => wp_next_scheduled('acp_generate_static_feed'),
                'status' => wp_next_scheduled('acp_generate_static_feed') ? 'scheduled' : 'not_scheduled'
            ]
        ];
    }
}
