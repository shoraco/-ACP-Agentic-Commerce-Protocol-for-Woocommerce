<?php
/**
 * ACP REST API endpoints
 * Professional implementation based on Magento ACP patterns
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class ACP_API {
    
    private $namespace = 'acp/v1';
    
    public function __construct() {
        // Constructor - routes will be registered via register_routes()
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Checkout sessions endpoints
        register_rest_route($this->namespace, '/checkout_sessions', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_checkout_session'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => $this->get_checkout_session_args()
        ));
        
        register_rest_route($this->namespace, '/checkout_sessions/(?P<session_id>[a-zA-Z0-9_-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_checkout_session'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'session_id' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_string($param) && !empty($param);
                    }
                )
            )
        ));
        
        register_rest_route($this->namespace, '/checkout_sessions/(?P<session_id>[a-zA-Z0-9_-]+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_checkout_session'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => $this->get_checkout_session_args()
        ));
        
        register_rest_route($this->namespace, '/checkout_sessions/(?P<session_id>[a-zA-Z0-9_-]+)/complete', array(
            'methods' => 'POST',
            'callback' => array($this, 'complete_checkout_session'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'session_id' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_string($param) && !empty($param);
                    }
                ),
                'payment_method_details' => array(
                    'required' => false,
                    'type' => 'object'
                )
            )
        ));
        
        register_rest_route($this->namespace, '/checkout_sessions/(?P<session_id>[a-zA-Z0-9_-]+)/cancel', array(
            'methods' => 'POST',
            'callback' => array($this, 'cancel_checkout_session'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'session_id' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_string($param) && !empty($param);
                    }
                )
            )
        ));
    }
    
    /**
     * Create checkout session with professional ACP compliance
     */
    public function create_checkout_session($request) {
        try {
            $params = $request->get_params();
            
            // Validate required fields according to ACP spec
            if (!isset($params['items']) || empty($params['items'])) {
                throw new ACP_Validation_Exception('Items are required to create a checkout session');
            }
            
            // Generate session ID
            $session_id = 'acp_session_' . wp_generate_password(16, false);
            $intent_id = 'intent_' . wp_generate_password(16, false);
            
            // Create WooCommerce cart
            $cart = WC()->cart;
            if (!$cart) {
                throw new ACP_Exception('WooCommerce cart not available');
            }
            
            // Clear existing cart
            $cart->empty_cart();
            
            // Process items and add to cart
            $line_items = [];
            $total_amount = 0;
            
            foreach ($params['items'] as $item) {
                $product_id = $this->get_or_create_product($item);
                $quantity = $item['quantity'] ?? 1;
                
                $cart->add_to_cart($product_id, $quantity);
                
                $product = wc_get_product($product_id);
                $line_items[] = [
                    'id' => (string) $product_id,
                    'name' => $product->get_name(),
                    'description' => $product->get_short_description(),
                    'quantity' => $quantity,
                    'unit_amount' => (float) $product->get_price(),
                    'total_amount' => (float) $product->get_price() * $quantity,
                    'tax_amount' => 0,
                    'discount_amount' => 0
                ];
                
                $total_amount += $product->get_price() * $quantity;
            }
            
            // Store session data
            $this->store_session_data($intent_id, $session_id, [
                'amount' => $total_amount,
                'currency' => get_woocommerce_currency(),
                'status' => 'pending'
            ]);
            
            // Build professional response
            $response_builder = new ACP_Response_Builder();
            $session_data = [
                'session_id' => $session_id,
                'status' => 'pending',
                'amount' => $total_amount,
                'currency' => get_woocommerce_currency(),
                'created_at' => current_time('c'),
                'updated_at' => current_time('c')
            ];
            
            return $response_builder->build_checkout_session_response(
                $session_data,
                $line_items,
                $this->calculate_total_details($cart),
                $this->get_fulfillment_options()
            );
            
        } catch (ACP_Validation_Exception $e) {
            return new WP_Error('validation_error', $e->getMessage(), array('status' => 400));
        } catch (ACP_Exception $e) {
            return new WP_Error('server_error', $e->getMessage(), array('status' => 500));
        } catch (Exception $e) {
            return new WP_Error('server_error', 'Internal server error', array('status' => 500));
        }
    }
    
    /**
     * Get checkout session
     */
    public function get_checkout_session($request) {
        $session_id = $request->get_param('session_id');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_sessions';
        
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE session_id = %s",
            $session_id
        ));
        
        if (!$session) {
            return new WP_Error('session_not_found', 'Session not found', array('status' => 404));
        }
        
        return array(
            'intent_id' => $session->intent_id,
            'session_id' => $session->session_id,
            'status' => $session->status,
            'amount' => $session->amount,
            'currency' => $session->currency,
            'order_id' => $session->order_id,
            'created_at' => $session->created_at,
            'updated_at' => $session->updated_at
        );
    }
    
    /**
     * Update checkout session
     */
    public function update_checkout_session($request) {
        $session_id = $request->get_param('session_id');
        $params = $request->get_params();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_sessions';
        
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE session_id = %s",
            $session_id
        ));
        
        if (!$session) {
            return new WP_Error('session_not_found', 'Session not found', array('status' => 404));
        }
        
        // Update session data
        $update_data = array();
        if (isset($params['amount'])) {
            $update_data['amount'] = $params['amount'];
        }
        if (isset($params['currency'])) {
            $update_data['currency'] = $params['currency'];
        }
        if (isset($params['status'])) {
            $update_data['status'] = $params['status'];
        }
        
        if (!empty($update_data)) {
            $wpdb->update(
                $table_name,
                $update_data,
                array('session_id' => $session_id)
            );
        }
        
        return array(
            'intent_id' => $session->intent_id,
            'session_id' => $session_id,
            'status' => $update_data['status'] ?? $session->status,
            'amount' => $update_data['amount'] ?? $session->amount,
            'currency' => $update_data['currency'] ?? $session->currency,
            'updated_at' => current_time('mysql')
        );
    }
    
    /**
     * Complete checkout session
     */
    public function complete_checkout_session($request) {
        $session_id = $request->get_param('session_id');
        $params = $request->get_params();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_sessions';
        
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE session_id = %s",
            $session_id
        ));
        
        if (!$session) {
            return new WP_Error('session_not_found', 'Session not found', array('status' => 404));
        }
        
        if ($session->status !== 'pending') {
            return new WP_Error('invalid_status', 'Session is not in pending status', array('status' => 400));
        }
        
        // Process payment
        $payment_result = $this->process_payment($session, $params);
        
        if ($payment_result['success']) {
            // Update session status
            $wpdb->update(
                $table_name,
                array('status' => 'completed', 'order_id' => $payment_result['order_id']),
                array('session_id' => $session_id)
            );
            
            return array(
                'intent_id' => $session->intent_id,
                'session_id' => $session_id,
                'status' => 'completed',
                'payment_id' => $payment_result['payment_id'],
                'order_id' => $payment_result['order_id'],
                'transaction_id' => $payment_result['transaction_id']
            );
        } else {
            // Update session status to failed
            $wpdb->update(
                $table_name,
                array('status' => 'failed'),
                array('session_id' => $session_id)
            );
            
            return new WP_Error('payment_failed', $payment_result['error'], array('status' => 400));
        }
    }
    
    /**
     * Cancel checkout session
     */
    public function cancel_checkout_session($request) {
        $session_id = $request->get_param('session_id');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_sessions';
        
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE session_id = %s",
            $session_id
        ));
        
        if (!$session) {
            return new WP_Error('session_not_found', 'Session not found', array('status' => 404));
        }
        
        // Update session status
        $wpdb->update(
            $table_name,
            array('status' => 'cancelled'),
            array('session_id' => $session_id)
        );
        
        return array(
            'intent_id' => $session->intent_id,
            'session_id' => $session_id,
            'status' => 'cancelled',
            'cancelled_at' => current_time('mysql')
        );
    }
    
    /**
     * Check API permission
     */
    public function check_permission($request) {
        $auth = new ACP_Auth();
        return $auth->validate_request($request);
    }
    
    /**
     * Get checkout session arguments
     */
    private function get_checkout_session_args() {
        return array(
            'amount' => array(
                'required' => true,
                'type' => 'number',
                'minimum' => 0.01
            ),
            'currency' => array(
                'required' => true,
                'type' => 'string',
                'enum' => array('TRY', 'USD', 'EUR')
            ),
            'buyer' => array(
                'required' => true,
                'type' => 'object',
                'properties' => array(
                    'id' => array('type' => 'string'),
                    'name' => array('type' => 'string'),
                    'email' => array('type' => 'string', 'format' => 'email'),
                    'phone' => array('type' => 'string')
                )
            ),
            'shipping' => array(
                'required' => false,
                'type' => 'object'
            ),
            'payment_method' => array(
                'required' => false,
                'type' => 'string',
                'enum' => array('card', 'bank_transfer', 'wallet')
            ),
            'merchant_order_id' => array(
                'required' => false,
                'type' => 'string'
            ),
            'metadata' => array(
                'required' => false,
                'type' => 'object'
            )
        );
    }
    
    /**
     * Get or create product for ACP session
     */
    private function get_or_create_product(array $item): int {
        // Try to find existing product by SKU
        if (isset($item['sku'])) {
            $existing_product = wc_get_product_id_by_sku($item['sku']);
            if ($existing_product) {
                return $existing_product;
            }
        }
        
        // Create new product
        $product = new WC_Product_Simple();
        $product->set_name($item['name'] ?? 'ACP Product');
        $product->set_sku($item['sku'] ?? 'acp-' . wp_generate_password(8, false));
        $product->set_price($item['price'] ?? 0);
        $product->set_virtual(true);
        $product->set_downloadable(false);
        $product->set_status('publish');
        $product->set_manage_stock(false);
        
        return $product->save();
    }
    
    /**
     * Calculate total details for cart
     */
    private function calculate_total_details($cart): array {
        return [
            'subtotal' => $cart->get_subtotal(),
            'tax' => $cart->get_total_tax(),
            'shipping' => $cart->get_shipping_total(),
            'discount' => $cart->get_discount_total(),
            'total' => $cart->get_total('edit')
        ];
    }
    
    /**
     * Get fulfillment options
     */
    private function get_fulfillment_options(): array {
        $options = [];
        
        // Get available shipping methods
        $shipping_zones = WC_Shipping_Zones::get_zones();
        
        foreach ($shipping_zones as $zone) {
            $zone_obj = new WC_Shipping_Zone($zone['id']);
            $shipping_methods = $zone_obj->get_shipping_methods(true);
            
            foreach ($shipping_methods as $method) {
                if ($method->is_enabled()) {
                    $options[] = [
                        'id' => $method->id,
                        'name' => $method->get_title(),
                        'description' => $method->get_method_description(),
                        'amount' => 0, // Will be calculated based on address
                        'estimated_delivery' => null
                    ];
                }
            }
        }
        
        // Add default zone methods
        $default_zone = new WC_Shipping_Zone(0);
        $default_methods = $default_zone->get_shipping_methods(true);
        
        foreach ($default_methods as $method) {
            if ($method->is_enabled()) {
                $options[] = [
                    'id' => $method->id,
                    'name' => $method->get_title(),
                    'description' => $method->get_method_description(),
                    'amount' => 0,
                    'estimated_delivery' => null
                ];
            }
        }
        
        return $options;
    }
    
    /**
     * Store session data
     */
    private function store_session_data($intent_id, $session_id, $params) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acp_sessions';
        
        $wpdb->insert(
            $table_name,
            array(
                'intent_id' => $intent_id,
                'session_id' => $session_id,
                'status' => 'pending',
                'amount' => $params['amount'],
                'currency' => $params['currency']
            ),
            array('%s', '%s', '%s', '%f', '%s')
        );
    }
    
    /**
     * Process payment
     */
    private function process_payment($session, $params) {
        // This is a simplified payment processing
        // In a real implementation, you would integrate with payment gateways
        
        try {
            // Create WooCommerce order
            $order = wc_create_order();
            
            if (!$order) {
                return array('success' => false, 'error' => 'Failed to create order');
            }
            
            // Add product to order
            $product_id = $this->create_virtual_product(array('amount' => $session->amount));
            $order->add_product(wc_get_product($product_id), 1);
            
            // Set order data
            $order->set_currency($session->currency);
            $order->set_total($session->amount);
            $order->set_payment_method('acp');
            $order->set_payment_method_title('ACP Payment');
            
            // Save order
            $order->save();
            
            // Mark as paid
            $order->payment_complete();
            
            return array(
                'success' => true,
                'order_id' => $order->get_id(),
                'payment_id' => 'pay_' . wp_generate_password(16, false),
                'transaction_id' => 'txn_' . wp_generate_password(16, false)
            );
            
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
}
