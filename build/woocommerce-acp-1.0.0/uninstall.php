<?php
/**
 * Uninstall script for ACP for WooCommerce
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clean up options
delete_option('acp_api_key');
delete_option('acp_webhook_secret');
delete_option('acp_sandbox_mode');
delete_option('acp_enable_feed');
delete_option('acp_enable_webhooks');
delete_option('acp_webhook_url');
delete_option('acp_enable_signature_validation');
delete_option('acp_timestamp_tolerance');
delete_option('acp_redis_enabled');
delete_option('acp_redis_host');
delete_option('acp_redis_port');
delete_option('acp_redis_password');
delete_option('acp_redis_database');
delete_option('acp_feed_max_products');
delete_option('acp_feed_categories');
delete_option('acp_session_retention_days');
delete_option('acp_webhook_retention_days');
delete_option('acp_log_level');
delete_option('acp_enable_debug');
delete_option('acp_db_version');

// Clean up database tables
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}acp_sessions");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}acp_webhooks");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}acp_logs");

// Clean up cron jobs
wp_clear_scheduled_hook('acp_cleanup_old_sessions');
wp_clear_scheduled_hook('acp_cleanup_old_webhooks');
wp_clear_scheduled_hook('acp_retry_failed_webhooks');
wp_clear_scheduled_hook('acp_rotate_logs');
wp_clear_scheduled_hook('acp_generate_static_feed');
