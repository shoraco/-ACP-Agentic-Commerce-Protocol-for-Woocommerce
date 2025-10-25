# ACP for WooCommerce

<div align="center">

**Agentic Commerce Protocol integration for WooCommerce stores**

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0%2B-blue.svg)](https://woocommerce.com/)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)](https://php.net/)

**Enable AI-powered commerce through standardized API endpoints and product feeds**

[Documentation](https://docs.shora.co) • [Shora Dashboard](https://app.shora.cloud) • [API Keys](https://app.shora.cloud/keys) • [Support](mailto:support@shora.co)

</div>

## Overview

This plugin implements the Agentic Commerce Protocol (ACP) for WooCommerce, providing standardized API endpoints that enable AI agents to discover, browse, and purchase products from your store. The implementation follows OpenAI's ACP specification and includes comprehensive security features, webhook support, and product feed generation.

**Requires Shora Account**: To use this plugin, you need a Shora account and API keys from [app.shora.cloud](https://app.shora.cloud).

## Features

### Core ACP Integration
- **REST API Endpoints**: Complete ACP-compatible checkout session management
- **Product Feed**: Standardized product catalog at `/acp/feed`
- **Webhook Support**: Real-time order event notifications
- **Security**: Bearer token authentication with idempotency support

### WooCommerce Integration
- **Cart Integration**: Native WooCommerce cart management
- **Order Processing**: Standard WooCommerce order creation
- **Product Sync**: Automatic product catalog synchronization
- **Payment Processing**: Integrated payment gateway support

### Security & Reliability
- **HMAC Signatures**: Secure webhook verification
- **Idempotency**: Redis/WP Transient support for duplicate prevention
- **Header Validation**: Comprehensive request validation
- **Error Handling**: Professional error logging and recovery

## Quick Start

### Prerequisites
- WordPress 5.8 or higher
- WooCommerce 8.0 or higher  
- PHP 8.0 or higher
- MySQL 5.7 or higher
- **Shora Account** (required) - [Sign up at app.shora.cloud](https://app.shora.cloud)

### Installation

1. **Get Shora API Keys**
   - Sign up at [app.shora.cloud](https://app.shora.cloud)
   - Navigate to [API Keys](https://app.shora.cloud/keys)
   - Generate your API key and webhook secret

2. **Install the Plugin**
   ```bash
   git clone https://github.com/shoraco/-ACP-Agentic-Commerce-Protocol-for-Woocommerce.git
   ```
   - Upload to `/wp-content/plugins/`
   - Activate in WordPress admin
   - Ensure WooCommerce is installed and active

3. **Configure Plugin**
   - Go to WooCommerce → Settings → ACP
   - Enter your Shora API key from [app.shora.cloud/keys](https://app.shora.cloud/keys)
   - Configure webhook settings
   - Test the connection

### Test the Integration

1. **Create a checkout session**
   ```bash
   curl -X POST https://yourstore.com/wp-json/acp/v1/checkout_sessions \
     -H "Authorization: Bearer YOUR_SHORA_API_KEY" \
     -H "Content-Type: application/json" \
     -H "Idempotency-Key: test-123" \
     -d '{
       "items": [
         {"sku": "product-123", "quantity": 1}
       ]
     }'
   ```

2. **Access product feed**
   ```bash
   curl https://yourstore.com/acp/feed
   ```

3. **Check webhook events**
   - Place a test order in WooCommerce
   - Verify webhook events are sent to Shora dashboard
   - Monitor events at [app.shora.cloud/webhooks](https://app.shora.cloud/webhooks)

## API Endpoints

### Checkout Sessions

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/wp-json/acp/v1/checkout_sessions` | Create checkout session |
| `GET` | `/wp-json/acp/v1/checkout_sessions/{id}` | Get session details |
| `PUT` | `/wp-json/acp/v1/checkout_sessions/{id}` | Update session |
| `POST` | `/wp-json/acp/v1/checkout_sessions/{id}/complete` | Complete payment |
| `POST` | `/wp-json/acp/v1/checkout_sessions/{id}/cancel` | Cancel session |

### Product Feed

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/acp/feed` | Get product catalog (JSON) |

## Configuration

### Environment Variables

```bash
# API Configuration
ACP_API_KEY=your_api_key_here
ACP_WEBHOOK_SECRET=your_webhook_secret_here
ACP_SANDBOX_MODE=true

# Redis Configuration (Optional)
ACP_REDIS_ENABLED=false
ACP_REDIS_HOST=localhost
ACP_REDIS_PORT=6379
ACP_REDIS_PASSWORD=
ACP_REDIS_DATABASE=0
```

### WordPress Options

The plugin stores configuration in WordPress options:

- `acp_api_key`: API authentication key
- `acp_webhook_secret`: Webhook signature secret
- `acp_sandbox_mode`: Enable/disable sandbox mode
- `acp_enable_feed`: Enable/disable product feed
- `acp_enable_webhooks`: Enable/disable webhook events
- `acp_webhook_url`: Target webhook URL

## Webhook Events

The plugin sends webhook events for order status changes:

```json
{
  "webhook_id": "webhook_abc123",
  "event_type": "order.status_changed",
  "order_id": 12345,
  "old_status": "pending",
  "new_status": "processing",
  "amount": "100.00",
  "currency": "TRY",
  "customer": {
    "id": 1,
    "email": "customer@example.com",
    "name": "John Doe"
  },
  "items": [...],
  "metadata": {...}
}
```

## Shora Dashboard Integration

### Account Management
- **API Keys**: Manage your API keys at [app.shora.cloud/keys](https://app.shora.cloud/keys)
- **Webhooks**: Configure webhook endpoints at [app.shora.cloud/webhooks](https://app.shora.cloud/webhooks)
- **Analytics**: View transaction insights and reports
- **Settings**: Configure your store settings and preferences

### Advanced Features
- **Payment Processing**: Multi-provider payment gateway integration
- **Real-time Monitoring**: Live transaction monitoring and alerts
- **Enterprise Support**: Priority support and SLA
- **Custom Integrations**: Tailored solutions for specific requirements

### Getting Started with Shora
1. **Sign up** at [app.shora.cloud](https://app.shora.cloud)
2. **Generate API keys** from your dashboard
3. **Configure webhooks** for real-time events
4. **Monitor transactions** and analytics

**Need Help?** [Contact Support](mailto:support@shora.co) | [Documentation](https://docs.shora.co)

## Development

### Local Development

1. **Clone the repository**
   ```bash
   git clone https://github.com/shora-co/woocommerce-acp-integration.git
   cd woocommerce-acp-integration
   ```

2. **Set up WordPress environment**
   ```bash
   # Using Docker
   docker-compose up -d
   
   # Or using local WordPress
   wp core download
   wp plugin install woocommerce --activate
   ```

3. **Run tests**
   ```bash
   phpunit tests/
   ```

### Code Structure

```
woocommerce-acp-integration/
├── woocommerce-acp.php          # Main plugin file
├── includes/
│   ├── class-acp-api.php         # REST API endpoints
│   ├── class-acp-auth.php        # Authentication & security
│   ├── class-acp-feed-generator.php # Product feed
│   ├── class-acp-webhook.php       # Webhook handler
│   └── class-acp-model.php        # Data models
├── tests/                        # Unit tests
└── README.md                     # Documentation
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

- **Documentation**: [docs.shora.co](https://docs.shora.co)
- **Community**: [Discord](https://discord.gg/shora)
- **Issues**: [GitHub Issues](https://github.com/shora-co/woocommerce-acp-integration/issues)
- **Email**: support@shora.co

## Changelog

### 1.0.0
- Initial release
- ACP checkout session management
- Product feed generation
- Webhook event handling
- Security and authentication
- WooCommerce integration

---

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

- **Documentation**: [docs.shora.co](https://docs.shora.co)
- **Dashboard**: [app.shora.cloud](https://app.shora.cloud)
- **API Keys**: [app.shora.cloud/keys](https://app.shora.cloud/keys)
- **Issues**: [GitHub Issues](https://github.com/shoraco/-ACP-Agentic-Commerce-Protocol-for-Woocommerce/issues)
- **Contact**: [support@shora.co](mailto:support@shora.co)

---

**ACP for WooCommerce** - Professional implementation of the Agentic Commerce Protocol for WooCommerce stores.

Built by [Shora](https://shora.co) - Commerce infrastructure for modern applications.

**Requires Shora Account**: [Sign up at app.shora.cloud](https://app.shora.cloud) to get started.
