<?php
/**
 * ACP Authentication and Security
 * Professional implementation based on Magento ACP patterns
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class ACP_Auth {
    
    /**
     * Validate API request with professional header validation
     */
    public function validate_request($request) {
        try {
            // Validate all required headers
            $header_validator = new ACP_Header_Validator();
            $headers = $header_validator->validate();
            
            // Validate Bearer token
            $auth_header = $headers['Authorization'] ?? null;
            if (!$auth_header) {
                throw new ACP_Authentication_Exception('Authorization header required');
            }
            
            // Extract Bearer token
            if (!preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
                throw new ACP_Authentication_Exception('Invalid authorization format');
            }
            
            $token = $matches[1];
            
            // Validate token
            if (!$this->validate_token($token)) {
                throw new ACP_Authentication_Exception('Invalid or expired token');
            }
            
            // Check idempotency for POST/PUT requests
            if (in_array($request->get_method(), array('POST', 'PUT'))) {
                $this->check_idempotency($headers['Idempotency-Key']);
            }
            
            // Validate timestamp (replay attack prevention)
            $header_validator->get_timestamp();
            
            return true;
            
        } catch (ACP_Authentication_Exception $e) {
            return new WP_Error('authentication_error', $e->getMessage(), array('status' => $e->getCode()));
        }
    }
    
    /**
     * Validate Bearer token
     */
    private function validate_token($token) {
        $api_key = get_option('acp_api_key');
        
        if (!$api_key) {
            return false;
        }
        
        // Simple token validation - in production, use proper JWT or similar
        return hash_equals($api_key, $token);
    }
    
    /**
     * Check idempotency with professional validation
     */
    private function check_idempotency(string $idempotency_key): void {
        if (empty($idempotency_key)) {
            throw new ACP_Authentication_Exception('Idempotency-Key header required');
        }
        
        // Validate idempotency key format
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $idempotency_key)) {
            throw new ACP_Authentication_Exception('Invalid Idempotency-Key format');
        }
        
        // Check if request was already processed
        $cache_key = 'acp_idempotency_' . md5($idempotency_key);
        $cached_result = $this->get_from_cache($cache_key);
        
        if ($cached_result !== false) {
            // Return cached response - this prevents duplicate processing
            throw new ACP_Authentication_Exception('Duplicate request detected (idempotency)');
        }
        
        // Store request for idempotency check (24 hours cache)
        $this->store_in_cache($cache_key, 'processing', 86400);
    }
    
    /**
     * Store idempotency result
     */
    public function store_idempotency_result($idempotency_key, $result) {
        $cache_key = 'acp_idempotency_' . md5($idempotency_key);
        $this->store_in_cache($cache_key, $result, 3600); // 1 hour
    }
    
    /**
     * Get from cache (Redis or WP Transient)
     */
    private function get_from_cache($key) {
        if ($this->is_redis_enabled()) {
            return $this->get_from_redis($key);
        } else {
            return get_transient($key);
        }
    }
    
    /**
     * Store in cache (Redis or WP Transient)
     */
    private function store_in_cache($key, $value, $expiration = 3600) {
        if ($this->is_redis_enabled()) {
            return $this->store_in_redis($key, $value, $expiration);
        } else {
            return set_transient($key, $value, $expiration);
        }
    }
    
    /**
     * Check if Redis is enabled
     */
    private function is_redis_enabled() {
        return get_option('acp_redis_enabled', false) && class_exists('Redis');
    }
    
    /**
     * Get from Redis
     */
    private function get_from_redis($key) {
        try {
            $redis = $this->get_redis_connection();
            if (!$redis) {
                return false;
            }
            
            $value = $redis->get($key);
            return $value !== false ? json_decode($value, true) : false;
            
        } catch (Exception $e) {
            error_log('ACP Redis get error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Store in Redis
     */
    private function store_in_redis($key, $value, $expiration = 3600) {
        try {
            $redis = $this->get_redis_connection();
            if (!$redis) {
                return false;
            }
            
            return $redis->setex($key, $expiration, json_encode($value));
            
        } catch (Exception $e) {
            error_log('ACP Redis set error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get Redis connection
     */
    private function get_redis_connection() {
        static $redis = null;
        
        if ($redis === null) {
            try {
                $redis = new Redis();
                $redis->connect(
                    get_option('acp_redis_host', 'localhost'),
                    get_option('acp_redis_port', 6379)
                );
                
                $password = get_option('acp_redis_password', '');
                if ($password) {
                    $redis->auth($password);
                }
                
                $database = get_option('acp_redis_database', 0);
                $redis->select($database);
                
            } catch (Exception $e) {
                error_log('ACP Redis connection error: ' . $e->getMessage());
                $redis = false;
            }
        }
        
        return $redis;
    }
    
    /**
     * Generate HMAC signature
     */
    public function generate_signature($payload, $secret = null) {
        if (!$secret) {
            $secret = get_option('acp_webhook_secret');
        }
        
        return hash_hmac('sha256', $payload, $secret);
    }
    
    /**
     * Verify HMAC signature
     */
    public function verify_signature($payload, $signature, $secret = null) {
        if (!$secret) {
            $secret = get_option('acp_webhook_secret');
        }
        
        $expected_signature = $this->generate_signature($payload, $secret);
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Generate API key
     */
    public function generate_api_key() {
        return 'acp_' . wp_generate_password(32, false);
    }
    
    /**
     * Generate webhook secret
     */
    public function generate_webhook_secret() {
        return 'whsec_' . wp_generate_password(32, false);
    }
}
