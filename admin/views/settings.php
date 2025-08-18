<?php
/**
 * General Settings Admin Page
 * 
 * Main configuration page for Smart Search Chatbot
 */

// Prevent direct access
defined('ABSPATH') || exit;

// Get current settings
$general_settings = get_option('ssgc_general_settings', array(
    'api_provider' => 'openai',
    'api_key' => '',
    'model' => 'gpt-3.5-turbo',
    'max_tokens' => 500,
    'temperature' => 0.7,
    'rate_limit' => 10,
    'enable_shortcode' => true,
    'shortcode_name' => 'smart_search_chat'
));

// Handle form submission
if (isset($_POST['ssgc_general_settings_nonce']) && wp_verify_nonce($_POST['ssgc_general_settings_nonce'], 'ssgc_general_settings')) {
    if (current_user_can('manage_options')) {
        $settings = array(
            'api_provider' => sanitize_text_field($_POST['api_provider'] ?? 'openai'),
            'api_key' => sanitize_text_field($_POST['api_key'] ?? ''),
            'model' => sanitize_text_field($_POST['model'] ?? 'gpt-3.5-turbo'),
            'max_tokens' => intval($_POST['max_tokens'] ?? 500),
            'temperature' => floatval($_POST['temperature'] ?? 0.7),
            'rate_limit' => intval($_POST['rate_limit'] ?? 10),
            'enable_shortcode' => isset($_POST['enable_shortcode']),
            'shortcode_name' => sanitize_text_field($_POST['shortcode_name'] ?? 'smart_search_chat')
        );
        
        update_option('ssgc_general_settings', $settings);
        $general_settings = $settings;
        
        echo '<div class="notice notice-success is-dismissible"><p>' . 
             __('Settings saved successfully.', 'smart-search-chatbot') . 
             '</p></div>';
    }
}

// Handle AI Toolkit settings submission
if (isset($_POST['ssgc_toolkit_nonce']) && wp_verify_nonce($_POST['ssgc_toolkit_nonce'], 'ssgc_toolkit_settings')) {
    if (current_user_can('manage_options')) {
        $toolkit_settings = array(
            'search_endpoint' => esc_url_raw($_POST['ssgc_toolkit']['search_endpoint'] ?? ''),
            'api_key' => trim((string)($_POST['ssgc_toolkit']['api_key'] ?? '')),
            'index_id' => sanitize_text_field($_POST['ssgc_toolkit']['index_id'] ?? ''),
            'mode' => in_array($_POST['ssgc_toolkit']['mode'] ?? 'similarity', array('similarity', 'find'), true) ? $_POST['ssgc_toolkit']['mode'] : 'similarity',
            'fields' => sanitize_text_field($_POST['ssgc_toolkit']['fields'] ?? 'post_title:8, post_content:1'),
            'top_k' => (int)($_POST['ssgc_toolkit']['top_k'] ?? 5),
            'min_score' => (float)($_POST['ssgc_toolkit']['min_score'] ?? 0),
            'restrict_to_context' => !empty($_POST['ssgc_toolkit']['restrict_to_context']) ? true : false,
        );
        
        update_option('ssgc_toolkit', $toolkit_settings);
        
        echo '<div class="notice notice-success is-dismissible"><p>' . 
             __('AI Toolkit settings saved successfully.', 'smart-search-chatbot') . 
             '</p></div>';
    }
}

// Get AI Toolkit settings
$toolkit_settings = get_option('ssgc_toolkit', array(
    'search_endpoint' => '',
    'api_key' => '',
    'index_id' => '',
    'mode' => 'similarity',
    'fields' => 'post_title:8, post_content:1',
    'top_k' => 5,
    'min_score' => 0.0,
    'restrict_to_context' => false,
));
?>

<div class="wrap ssgc-admin-page">
    <h1><?php _e('General Settings', 'smart-search-chatbot'); ?></h1>
    <p><?php _e('Configure the core settings for your Smart Search Chatbot, including AI provider, model settings, and general behavior.', 'smart-search-chatbot'); ?></p>
    
    <form method="post" action="">
        <?php wp_nonce_field('ssgc_general_settings', 'ssgc_general_settings_nonce'); ?>
        
        <h2><?php _e('AI Provider Settings', 'smart-search-chatbot'); ?></h2>
        <table class="form-table" role="presentation">
            <tbody>
                <!-- API Provider -->
                <tr>
                    <th scope="row">
                        <label for="api_provider"><?php _e('AI Provider', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <select id="api_provider" name="api_provider">
                            <option value="openai" <?php selected($general_settings['api_provider'], 'openai'); ?>><?php _e('OpenAI', 'smart-search-chatbot'); ?></option>
                            <option value="gemini" <?php selected($general_settings['api_provider'], 'gemini'); ?>><?php _e('Google Gemini', 'smart-search-chatbot'); ?></option>
                            <option value="anthropic" <?php selected($general_settings['api_provider'], 'anthropic'); ?>><?php _e('Anthropic Claude', 'smart-search-chatbot'); ?></option>
                            <option value="custom" <?php selected($general_settings['api_provider'], 'custom'); ?>><?php _e('Custom API', 'smart-search-chatbot'); ?></option>
                        </select>
                        <p class="description">
                            <?php _e('Choose your preferred AI provider. Each provider has different models and pricing.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
                
                <!-- API Key -->
                <tr>
                    <th scope="row">
                        <label for="api_key"><?php _e('API Key', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <input type="password" id="api_key" name="api_key" value="<?php echo esc_attr($general_settings['api_key']); ?>" class="regular-text" />
                        <button type="button" id="toggle-api-key" class="button button-small">
                            <?php _e('Show', 'smart-search-chatbot'); ?>
                        </button>
                        <p class="description">
                            <?php _e('Your API key for the selected provider. This is stored securely and never displayed in logs.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
                
                <!-- Model -->
                <tr>
                    <th scope="row">
                        <label for="model"><?php _e('Model', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <select id="model" name="model">
                            <!-- OpenAI Models -->
                            <optgroup label="OpenAI" class="provider-models" data-provider="openai">
                                <option value="gpt-4" <?php selected($general_settings['model'], 'gpt-4'); ?>>GPT-4</option>
                                <option value="gpt-4-turbo" <?php selected($general_settings['model'], 'gpt-4-turbo'); ?>>GPT-4 Turbo</option>
                                <option value="gpt-3.5-turbo" <?php selected($general_settings['model'], 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                            </optgroup>
                            
                            <!-- Gemini Models -->
                            <optgroup label="Google Gemini" class="provider-models" data-provider="gemini">
                                <option value="gemini-pro" <?php selected($general_settings['model'], 'gemini-pro'); ?>>Gemini Pro</option>
                                <option value="gemini-pro-vision" <?php selected($general_settings['model'], 'gemini-pro-vision'); ?>>Gemini Pro Vision</option>
                            </optgroup>
                            
                            <!-- Anthropic Models -->
                            <optgroup label="Anthropic" class="provider-models" data-provider="anthropic">
                                <option value="claude-3-opus" <?php selected($general_settings['model'], 'claude-3-opus'); ?>>Claude 3 Opus</option>
                                <option value="claude-3-sonnet" <?php selected($general_settings['model'], 'claude-3-sonnet'); ?>>Claude 3 Sonnet</option>
                                <option value="claude-3-haiku" <?php selected($general_settings['model'], 'claude-3-haiku'); ?>>Claude 3 Haiku</option>
                            </optgroup>
                        </select>
                        <p class="description">
                            <?php _e('The AI model to use for generating responses. Different models have different capabilities and costs.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2><?php _e('Response Settings', 'smart-search-chatbot'); ?></h2>
        <table class="form-table" role="presentation">
            <tbody>
                <!-- Max Tokens -->
                <tr>
                    <th scope="row">
                        <label for="max_tokens"><?php _e('Max Tokens', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="max_tokens" name="max_tokens" value="<?php echo esc_attr($general_settings['max_tokens']); ?>" min="50" max="4000" class="small-text" />
                        <p class="description">
                            <?php _e('Maximum number of tokens in the AI response. Higher values allow longer responses but cost more. Recommended: 500-1000.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
                
                <!-- Temperature -->
                <tr>
                    <th scope="row">
                        <label for="temperature"><?php _e('Temperature', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="temperature" name="temperature" value="<?php echo esc_attr($general_settings['temperature']); ?>" min="0" max="2" step="0.1" class="small-text" />
                        <p class="description">
                            <?php _e('Controls randomness in responses. Lower values (0.1-0.3) are more focused, higher values (0.7-1.0) are more creative. Recommended: 0.7.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
                
                <!-- Rate Limiting -->
                <tr>
                    <th scope="row">
                        <label for="rate_limit"><?php _e('Rate Limit', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="rate_limit" name="rate_limit" value="<?php echo esc_attr($general_settings['rate_limit']); ?>" min="1" max="100" class="small-text" />
                        <span><?php _e('requests per minute per user', 'smart-search-chatbot'); ?></span>
                        <p class="description">
                            <?php _e('Maximum number of chat requests per user per minute. Helps prevent abuse and control costs.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2><?php _e('Shortcode Settings', 'smart-search-chatbot'); ?></h2>
        <table class="form-table" role="presentation">
            <tbody>
                <!-- Enable Shortcode -->
                <tr>
                    <th scope="row">
                        <label for="enable_shortcode"><?php _e('Enable Shortcode', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label for="enable_shortcode">
                                <input type="checkbox" id="enable_shortcode" name="enable_shortcode" value="1" <?php checked($general_settings['enable_shortcode']); ?> />
                                <?php _e('Enable the chat shortcode for embedding in posts and pages', 'smart-search-chatbot'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, you can use the shortcode to embed the chat interface anywhere on your site.', 'smart-search-chatbot'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
                
                <!-- Shortcode Name -->
                <tr>
                    <th scope="row">
                        <label for="shortcode_name"><?php _e('Shortcode Name', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="shortcode_name" name="shortcode_name" value="<?php echo esc_attr($general_settings['shortcode_name']); ?>" class="regular-text" />
                        <p class="description">
                            <?php printf(__('The shortcode name to use. Current shortcode: %s', 'smart-search-chatbot'), '<code>[' . esc_html($general_settings['shortcode_name']) . ']</code>'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button(__('Save Settings', 'smart-search-chatbot')); ?>
    </form>
    
    <!-- AI Toolkit (Smart Search) Settings -->
    <form method="post" action="">
        <?php wp_nonce_field('ssgc_toolkit_settings', 'ssgc_toolkit_nonce'); ?>
        
        <h2><?php _e('AI Toolkit (Smart Search)', 'smart-search-chatbot'); ?></h2>
        <p><?php _e('Configure WP Engine AI Toolkit Smart Search integration for enhanced context retrieval.', 'smart-search-chatbot'); ?></p>
        
        <table class="form-table" role="presentation">
            <tbody>
                <!-- Search Endpoint -->
                <tr>
                    <th scope="row">
                        <label for="search_endpoint"><?php _e('Search Endpoint', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="search_endpoint" name="ssgc_toolkit[search_endpoint]" value="<?php echo esc_attr($toolkit_settings['search_endpoint']); ?>" class="regular-text" placeholder="https://your-site.com/wp-json/ai-toolkit/v1/search" />
                        <p class="description">
                            <?php _e('Full URL to your AI Toolkit search endpoint. Leave empty to disable Smart Search integration.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
                
                <!-- API Key -->
                <tr>
                    <th scope="row">
                        <label for="toolkit_api_key"><?php _e('API Key', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <input type="password" id="toolkit_api_key" name="ssgc_toolkit[api_key]" value="<?php echo esc_attr($toolkit_settings['api_key']); ?>" class="regular-text" />
                        <button type="button" id="toggle-toolkit-key" class="button button-small">
                            <?php _e('Show', 'smart-search-chatbot'); ?>
                        </button>
                        <p class="description">
                            <?php _e('API key for the search endpoint (if required). Leave empty if no authentication is needed.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
                
                <!-- Index ID -->
                <tr>
                    <th scope="row">
                        <label for="index_id"><?php _e('Index ID', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="index_id" name="ssgc_toolkit[index_id]" value="<?php echo esc_attr($toolkit_settings['index_id']); ?>" class="regular-text" />
                        <p class="description">
                            <?php _e('Search index identifier (if required by your endpoint). Leave empty if not needed.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
                
                <!-- Mode -->
                <tr>
                    <th scope="row">
                        <label for="toolkit_mode"><?php _e('Search Mode', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <select id="toolkit_mode" name="ssgc_toolkit[mode]">
                            <option value="similarity" <?php selected($toolkit_settings['mode'], 'similarity'); ?>><?php _e('Similarity Search', 'smart-search-chatbot'); ?></option>
                            <option value="find" <?php selected($toolkit_settings['mode'], 'find'); ?>><?php _e('Find Search', 'smart-search-chatbot'); ?></option>
                        </select>
                        <p class="description">
                            <?php _e('Search mode: "similarity" uses vector similarity, "find" uses text search. Similarity is recommended for most use cases.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
                
                <!-- Fields -->
                <tr>
                    <th scope="row">
                        <label for="toolkit_fields"><?php _e('Field Boosts', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="toolkit_fields" name="ssgc_toolkit[fields]" value="<?php echo esc_attr($toolkit_settings['fields']); ?>" class="regular-text" placeholder="post_title:8, post_content:1" />
                        <p class="description">
                            <?php _e('Field boost weights for similarity search (comma-separated). Format: field_name:boost_weight. Higher weights prioritize that field.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
                
                <!-- Top K -->
                <tr>
                    <th scope="row">
                        <label for="top_k"><?php _e('Max Results', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="top_k" name="ssgc_toolkit[top_k]" value="<?php echo esc_attr($toolkit_settings['top_k']); ?>" min="1" max="20" class="small-text" />
                        <p class="description">
                            <?php _e('Maximum number of search results to retrieve for context. Recommended: 3-7.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
                
                <!-- Min Score -->
                <tr>
                    <th scope="row">
                        <label for="min_score"><?php _e('Minimum Score', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="min_score" name="ssgc_toolkit[min_score]" value="<?php echo esc_attr($toolkit_settings['min_score']); ?>" min="0" max="1" step="0.01" class="small-text" />
                        <p class="description">
                            <?php _e('Minimum relevance score for search results (0.0-1.0). Lower scores include more results. Recommended: 0.0-0.3.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
                
                <!-- Restrict to Context -->
                <tr>
                    <th scope="row">
                        <label for="restrict_to_context"><?php _e('Answer Restrictions', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label for="restrict_to_context">
                                <input type="checkbox" id="restrict_to_context" name="ssgc_toolkit[restrict_to_context]" value="1" <?php checked(!empty($toolkit_settings['restrict_to_context'])); ?> />
                                <?php _e('Restrict answers to MVDB context only', 'smart-search-chatbot'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, the assistant will answer only using indexed site content. If no relevant content is found, it will say it doesn\'t know instead of using general knowledge.', 'smart-search-chatbot'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button(__('Save AI Toolkit Settings', 'smart-search-chatbot')); ?>
    </form>
    
    <!-- Search Debug Section -->
    <div class="ssgc-test-section">
        <h2><?php _e('Search Debug', 'smart-search-chatbot'); ?></h2>
        <p><?php _e('Test your MVDB endpoint and view parsed results used for RAG.', 'smart-search-chatbot'); ?></p>
        
        <div class="ssgc-test-container">
            <input type="text" id="ssgc-test-q" placeholder="<?php esc_attr_e('Try a query like: pricing', 'smart-search-chatbot'); ?>" style="width: 320px; margin-right: 10px;" />
            <button type="button" class="button" id="ssgc-test-search">
                <?php _e('Run Search', 'smart-search-chatbot'); ?>
            </button>
            <pre id="ssgc-test-out" style="margin-top: 8px; max-height: 260px; overflow: auto; background: #f6f7f7; padding: 12px; border-radius: 6px; font-size: 12px; line-height: 1.4;"><?php _e('(results will appear here)', 'smart-search-chatbot'); ?></pre>
        </div>
    </div>
    
    <!-- API Test Section -->
    <div class="ssgc-test-section">
        <h2><?php _e('Test API Connection', 'smart-search-chatbot'); ?></h2>
        <p><?php _e('Test your API configuration to ensure everything is working correctly.', 'smart-search-chatbot'); ?></p>
        
        <div class="ssgc-test-container">
            <button type="button" id="test-api" class="button button-primary">
                <?php _e('Test API Connection', 'smart-search-chatbot'); ?>
            </button>
            <div id="test-results" class="ssgc-test-results" style="display: none;">
                <h4><?php _e('Test Results:', 'smart-search-chatbot'); ?></h4>
                <div class="ssgc-test-output"></div>
            </div>
        </div>
    </div>
    
    <!-- System Information -->
    <div class="ssgc-system-info">
        <h2><?php _e('System Information', 'smart-search-chatbot'); ?></h2>
        <div class="ssgc-info-grid">
            <div class="ssgc-info-item">
                <strong><?php _e('Plugin Version:', 'smart-search-chatbot'); ?></strong>
                <span><?php echo esc_html(SSGC_VERSION); ?></span>
            </div>
            
            <div class="ssgc-info-item">
                <strong><?php _e('WordPress Version:', 'smart-search-chatbot'); ?></strong>
                <span><?php echo esc_html(get_bloginfo('version')); ?></span>
            </div>
            
            <div class="ssgc-info-item">
                <strong><?php _e('PHP Version:', 'smart-search-chatbot'); ?></strong>
                <span><?php echo esc_html(PHP_VERSION); ?></span>
            </div>
            
            <div class="ssgc-info-item">
                <strong><?php _e('Database Version:', 'smart-search-chatbot'); ?></strong>
                <span><?php echo esc_html($GLOBALS['wpdb']->db_version()); ?></span>
            </div>
            
            <div class="ssgc-info-item">
                <strong><?php _e('REST API:', 'smart-search-chatbot'); ?></strong>
                <span class="ssgc-status-ok"><?php _e('Available', 'smart-search-chatbot'); ?></span>
            </div>
            
            <div class="ssgc-info-item">
                <strong><?php _e('cURL Support:', 'smart-search-chatbot'); ?></strong>
                <span class="<?php echo function_exists('curl_init') ? 'ssgc-status-ok' : 'ssgc-status-error'; ?>">
                    <?php echo function_exists('curl_init') ? __('Available', 'smart-search-chatbot') : __('Not Available', 'smart-search-chatbot'); ?>
                </span>
            </div>
        </div>
    </div>
</div>

<style>
.ssgc-test-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-top: 30px;
}

.ssgc-test-container {
    margin-top: 15px;
}

.ssgc-test-results {
    margin-top: 20px;
    padding: 15px;
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.ssgc-test-results h4 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #1d2327;
}

.ssgc-test-output {
    background: #fff;
    padding: 15px;
    border-radius: 4px;
    border: 1px solid #ddd;
    font-family: monospace;
    font-size: 12px;
    line-height: 1.5;
    white-space: pre-wrap;
}

.ssgc-system-info {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-top: 30px;
}

.ssgc-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.ssgc-info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: #f6f7f7;
    border-radius: 4px;
}

.ssgc-info-item strong {
    color: #1d2327;
}

.ssgc-status-ok {
    color: #00a32a;
    font-weight: 500;
}

.ssgc-status-error {
    color: #d63638;
    font-weight: 500;
}

.provider-models {
    display: none;
}

.provider-models.active {
    display: block;
}

#toggle-api-key {
    margin-left: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Show/hide models based on provider
    function updateModelOptions() {
        var provider = $('#api_provider').val();
        $('.provider-models').removeClass('active');
        $('.provider-models[data-provider="' + provider + '"]').addClass('active');
        
        // Select first option of active provider if current selection is not visible
        var currentModel = $('#model').val();
        var activeOptions = $('.provider-models.active option');
        var isCurrentVisible = activeOptions.filter('[value="' + currentModel + '"]').length > 0;
        
        if (!isCurrentVisible && activeOptions.length > 0) {
            $('#model').val(activeOptions.first().val());
        }
    }
    
    $('#api_provider').on('change', updateModelOptions);
    updateModelOptions(); // Initialize on page load
    
    // Toggle API key visibility
    $('#toggle-api-key').on('click', function() {
        var input = $('#api_key');
        var button = $(this);
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            button.text('<?php esc_js(_e('Hide', 'smart-search-chatbot')); ?>');
        } else {
            input.attr('type', 'password');
            button.text('<?php esc_js(_e('Show', 'smart-search-chatbot')); ?>');
        }
    });
    
    // Toggle Toolkit API key visibility
    $('#toggle-toolkit-key').on('click', function() {
        var input = $('#toolkit_api_key');
        var button = $(this);
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            button.text('<?php esc_js(_e('Hide', 'smart-search-chatbot')); ?>');
        } else {
            input.attr('type', 'password');
            button.text('<?php esc_js(_e('Show', 'smart-search-chatbot')); ?>');
        }
    });
    
    // Test API connection
    $('#test-api').on('click', async function() {
        var button = $(this);
        var resultsDiv = $('#test-results');
        var outputDiv = $('.ssgc-test-output');
        
        button.prop('disabled', true).text('<?php esc_js(_e('Testing...', 'smart-search-chatbot')); ?>');
        resultsDiv.show();
        outputDiv.text('<?php esc_js(_e('Testing API connection...', 'smart-search-chatbot')); ?>');
        
        try {
            const response = await fetch('<?php echo esc_js(rest_url('ssgc/v1/test')); ?>', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                }
            });
            
            const data = await response.json();
            
            if (data.ok) {
                outputDiv.text('✅ Connected to ' + data.provider + ' (' + data.model + ') in ' + data.latency_ms + 'ms');
            } else {
                outputDiv.text('❌ ' + (data.error || 'Test failed'));
            }
        } catch (error) {
            outputDiv.text('❌ Network error: ' + error.message);
        }
        
        button.prop('disabled', false).text('<?php esc_js(_e('Test API Connection', 'smart-search-chatbot')); ?>');
    });
    
    // Validate temperature range
    $('#temperature').on('change', function() {
        var value = parseFloat($(this).val());
        if (value < 0) $(this).val(0);
        if (value > 2) $(this).val(2);
    });
    
    // Validate max tokens
    $('#max_tokens').on('change', function() {
        var value = parseInt($(this).val());
        if (value < 50) $(this).val(50);
        if (value > 4000) $(this).val(4000);
    });
    
    // Update shortcode preview
    $('#shortcode_name').on('input', function() {
        var name = $(this).val() || 'smart_search_chat';
        $(this).next('.description').html('<?php esc_js(_e('The shortcode name to use. Current shortcode:', 'smart-search-chatbot')); ?> <code>[' + name + ']</code>');
    });
});
</script>

<?php
  // Inline bootstrap for the Search Debug panel (nonce + REST URLs)
  $__ssgc_admin_boot = array(
    'nonce'          => wp_create_nonce('wp_rest'),
    'searchTestUrl'  => site_url('index.php?rest_route=/ssgc/v1/search-test'),
    'searchDebugUrl' => site_url('index.php?rest_route=/ssgc/v1/search-debug'),
  );
?>
<script>
  // Provide SSGC_ADMIN if not already set by enqueue
  window.SSGC_ADMIN = window.SSGC_ADMIN || <?php echo wp_json_encode($__ssgc_admin_boot); ?>;
</script>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const btn = document.getElementById('ssgc-test-search');
  const q   = document.getElementById('ssgc-test-q');
  const out = document.getElementById('ssgc-test-out');

  if (!btn) { console.warn('[SSGC] Debug button not found'); return; }
  if (!window.SSGC_ADMIN) { console.warn('[SSGC] SSGC_ADMIN missing'); return; }

  const TEST_URL   = SSGC_ADMIN.searchTestUrl;
  const DEBUG_URL  = SSGC_ADMIN.searchDebugUrl;
  const NONCE      = SSGC_ADMIN.nonce;

  console.log('[SSGC] Debug ready', {TEST_URL, DEBUG_URL});

  async function tryFetch(base, term) {
    const url = base + (base.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(term || 'test');
    const res = await fetch(url, {
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': NONCE }
    });
    const text = await res.text();
    let data; try { data = JSON.parse(text); } catch { data = { rawText: text }; }
    return { res, data, url };
  }

  btn.addEventListener('click', async function(){
    out.textContent = 'Searching…';
    const term = (q && q.value) ? q.value : 'test';
    try {
      let {res, data, url} = await tryFetch(TEST_URL, term);
      if (!res.ok && res.status !== 401) {
        ({res, data, url} = await tryFetch(DEBUG_URL, term));
      }
      if (!res.ok || data.error) {
        out.textContent = '❌ ' + (data.error ? JSON.stringify(data.error) : (res.status + ' ' + res.statusText)) + '\nURL: ' + url + (data.rawText ? ('\n\n' + data.rawText) : '');
        return;
      }

      const raw = data.raw || {};
      const s = raw?.data?.similarity;
      const f = raw?.data?.find;
      const docs = (s?.docs || s?.documents || f?.documents || []);
      const first = docs[0] || null;
      const preview = first ? {
        keys: Object.keys(first).slice(0,10),
        dataKeys: first.data ? Object.keys(first.data).slice(0,10) : [],
        score: first.score ?? first._score ?? null,
        sampleTitle: first.data?.post_title || first.data?.title || null,
        sampleUrl: first.data?.post_url || first.data?.url || null
      } : null;

      out.textContent = JSON.stringify({ status: data.status, parsed: data.parsed, rawPreview: preview }, null, 2);
    } catch(e){
      out.textContent = '❌ ' + e.message;
    }
  });
  
  // Allow Enter key to trigger search
  if (q) {
    q.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        btn.click();
      }
    });
  }
});
</script>
