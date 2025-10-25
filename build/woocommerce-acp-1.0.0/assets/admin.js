/**
 * ACP Admin JavaScript
 * Professional admin interface functionality
 */

jQuery(document).ready(function($) {
    'use strict';
    
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
        var button = $(this);
        button.prop('disabled', true).text('Generating...');
        
        $.post(ajaxurl, {
            action: 'acp_generate_api_key',
            nonce: $('#acp_admin_nonce').val()
        }, function(response) {
            if (response.success) {
                $('input[name="acp_api_key"]').val(response.data);
                showNotice('API key generated successfully', 'success');
            } else {
                showNotice('Failed to generate API key', 'error');
            }
        }).always(function() {
            button.prop('disabled', false).text('Generate New Key');
        });
    });
    
    // Generate webhook secret
    $('#generate-webhook-secret').click(function() {
        var button = $(this);
        button.prop('disabled', true).text('Generating...');
        
        $.post(ajaxurl, {
            action: 'acp_generate_webhook_secret',
            nonce: $('#acp_admin_nonce').val()
        }, function(response) {
            if (response.success) {
                $('input[name="acp_webhook_secret"]').val(response.data);
                showNotice('Webhook secret generated successfully', 'success');
            } else {
                showNotice('Failed to generate webhook secret', 'error');
            }
        }).always(function() {
            button.prop('disabled', false).text('Generate New Secret');
        });
    });
    
    // View logs
    $('#view-logs').click(function() {
        var button = $(this);
        var logContent = $('#log-content');
        
        if (logContent.is(':visible')) {
            logContent.hide();
            button.text('View Logs');
        } else {
            button.prop('disabled', true).text('Loading...');
            
            $.post(ajaxurl, {
                action: 'acp_get_logs',
                nonce: $('#acp_admin_nonce').val()
            }, function(response) {
                if (response.success) {
                    logContent.find('pre').text(response.data);
                    logContent.show();
                    button.text('Hide Logs');
                } else {
                    showNotice('Failed to load logs', 'error');
                }
            }).always(function() {
                button.prop('disabled', false);
            });
        }
    });
    
    // Clear logs
    $('#clear-logs').click(function() {
        if (confirm('Are you sure you want to clear all logs? This action cannot be undone.')) {
            var button = $(this);
            button.prop('disabled', true).text('Clearing...');
            
            $.post(ajaxurl, {
                action: 'acp_clear_logs',
                nonce: $('#acp_admin_nonce').val()
            }, function(response) {
                if (response.success) {
                    showNotice('Logs cleared successfully', 'success');
                    $('#log-content pre').text('');
                } else {
                    showNotice('Failed to clear logs', 'error');
                }
            }).always(function() {
                button.prop('disabled', false).text('Clear Logs');
            });
        }
    });
    
    // Download logs
    $('#download-logs').click(function() {
        var button = $(this);
        button.prop('disabled', true).text('Preparing...');
        
        $.post(ajaxurl, {
            action: 'acp_download_logs',
            nonce: $('#acp_admin_nonce').val()
        }, function(response) {
            if (response.success) {
                // Create download link
                var link = document.createElement('a');
                link.href = response.data.download_url;
                link.download = 'acp-logs-' + new Date().toISOString().slice(0, 19).replace(/:/g, '-') + '.log';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                showNotice('Log download started', 'success');
            } else {
                showNotice('Failed to prepare log download', 'error');
            }
        }).always(function() {
            button.prop('disabled', false).text('Download Logs');
        });
    });
    
    // Test connection
    $('#test-connection').click(function() {
        var button = $(this);
        button.prop('disabled', true).text('Testing...');
        
        $.post(ajaxurl, {
            action: 'acp_test_connection',
            nonce: $('#acp_admin_nonce').val()
        }, function(response) {
            if (response.success) {
                var tests = response.data.tests;
                var status = response.data.overall_status;
                
                var message = 'Connection test completed:\n';
                for (var test in tests) {
                    var status_text = tests[test] ? '✓ PASS' : '✗ FAIL';
                    message += test.replace(/_/g, ' ').toUpperCase() + ': ' + status_text + '\n';
                }
                
                if (status === 'passed') {
                    showNotice(message, 'success');
                } else {
                    showNotice(message, 'warning');
                }
            } else {
                showNotice('Connection test failed', 'error');
            }
        }).always(function() {
            button.prop('disabled', false).text('Test Connection');
        });
    });
    
    // Get statistics
    $('#get-stats').click(function() {
        var button = $(this);
        button.prop('disabled', true).text('Loading...');
        
        $.post(ajaxurl, {
            action: 'acp_get_stats',
            nonce: $('#acp_admin_nonce').val()
        }, function(response) {
            if (response.success) {
                var stats = response.data;
                var message = 'ACP Statistics:\n\n';
                message += 'Sessions: ' + stats.sessions.total + ' total\n';
                message += '  - Pending: ' + stats.sessions.pending + '\n';
                message += '  - Completed: ' + stats.sessions.completed + '\n';
                message += '  - Failed: ' + stats.sessions.failed + '\n';
                message += '  - Cancelled: ' + stats.sessions.cancelled + '\n\n';
                message += 'Webhooks: ' + stats.webhooks.total + ' total\n';
                message += '  - Sent: ' + stats.webhooks.sent + '\n';
                message += '  - Failed: ' + stats.webhooks.failed + '\n';
                message += '  - Pending: ' + stats.webhooks.pending + '\n\n';
                message += 'Log Size: ' + formatBytes(stats.log_size);
                
                showNotice(message, 'info');
            } else {
                showNotice('Failed to load statistics', 'error');
            }
        }).always(function() {
            button.prop('disabled', false).text('Get Statistics');
        });
    });
    
    // Helper function to show notices
    function showNotice(message, type) {
        var noticeClass = 'notice-' + type;
        var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message.replace(/\n/g, '<br>') + '</p></div>');
        
        $('.wrap h1').after(notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notice.fadeOut();
        }, 5000);
    }
    
    // Helper function to format bytes
    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});
