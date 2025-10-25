<?php
/**
 * ACP Webhook Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACP_Webhook {
    
    public function __construct() {
        // Constructor
    }
    
    /**
     * Handle order status change
     */
    public function handle_order_status_change($order_id, $old_status, $new_status) {
        // Check if webhooks are enabled
        if (!get_option('acp_enable_webhooks', true)) {
            return;
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        // Generate webhook event
        $webhook_data = $this->generate_webhook_data($order, $old_status, $new_status);
        
        // Store webhook in database
        $this->store_webhook($webhook_data);
        
        // Send webhook if configured
        $this->send_webhook($webhook_data);
    }
    
    /**
     * Generate webhook data
     */
    private function generate_webhook_data($order, $old_status, $new_status) {
        $webhook_id = 'webhook_' . wp_generate_password(16, false);
        
        return array(
            'webhook_id' => $webhook_id,
            'event_type' => 'order.status_changed',
            'order_id' => $order->get_id(),
            'old_status' => $old_status,
            'new_status' => $new_status,
            'amount' => $order->get_total(),
            'currency' => $order->get_currency(),
            'customer' => array(
                'id' => $order->get_customer_id(),
                'email' => $order->get_billing_email(),
                'name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'phone' => $order->get_billing_phone()
            ),
            'billing_address' => array(
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'company' => $order->get_billing_company(),
                'address_1' => $order->get_billing_address_1(),
                'address_2' => $order->get_billing_address_2(),
                'city' => $order->get_billing_city(),
                'state' => $order->get_billing_state(),
                'postcode' => $order->get_billing_postcode(),
                'country' => $order->get_billing_country()
            ),
            'shipping_address' => array(
                'first_name' => $order->get_shipping_first_name(),
                'last_name' => $order->get_shipping_last_name(),
                'company' => $order->get_shipping_company(),
                'address_1' => $order->get_shipping_address_1(),
                'address_2' => $order->get_shipping_address_2(),
                'city' => $order->get_shipping_city(),
                'state' => $order->get_shipping_state(),
                'postcode' => $order->get_shipping_postcode(),
                'country' => $order->get_shipping_country()
            ),
            'items' => $this->get_order_items($order),
            'payment_method' => $order->get_payment_method(),
            'payment_method_title' => $order->get_payment_method_title(),
            'transaction_id' => $order->get_transaction_id(),
            'date_created' => $order->get_date_created()->format('c'),
            'date_modified' => $order->get_date_modified()->format('c'),
            'metadata' => $this->get_order_metadata($order)
        );
    }
    
    /**
     * Get order items
     */
    private function get_order_items($order) {
        $items = array();
        
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            
            $items[] = array(
                'id' => $item_id,
                'product_id' => $item->get_product_id(),
                'variation_id' => $item->get_variation_id(),
                'name' => $item->get_name(),
                'sku' => $product ? $product->get_sku() : '',
                'quantity' => $item->get_quantity(),
                'subtotal' => $item->get_subtotal(),
                'total' => $item->get_total(),
                'tax_class' => $item->get_tax_class(),
                'meta_data' => $this->get_item_meta_data($item)
            );
        }
        
        return $items;
    }
    
    /**
     * Get item meta data
     */
    private function get_item_meta_data($item) {
        $meta_data = array();
        
        foreach ($item->get_meta_data() as $meta) {
            $meta_data[] = array(
                'key' => $meta->key,
                'value' => $meta->value,
                'display_key' => $meta->display_key,
                'display_value' => $meta->display_value
            );
        }
        
        return $meta_data;
    }
    
    /**
     * Get order metadata
     */
    private function get_order_metadata($order) {
        $metadata = array();
        
        foreach ($order->get_meta_data() as $meta) {
            $metadata[$meta->key] = $meta->value;
        }
        
        return $metadata;
    }
    
    /**
     * Store webhook in database
     */
    private function store_webhook($webhook_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_webhooks';
        
        $wpdb->insert(
            $table_name,
            array(
                'webhook_id' => $webhook_data['webhook_id'],
                'event_type' => $webhook_data['event_type'],
                'order_id' => $webhook_data['order_id'],
                'payload' => json_encode($webhook_data),
                'status' => 'pending'
            ),
            array('%s', '%s', '%d', '%s', '%s')
        );
    }
    
    /**
     * Send webhook
     */
    private function send_webhook($webhook_data) {
        $webhook_url = get_option('acp_webhook_url');
        
        if (!$webhook_url) {
            return;
        }
        
        $payload = json_encode($webhook_data);
        $signature = $this->generate_signature($payload);
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-ACP-Signature' => $signature,
                'X-ACP-Event' => $webhook_data['event_type'],
                'User-Agent' => 'ACP-WooCommerce-Webhook/1.0'
            ),
            'body' => $payload,
            'timeout' => 30
        );
        
        $response = wp_remote_post($webhook_url, $args);
        
        if (is_wp_error($response)) {
            $this->log_webhook_error($webhook_data['webhook_id'], $response->get_error_message());
        } else {
            $this->update_webhook_status($webhook_data['webhook_id'], 'sent', $response);
        }
    }
    
    /**
     * Generate webhook signature
     */
    private function generate_signature($payload) {
        $secret = get_option('acp_webhook_secret');
        return 'sha256=' . hash_hmac('sha256', $payload, $secret);
    }
    
    /**
     * Log webhook error
     */
    private function log_webhook_error($webhook_id, $error_message) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_webhooks';
        
        $wpdb->update(
            $table_name,
            array(
                'status' => 'failed',
                'processed_at' => current_time('mysql')
            ),
            array('webhook_id' => $webhook_id)
        );
        
        error_log("ACP Webhook Error [{$webhook_id}]: {$error_message}");
    }
    
    /**
     * Update webhook status
     */
    private function update_webhook_status($webhook_id, $status, $response = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_webhooks';
        
        $update_data = array(
            'status' => $status,
            'processed_at' => current_time('mysql')
        );
        
        if ($response) {
            $update_data['response_code'] = wp_remote_retrieve_response_code($response);
            $update_data['response_body'] = wp_remote_retrieve_body($response);
        }
        
        $wpdb->update(
            $table_name,
            $update_data,
            array('webhook_id' => $webhook_id)
        );
    }
    
    /**
     * Retry failed webhooks
     */
    public function retry_failed_webhooks() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_webhooks';
        
        $failed_webhooks = $wpdb->get_results(
            "SELECT * FROM $table_name WHERE status = 'failed' AND attempts < 3 ORDER BY created_at ASC LIMIT 10"
        );
        
        foreach ($failed_webhooks as $webhook) {
            $webhook_data = json_decode($webhook->payload, true);
            $this->send_webhook($webhook_data);
            
            // Increment attempts
            $wpdb->update(
                $table_name,
                array('attempts' => $webhook->attempts + 1),
                array('id' => $webhook->id)
            );
        }
    }
    
    /**
     * Get webhook statistics
     */
    public function get_webhook_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_webhooks';
        
        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM $table_name"
        );
        
        return $stats;
    }
}
