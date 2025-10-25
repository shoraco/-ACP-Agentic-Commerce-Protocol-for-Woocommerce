<?php
/**
 * ACP Header Validator - Professional security implementation
 * Based on Magento ACP implementation patterns
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class ACP_Header_Validator {
    
    private const REQUIRED_HEADERS = [
        'Idempotency-Key',
        'Request-Id', 
        'Timestamp',
    ];
    
    private const OPTIONAL_HEADERS = [
        'Signature',
        'API-Version',
        'Authorization'
    ];
    
    private $logger;
    
    public function __construct() {
        $this->logger = new ACP_Logger();
    }
    
    /**
     * Validate all required headers
     * 
     * @throws ACP_Authentication_Exception
     */
    public function validate(): array {
        $headers = [];
        
        // Validate required headers
        foreach (self::REQUIRED_HEADERS as $headerName) {
            $value = $this->get_header($headerName);
            if (empty($value)) {
                $this->logger->error("Missing required header: {$headerName}");
                throw new ACP_Authentication_Exception("Missing required header: {$headerName}");
            }
            $headers[$headerName] = $value;
        }
        
        // Get optional headers
        foreach (self::OPTIONAL_HEADERS as $headerName) {
            $value = $this->get_header($headerName);
            if (!empty($value)) {
                $headers[$headerName] = $value;
            }
        }
        
        return $headers;
    }
    
    /**
     * Get header value (case-insensitive)
     */
    private function get_header(string $headerName): ?string {
        // Try standard HTTP headers
        $value = $this->get_server_header($headerName);
        if ($value) {
            return $value;
        }
        
        // Try uppercase with underscores (HTTP_X_HEADER format)
        $upperName = 'HTTP_' . str_replace('-', '_', strtoupper($headerName));
        $value = $_SERVER[$upperName] ?? null;
        if ($value) {
            return $value;
        }
        
        return null;
    }
    
    /**
     * Get header from server variables
     */
    private function get_server_header(string $headerName): ?string {
        $key = 'HTTP_' . str_replace('-', '_', strtoupper($headerName));
        return $_SERVER[$key] ?? null;
    }
    
    /**
     * Get idempotency key from headers
     */
    public function get_idempotency_key(): string {
        $key = $this->get_header('Idempotency-Key');
        if (empty($key)) {
            throw new ACP_Authentication_Exception('Idempotency-Key header is required');
        }
        return $key;
    }
    
    /**
     * Get request ID from headers
     */
    public function get_request_id(): string {
        $requestId = $this->get_header('Request-Id');
        if (empty($requestId)) {
            throw new ACP_Authentication_Exception('Request-Id header is required');
        }
        return $requestId;
    }
    
    /**
     * Get timestamp from headers
     */
    public function get_timestamp(): int {
        $timestamp = $this->get_header('Timestamp');
        if (empty($timestamp)) {
            throw new ACP_Authentication_Exception('Timestamp header is required');
        }
        
        if (!is_numeric($timestamp)) {
            throw new ACP_Authentication_Exception('Timestamp must be a valid Unix timestamp');
        }
        
        $timestamp = (int) $timestamp;
        
        // Validate timestamp is not too old (5 minutes tolerance)
        $currentTime = time();
        $tolerance = 300; // 5 minutes
        
        if ($timestamp < ($currentTime - $tolerance)) {
            throw new ACP_Authentication_Exception('Timestamp is too old (replay attack prevention)');
        }
        
        if ($timestamp > ($currentTime + $tolerance)) {
            throw new ACP_Authentication_Exception('Timestamp is in the future');
        }
        
        return $timestamp;
    }
    
    /**
     * Get signature from headers
     */
    public function get_signature(): ?string {
        return $this->get_header('Signature');
    }
    
    /**
     * Get API version from headers
     */
    public function get_api_version(): ?string {
        return $this->get_header('API-Version');
    }
    
    /**
     * Get authorization header
     */
    public function get_authorization(): ?string {
        return $this->get_header('Authorization');
    }
}

/**
 * Custom exception for ACP authentication errors
 */
class ACP_Authentication_Exception extends Exception {
    public function __construct(string $message = "", int $code = 401, ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
