# Smart Search Chatbot WordPress Plugin

A comprehensive AI-powered chatbot plugin for WordPress with Smart Search integration, featuring both embedded shortcode and site-agnostic widget capabilities.

## Features

### 🤖 AI-Powered Chat
- Support for multiple AI providers (OpenAI, Google Gemini, Anthropic Claude)
- Configurable AI models and parameters
- Intelligent response generation with context awareness
- Rate limiting and abuse prevention

### 🔍 Smart Search Integration
- Seamless integration with WP Engine AI Toolkit and Smart Search
- Automatic content retrieval and citation generation
- Context-aware responses based on your site content
- Fallback gracefully when Smart Search is not available

### 📊 Comprehensive Logging
- Detailed conversation logging with privacy controls
- PII redaction and data retention management
- Export capabilities for analysis and compliance
- Session tracking and analytics

### 🎭 Persona Management
- Customizable AI personality and behavior
- Style and tone configuration
- Instructions and knowledge area definition
- Live persona testing capabilities

### 🌐 Site-Agnostic Widget
- Floating chat bubble for any website
- CDN-friendly loader script
- Cross-origin support with proper CORS handling
- Customizable appearance and positioning
- Proactive greeting messages

### 🛠 Admin Interface
- Comprehensive dashboard with statistics
- Easy configuration through WordPress admin
- Health monitoring and system status
- Multiple deployment options

## Installation

### Method 1: WordPress Admin (Recommended)
1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" and select the ZIP file
4. Activate the plugin

### Method 2: Manual Installation
1. Extract the plugin files to `/wp-content/plugins/smart-search-chatbot/`
2. Activate the plugin through the WordPress admin

### Method 3: WP-CLI
```bash
wp plugin install smart-search-chatbot.zip --activate
```

## Quick Start

### 1. Basic Configuration
1. Go to **Smart Search Chatbot → Settings**
2. Choose your AI provider (OpenAI, Gemini, etc.)
3. Enter your API key
4. Select a model and configure response settings
5. Save settings

### 2. Configure Persona (Optional)
1. Go to **Smart Search Chatbot → Persona**
2. Enable custom persona
3. Define instructions and communication style
4. Test your persona with sample messages

### 3. Enable Widget (Optional)
1. Go to **Smart Search Chatbot → Widget**
2. Enable the floating widget
3. Configure appearance and position
4. Set up proactive greeting if desired

### 4. Use Shortcode
Add the chatbot to any post or page:
```
[smart_search_chat]
```

With custom parameters:
```
[smart_search_chat height="600px" placeholder="How can I help you?"]
```

## Configuration

### AI Provider Settings

#### OpenAI
- **API Key**: Your OpenAI API key
- **Models**: GPT-4, GPT-4 Turbo, GPT-3.5 Turbo
- **Documentation**: [OpenAI API Docs](https://platform.openai.com/docs)

#### Google Gemini
- **API Key**: Your Google AI API key
- **Models**: Gemini Pro, Gemini Pro Vision
- **Documentation**: [Google AI Docs](https://ai.google.dev/docs)

#### Anthropic Claude
- **API Key**: Your Anthropic API key
- **Models**: Claude 3 Opus, Sonnet, Haiku
- **Documentation**: [Anthropic API Docs](https://docs.anthropic.com)

### Smart Search Integration

The plugin automatically detects and integrates with:
- **WP Engine AI Toolkit**
- **WP Engine Smart Search**

When available, the chatbot will:
- Query your site content for relevant information
- Include citations in responses
- Provide more accurate, site-specific answers

### Widget Configuration

#### Basic Setup
```html
<script src="https://your-cdn.com/ssgc-loader.js" 
        data-config-url="https://yoursite.com/wp-json/ssgc/v1/widget-config"
        defer></script>
```

#### Advanced Configuration
```html
<script src="https://your-cdn.com/ssgc-loader.js" 
        data-config-url="https://yoursite.com/wp-json/ssgc/v1/widget-config"
        data-color="#007cba"
        data-position="br"
        defer></script>
```

#### Widget Options
- `data-config-url`: URL to widget configuration endpoint (required)
- `data-color`: Primary color for the widget (optional)
- `data-position`: Position (`br` for bottom-right, `bl` for bottom-left)

## REST API Endpoints

### Public Endpoints

#### Widget Configuration
```
GET /wp-json/ssgc/v1/widget-config
```
Returns widget configuration including colors, position, and API endpoints.

#### Chat
```
POST /wp-json/ssgc/v1/chat
Content-Type: application/json

{
  "session_id": "uuid-v4-string",
  "prompt": "User message"
}
```

Response:
```json
{
  "text": "AI response",
  "citations": [
    {
      "title": "Page Title",
      "url": "https://example.com/page"
    }
  ],
  "usage": {
    "tokens": 150
  },
  "response_time": 1.234
}
```

#### Health Check
```
GET /wp-json/ssgc/v1/health
```
Returns system status and available integrations.

## Admin Pages

### Overview (`/wp-admin/admin.php?page=ssgc-hub`)
- Dashboard with key statistics
- Widget and persona status
- System health monitoring
- Quick actions and recent activity

### Chat Logs (`/wp-admin/admin.php?page=ssc-chatbot-logs`)
- View all chat conversations
- Search and filter capabilities
- Export logs to CSV
- Session details and analytics

### Log Settings (`/wp-admin/admin.php?page=ssc-chat-logs-settings`)
- Configure data retention
- PII redaction settings
- Privacy and compliance tools
- Data management actions

### Persona (`/wp-admin/admin.php?page=ssgc-persona`)
- Define AI personality and behavior
- Set communication style and tone
- Test persona with sample messages
- Instructions and knowledge areas

### Widget (`/wp-admin/admin.php?page=ssgc-widget`)
- Enable/disable floating widget
- Configure appearance and position
- Set up proactive greetings
- Implementation code snippets

### Settings (`/wp-admin/admin.php?page=ssgc-settings`)
- AI provider configuration
- Model and response settings
- Rate limiting and security
- Shortcode configuration

## File Structure

```
smart-search-chatbot/
├── smart-search-chatbot.php          # Main plugin file
├── includes/                         # Core classes
│   ├── class-ssgc-admin-menu.php    # Admin menu management
│   ├── class-ssgc-widget.php        # Widget functionality
│   ├── class-ssgc-chat.php          # Chat handler
│   ├── class-ssgc-persona.php       # Persona management
│   └── class-ssgc-logs.php          # Logging system
├── admin/                           # Admin interface
│   └── views/                       # Admin page templates
│       ├── overview.php
│       ├── chat-logs.php
│       ├── log-settings.php
│       ├── persona.php
│       ├── widget.php
│       └── settings.php
├── assets/                          # Plugin assets
│   ├── js/
│   │   └── widget-loader.js         # Widget loader script
│   └── css/
├── src/                            # Source files for widget
│   ├── loader.js                   # Widget loader source
│   ├── widget.html                 # Widget iframe source
│   └── lib/
│       └── uuid.js                 # UUID utility
└── dist/                           # Built widget files
    ├── ssgc-loader.js              # Production loader
    └── ssgc-widget.html            # Production widget
```

## Security Features

### Data Protection
- PII redaction for sensitive information
- Secure API key storage
- Rate limiting per user/session
- Input sanitization and validation

### CORS Configuration
- Same-origin policy enforcement
- Configurable allowed origins
- Proper security headers
- Frame protection

### Privacy Compliance
- GDPR-ready data handling
- Configurable data retention
- Export and deletion capabilities
- Audit trail maintenance

## Performance Optimization

### Caching
- Widget configuration caching
- Optimized database queries
- Minimal resource loading
- CDN-friendly assets

### Rate Limiting
- Per-user request limits
- Session-based throttling
- Abuse prevention
- Cost control

## Troubleshooting

### Common Issues

#### Widget Not Loading
1. Check widget is enabled in settings
2. Verify config URL is accessible
3. Check browser console for errors
4. Ensure CORS is properly configured

#### API Errors
1. Verify API key is correct
2. Check provider service status
3. Review rate limits and quotas
4. Test API connection in settings

#### Smart Search Not Working
1. Ensure AI Toolkit is installed and active
2. Check Smart Search configuration
3. Verify content indexing is complete
4. Review health status in overview

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Support
- Check the WordPress admin health status
- Review error logs in `/wp-content/debug.log`
- Test API connections in the settings page
- Export logs for analysis

## Development

### Local Development
1. Clone the repository
2. Set up a local WordPress environment
3. Symlink or copy plugin to `/wp-content/plugins/`
4. Activate and configure

### Building Widget Assets
```bash
# Copy source to dist for production
cp src/loader.js dist/ssgc-loader.js
cp src/widget.html dist/ssgc-widget.html
```

### Testing
- Test with different AI providers
- Verify widget functionality across browsers
- Check mobile responsiveness
- Test CORS and security features

## Requirements

### WordPress
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

### Server
- cURL support (for API calls)
- JSON support
- WordPress REST API enabled

### Optional
- WP Engine AI Toolkit (for Smart Search)
- SSL certificate (recommended)
- CDN for widget assets (recommended)

## License

GPL v2 or later. See LICENSE file for details.

## Changelog

### Version 2.0.0
- Added site-agnostic widget functionality
- Integrated Smart Search support
- Enhanced admin interface
- Improved security and privacy features
- Added comprehensive logging system
- Persona management capabilities

### Version 1.0.0
- Initial release
- Basic chatbot functionality
- Shortcode support
- Simple admin interface

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## Support

For support, please:
1. Check the troubleshooting section
2. Review the WordPress admin health status
3. Check error logs
4. Create an issue on GitHub with detailed information

---

**Smart Search Chatbot** - Bringing AI-powered conversations to WordPress with enterprise-grade features and flexibility.
