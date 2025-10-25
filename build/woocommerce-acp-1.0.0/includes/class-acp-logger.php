<?php
/**
 * ACP Logger - Professional logging implementation
 * Based on Magento ACP implementation patterns
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class ACP_Logger {
    
    private const LOG_LEVEL_ERROR = 'error';
    private const LOG_LEVEL_WARNING = 'warning';
    private const LOG_LEVEL_INFO = 'info';
    private const LOG_LEVEL_DEBUG = 'debug';
    
    private $log_file;
    
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_file = $upload_dir['basedir'] . '/acp-logs/acp.log';
        
        // Ensure log directory exists
        $log_dir = dirname($this->log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
    }
    
    /**
     * Log error message
     */
    public function error(string $message, array $context = []): void {
        $this->log(self::LOG_LEVEL_ERROR, $message, $context);
    }
    
    /**
     * Log warning message
     */
    public function warning(string $message, array $context = []): void {
        $this->log(self::LOG_LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Log info message
     */
    public function info(string $message, array $context = []): void {
        $this->log(self::LOG_LEVEL_INFO, $message, $context);
    }
    
    /**
     * Log debug message
     */
    public function debug(string $message, array $context = []): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->log(self::LOG_LEVEL_DEBUG, $message, $context);
        }
    }
    
    /**
     * Write log entry
     */
    private function log(string $level, string $message, array $context = []): void {
        $timestamp = current_time('Y-m-d H:i:s');
        $context_str = !empty($context) ? ' ' . json_encode($context) : '';
        $log_entry = "[{$timestamp}] [{$level}] {$message}{$context_str}" . PHP_EOL;
        
        // Write to file
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // Also log to WordPress debug log if enabled
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log("ACP [{$level}]: {$message}" . $context_str);
        }
    }
    
    /**
     * Get log file path
     */
    public function get_log_file(): string {
        return $this->log_file;
    }
    
    /**
     * Clear log file
     */
    public function clear_logs(): bool {
        if (file_exists($this->log_file)) {
            return unlink($this->log_file);
        }
        return true;
    }
    
    /**
     * Get log file size
     */
    public function get_log_size(): int {
        if (file_exists($this->log_file)) {
            return filesize($this->log_file);
        }
        return 0;
    }
    
    /**
     * Rotate log file if too large
     */
    public function rotate_logs(int $max_size = 10485760): void { // 10MB
        if ($this->get_log_size() > $max_size) {
            $backup_file = $this->log_file . '.' . time();
            rename($this->log_file, $backup_file);
            
            // Keep only last 5 backup files
            $this->cleanup_old_logs();
        }
    }
    
    /**
     * Clean up old log files
     */
    private function cleanup_old_logs(int $keep_count = 5): void {
        $log_dir = dirname($this->log_file);
        $pattern = $log_dir . '/acp.log.*';
        $files = glob($pattern);
        
        if (count($files) > $keep_count) {
            // Sort by modification time (oldest first)
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Remove oldest files
            $files_to_remove = array_slice($files, 0, count($files) - $keep_count);
            foreach ($files_to_remove as $file) {
                unlink($file);
            }
        }
    }
}
