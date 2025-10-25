# ACP for WooCommerce

Agentic Commerce Protocol integration for WooCommerce stores. Enable AI-powered commerce through standardized API endpoints and product feeds.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0%2B-blue.svg)](https://woocommerce.com/)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)](https://php.net/)

## Overview

This plugin implements the Agentic Commerce Protocol (ACP) for WooCommerce, providing standardized API endpoints that enable AI agents to discover, browse, and purchase products from your store. The implementation follows OpenAI's ACP specification and includes comprehensive security features, webhook support, and product feed generation.

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

### Installation

1. **Download the plugin**
   ```bash
   git clone https://github.com/shora-co/woocommerce-acp-integration.git
   ```

2. **Install in WordPress**
   - Upload the plugin folder to `/wp-content/plugins/`
   - Activate the plugin in WordPress admin
   - Ensure WooCommerce is installed and active

3. **Configure API Keys**
   - Go to WooCommerce → Settings → ACP
   - Generate API key and webhook secret
   - Configure webhook URL (optional)

### Test the Integration

1. **Create a checkout session**
   ```bash
   curl -X POST https://yourstore.com/wp-json/acp/v1/checkout_sessions \
     -H "Authorization: Bearer YOUR_API_KEY" \
     -H "Content-Type: application/json" \
     -H "Idempotency-Key: test-123" \
     -d '{
       "amount": 100.00,
       "currency": "TRY",
       "buyer": {
         "id": "buyer_123",
         "name": "Test User",
         "email": "test@example.com"
       }
     }'
   ```

2. **Access product feed**
   ```bash
   curl https://yourstore.com/acp/feed
   ```

3. **Check webhook events**
   - Place a test order in WooCommerce
   - Verify webhook events are sent to configured URL

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

## Advanced Features

### Shora API Integration

For production environments and advanced functionality:

- **Payment Processing**: Multi-provider payment gateway integration
- **Analytics**: Comprehensive transaction insights and reporting
- **Enterprise Support**: Priority support and SLA
- **Custom Integrations**: Tailored solutions for specific requirements

**Professional Services Available**

[Contact Sales](https://shora.co/contact) | [Documentation](https://docs.shora.co)

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
- **Issues**: [GitHub Issues](https://github.com/shoraco/-ACP-Agentic-Commerce-Protocol-for-Woocommerce/issues)
- **Contact**: [support@shora.co](mailto:support@shora.co)

---

**ACP for WooCommerce** - Professional implementation of the Agentic Commerce Protocol for WooCommerce stores.

Built by [Shora](https://shora.co) - Commerce infrastructure for modern applications.
