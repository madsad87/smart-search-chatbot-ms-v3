<?php
/**
 * Widget Settings Admin Page
 * 
 * Configuration page for the floating widget
 */

// Prevent direct access
defined('ABSPATH') || exit;

// Get widget instance and settings
$widget = SSGC_Widget::get_instance();
$settings = $widget->get_settings();
?>

<div class="wrap ssgc-admin-page">
    <h1><?php _e('Widget Settings', 'smart-search-chatbot'); ?></h1>
    <p><?php _e('Configure the floating chat widget that appears on your website. The widget provides a site-agnostic interface that can be embedded on any page.', 'smart-search-chatbot'); ?></p>
    
    <form method="post" action="">
        <?php wp_nonce_field('ssgc_widget_settings', 'ssgc_widget_settings_nonce'); ?>
        
        <table class="form-table" role="presentation">
            <tbody>
                <!-- Enable Widget -->
                <tr>
                    <th scope="row">
                        <label for="widget_enabled"><?php _e('Enable Widget', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label for="widget_enabled">
                                <input type="checkbox" id="widget_enabled" name="widget_enabled" value="1" <?php checked($settings['enabled']); ?> />
                                <?php _e('Show floating chat widget on website', 'smart-search-chatbot'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, a floating chat bubble will appear on all pages of your website.', 'smart-search-chatbot'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
                
                <!-- Position -->
                <tr>
                    <th scope="row">
                        <label for="widget_position"><?php _e('Position', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <select id="widget_position" name="widget_position">
                            <option value="br" <?php selected($settings['position'], 'br'); ?>><?php _e('Bottom Right', 'smart-search-chatbot'); ?></option>
                            <option value="bl" <?php selected($settings['position'], 'bl'); ?>><?php _e('Bottom Left', 'smart-search-chatbot'); ?></option>
                        </select>
                        <p class="description">
                            <?php _e('Choose where the chat bubble appears on the page.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
                
                <!-- Theme Color -->
                <tr>
                    <th scope="row">
                        <label for="widget_color"><?php _e('Theme Color', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <input type="color" id="widget_color" name="widget_color" value="<?php echo esc_attr($settings['color']); ?>" />
                        <input type="text" id="widget_color_text" value="<?php echo esc_attr($settings['color']); ?>" pattern="^#[0-9A-Fa-f]{6}$" />
                        <p class="description">
                            <?php _e('The primary color for the chat bubble and interface elements.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
                
                <!-- Z-Index -->
                <tr>
                    <th scope="row">
                        <label for="widget_z_index"><?php _e('Z-Index', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="widget_z_index" name="widget_z_index" value="<?php echo esc_attr($settings['z_index']); ?>" min="1" max="9999999" />
                        <p class="description">
                            <?php _e('Controls the stacking order of the widget. Higher values appear above other elements. Default: 999999', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2><?php _e('Proactive Greeting', 'smart-search-chatbot'); ?></h2>
        <p><?php _e('Configure an automatic greeting message that appears after a delay to encourage user engagement.', 'smart-search-chatbot'); ?></p>
        
        <table class="form-table" role="presentation">
            <tbody>
                <!-- Enable Greeting -->
                <tr>
                    <th scope="row">
                        <label for="greet_enabled"><?php _e('Enable Greeting', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label for="greet_enabled">
                                <input type="checkbox" id="greet_enabled" name="greet_enabled" value="1" <?php checked($settings['greet']['enabled'] ?? false); ?> />
                                <?php _e('Show proactive greeting message', 'smart-search-chatbot'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, a greeting message will appear automatically after the specified delay.', 'smart-search-chatbot'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
                
                <!-- Greeting Delay -->
                <tr>
                    <th scope="row">
                        <label for="greet_delay"><?php _e('Delay (milliseconds)', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="greet_delay" name="greet_delay" value="<?php echo esc_attr($settings['greet']['delay_ms'] ?? 8000); ?>" min="1000" max="60000" step="1000" />
                        <p class="description">
                            <?php _e('How long to wait before showing the greeting (1000 = 1 second). Recommended: 8000-15000ms.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
                
                <!-- Greeting Message -->
                <tr>
                    <th scope="row">
                        <label for="greet_message"><?php _e('Greeting Message', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="greet_message" name="greet_message" value="<?php echo esc_attr($settings['greet']['message'] ?? 'Need help?'); ?>" class="regular-text" maxlength="100" />
                        <p class="description">
                            <?php _e('The message to display in the greeting. Keep it short and friendly.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2><?php _e('Configuration Endpoint', 'smart-search-chatbot'); ?></h2>
        <p><?php _e('The widget automatically exposes a configuration endpoint that provides settings to the chat widget.', 'smart-search-chatbot'); ?></p>
        
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><?php _e('Config URL', 'smart-search-chatbot'); ?></th>
                    <td>
                        <code><?php echo esc_html(rest_url('ssgc/v1/widget-config')); ?></code>
                        <a href="<?php echo esc_url(rest_url('ssgc/v1/widget-config')); ?>" target="_blank" class="button button-small">
                            <?php _e('Test Endpoint', 'smart-search-chatbot'); ?>
                        </a>
                        <p class="description">
                            <?php _e('This endpoint provides configuration data to the widget. It\'s automatically available when the plugin is active.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Chat Endpoint', 'smart-search-chatbot'); ?></th>
                    <td>
                        <code><?php echo esc_html(rest_url('ssgc/v1/chat')); ?></code>
                        <p class="description">
                            <?php _e('The endpoint that handles chat requests from the widget.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2><?php _e('Implementation', 'smart-search-chatbot'); ?></h2>
        <p><?php _e('To use the widget on external sites or with a CDN, use the following code snippet:', 'smart-search-chatbot'); ?></p>
        
        <div class="ssgc-code-example">
            <h4><?php _e('Basic Implementation', 'smart-search-chatbot'); ?></h4>
            <textarea readonly class="ssgc-code-textarea">&lt;script src="https://your-cdn.com/ssgc-loader.js" 
        data-config-url="<?php echo esc_html(rest_url('ssgc/v1/widget-config')); ?>"
        defer&gt;&lt;/script&gt;</textarea>
            
            <h4><?php _e('With Custom Settings', 'smart-search-chatbot'); ?></h4>
            <textarea readonly class="ssgc-code-textarea">&lt;script src="https://your-cdn.com/ssgc-loader.js" 
        data-config-url="<?php echo esc_html(rest_url('ssgc/v1/widget-config')); ?>"
        data-color="<?php echo esc_attr($settings['color']); ?>"
        data-position="<?php echo esc_attr($settings['position']); ?>"
        defer&gt;&lt;/script&gt;</textarea>
        </div>
        
        <?php submit_button(__('Save Widget Settings', 'smart-search-chatbot')); ?>
    </form>
    
    <?php if ($settings['enabled']): ?>
    <div class="ssgc-widget-preview">
        <h2><?php _e('Widget Preview', 'smart-search-chatbot'); ?></h2>
        <p><?php _e('The widget is currently enabled and will appear on your website with these settings:', 'smart-search-chatbot'); ?></p>
        
        <div class="ssgc-preview-container">
            <div class="ssgc-preview-widget" style="background-color: <?php echo esc_attr($settings['color']); ?>;">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="white">
                    <path d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h4l4 4 4-4h4c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/>
                </svg>
            </div>
            <p class="ssgc-preview-label">
                <?php echo $settings['position'] === 'br' ? __('Bottom Right', 'smart-search-chatbot') : __('Bottom Left', 'smart-search-chatbot'); ?>
            </p>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.ssgc-code-example {
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.ssgc-code-example h4 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #1d2327;
}

.ssgc-code-textarea {
    width: 100%;
    height: 80px;
    font-family: Consolas, Monaco, monospace;
    font-size: 12px;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 3px;
    padding: 10px;
    resize: vertical;
    margin-bottom: 15px;
}

.ssgc-widget-preview {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-top: 30px;
}

.ssgc-preview-container {
    position: relative;
    height: 200px;
    background: #f0f0f1;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    overflow: hidden;
}

.ssgc-preview-widget {
    position: absolute;
    bottom: 20px;
    <?php echo $settings['position'] === 'bl' ? 'left' : 'right'; ?>: 20px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    cursor: pointer;
    transition: transform 0.2s ease;
}

.ssgc-preview-widget:hover {
    transform: scale(1.05);
}

.ssgc-preview-label {
    position: absolute;
    bottom: 10px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 5px 10px;
    border-radius: 3px;
    font-size: 12px;
    margin: 0;
}

#widget_color_text {
    margin-left: 10px;
    width: 80px;
    font-family: monospace;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Sync color picker with text input
    $('#widget_color').on('change', function() {
        $('#widget_color_text').val($(this).val());
        $('.ssgc-preview-widget').css('background-color', $(this).val());
    });
    
    $('#widget_color_text').on('change keyup', function() {
        var color = $(this).val();
        if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
            $('#widget_color').val(color);
            $('.ssgc-preview-widget').css('background-color', color);
        }
    });
    
    // Update preview position
    $('#widget_position').on('change', function() {
        var position = $(this).val();
        var widget = $('.ssgc-preview-widget');
        
        if (position === 'bl') {
            widget.css({left: '20px', right: 'auto'});
            $('.ssgc-preview-label').text('Bottom Left');
        } else {
            widget.css({right: '20px', left: 'auto'});
            $('.ssgc-preview-label').text('Bottom Right');
        }
    });
    
    // Auto-select code when clicked
    $('.ssgc-code-textarea').on('click', function() {
        $(this).select();
    });
});
</script>
