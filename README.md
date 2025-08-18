# Smart Search Chatbot WordPress Plugin - Version 3

A comprehensive AI-powered chatbot plugin for WordPress with advanced Smart Search integration, featuring both embedded shortcode and site-agnostic widget capabilities with enterprise-grade MVDB support.

## 🚀 What's New in Version 3

### 🔍 Advanced MVDB Integration
- **Multi-Vector Database Support**: Resilient GraphQL queries that adapt to different MVDB endpoint schemas
- **Intelligent Field Mapping**: Heuristic parsing of Map-based data fields with automatic fallback strategies
- **Schema-Agnostic Queries**: Support for `similarity.docs`, `similarity.documents`, and `find.documents` response formats
- **Real-Time Debug Panel**: Interactive MVDB testing with raw response inspection and field mapping visualization

### 🎯 Context Restriction Controls
- **MVDB-Only Mode**: Option to restrict AI responses to only indexed site content
- **Controlled Fallback**: Graceful "don't know" responses when no relevant content is found
- **Persona-Aware Grounding**: Dynamic system prompts that adapt based on restriction settings

### 🛠 Enhanced Admin Experience
- **Search Debug Panel**: Test MVDB queries directly from WordPress admin with detailed response analysis
- **Comprehensive Error Reporting**: GraphQL error surfacing with structured debugging information
- **Dual Endpoint Support**: Primary and fallback REST endpoints with automatic switching
- **Authenticated Debug Interface**: Secure admin-only testing with proper nonce authentication

## Features

### 🤖 AI-Powered Chat
- Support for multiple AI providers (OpenAI, Google Gemini, Anthropic Claude)
- Configurable AI models and parameters
- Intelligent response generation with context awareness
- Rate limiting and abuse prevention
- **NEW**: Context restriction controls for MVDB-only responses

### 🔍 Smart Search Integration
- **NEW**: Multi-schema MVDB support with automatic endpoint detection
- **NEW**: Heuristic field mapping for flexible data structures
- **NEW**: Real-time debug interface with raw response inspection
- Seamless integration with WP Engine AI Toolkit and Smart Search
- Automatic content retrieval and citation generation
- Context-aware responses based on your site content
- Fallback gracefully when Smart Search is not available

### 📊 Comprehensive Logging
- Detailed conversation logging with privacy controls
- PII redaction and data retention management
- Export capabilities for analysis and compliance
- Session tracking and analytics
- **NEW**: Logging for restricted responses with zero token usage

### 🎭 Persona Management
- Customizable AI personality and behavior
- Style and tone configuration
- Instructions and knowledge area definition
- Live persona testing capabilities
- **NEW**: Dynamic persona grounding based on context restrictions

### 🌐 Site-Agnostic Widget
- Floating chat bubble for any website
- CDN-friendly loader script
- Cross-origin support with proper CORS handling
- Customizable appearance and positioning
- Proactive greeting messages
- **NEW**: Bulletproof initialization with inline bootstrap fallback

### 🛠 Admin Interface
- Comprehensive dashboard with statistics
- Easy configuration through WordPress admin
- Health monitoring and system status
- Multiple deployment options
- **NEW**: Interactive MVDB testing and debugging tools

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

### 2. Configure MVDB Integration (New in V3)
1. Go to **Smart Search Chatbot → Settings**
2. Scroll to **AI Toolkit (Smart Search)** section
3. Enter your MVDB search endpoint URL
4. Add your API key if required
5. **Optional**: Enable "Restrict answers to MVDB context only"
6. Test your configuration using the **Search Debug** panel

### 3. Test MVDB Integration (New in V3)
1. In the **Search Debug** panel, enter a test query
2. Click **Run Search** to see real-time results
3. Review the `rawPreview` to understand your data structure
4. Check `parsed` results to see normalized output
5. Use this information to optimize your content indexing

### 4. Configure Persona (Optional)
1. Go to **Smart Search Chatbot → Persona**
2. Enable custom persona
3. Define instructions and communication style
4. Test your persona with sample messages

### 5. Enable Widget (Optional)
1. Go to **Smart Search Chatbot → Widget**
2. Enable the floating widget
3. Configure appearance and position
4. Set up proactive greeting if desired

### 6. Use Shortcode
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

### MVDB Integration (Enhanced in V3)

The plugin now supports multiple MVDB endpoint schemas and automatically adapts to your specific implementation:

#### Supported GraphQL Schemas
1. **similarity.docs**: `{ similarity(query: $q) { docs { score data } } }`
2. **similarity.documents**: `{ similarity(query: $q) { documents { score data } } }`
3. **find.documents**: `{ find(query: $q) { documents { score data } } }`

#### Automatic Field Detection
The plugin intelligently maps fields from your MVDB data:
- **Titles**: `post_title`, `title`, `name`, `heading`
- **URLs**: `post_url`, `url`, `permalink`, `link`
- **Content**: `post_content`, `content`, `excerpt`, `summary`, `text`, `description`, `body`
- **Scores**: `score`, `_score`

#### Context Restriction Options
- **Unrestricted (Default)**: Uses MVDB when available, falls back to general knowledge
- **MVDB-Only**: Only answers using indexed content, says "don't know" when no context found

#### Configuration Steps
1. **Search Endpoint**: Full URL to your MVDB GraphQL endpoint
2. **API Key**: Bearer token for authentication (if required)
3. **Test Connection**: Use the Search Debug panel to verify setup
4. **Enable Restrictions**: Optionally restrict to MVDB-only responses

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

### Admin Endpoints (New in V3)

#### Search Debug (Primary)
```
GET /wp-json/ssgc/v1/search-test?q=your+query
X-WP-Nonce: [nonce]
```

#### Search Debug (Fallback)
```
GET /wp-json/ssgc/v1/search-debug?q=your+query
X-WP-Nonce: [nonce]
```

Response:
```json
{
  "status": 200,
  "mode": "similarity_docs",
  "endpoint": "https://your-site.com/wp-json/ai-toolkit/v1/search",
  "variables": {"q": "your query"},
  "raw": {
    "data": {
      "similarity": {
        "docs": [
          {"score": 0.85, "data": {"post_title": "Page Title"}}
        ]
      }
    }
  },
  "parsed": [
    {"title": "Page Title", "url": "https://site.com/page", "snippet": "Content...", "score": 0.85}
  ],
  "rawPreview": {
    "keys": ["score", "data"],
    "dataKeys": ["post_title", "post_url", "post_content"],
    "score": 0.85,
    "sampleTitle": "Page Title",
    "sampleUrl": "https://site.com/page"
  }
}
```

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

### Settings (`/wp-admin/admin.php?page=ssgc-settings`) - Enhanced in V3
- AI provider configuration
- Model and response settings
- Rate limiting and security
- Shortcode configuration
- **NEW**: MVDB integration settings
- **NEW**: Context restriction controls
- **NEW**: Interactive Search Debug panel

## MVDB Debug Panel (New in V3)

The Search Debug panel provides real-time testing and diagnostics for your MVDB integration:

### Features
- **Live Query Testing**: Test search queries directly from WordPress admin
- **Multi-Schema Support**: Automatically tries different GraphQL query formats
- **Raw Response Inspection**: View complete GraphQL responses for debugging
- **Field Mapping Preview**: See how fields are extracted from your data
- **Error Diagnostics**: Detailed GraphQL error reporting with suggestions

### Usage
1. Navigate to **Settings → Search Debug**
2. Enter a test query (e.g., "pricing", "about us")
3. Click **Run Search**
4. Review the results:
   - **status**: HTTP response code
   - **mode**: Which GraphQL schema worked
   - **parsed**: Normalized results used by the chatbot
   - **rawPreview**: Sample of your raw data structure

### Troubleshooting with Debug Panel
- **No results**: Check if content is properly indexed in your MVDB
- **Schema errors**: Review `rawPreview.keys` to understand your data structure
- **Field mapping issues**: Check `rawPreview.dataKeys` for available fields
- **Authentication errors**: Verify API key and endpoint URL

## File Structure

```
smart-search-chatbot/
├── smart-search-chatbot.php          # Main plugin file
├── includes/                         # Core classes
│   ├── class-ssgc-admin-menu.php    # Admin menu management
│   ├── class-ssgc-widget.php        # Widget functionality
│   ├── class-ssgc-chat.php          # Chat handler with MVDB integration
│   ├── class-ssgc-persona.php       # Persona management
│   ├── class-ssgc-retrieval.php     # MVDB retrieval with multi-schema support
│   └── class-ssgc-logs.php          # Logging system
├── admin/                           # Admin interface
│   └── views/                       # Admin page templates
│       ├── overview.php
│       ├── chat-logs.php
│       ├── log-settings.php
│       ├── persona.php
│       ├── widget.php
│       └── settings.php             # Enhanced with MVDB debug panel
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
- **NEW**: Authenticated admin endpoints with nonce verification

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

### MVDB Optimization (New in V3)
- Intelligent query fallback reduces failed requests
- Heuristic field mapping minimizes data processing
- Schema detection caching for repeated queries

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

#### MVDB Integration Issues (New in V3)
1. Use the **Search Debug panel** to test your endpoint
2. Check the `rawPreview` to understand your data structure
3. Verify your GraphQL endpoint supports the expected schema
4. Review error messages for specific GraphQL issues
5. Ensure your API key has proper permissions

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### MVDB Debugging (New in V3)
1. **Test Endpoint**: Use Settings → Search Debug to test queries
2. **Check Schema**: Review which query format works (`similarity_docs`, `similarity_documents`, `find_documents`)
3. **Inspect Fields**: Use `rawPreview.dataKeys` to see available fields
4. **Verify Authentication**: Ensure API key is correct and has proper permissions

### Support
- Check the WordPress admin health status
- Review error logs in `/wp-content/debug.log`
- Test API connections in the settings page
- **NEW**: Use the Search Debug panel for MVDB diagnostics
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

### Testing MVDB Integration (New in V3)
1. Set up a test MVDB endpoint
2. Configure the plugin with test credentials
3. Use the Search Debug panel to verify connectivity
4. Test different query formats and field mappings
5. Verify context restriction functionality

### Testing
- Test with different AI providers
- Verify widget functionality across browsers
- Check mobile responsiveness
- Test CORS and security features
- **NEW**: Test MVDB integration with various schemas

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
- MVDB endpoint with GraphQL support
- SSL certificate (recommended)
- CDN for widget assets (recommended)

## License

GPL v2 or later. See LICENSE file for details.

## Changelog

### Version 3.0.0 (Current)
- **NEW**: Multi-Vector Database (MVDB) support with schema-agnostic queries
- **NEW**: Interactive Search Debug panel for real-time MVDB testing
- **NEW**: Context restriction controls (MVDB-only responses)
- **NEW**: Heuristic field mapping for flexible data structures
- **NEW**: Dual REST endpoints with automatic fallback
- **NEW**: Enhanced error reporting with GraphQL diagnostics
- **NEW**: Authenticated admin debugging interface
- **NEW**: Dynamic persona grounding based on context restrictions
- **IMPROVED**: Widget initialization with bulletproof fallback system
- **IMPROVED**: Admin interface with comprehensive MVDB tools

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
4. Test thoroughly (including MVDB integration)
5. Submit a pull request

## Support

For support, please:
1. Check the troubleshooting section
2. Use the Search Debug panel for MVDB issues
3. Review the WordPress admin health status
4. Check error logs
5. Create an issue on GitHub with detailed information

---

**Smart Search Chatbot Version 3** - Enterprise-grade AI-powered conversations for WordPress with advanced Multi-Vector Database integration and comprehensive debugging tools.
