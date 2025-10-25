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

This plugin implements the **Agentic Commerce Protocol (ACP)** for WooCommerce, providing standardized API endpoints that enable AI agents to discover, browse, and purchase products from your store. The implementation follows the official ACP specification maintained by **OpenAI** and **Stripe**.

**ACP Compliance**: This plugin implements the official [Agentic Commerce Protocol specification](https://github.com/agentic-commerce-protocol/agentic-commerce-protocol) with full OpenAPI compliance and JSON Schema validation.

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
- **Event Sourcing**: Complete audit trail with version history
- **Real-time Events**: Instant webhook notifications via WordPress hooks
- **Production Monitoring**: Built-in logging and performance tracking

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

### ACP-Compliant Checkout Sessions

This plugin implements the official [ACP Checkout API specification](https://github.com/agentic-commerce-protocol/agentic-commerce-protocol/blob/main/spec/openapi/openapi.agentic_checkout.yaml):

| Method | Endpoint | Description | ACP Spec |
|--------|----------|-------------|----------|
| `POST` | `/wp-json/acp/v1/checkout_sessions` | Create checkout session | ✅ Compliant |
| `GET` | `/wp-json/acp/v1/checkout_sessions/{id}` | Get session details | ✅ Compliant |
| `PUT` | `/wp-json/acp/v1/checkout_sessions/{id}` | Update session | ✅ Compliant |
| `POST` | `/wp-json/acp/v1/checkout_sessions/{id}/complete` | Complete payment | ✅ Compliant |
| `POST` | `/wp-json/acp/v1/checkout_sessions/{id}/cancel` | Cancel session | ✅ Compliant |

### Product Feed

| Method | Endpoint | Description | ACP Spec |
|--------|----------|-------------|----------|
| `GET` | `/acp/feed` | Get product catalog (JSON) | ✅ Compliant |

### ACP Specification Compliance

- **OpenAPI 3.0**: Full compliance with [ACP OpenAPI spec](https://github.com/agentic-commerce-protocol/agentic-commerce-protocol/blob/main/spec/openapi/openapi.agentic_checkout.yaml)
- **JSON Schema**: Validates against [ACP JSON schemas](https://github.com/agentic-commerce-protocol/agentic-commerce-protocol/blob/main/spec/json-schema)
- **RFC Compliance**: Follows [ACP RFC specifications](https://github.com/agentic-commerce-protocol/agentic-commerce-protocol/blob/main/rfcs)
- **Examples**: Compatible with [ACP examples](https://github.com/agentic-commerce-protocol/agentic-commerce-protocol/blob/main/examples)

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

---

## Why Choose ACP for WooCommerce?

### 🚀 **AI-Ready Commerce**
Enable your WooCommerce store to participate in the AI revolution. Let ChatGPT and AI agents discover, browse, and purchase from your store through natural language interactions.

### 🛡️ **Enterprise-Grade Security**
- **Bearer Token Authentication** - Secure API access
- **HMAC Signature Validation** - Tamper-proof webhooks
- **Idempotency Protection** - Prevent duplicate transactions
- **Header Validation** - Comprehensive request security
- **Event Sourcing** - Complete audit trail

### 📊 **Real-Time Analytics**
- **Transaction Monitoring** - Live order tracking
- **Performance Metrics** - API response times and success rates
- **Error Tracking** - Comprehensive logging and debugging
- **Webhook Analytics** - Delivery success and failure rates

### 🔧 **Developer Experience**
- **Professional Admin Interface** - Easy configuration and monitoring
- **Comprehensive Documentation** - Complete API reference
- **Testing Tools** - Built-in connection testing
- **Log Management** - View, download, and clear logs
- **Cron Jobs** - Automated maintenance and cleanup

### 🌐 **Production Ready**
- **WordPress Integration** - Native WooCommerce compatibility
- **Theme Compatibility** - Works with all modern WordPress themes
- **Performance Optimized** - Efficient database queries and caching
- **Scalable Architecture** - Handles high-volume transactions
- **Error Recovery** - Automatic retry mechanisms

### 💰 **Cost Effective**
- **Free Plugin** - No licensing fees
- **Shora API Integration** - Pay only for what you use
- **Efficient Resource Usage** - Optimized for WordPress environment
- **No Vendor Lock-in** - Open source MIT license

## Comparison with Other ACP Implementations

### 🆚 **ACP for WooCommerce vs Serverless Solutions**

| Feature | ACP for WooCommerce | Serverless ACP |
|---------|-------------------|----------------|
| **Deployment** | WordPress Plugin | AWS Lambda + DynamoDB |
| **Setup Time** | 5 minutes | 30+ minutes |
| **Maintenance** | WordPress Admin | AWS Console |
| **Cost** | Free + Shora API | AWS Infrastructure |
| **WordPress Integration** | Native | External API |
| **Theme Compatibility** | All WordPress Themes | N/A |
| **Admin Interface** | Built-in | Custom Development |
| **Logging** | WordPress Logs | CloudWatch |
| **Updates** | WordPress Updates | Manual Deployment |

### 🎯 **Why WordPress Users Choose ACP for WooCommerce**

**✅ Instant Setup**
- No AWS knowledge required
- Familiar WordPress interface
- One-click activation

**✅ Native Integration**
- Seamless WooCommerce integration
- No external dependencies
- WordPress-native security

**✅ Cost Effective**
- No infrastructure costs
- Pay only for Shora API usage
- No vendor lock-in

**✅ Developer Friendly**
- WordPress development standards
- Familiar PHP codebase
- Extensive documentation

**✅ Production Ready**
- Battle-tested WordPress platform
- Automatic updates and security
- Professional support

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

---

## About

Professional WordPress plugin implementing the **Agentic Commerce Protocol (ACP)** for WooCommerce stores. This plugin provides a complete implementation of the official ACP specification maintained by **OpenAI** and **Stripe**.

**ACP Specification Compliance:**
- **Official ACP Implementation** - Full compliance with [agentic-commerce-protocol/agentic-commerce-protocol](https://github.com/agentic-commerce-protocol/agentic-commerce-protocol)
- **OpenAPI 3.0** - Implements [ACP OpenAPI specification](https://github.com/agentic-commerce-protocol/agentic-commerce-protocol/blob/main/spec/openapi/openapi.agentic_checkout.yaml)
- **JSON Schema** - Validates against [ACP JSON schemas](https://github.com/agentic-commerce-protocol/agentic-commerce-protocol/blob/main/spec/json-schema)
- **RFC Compliance** - Follows [ACP RFC specifications](https://github.com/agentic-commerce-protocol/agentic-commerce-protocol/blob/main/rfcs)

**Key Features:**
- **WordPress Integration** - Native WooCommerce integration with admin interface
- **ACP Compliance** - Full OpenAI and Stripe ACP specification implementation
- **Security First** - Bearer token authentication, HMAC signatures, idempotency
- **Real-time Events** - Webhook notifications for order lifecycle events
- **Production Ready** - Comprehensive logging, error handling, and monitoring
- **Developer Friendly** - Professional admin interface with configuration options

**Perfect for:**
- E-commerce stores wanting AI agent integration
- Developers building AI-powered commerce solutions
- Businesses looking to enable ChatGPT and AI agent shopping
- WooCommerce stores seeking modern API capabilities

**Resources:**
- [ACP Specification](https://github.com/agentic-commerce-protocol/agentic-commerce-protocol)
- [Documentation](https://docs.shora.co)
- [Shora Dashboard](https://app.shora.cloud)
- [API Keys](https://app.shora.cloud/keys)
- [Support](mailto:support@shora.co)

**License:** MIT License - [View License](LICENSE)
