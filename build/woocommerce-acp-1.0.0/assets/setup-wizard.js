/**
 * ACP Setup Wizard JavaScript
 * Interactive setup process for non-technical users
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Setup wizard functionality
    const SetupWizard = {
        init: function() {
            this.bindEvents();
            this.initializeForm();
        },
        
        bindEvents: function() {
            // Save API keys
            $('#save-api-keys').on('click', this.saveApiKeys.bind(this));
            
            // Run connection test
            $('#run-connection-test').on('click', this.runConnectionTest.bind(this));
            
            // Form validation
            $('#acp-api-keys-form input').on('blur', this.validateField.bind(this));
            
            // Real-time validation
            $('#api_key, #webhook_secret').on('input', this.validateApiKeys.bind(this));
        },
        
        initializeForm: function() {
            // Add visual feedback to form inputs
            $('.acp-form-input').each(function() {
                $(this).on('focus', function() {
                    $(this).parent().addClass('focused');
                }).on('blur', function() {
                    $(this).parent().removeClass('focused');
                });
            });
        },
        
        saveApiKeys: function() {
            const $button = $('#save-api-keys');
            const $form = $('#acp-api-keys-form');
            
            // Validate form
            if (!this.validateForm()) {
                this.showError('Please fill in all required fields correctly.');
                return;
            }
            
            // Show loading state
            $button.prop('disabled', true).html('<span class="acp-loading"></span> Saving...');
            
            // Collect form data
            const formData = {
                action: 'acp_setup_wizard',
                action_type: 'save_api_keys',
                api_key: $('#api_key').val(),
                webhook_secret: $('#webhook_secret').val(),
                sandbox_mode: $('#sandbox_mode').is(':checked'),
                nonce: acpSetup.nonce
            };
            
            // Send AJAX request
            $.post(acpSetup.ajaxUrl, formData)
                .done(function(response) {
                    if (response.success) {
                        SetupWizard.showSuccess('API keys saved successfully!');
                        setTimeout(function() {
                            window.location.href = '?page=acp-setup-wizard&step=4';
                        }, 1500);
                    } else {
                        SetupWizard.showError(response.data || 'Failed to save API keys.');
                    }
                })
                .fail(function() {
                    SetupWizard.showError('Network error. Please try again.');
                })
                .always(function() {
                    $button.prop('disabled', false).html('Save API Keys →');
                });
        },
        
        runConnectionTest: function() {
            const $button = $('#run-connection-test');
            const $results = $('#acp-test-results');
            
            // Show loading state
            $button.prop('disabled', true).html('<span class="acp-loading"></span> Testing...');
            $results.html('<div class="acp-test-item"><span class="acp-test-icon">⏳</span><span class="acp-test-text">Running connection tests...</span></div>');
            
            // Send AJAX request
            $.post(acpSetup.ajaxUrl, {
                action: 'acp_setup_wizard',
                action_type: 'test_connection',
                nonce: acpSetup.nonce
            })
            .done(function(response) {
                if (response.success) {
                    SetupWizard.displayTestResults(response.data);
                    $('#proceed-to-complete').show();
                } else {
                    SetupWizard.showError('Connection test failed.');
                }
            })
            .fail(function() {
                SetupWizard.showError('Network error during connection test.');
            })
            .always(function() {
                $button.prop('disabled', false).html('Run Connection Test');
            });
        },
        
        displayTestResults: function(results) {
            const $results = $('#acp-test-results');
            let html = '';
            
            results.forEach(function(result) {
                const icon = result.status === 'PASS' ? '✅' : '❌';
                const statusClass = result.status === 'PASS' ? 'pass' : 'fail';
                
                html += `
                    <div class="acp-test-item ${statusClass}">
                        <span class="acp-test-icon">${icon}</span>
                        <span class="acp-test-text">${result.test}: ${result.message}</span>
                    </div>
                `;
            });
            
            $results.html(html);
            
            // Add success animation
            $('.acp-test-item.pass').addClass('acp-success');
        },
        
        validateForm: function() {
            let isValid = true;
            
            // Validate API key
            const apiKey = $('#api_key').val();
            if (!apiKey || apiKey.length < 32) {
                this.highlightField('#api_key', false);
                isValid = false;
            } else {
                this.highlightField('#api_key', true);
            }
            
            // Validate webhook secret
            const webhookSecret = $('#webhook_secret').val();
            if (!webhookSecret || webhookSecret.length < 32) {
                this.highlightField('#webhook_secret', false);
                isValid = false;
            } else {
                this.highlightField('#webhook_secret', true);
            }
            
            return isValid;
        },
        
        validateField: function(event) {
            const $field = $(event.target);
            const value = $field.val();
            const fieldName = $field.attr('id');
            
            let isValid = false;
            
            switch (fieldName) {
                case 'api_key':
                    isValid = value.length >= 32 && value.startsWith('sk_');
                    break;
                case 'webhook_secret':
                    isValid = value.length >= 32 && value.startsWith('whsec_');
                    break;
                default:
                    isValid = value.length > 0;
            }
            
            this.highlightField($field, isValid);
        },
        
        validateApiKeys: function() {
            const apiKey = $('#api_key').val();
            const webhookSecret = $('#webhook_secret').val();
            
            // Real-time validation feedback
            if (apiKey.length > 0) {
                if (apiKey.startsWith('sk_') && apiKey.length >= 32) {
                    this.highlightField('#api_key', true);
                } else {
                    this.highlightField('#api_key', false);
                }
            }
            
            if (webhookSecret.length > 0) {
                if (webhookSecret.startsWith('whsec_') && webhookSecret.length >= 32) {
                    this.highlightField('#webhook_secret', true);
                } else {
                    this.highlightField('#webhook_secret', false);
                }
            }
        },
        
        highlightField: function(selector, isValid) {
            const $field = $(selector);
            const $group = $field.closest('.acp-form-group');
            
            if (isValid) {
                $field.removeClass('error').addClass('success');
                $group.removeClass('error').addClass('success');
            } else {
                $field.removeClass('success').addClass('error');
                $group.removeClass('success').addClass('error');
            }
        },
        
        showSuccess: function(message) {
            this.showNotification(message, 'success');
        },
        
        showError: function(message) {
            this.showNotification(message, 'error');
        },
        
        showNotification: function(message, type) {
            const $notification = $(`
                <div class="acp-notification acp-notification-${type}">
                    <span class="acp-notification-icon">${type === 'success' ? '✅' : '❌'}</span>
                    <span class="acp-notification-text">${message}</span>
                </div>
            `);
            
            // Remove existing notifications
            $('.acp-notification').remove();
            
            // Add new notification
            $('.acp-wizard-content').prepend($notification);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        // Utility functions
        formatApiKey: function(apiKey) {
            if (apiKey.length > 20) {
                return apiKey.substring(0, 8) + '...' + apiKey.substring(apiKey.length - 8);
            }
            return apiKey;
        },
        
        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    SetupWizard.showSuccess('Copied to clipboard!');
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                SetupWizard.showSuccess('Copied to clipboard!');
            }
        }
    };
    
    // Initialize setup wizard
    SetupWizard.init();
    
    // Add CSS for notifications
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .acp-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                border-radius: 8px;
                padding: 15px 20px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                z-index: 9999;
                display: flex;
                align-items: center;
                gap: 10px;
                animation: slideIn 0.3s ease;
            }
            
            .acp-notification-success {
                border-left: 4px solid #4caf50;
            }
            
            .acp-notification-error {
                border-left: 4px solid #f44336;
            }
            
            .acp-notification-icon {
                font-size: 1.2em;
            }
            
            .acp-form-group.success .acp-form-input {
                border-color: #4caf50;
                background: #f1f8e9;
            }
            
            .acp-form-group.error .acp-form-input {
                border-color: #f44336;
                background: #ffebee;
            }
            
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `)
        .appendTo('head');
});
