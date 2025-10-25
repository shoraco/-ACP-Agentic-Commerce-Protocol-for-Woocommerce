<?php
/**
 * ACP Setup Wizard
 * User-friendly setup process for non-technical users
 * 
 * @link https://github.com/agentic-commerce-protocol/agentic-commerce-protocol
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ACP Setup Wizard Class
 * 
 * Provides a step-by-step setup wizard for non-technical users
 * to easily configure the ACP plugin without technical knowledge.
 * 
 * @since 1.0.0
 * @package WooCommerce_ACP
 */
class ACP_Setup_Wizard {
    
    /**
     * Current step
     * 
     * @var int
     */
    private $current_step = 1;
    
    /**
     * Total steps
     * 
     * @var int
     */
    private $total_steps = 5;
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_setup_page'));
        add_action('wp_ajax_acp_setup_wizard', array($this, 'handle_setup_request'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Add setup wizard page
     */
    public function add_setup_page(): void {
        add_submenu_page(
            'woocommerce',
            'ACP Setup Wizard',
            'ACP Setup',
            'manage_woocommerce',
            'acp-setup-wizard',
            array($this, 'setup_wizard_page')
        );
    }
    
    /**
     * Enqueue setup wizard scripts
     */
    public function enqueue_scripts(string $hook): void {
        if ($hook !== 'woocommerce_page_acp-setup-wizard') {
            return;
        }
        
        wp_enqueue_script('acp-setup-wizard', WOOCOMMERCE_ACP_PLUGIN_URL . 'assets/setup-wizard.js', array('jquery'), WOOCOMMERCE_ACP_VERSION, true);
        wp_enqueue_style('acp-setup-wizard', WOOCOMMERCE_ACP_PLUGIN_URL . 'assets/setup-wizard.css', array(), WOOCOMMERCE_ACP_VERSION);
        
        wp_localize_script('acp-setup-wizard', 'acpSetup', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('acp_setup_nonce'),
            'steps' => $this->total_steps
        ));
    }
    
    /**
     * Setup wizard page
     */
    public function setup_wizard_page(): void {
        $this->current_step = isset($_GET['step']) ? (int) $_GET['step'] : 1;
        ?>
        <div class="wrap acp-setup-wizard">
            <div class="acp-wizard-header">
                <h1>🚀 ACP for WooCommerce Setup</h1>
                <p class="acp-wizard-subtitle">Let's get your store ready for AI agents in just 5 minutes!</p>
            </div>
            
            <div class="acp-wizard-progress">
                <div class="acp-progress-bar">
                    <div class="acp-progress-fill" style="width: <?php echo ($this->current_step / $this->total_steps) * 100; ?>%"></div>
                </div>
                <div class="acp-progress-steps">
                    <?php for ($i = 1; $i <= $this->total_steps; $i++): ?>
                        <div class="acp-step <?php echo $i <= $this->current_step ? 'active' : ''; ?>">
                            <span class="acp-step-number"><?php echo $i; ?></span>
                            <span class="acp-step-label"><?php echo $this->get_step_label($i); ?></span>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="acp-wizard-content">
                <?php $this->render_current_step(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get step label
     */
    private function get_step_label(int $step): string {
        $labels = array(
            1 => 'Welcome',
            2 => 'Shora Account',
            3 => 'API Keys',
            4 => 'Test Connection',
            5 => 'Complete'
        );
        
        return $labels[$step] ?? 'Step ' . $step;
    }
    
    /**
     * Render current step
     */
    private function render_current_step(): void {
        switch ($this->current_step) {
            case 1:
                $this->render_welcome_step();
                break;
            case 2:
                $this->render_account_step();
                break;
            case 3:
                $this->render_api_keys_step();
                break;
            case 4:
                $this->render_test_step();
                break;
            case 5:
                $this->render_complete_step();
                break;
            default:
                $this->render_welcome_step();
        }
    }
    
    /**
     * Render welcome step
     */
    private function render_welcome_step(): void {
        ?>
        <div class="acp-step-content">
            <div class="acp-welcome-card">
                <h2>🎉 Welcome to ACP for WooCommerce!</h2>
                <p>You're about to enable AI agents (like ChatGPT) to discover and purchase products from your store. This is the future of e-commerce!</p>
                
                <div class="acp-benefits">
                    <h3>What you'll get:</h3>
                    <ul>
                        <li>✅ AI agents can browse your products</li>
                        <li>✅ Customers can shop through ChatGPT</li>
                        <li>✅ Automatic order processing</li>
                        <li>✅ Real-time inventory updates</li>
                        <li>✅ Professional admin dashboard</li>
                    </ul>
                </div>
                
                <div class="acp-time-estimate">
                    <strong>⏱️ Setup time: 5 minutes</strong>
                </div>
                
                <div class="acp-wizard-actions">
                    <a href="?page=acp-setup-wizard&step=2" class="button button-primary button-large">
                        Let's Get Started! 🚀
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render account step
     */
    private function render_account_step(): void {
        ?>
        <div class="acp-step-content">
            <div class="acp-account-card">
                <h2>📝 Step 1: Create Your Shora Account</h2>
                <p>You need a free Shora account to get your API keys. Don't worry, it's completely free!</p>
                
                <div class="acp-account-steps">
                    <div class="acp-account-step">
                        <div class="acp-step-icon">1️⃣</div>
                        <div class="acp-step-content">
                            <h3>Go to Shora Dashboard</h3>
                            <p>Click the button below to open Shora in a new tab</p>
                            <a href="https://app.shora.cloud" target="_blank" class="button button-secondary">
                                Open Shora Dashboard ↗️
                            </a>
                        </div>
                    </div>
                    
                    <div class="acp-account-step">
                        <div class="acp-step-icon">2️⃣</div>
                        <div class="acp-step-content">
                            <h3>Sign Up (It's Free!)</h3>
                            <p>Create your account with your email address</p>
                        </div>
                    </div>
                    
                    <div class="acp-account-step">
                        <div class="acp-step-icon">3️⃣</div>
                        <div class="acp-step-content">
                            <h3>Verify Your Email</h3>
                            <p>Check your email and click the verification link</p>
                        </div>
                    </div>
                </div>
                
                <div class="acp-wizard-actions">
                    <a href="?page=acp-setup-wizard&step=1" class="button button-secondary">
                        ← Back
                    </a>
                    <a href="?page=acp-setup-wizard&step=3" class="button button-primary">
                        I've Created My Account →
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render API keys step
     */
    private function render_api_keys_step(): void {
        ?>
        <div class="acp-step-content">
            <div class="acp-api-keys-card">
                <h2>🔑 Step 2: Get Your API Keys</h2>
                <p>Now let's get your API keys from Shora dashboard.</p>
                
                <div class="acp-api-steps">
                    <div class="acp-api-step">
                        <div class="acp-step-icon">1️⃣</div>
                        <div class="acp-step-content">
                            <h3>Go to API Keys Section</h3>
                            <p>In your Shora dashboard, click on "API Keys" in the sidebar</p>
                            <a href="https://app.shora.cloud/keys" target="_blank" class="button button-secondary">
                                Open API Keys Page ↗️
                            </a>
                        </div>
                    </div>
                    
                    <div class="acp-api-step">
                        <div class="acp-step-icon">2️⃣</div>
                        <div class="acp-step-content">
                            <h3>Generate New API Key</h3>
                            <p>Click "Generate New Key" and copy the API key</p>
                        </div>
                    </div>
                    
                    <div class="acp-api-step">
                        <div class="acp-step-icon">3️⃣</div>
                        <div class="acp-step-content">
                            <h3>Generate Webhook Secret</h3>
                            <p>Click "Generate Webhook Secret" and copy the secret</p>
                        </div>
                    </div>
                </div>
                
                <div class="acp-api-form">
                    <h3>Enter Your API Keys Below:</h3>
                    <form id="acp-api-keys-form">
                        <div class="acp-form-group">
                            <label for="api_key">API Key:</label>
                            <input type="text" id="api_key" name="api_key" placeholder="sk_..." class="acp-form-input" required>
                            <small>This starts with "sk_" and is about 50 characters long</small>
                        </div>
                        
                        <div class="acp-form-group">
                            <label for="webhook_secret">Webhook Secret:</label>
                            <input type="text" id="webhook_secret" name="webhook_secret" placeholder="whsec_..." class="acp-form-input" required>
                            <small>This starts with "whsec_" and is about 50 characters long</small>
                        </div>
                        
                        <div class="acp-form-group">
                            <label>
                                <input type="checkbox" id="sandbox_mode" name="sandbox_mode" checked>
                                Enable Sandbox Mode (Recommended for testing)
                            </label>
                        </div>
                    </form>
                </div>
                
                <div class="acp-wizard-actions">
                    <a href="?page=acp-setup-wizard&step=2" class="button button-secondary">
                        ← Back
                    </a>
                    <button id="save-api-keys" class="button button-primary">
                        Save API Keys →
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render test step
     */
    private function render_test_step(): void {
        ?>
        <div class="acp-step-content">
            <div class="acp-test-card">
                <h2>🧪 Step 3: Test Your Connection</h2>
                <p>Let's make sure everything is working correctly!</p>
                
                <div class="acp-test-results" id="acp-test-results">
                    <div class="acp-test-item">
                        <span class="acp-test-icon">⏳</span>
                        <span class="acp-test-text">Testing API connection...</span>
                    </div>
                </div>
                
                <div class="acp-test-actions">
                    <button id="run-connection-test" class="button button-primary">
                        Run Connection Test
                    </button>
                </div>
                
                <div class="acp-wizard-actions">
                    <a href="?page=acp-setup-wizard&step=3" class="button button-secondary">
                        ← Back
                    </a>
                    <a href="?page=acp-setup-wizard&step=5" class="button button-primary" id="proceed-to-complete" style="display: none;">
                        Continue to Complete →
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render complete step
     */
    private function render_complete_step(): void {
        ?>
        <div class="acp-step-content">
            <div class="acp-complete-card">
                <h2>🎉 Congratulations! You're All Set!</h2>
                <p>Your WooCommerce store is now ready for AI agents!</p>
                
                <div class="acp-success-features">
                    <h3>✅ What's Now Enabled:</h3>
                    <ul>
                        <li>AI agents can discover your products</li>
                        <li>Customers can shop through ChatGPT</li>
                        <li>Automatic order processing</li>
                        <li>Real-time inventory updates</li>
                        <li>Professional admin dashboard</li>
                    </ul>
                </div>
                
                <div class="acp-next-steps">
                    <h3>🚀 Next Steps:</h3>
                    <ol>
                        <li>Test your store with ChatGPT</li>
                        <li>Monitor your ACP dashboard</li>
                        <li>Configure additional settings if needed</li>
                    </ol>
                </div>
                
                <div class="acp-wizard-actions">
                    <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=acp'); ?>" class="button button-primary button-large">
                        Go to ACP Settings
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=acp-settings'); ?>" class="button button-secondary">
                        View Dashboard
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle setup wizard AJAX requests
     */
    public function handle_setup_request(): void {
        check_ajax_referer('acp_setup_nonce', 'nonce');
        
        $action = sanitize_text_field($_POST['action_type']);
        
        switch ($action) {
            case 'save_api_keys':
                $this->save_api_keys();
                break;
            case 'test_connection':
                $this->test_connection();
                break;
            default:
                wp_send_json_error('Invalid action');
        }
    }
    
    /**
     * Save API keys
     */
    private function save_api_keys(): void {
        $api_key = sanitize_text_field($_POST['api_key']);
        $webhook_secret = sanitize_text_field($_POST['webhook_secret']);
        $sandbox_mode = isset($_POST['sandbox_mode']) ? true : false;
        
        if (empty($api_key) || empty($webhook_secret)) {
            wp_send_json_error('API key and webhook secret are required');
        }
        
        update_option('acp_api_key', $api_key);
        update_option('acp_webhook_secret', $webhook_secret);
        update_option('acp_sandbox_mode', $sandbox_mode);
        
        wp_send_json_success('API keys saved successfully');
    }
    
    /**
     * Test connection
     */
    private function test_connection(): void {
        $test_results = array();
        
        // Test API key
        $api_key = get_option('acp_api_key');
        if (empty($api_key)) {
            $test_results[] = array(
                'test' => 'API Key',
                'status' => 'FAIL',
                'message' => 'API key not found'
            );
        } else {
            $test_results[] = array(
                'test' => 'API Key',
                'status' => 'PASS',
                'message' => 'API key is configured'
            );
        }
        
        // Test webhook secret
        $webhook_secret = get_option('acp_webhook_secret');
        if (empty($webhook_secret)) {
            $test_results[] = array(
                'test' => 'Webhook Secret',
                'status' => 'FAIL',
                'message' => 'Webhook secret not found'
            );
        } else {
            $test_results[] = array(
                'test' => 'Webhook Secret',
                'status' => 'PASS',
                'message' => 'Webhook secret is configured'
            );
        }
        
        // Test WooCommerce
        if (!class_exists('WooCommerce')) {
            $test_results[] = array(
                'test' => 'WooCommerce',
                'status' => 'FAIL',
                'message' => 'WooCommerce is not active'
            );
        } else {
            $test_results[] = array(
                'test' => 'WooCommerce',
                'status' => 'PASS',
                'message' => 'WooCommerce is active'
            );
        }
        
        wp_send_json_success($test_results);
    }
}
