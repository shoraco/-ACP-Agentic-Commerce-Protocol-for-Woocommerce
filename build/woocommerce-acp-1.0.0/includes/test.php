<?php
/**
 * ACP Plugin Tests
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACP_Test {
    
    /**
     * Run basic plugin tests
     */
    public static function run_tests() {
        $tests = array(
            'test_plugin_activation' => 'Plugin Activation',
            'test_database_tables' => 'Database Tables',
            'test_api_endpoints' => 'API Endpoints',
            'test_authentication' => 'Authentication',
            'test_product_feed' => 'Product Feed',
            'test_webhook_handling' => 'Webhook Handling'
        );
        
        $results = array();
        
        foreach ($tests as $test_method => $test_name) {
            $start_time = microtime(true);
            $result = self::$test_method();
            $end_time = microtime(true);
            
            $results[] = array(
                'name' => $test_name,
                'method' => $test_method,
                'status' => $result ? 'PASS' : 'FAIL',
                'duration' => round(($end_time - $start_time) * 1000, 2) . 'ms'
            );
        }
        
        return $results;
    }
    
    /**
     * Test plugin activation
     */
    private static function test_plugin_activation() {
        // Check if plugin is active
        if (!is_plugin_active('woocommerce-acp/woocommerce-acp.php')) {
            return false;
        }
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return false;
        }
        
        // Check if main class exists
        if (!class_exists('WooCommerce_ACP')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Test database tables
     */
    private static function test_database_tables() {
        global $wpdb;
        
        // Check ACP sessions table
        $sessions_table = $wpdb->prefix . 'acp_sessions';
        $sessions_exists = $wpdb->get_var("SHOW TABLES LIKE '$sessions_table'") === $sessions_table;
        
        // Check ACP webhooks table
        $webhooks_table = $wpdb->prefix . 'acp_webhooks';
        $webhooks_exists = $wpdb->get_var("SHOW TABLES LIKE '$webhooks_table'") === $webhooks_table;
        
        return $sessions_exists && $webhooks_exists;
    }
    
    /**
     * Test API endpoints
     */
    private static function test_api_endpoints() {
        // Test if REST API routes are registered
        $routes = rest_get_server()->get_routes();
        
        $required_routes = array(
            '/acp/v1/checkout_sessions',
            '/acp/v1/checkout_sessions/(?P<session_id>[a-zA-Z0-9_-]+)',
            '/acp/v1/checkout_sessions/(?P<session_id>[a-zA-Z0-9_-]+)/complete',
            '/acp/v1/checkout_sessions/(?P<session_id>[a-zA-Z0-9_-]+)/cancel'
        );
        
        foreach ($required_routes as $route) {
            if (!isset($routes[$route])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Test authentication
     */
    private static function test_authentication() {
        // Check if API key is set
        $api_key = get_option('acp_api_key');
        if (empty($api_key)) {
            return false;
        }
        
        // Check if webhook secret is set
        $webhook_secret = get_option('acp_webhook_secret');
        if (empty($webhook_secret)) {
            return false;
        }
        
        // Test auth class
        if (!class_exists('ACP_Auth')) {
            return false;
        }
        
        $auth = new ACP_Auth();
        
        // Test signature generation
        $test_payload = 'test_payload';
        $signature = $auth->generate_signature($test_payload);
        
        if (empty($signature)) {
            return false;
        }
        
        // Test signature verification
        $is_valid = $auth->verify_signature($test_payload, $signature);
        
        return $is_valid;
    }
    
    /**
     * Test product feed
     */
    private static function test_product_feed() {
        // Check if feed generator class exists
        if (!class_exists('ACP_Feed_Generator')) {
            return false;
        }
        
        // Check if feed is enabled
        $feed_enabled = get_option('acp_enable_feed', true);
        if (!$feed_enabled) {
            return false;
        }
        
        // Test feed generation
        $feed_generator = new ACP_Feed_Generator();
        
        // Check if rewrite rules are added
        $rewrite_rules = get_option('rewrite_rules');
        if (!isset($rewrite_rules['^acp/feed/?$'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Test webhook handling
     */
    private static function test_webhook_handling() {
        // Check if webhook class exists
        if (!class_exists('ACP_Webhook')) {
            return false;
        }
        
        // Check if webhooks are enabled
        $webhooks_enabled = get_option('acp_enable_webhooks', true);
        if (!$webhooks_enabled) {
            return false;
        }
        
        // Test webhook class instantiation
        $webhook = new ACP_Webhook();
        
        // Check if webhook table exists
        global $wpdb;
        $webhooks_table = $wpdb->prefix . 'acp_webhooks';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$webhooks_table'") === $webhooks_table;
        
        return $table_exists;
    }
    
    /**
     * Test checkout session creation
     */
    public static function test_checkout_session_creation() {
        $test_data = array(
            'amount' => 100.00,
            'currency' => 'TRY',
            'buyer' => array(
                'id' => 'test_buyer_123',
                'name' => 'Test User',
                'email' => 'test@example.com'
            )
        );
        
        // Create a mock request
        $request = new WP_REST_Request('POST', '/acp/v1/checkout_sessions');
        $request->set_body_params($test_data);
        
        // Set authorization header
        $api_key = get_option('acp_api_key');
        $request->set_header('authorization', 'Bearer ' . $api_key);
        $request->set_header('idempotency-key', 'test-' . time());
        
        // Test API endpoint
        $api = new ACP_API();
        $response = $api->create_checkout_session($request);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        // Check response structure
        $required_fields = array('intent_id', 'session_id', 'status', 'amount', 'currency');
        foreach ($required_fields as $field) {
            if (!isset($response[$field])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Test product feed generation
     */
    public static function test_product_feed_generation() {
        // Create a test product
        $product = new WC_Product_Simple();
        $product->set_name('Test Product');
        $product->set_sku('test-product-123');
        $product->set_price(50.00);
        $product->set_status('publish');
        $product_id = $product->save();
        
        if (!$product_id) {
            return false;
        }
        
        // Generate feed
        $feed_generator = new ACP_Feed_Generator();
        $products = $feed_generator->get_products();
        
        // Check if test product is in feed
        $found = false;
        foreach ($products as $feed_product) {
            if ($feed_product['id'] == $product_id) {
                $found = true;
                break;
            }
        }
        
        // Clean up test product
        wp_delete_post($product_id, true);
        
        return $found;
    }
    
    /**
     * Generate test report
     */
    public static function generate_test_report() {
        $results = self::run_tests();
        
        $report = array(
            'timestamp' => current_time('c'),
            'plugin_version' => WOOCOMMERCE_ACP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'woocommerce_version' => WC()->version,
            'php_version' => PHP_VERSION,
            'tests' => $results,
            'summary' => array(
                'total' => count($results),
                'passed' => count(array_filter($results, function($test) {
                    return $test['status'] === 'PASS';
                })),
                'failed' => count(array_filter($results, function($test) {
                    return $test['status'] === 'FAIL';
                }))
            )
        );
        
        return $report;
    }
}
