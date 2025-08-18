<?php
/**
 * Persona Settings Admin Page
 * 
 * Configuration page for AI persona and behavior
 */

// Prevent direct access
defined('ABSPATH') || exit;

// Get persona instance and settings
$persona = SSGC_Persona::get_instance();
$settings = $persona->get_settings();
?>

<div class="wrap ssgc-admin-page">
    <h1><?php _e('Persona Settings', 'smart-search-chatbot'); ?></h1>
    <p><?php _e('Configure the AI personality and behavior for your chatbot. The persona settings define how the AI responds to users and what tone it uses.', 'smart-search-chatbot'); ?></p>
    
    <form method="post" action="">
        <?php wp_nonce_field('ssgc_persona_settings', 'ssgc_persona_settings_nonce'); ?>
        
        <table class="form-table" role="presentation">
            <tbody>
                <!-- Enable Persona -->
                <tr>
                    <th scope="row">
                        <label for="persona_enabled"><?php _e('Enable Persona', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label for="persona_enabled">
                                <input type="checkbox" id="persona_enabled" name="persona_enabled" value="1" <?php checked($settings['enabled']); ?> />
                                <?php _e('Use custom persona settings', 'smart-search-chatbot'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When disabled, the AI will use default assistant behavior.', 'smart-search-chatbot'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
                
                <!-- Persona Name -->
                <tr>
                    <th scope="row">
                        <label for="persona_name"><?php _e('Persona Name', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="persona_name" name="persona_name" value="<?php echo esc_attr($settings['name'] ?? ''); ?>" class="regular-text" />
                        <p class="description">
                            <?php _e('A name for your AI persona (optional). This helps you identify the persona but may not be shown to users.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
                
                <!-- Persona Description -->
                <tr>
                    <th scope="row">
                        <label for="persona_description"><?php _e('Description', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <textarea id="persona_description" name="persona_description" rows="3" class="large-text"><?php echo esc_textarea($settings['description'] ?? ''); ?></textarea>
                        <p class="description">
                            <?php _e('A brief description of your persona for your own reference (optional).', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
                
                <!-- Instructions -->
                <tr>
                    <th scope="row">
                        <label for="persona_instructions"><?php _e('Instructions', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <textarea id="persona_instructions" name="persona_instructions" rows="8" class="large-text" required><?php echo esc_textarea($settings['instructions'] ?? 'You are a helpful AI assistant.'); ?></textarea>
                        <p class="description">
                            <?php _e('Detailed instructions that define how the AI should behave, what it knows, and how it should respond. Be specific about tone, expertise areas, and any limitations.', 'smart-search-chatbot'); ?>
                        </p>
                        
                        <details class="ssgc-help-details">
                            <summary><?php _e('Examples and Tips', 'smart-search-chatbot'); ?></summary>
                            <div class="ssgc-help-content">
                                <h4><?php _e('Good Instructions Include:', 'smart-search-chatbot'); ?></h4>
                                <ul>
                                    <li><?php _e('Role definition: "You are a customer support specialist for [Company Name]"', 'smart-search-chatbot'); ?></li>
                                    <li><?php _e('Tone guidance: "Be friendly, professional, and helpful"', 'smart-search-chatbot'); ?></li>
                                    <li><?php _e('Knowledge areas: "You specialize in product information and troubleshooting"', 'smart-search-chatbot'); ?></li>
                                    <li><?php _e('Limitations: "If you don\'t know something, admit it and offer to connect them with a human"', 'smart-search-chatbot'); ?></li>
                                </ul>
                                
                                <h4><?php _e('Example:', 'smart-search-chatbot'); ?></h4>
                                <p><em>"You are a knowledgeable customer support representative for TechCorp, a software company. You are friendly, patient, and always try to be helpful. You specialize in helping customers with product questions, account issues, and basic troubleshooting. When you don't know something, you admit it honestly and offer to connect the customer with a specialist. Always maintain a professional but warm tone."</em></p>
                            </div>
                        </details>
                    </td>
                </tr>
                
                <!-- Style -->
                <tr>
                    <th scope="row">
                        <label for="persona_style"><?php _e('Communication Style', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <select id="persona_style" name="persona_style">
                            <option value="friendly" <?php selected($settings['style'], 'friendly'); ?>><?php _e('Friendly', 'smart-search-chatbot'); ?></option>
                            <option value="professional" <?php selected($settings['style'], 'professional'); ?>><?php _e('Professional', 'smart-search-chatbot'); ?></option>
                            <option value="casual" <?php selected($settings['style'], 'casual'); ?>><?php _e('Casual', 'smart-search-chatbot'); ?></option>
                            <option value="formal" <?php selected($settings['style'], 'formal'); ?>><?php _e('Formal', 'smart-search-chatbot'); ?></option>
                            <option value="enthusiastic" <?php selected($settings['style'], 'enthusiastic'); ?>><?php _e('Enthusiastic', 'smart-search-chatbot'); ?></option>
                            <option value="concise" <?php selected($settings['style'], 'concise'); ?>><?php _e('Concise', 'smart-search-chatbot'); ?></option>
                        </select>
                        <p class="description">
                            <?php _e('The overall communication style for responses. This works together with your detailed instructions.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button(__('Save Persona Settings', 'smart-search-chatbot')); ?>
    </form>
    
    <!-- Test Persona -->
    <div class="ssgc-test-section">
        <h2><?php _e('Test Your Persona', 'smart-search-chatbot'); ?></h2>
        <p><?php _e('Send a test message to see how your persona responds. This helps you fine-tune the instructions and style.', 'smart-search-chatbot'); ?></p>
        
        <div class="ssgc-test-container">
            <div class="ssgc-test-input">
                <textarea id="test-message" placeholder="<?php esc_attr_e('Type a test message here...', 'smart-search-chatbot'); ?>" rows="3"><?php echo esc_textarea('Hello, how can you help me?'); ?></textarea>
                <button type="button" id="test-persona-btn" class="button button-primary">
                    <?php _e('Test Persona', 'smart-search-chatbot'); ?>
                </button>
            </div>
            
            <div id="test-response" class="ssgc-test-response" style="display: none;">
                <h4><?php _e('Response:', 'smart-search-chatbot'); ?></h4>
                <div class="ssgc-response-content"></div>
                <div class="ssgc-response-meta"></div>
            </div>
        </div>
    </div>
    
    <!-- Current Settings Summary -->
    <div class="ssgc-summary-section">
        <h2><?php _e('Current Settings Summary', 'smart-search-chatbot'); ?></h2>
        <div class="ssgc-summary-grid">
            <div class="ssgc-summary-item">
                <strong><?php _e('Status:', 'smart-search-chatbot'); ?></strong>
                <span class="<?php echo $settings['enabled'] ? 'enabled' : 'disabled'; ?>">
                    <?php echo $settings['enabled'] ? __('Enabled', 'smart-search-chatbot') : __('Disabled', 'smart-search-chatbot'); ?>
                </span>
            </div>
            
            <div class="ssgc-summary-item">
                <strong><?php _e('Style:', 'smart-search-chatbot'); ?></strong>
                <span><?php echo esc_html(ucfirst($settings['style'] ?? 'friendly')); ?></span>
            </div>
            
            <div class="ssgc-summary-item">
                <strong><?php _e('Instructions Length:', 'smart-search-chatbot'); ?></strong>
                <span><?php echo strlen($settings['instructions'] ?? ''); ?> <?php _e('characters', 'smart-search-chatbot'); ?></span>
            </div>
        </div>
    </div>
</div>

<style>
.ssgc-help-details {
    margin-top: 10px;
}

.ssgc-help-details summary {
    cursor: pointer;
    color: #0073aa;
    font-weight: 500;
}

.ssgc-help-details summary:hover {
    color: #005a87;
}

.ssgc-help-content {
    margin-top: 15px;
    padding: 15px;
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.ssgc-help-content h4 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #1d2327;
}

.ssgc-help-content ul {
    margin-left: 20px;
    margin-bottom: 15px;
}

.ssgc-help-content em {
    display: block;
    padding: 10px;
    background: #fff;
    border-left: 4px solid #0073aa;
    margin-top: 10px;
}

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

.ssgc-test-input {
    display: flex;
    gap: 10px;
    align-items: flex-end;
}

.ssgc-test-input textarea {
    flex: 1;
    resize: vertical;
}

.ssgc-test-response {
    margin-top: 20px;
    padding: 15px;
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.ssgc-test-response h4 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #1d2327;
}

.ssgc-response-content {
    background: #fff;
    padding: 15px;
    border-radius: 4px;
    border: 1px solid #ddd;
    margin-bottom: 10px;
    line-height: 1.5;
}

.ssgc-response-meta {
    font-size: 12px;
    color: #646970;
}

.ssgc-summary-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-top: 30px;
}

.ssgc-summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.ssgc-summary-item {
    padding: 10px;
    background: #f6f7f7;
    border-radius: 4px;
}

.ssgc-summary-item strong {
    display: block;
    margin-bottom: 5px;
    color: #1d2327;
}

.ssgc-summary-item .enabled {
    color: #00a32a;
    font-weight: 500;
}

.ssgc-summary-item .disabled {
    color: #d63638;
    font-weight: 500;
}

.ssgc-loading {
    opacity: 0.6;
    pointer-events: none;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Test persona functionality
    $('#test-persona-btn').on('click', function() {
        var button = $(this);
        var message = $('#test-message').val().trim();
        var responseDiv = $('#test-response');
        var contentDiv = $('.ssgc-response-content');
        var metaDiv = $('.ssgc-response-meta');
        
        if (!message) {
            alert('<?php esc_js(_e('Please enter a test message.', 'smart-search-chatbot')); ?>');
            return;
        }
        
        // Show loading state
        button.prop('disabled', true).text('<?php esc_js(_e('Testing...', 'smart-search-chatbot')); ?>');
        responseDiv.show();
        contentDiv.html('<div class="ssgc-loading"><?php esc_js(_e('Generating response...', 'smart-search-chatbot')); ?></div>');
        metaDiv.html('');
        
        // Send test request
        $.post(ajaxurl, {
            action: 'ssgc_test_persona',
            nonce: ssgc_admin.nonce,
            message: message
        })
        .done(function(response) {
            if (response.success) {
                contentDiv.html(response.data.text);
                metaDiv.html(
                    '<strong><?php esc_js(_e('Persona Used:', 'smart-search-chatbot')); ?></strong> ' + 
                    response.data.persona_used.substring(0, 100) + '...<br>' +
                    '<strong><?php esc_js(_e('Style:', 'smart-search-chatbot')); ?></strong> ' + 
                    response.data.style
                );
            } else {
                contentDiv.html('<div style="color: #d63638;"><?php esc_js(_e('Error testing persona. Please try again.', 'smart-search-chatbot')); ?></div>');
            }
        })
        .fail(function() {
            contentDiv.html('<div style="color: #d63638;"><?php esc_js(_e('Error testing persona. Please try again.', 'smart-search-chatbot')); ?></div>');
        })
        .always(function() {
            button.prop('disabled', false).text('<?php esc_js(_e('Test Persona', 'smart-search-chatbot')); ?>');
        });
    });
    
    // Character counter for instructions
    $('#persona_instructions').on('input', function() {
        var length = $(this).val().length;
        $('.ssgc-summary-item:contains("<?php esc_js(_e('Instructions Length:', 'smart-search-chatbot')); ?>") span').text(length + ' <?php esc_js(_e('characters', 'smart-search-chatbot')); ?>');
    });
    
    // Update summary when settings change
    $('#persona_enabled').on('change', function() {
        var status = $(this).is(':checked') ? '<?php esc_js(_e('Enabled', 'smart-search-chatbot')); ?>' : '<?php esc_js(_e('Disabled', 'smart-search-chatbot')); ?>';
        var className = $(this).is(':checked') ? 'enabled' : 'disabled';
        $('.ssgc-summary-item:contains("<?php esc_js(_e('Status:', 'smart-search-chatbot')); ?>") span').text(status).attr('class', className);
    });
    
    $('#persona_style').on('change', function() {
        var style = $(this).val();
        style = style.charAt(0).toUpperCase() + style.slice(1);
        $('.ssgc-summary-item:contains("<?php esc_js(_e('Style:', 'smart-search-chatbot')); ?>") span').text(style);
    });
});
</script>
