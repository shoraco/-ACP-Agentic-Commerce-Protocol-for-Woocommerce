#!/bin/bash

# ACP for WooCommerce Plugin Build Script
# Creates a production-ready WordPress plugin package

set -e

echo "🚀 Building ACP for WooCommerce Plugin..."

# Plugin details
PLUGIN_NAME="woocommerce-acp"
PLUGIN_VERSION="1.0.0"
BUILD_DIR="build"
PACKAGE_NAME="${PLUGIN_NAME}-${PLUGIN_VERSION}"

# Clean build directory
echo "🧹 Cleaning build directory..."
rm -rf $BUILD_DIR
mkdir -p $BUILD_DIR

# Create plugin package directory
echo "📦 Creating plugin package..."
mkdir -p $BUILD_DIR/$PACKAGE_NAME

# Copy plugin files
echo "📋 Copying plugin files..."
cp woocommerce-acp.php $BUILD_DIR/$PACKAGE_NAME/
cp -r includes $BUILD_DIR/$PACKAGE_NAME/
cp -r assets $BUILD_DIR/$PACKAGE_NAME/
cp README.md $BUILD_DIR/$PACKAGE_NAME/
cp LICENSE $BUILD_DIR/$PACKAGE_NAME/

# Create uninstall.php
echo "🗑️ Creating uninstall.php..."
cat > $BUILD_DIR/$PACKAGE_NAME/uninstall.php << 'EOF'
<?php
/**
 * Uninstall script for ACP for WooCommerce
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clean up options
delete_option('acp_api_key');
delete_option('acp_webhook_secret');
delete_option('acp_sandbox_mode');
delete_option('acp_enable_feed');
delete_option('acp_enable_webhooks');
delete_option('acp_webhook_url');
delete_option('acp_enable_signature_validation');
delete_option('acp_timestamp_tolerance');
delete_option('acp_redis_enabled');
delete_option('acp_redis_host');
delete_option('acp_redis_port');
delete_option('acp_redis_password');
delete_option('acp_redis_database');
delete_option('acp_feed_max_products');
delete_option('acp_feed_categories');
delete_option('acp_session_retention_days');
delete_option('acp_webhook_retention_days');
delete_option('acp_log_level');
delete_option('acp_enable_debug');
delete_option('acp_db_version');

// Clean up database tables
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}acp_sessions");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}acp_webhooks");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}acp_logs");

// Clean up cron jobs
wp_clear_scheduled_hook('acp_cleanup_old_sessions');
wp_clear_scheduled_hook('acp_cleanup_old_webhooks');
wp_clear_scheduled_hook('acp_retry_failed_webhooks');
wp_clear_scheduled_hook('acp_rotate_logs');
wp_clear_scheduled_hook('acp_generate_static_feed');
EOF

# Create languages directory
echo "🌍 Creating languages directory..."
mkdir -p $BUILD_DIR/$PACKAGE_NAME/languages

# Create .gitignore for build
echo "📝 Creating .gitignore..."
cat > $BUILD_DIR/$PACKAGE_NAME/.gitignore << 'EOF'
# WordPress
wp-config.php
wp-content/uploads/
wp-content/cache/
wp-content/backup-db/
wp-content/advanced-cache.php
wp-content/wp-cache-config.php

# Logs
*.log
error_log
debug.log

# OS
.DS_Store
Thumbs.db

# IDE
.vscode/
.idea/
*.swp
*.swo

# Build
build/
*.zip
EOF

# Create plugin info file
echo "ℹ️ Creating plugin info file..."
cat > $BUILD_DIR/$PACKAGE_NAME/plugin-info.txt << EOF
Plugin Name: ACP for WooCommerce
Plugin URI: https://shora.co
Description: Commerce infra for the AI era - Agentic Commerce Protocol integration for WooCommerce
Version: $PLUGIN_VERSION
Author: Shora
Author URI: https://shora.co
License: MIT
Text Domain: woocommerce-acp
Domain Path: /languages
Requires at least: 5.8
Tested up to: 6.6
WC requires at least: 8.0
WC tested up to: 9.0
Requires PHP: 8.0
Network: false
Update URI: https://github.com/shoraco/-ACP-Agentic-Commerce-Protocol-for-Woocommerce

== Description ==

ACP for WooCommerce enables your WooCommerce store to participate in the Agentic Commerce Protocol (ACP), allowing AI agents and ChatGPT to discover, browse, and purchase products from your store through natural language interactions.

== Installation ==

1. Upload the plugin files to the /wp-content/plugins/woocommerce-acp directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the ACP Setup Wizard to configure your API keys.
4. Your store is now ready for AI agents!

== Frequently Asked Questions ==

= Do I need a Shora account? =

Yes, you need a free Shora account to get your API keys. Sign up at https://app.shora.cloud

= Is this plugin free? =

Yes, the plugin is completely free. You only pay for Shora API usage.

= What is the Agentic Commerce Protocol? =

ACP is an open standard maintained by OpenAI and Stripe that enables AI agents to discover and purchase products from online stores.

== Screenshots ==

1. Setup Wizard - Easy 5-step setup process
2. Admin Dashboard - Professional configuration interface
3. API Testing - Built-in connection testing
4. Analytics - Real-time transaction monitoring

== Changelog ==

= 1.0.0 =
* Initial release
* Complete ACP specification implementation
* User-friendly setup wizard
* Professional admin interface
* Comprehensive testing suite
* Production-ready features

== Upgrade Notice ==

= 1.0.0 =
Initial release of ACP for WooCommerce plugin.
EOF

# Create ZIP package
echo "📦 Creating ZIP package..."
cd $BUILD_DIR
zip -r "${PACKAGE_NAME}.zip" $PACKAGE_NAME/
cd ..

# Create checksums
echo "🔍 Creating checksums..."
cd $BUILD_DIR
sha256sum "${PACKAGE_NAME}.zip" > "${PACKAGE_NAME}.zip.sha256"
md5sum "${PACKAGE_NAME}.zip" > "${PACKAGE_NAME}.zip.md5"
cd ..

# Display build results
echo ""
echo "✅ Build completed successfully!"
echo ""
echo "📦 Package: $BUILD_DIR/${PACKAGE_NAME}.zip"
echo "📁 Directory: $BUILD_DIR/$PACKAGE_NAME/"
echo "🔍 Checksums: $BUILD_DIR/${PACKAGE_NAME}.zip.sha256"
echo ""
echo "📋 Package contents:"
ls -la $BUILD_DIR/$PACKAGE_NAME/
echo ""
echo "📊 Package size:"
du -sh $BUILD_DIR/${PACKAGE_NAME}.zip
echo ""
echo "🚀 Ready for distribution!"
