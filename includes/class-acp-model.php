<?php
/**
 * ACP Data Models
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACP_Model {
    
    /**
     * Get ACP session by intent ID
     */
    public static function get_session_by_intent($intent_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_sessions';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE intent_id = %s",
            $intent_id
        ));
    }
    
    /**
     * Get ACP session by session ID
     */
    public static function get_session_by_session_id($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_sessions';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE session_id = %s",
            $session_id
        ));
    }
    
    /**
     * Create ACP session
     */
    public static function create_session($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_sessions';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'intent_id' => $data['intent_id'],
                'session_id' => $data['session_id'],
                'status' => $data['status'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'order_id' => $data['order_id'] ?? null
            ),
            array('%s', '%s', '%s', '%f', '%s', '%d')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Update ACP session
     */
    public static function update_session($session_id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_sessions';
        
        return $wpdb->update(
            $table_name,
            $data,
            array('session_id' => $session_id)
        );
    }
    
    /**
     * Get ACP webhook by webhook ID
     */
    public static function get_webhook($webhook_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_webhooks';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE webhook_id = %s",
            $webhook_id
        ));
    }
    
    /**
     * Create ACP webhook
     */
    public static function create_webhook($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_webhooks';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'webhook_id' => $data['webhook_id'],
                'event_type' => $data['event_type'],
                'order_id' => $data['order_id'],
                'payload' => $data['payload'],
                'status' => $data['status']
            ),
            array('%s', '%s', '%d', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Update ACP webhook
     */
    public static function update_webhook($webhook_id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_webhooks';
        
        return $wpdb->update(
            $table_name,
            $data,
            array('webhook_id' => $webhook_id)
        );
    }
    
    /**
     * Get sessions by status
     */
    public static function get_sessions_by_status($status, $limit = 50) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_sessions';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE status = %s ORDER BY created_at DESC LIMIT %d",
            $status,
            $limit
        ));
    }
    
    /**
     * Get webhooks by status
     */
    public static function get_webhooks_by_status($status, $limit = 50) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_webhooks';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE status = %s ORDER BY created_at DESC LIMIT %d",
            $status,
            $limit
        ));
    }
    
    /**
     * Get session statistics
     */
    public static function get_session_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_sessions';
        
        return $wpdb->get_row(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(amount) as total_amount
            FROM $table_name"
        );
    }
    
    /**
     * Clean up old sessions
     */
    public static function cleanup_old_sessions($days = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_sessions';
        
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
    }
    
    /**
     * Clean up old webhooks
     */
    public static function cleanup_old_webhooks($days = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_webhooks';
        
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
    }
}
