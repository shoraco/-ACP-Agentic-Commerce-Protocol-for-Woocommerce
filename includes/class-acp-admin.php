<?php
/**
 * ACP Admin Interface
 * Professional admin panel based on Magento ACP patterns
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class ACP_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu(): void {
        add_submenu_page(
            'woocommerce',
            'ACP Settings',
            'ACP Settings',
            'manage_woocommerce',
            'acp-settings',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings(): void {
        // General Settings
        register_setting('acp_settings', 'acp_api_key');
        register_setting('acp_settings', 'acp_webhook_secret');
        register_setting('acp_settings', 'acp_sandbox_mode');
        register_setting('acp_settings', 'acp_enable_feed');
        register_setting('acp_settings', 'acp_enable_webhooks');
        register_setting('acp_settings', 'acp_webhook_url');
        
        // Security Settings
        register_setting('acp_settings', 'acp_enable_signature_validation');
        register_setting('acp_settings', 'acp_timestamp_tolerance');
        
        // Redis Settings
        register_setting('acp_settings', 'acp_redis_enabled');
        register_setting('acp_settings', 'acp_redis_host');
        register_setting('acp_settings', 'acp_redis_port');
        register_setting('acp_settings', 'acp_redis_password');
        register_setting('acp_settings', 'acp_redis_database');
        
        // Feed Settings
        register_setting('acp_settings', 'acp_feed_max_products');
        register_setting('acp_settings', 'acp_feed_categories');
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts(string $hook): void {
        if ($hook !== 'woocommerce_page_acp-settings') {
            return;
        }
        
        wp_enqueue_script('acp-admin', WOOCOMMERCE_ACP_PLUGIN_URL . 'assets/admin.js', array('jquery'), WOOCOMMERCE_ACP_VERSION, true);
        wp_enqueue_style('acp-admin', WOOCOMMERCE_ACP_PLUGIN_URL . 'assets/admin.css', array(), WOOCOMMERCE_ACP_VERSION);
    }
    
    /**
     * Admin page
     */
    public function admin_page(): void {
        ?>
        <div class="wrap">
            <h1>ACP for WooCommerce Settings</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('acp_settings'); ?>
                
                <div class="acp-admin-tabs">
                    <nav class="nav-tab-wrapper">
                        <a href="#general" class="nav-tab nav-tab-active">General</a>
                        <a href="#security" class="nav-tab">Security</a>
                        <a href="#feed" class="nav-tab">Product Feed</a>
                        <a href="#webhooks" class="nav-tab">Webhooks</a>
                        <a href="#redis" class="nav-tab">Redis</a>
                        <a href="#logs" class="nav-tab">Logs</a>
                    </nav>
                    
                    <!-- General Tab -->
                    <div id="general" class="tab-content">
                        <table class="form-table">
                            <tr>
                                <th scope="row">API Key</th>
                                <td>
                                    <input type="text" name="acp_api_key" value="<?php echo esc_attr(get_option('acp_api_key')); ?>" class="regular-text" />
                                    <button type="button" class="button" id="generate-api-key">Generate New Key</button>
                                    <p class="description">API key for ACP authentication</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Webhook Secret</th>
                                <td>
                                    <input type="text" name="acp_webhook_secret" value="<?php echo esc_attr(get_option('acp_webhook_secret')); ?>" class="regular-text" />
                                    <button type="button" class="button" id="generate-webhook-secret">Generate New Secret</button>
                                    <p class="description">Secret for webhook signature validation</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Sandbox Mode</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="acp_sandbox_mode" value="1" <?php checked(get_option('acp_sandbox_mode'), 1); ?> />
                                        Enable sandbox mode for testing
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Security Tab -->
                    <div id="security" class="tab-content" style="display: none;">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Enable Signature Validation</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="acp_enable_signature_validation" value="1" <?php checked(get_option('acp_enable_signature_validation'), 1); ?> />
                                        Enable HMAC signature validation
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Timestamp Tolerance</th>
                                <td>
                                    <input type="number" name="acp_timestamp_tolerance" value="<?php echo esc_attr(get_option('acp_timestamp_tolerance', 300)); ?>" min="60" max="3600" />
                                    <p class="description">Seconds (default: 300)</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Feed Tab -->
                    <div id="feed" class="tab-content" style="display: none;">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Enable Product Feed</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="acp_enable_feed" value="1" <?php checked(get_option('acp_enable_feed', 1), 1); ?> />
                                        Enable product feed at /acp/feed
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Max Products</th>
                                <td>
                                    <input type="number" name="acp_feed_max_products" value="<?php echo esc_attr(get_option('acp_feed_max_products', 1000)); ?>" min="1" max="10000" />
                                    <p class="description">Maximum products in feed (default: 1000)</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Categories</th>
                                <td>
                                    <input type="text" name="acp_feed_categories" value="<?php echo esc_attr(get_option('acp_feed_categories')); ?>" class="regular-text" />
                                    <p class="description">Comma-separated category IDs (empty = all)</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Webhooks Tab -->
                    <div id="webhooks" class="tab-content" style="display: none;">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Enable Webhooks</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="acp_enable_webhooks" value="1" <?php checked(get_option('acp_enable_webhooks', 1), 1); ?> />
                                        Enable webhook notifications
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Webhook URL</th>
                                <td>
                                    <input type="url" name="acp_webhook_url" value="<?php echo esc_attr(get_option('acp_webhook_url')); ?>" class="regular-text" />
                                    <p class="description">Target URL for webhook notifications</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Redis Tab -->
                    <div id="redis" class="tab-content" style="display: none;">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Enable Redis</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="acp_redis_enabled" value="1" <?php checked(get_option('acp_redis_enabled'), 1); ?> />
                                        Use Redis for caching (recommended for production)
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Redis Host</th>
                                <td>
                                    <input type="text" name="acp_redis_host" value="<?php echo esc_attr(get_option('acp_redis_host', 'localhost')); ?>" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Redis Port</th>
                                <td>
                                    <input type="number" name="acp_redis_port" value="<?php echo esc_attr(get_option('acp_redis_port', 6379)); ?>" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Redis Password</th>
                                <td>
                                    <input type="password" name="acp_redis_password" value="<?php echo esc_attr(get_option('acp_redis_password')); ?>" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Redis Database</th>
                                <td>
                                    <input type="number" name="acp_redis_database" value="<?php echo esc_attr(get_option('acp_redis_database', 0)); ?>" min="0" max="15" />
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Logs Tab -->
                    <div id="logs" class="tab-content" style="display: none;">
                        <h3>ACP Logs</h3>
                        <p>
                            <button type="button" class="button" id="view-logs">View Logs</button>
                            <button type="button" class="button" id="clear-logs">Clear Logs</button>
                            <button type="button" class="button" id="download-logs">Download Logs</button>
                        </p>
                        <div id="log-content" style="display: none;">
                            <pre style="background: #f1f1f1; padding: 10px; max-height: 400px; overflow-y: auto;"></pre>
                        </div>
                    </div>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.nav-tab').click(function(e) {
                e.preventDefault();
                $('.nav-tab').removeClass('nav-tab-active');
                $('.tab-content').hide();
                $(this).addClass('nav-tab-active');
                $($(this).attr('href')).show();
            });
            
            // Generate API key
            $('#generate-api-key').click(function() {
                $.post(ajaxurl, {
                    action: 'acp_generate_api_key'
                }, function(response) {
                    if (response.success) {
                        $('input[name="acp_api_key"]').val(response.data);
                    }
                });
            });
            
            // Generate webhook secret
            $('#generate-webhook-secret').click(function() {
                $.post(ajaxurl, {
                    action: 'acp_generate_webhook_secret'
                }, function(response) {
                    if (response.success) {
                        $('input[name="acp_webhook_secret"]').val(response.data);
                    }
                });
            });
            
            // View logs
            $('#view-logs').click(function() {
                $('#log-content').toggle();
                if ($('#log-content').is(':visible')) {
                    $.post(ajaxurl, {
                        action: 'acp_get_logs'
                    }, function(response) {
                        if (response.success) {
                            $('#log-content pre').text(response.data);
                        }
                    });
                }
            });
            
            // Clear logs
            $('#clear-logs').click(function() {
                if (confirm('Are you sure you want to clear all logs?')) {
                    $.post(ajaxurl, {
                        action: 'acp_clear_logs'
                    }, function(response) {
                        if (response.success) {
                            alert('Logs cleared successfully');
                            $('#log-content pre').text('');
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
}
