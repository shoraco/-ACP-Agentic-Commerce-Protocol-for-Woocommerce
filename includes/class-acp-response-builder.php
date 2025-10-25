<?php
/**
 * ACP Response Builder - Professional response formatting
 * Based on Magento ACP implementation patterns
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class ACP_Response_Builder {
    
    /**
     * Build checkout session response according to ACP spec
     */
    public function build_checkout_session_response(array $session_data, array $line_items = [], array $total_details = [], array $fulfillment_options = []): array {
        return [
            'id' => $session_data['session_id'],
            'status' => $this->map_status($session_data['status']),
            'amount_total' => $this->convert_to_cents($session_data['amount']),
            'currency' => $session_data['currency'],
            'line_items' => $line_items,
            'total_details' => $total_details,
            'fulfillment_options' => $fulfillment_options,
            'order_url' => $session_data['order_url'] ?? null,
            'confirmation_email_sent' => $session_data['confirmation_email_sent'] ?? false,
            'created_at' => $session_data['created_at'],
            'updated_at' => $session_data['updated_at']
        ];
    }
    
    /**
     * Build line items response
     */
    public function build_line_items(array $items): array {
        $line_items = [];
        
        foreach ($items as $item) {
            $line_items[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'description' => $item['description'] ?? null,
                'quantity' => $item['quantity'],
                'unit_amount' => $this->convert_to_cents($item['unit_amount']),
                'total_amount' => $this->convert_to_cents($item['total_amount']),
                'tax_amount' => $this->convert_to_cents($item['tax_amount'] ?? 0),
                'discount_amount' => $this->convert_to_cents($item['discount_amount'] ?? 0)
            ];
        }
        
        return $line_items;
    }
    
    /**
     * Build total details response
     */
    public function build_total_details(array $totals): array {
        return [
            'subtotal' => $this->convert_to_cents($totals['subtotal']),
            'tax' => $this->convert_to_cents($totals['tax']),
            'shipping' => $this->convert_to_cents($totals['shipping']),
            'discount' => $this->convert_to_cents($totals['discount']),
            'total' => $this->convert_to_cents($totals['total'])
        ];
    }
    
    /**
     * Build fulfillment options response
     */
    public function build_fulfillment_options(array $shipping_methods): array {
        $options = [];
        
        foreach ($shipping_methods as $method) {
            $options[] = [
                'id' => $method['id'],
                'name' => $method['name'],
                'description' => $method['description'] ?? null,
                'amount' => $this->convert_to_cents($method['amount']),
                'estimated_delivery' => $method['estimated_delivery'] ?? null
            ];
        }
        
        return $options;
    }
    
    /**
     * Build error response
     */
    public function build_error_response(string $code, string $message, array $details = []): array {
        return [
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details
            ]
        ];
    }
    
    /**
     * Map internal status to ACP status
     */
    private function map_status(string $status): string {
        $status_map = [
            'pending' => 'not_ready_for_payment',
            'ready' => 'ready_for_payment', 
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'failed' => 'cancelled'
        ];
        
        return $status_map[$status] ?? 'not_ready_for_payment';
    }
    
    /**
     * Convert amount to cents (ACP spec requirement)
     */
    private function convert_to_cents(float $amount): int {
        return (int) round($amount * 100);
    }
    
    /**
     * Build product feed response
     */
    public function build_product_feed_response(array $products): array {
        return [
            'version' => '1.0',
            'generated_at' => current_time('c'),
            'total_products' => count($products),
            'currency' => get_woocommerce_currency(),
            'store_url' => home_url(),
            'store_name' => get_bloginfo('name'),
            'products' => $products
        ];
    }
    
    /**
     * Build webhook response
     */
    public function build_webhook_response(array $webhook_data): array {
        return [
            'webhook_id' => $webhook_data['webhook_id'],
            'event_type' => $webhook_data['event_type'],
            'order_id' => $webhook_data['order_id'],
            'status' => $webhook_data['status'],
            'amount' => $this->convert_to_cents($webhook_data['amount']),
            'currency' => $webhook_data['currency'],
            'customer' => $webhook_data['customer'],
            'items' => $webhook_data['items'],
            'metadata' => $webhook_data['metadata'],
            'created_at' => $webhook_data['created_at']
        ];
    }
}
