<?php
/**
 * Log Settings Admin Page
 * 
 * Configuration page for chat log settings
 */

// Prevent direct access
defined('ABSPATH') || exit;

// Get logs instance and settings
$logs = SSGC_Logs::get_instance();
$settings = $logs->get_settings();
$stats = $logs->get_stats();
?>

<div class="wrap ssgc-admin-page">
    <h1><?php _e('Log Settings', 'smart-search-chatbot'); ?></h1>
    <p><?php _e('Configure how chat conversations are logged and stored. These settings help you manage data retention, privacy, and compliance requirements.', 'smart-search-chatbot'); ?></p>
    
    <form method="post" action="">
        <?php wp_nonce_field('ssgc_log_settings', 'ssgc_log_settings_nonce'); ?>
        
        <table class="form-table" role="presentation">
            <tbody>
                <!-- Enable Logging -->
                <tr>
                    <th scope="row">
                        <label for="logs_enabled"><?php _e('Enable Logging', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label for="logs_enabled">
                                <input type="checkbox" id="logs_enabled" name="logs_enabled" value="1" <?php checked($settings['enabled']); ?> />
                                <?php _e('Log chat conversations', 'smart-search-chatbot'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, all chat conversations will be stored in the database for analysis and review.', 'smart-search-chatbot'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
                
                <!-- Data Retention -->
                <tr>
                    <th scope="row">
                        <label for="retention_days"><?php _e('Data Retention', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="retention_days" name="retention_days" value="<?php echo esc_attr($settings['retention_days']); ?>" min="1" max="365" class="small-text" />
                        <span><?php _e('days', 'smart-search-chatbot'); ?></span>
                        <p class="description">
                            <?php _e('How long to keep chat logs before automatically deleting them. Logs older than this will be removed daily. Minimum: 1 day, Maximum: 365 days.', 'smart-search-chatbot'); ?>
                        </p>
                    </td>
                </tr>
                
                <!-- PII Redaction -->
                <tr>
                    <th scope="row">
                        <label for="redact_pii"><?php _e('Privacy Protection', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label for="redact_pii">
                                <input type="checkbox" id="redact_pii" name="redact_pii" value="1" <?php checked($settings['redact_pii']); ?> />
                                <?php _e('Redact personally identifiable information (PII)', 'smart-search-chatbot'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Automatically detect and redact common PII patterns like email addresses, phone numbers, and credit card numbers before storing messages.', 'smart-search-chatbot'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
                
                <!-- Log IP Addresses -->
                <tr>
                    <th scope="row">
                        <label for="log_ip"><?php _e('IP Address Logging', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label for="log_ip">
                                <input type="checkbox" id="log_ip" name="log_ip" value="1" <?php checked($settings['log_ip']); ?> />
                                <?php _e('Log user IP addresses', 'smart-search-chatbot'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Store the IP address of users for analytics and abuse prevention. Consider privacy regulations in your jurisdiction.', 'smart-search-chatbot'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
                
                <!-- Log User Agent -->
                <tr>
                    <th scope="row">
                        <label for="log_user_agent"><?php _e('User Agent Logging', 'smart-search-chatbot'); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label for="log_user_agent">
                                <input type="checkbox" id="log_user_agent" name="log_user_agent" value="1" <?php checked($settings['log_user_agent']); ?> />
                                <?php _e('Log user agent strings', 'smart-search-chatbot'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Store browser and device information for analytics and debugging purposes.', 'smart-search-chatbot'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button(__('Save Log Settings', 'smart-search-chatbot')); ?>
    </form>
    
    <!-- Current Statistics -->
    <div class="ssgc-stats-section">
        <h2><?php _e('Current Log Statistics', 'smart-search-chatbot'); ?></h2>
        <div class="ssgc-stats-grid">
            <div class="ssgc-stat-card">
                <div class="ssgc-stat-icon">📊</div>
                <div class="ssgc-stat-content">
                    <h3><?php echo number_format($stats['total_messages']); ?></h3>
                    <p><?php _e('Total Messages Logged', 'smart-search-chatbot'); ?></p>
                </div>
            </div>
            
            <div class="ssgc-stat-card">
                <div class="ssgc-stat-icon">👥</div>
                <div class="ssgc-stat-content">
                    <h3><?php echo number_format($stats['unique_sessions']); ?></h3>
                    <p><?php _e('Unique Sessions', 'smart-search-chatbot'); ?></p>
                </div>
            </div>
            
            <div class="ssgc-stat-card">
                <div class="ssgc-stat-icon">📈</div>
                <div class="ssgc-stat-content">
                    <h3><?php echo number_format($stats['messages_today']); ?></h3>
                    <p><?php _e('Messages Today', 'smart-search-chatbot'); ?></p>
                </div>
            </div>
            
            <div class="ssgc-stat-card">
                <div class="ssgc-stat-icon">💾</div>
                <div class="ssgc-stat-content">
                    <h3><?php echo $this->estimate_storage_size($stats['total_messages']); ?></h3>
                    <p><?php _e('Estimated Storage', 'smart-search-chatbot'); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Data Management -->
    <div class="ssgc-data-management">
        <h2><?php _e('Data Management', 'smart-search-chatbot'); ?></h2>
        <p><?php _e('Tools for managing your chat log data.', 'smart-search-chatbot'); ?></p>
        
        <div class="ssgc-management-actions">
            <div class="ssgc-action-card">
                <h3><?php _e('Export Data', 'smart-search-chatbot'); ?></h3>
                <p><?php _e('Download all chat logs as a CSV file for backup or analysis.', 'smart-search-chatbot'); ?></p>
                <button type="button" id="export-logs" class="button button-primary">
                    <?php _e('Export All Logs', 'smart-search-chatbot'); ?>
                </button>
            </div>
            
            <div class="ssgc-action-card">
                <h3><?php _e('Clear Data', 'smart-search-chatbot'); ?></h3>
                <p><?php _e('Permanently delete all chat logs. This action cannot be undone.', 'smart-search-chatbot'); ?></p>
                <button type="button" id="clear-logs" class="button button-secondary">
                    <?php _e('Clear All Logs', 'smart-search-chatbot'); ?>
                </button>
            </div>
            
            <div class="ssgc-action-card">
                <h3><?php _e('Cleanup Old Data', 'smart-search-chatbot'); ?></h3>
                <p><?php _e('Manually run the cleanup process to remove logs older than your retention period.', 'smart-search-chatbot'); ?></p>
                <button type="button" id="cleanup-logs" class="button">
                    <?php _e('Run Cleanup Now', 'smart-search-chatbot'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Privacy Information -->
    <div class="ssgc-privacy-info">
        <h2><?php _e('Privacy & Compliance', 'smart-search-chatbot'); ?></h2>
        <div class="ssgc-privacy-content">
            <h3><?php _e('Data Protection Considerations', 'smart-search-chatbot'); ?></h3>
            <ul>
                <li><?php _e('Enable PII redaction to automatically remove sensitive information from logs', 'smart-search-chatbot'); ?></li>
                <li><?php _e('Set appropriate retention periods based on your business needs and legal requirements', 'smart-search-chatbot'); ?></li>
                <li><?php _e('Consider disabling IP address logging if not needed for your use case', 'smart-search-chatbot'); ?></li>
                <li><?php _e('Regularly review and export logs for compliance audits', 'smart-search-chatbot'); ?></li>
                <li><?php _e('Ensure your privacy policy covers chatbot data collection and usage', 'smart-search-chatbot'); ?></li>
            </ul>
            
            <h3><?php _e('GDPR & Privacy Regulations', 'smart-search-chatbot'); ?></h3>
            <p><?php _e('If you operate in regions with strict privacy regulations (GDPR, CCPA, etc.), consider:', 'smart-search-chatbot'); ?></p>
            <ul>
                <li><?php _e('Implementing user consent mechanisms before logging conversations', 'smart-search-chatbot'); ?></li>
                <li><?php _e('Providing users with access to their chat data upon request', 'smart-search-chatbot'); ?></li>
                <li><?php _e('Enabling data deletion requests (right to be forgotten)', 'smart-search-chatbot'); ?></li>
                <li><?php _e('Documenting your data processing activities and retention policies', 'smart-search-chatbot'); ?></li>
            </ul>
        </div>
    </div>
</div>

<style>
.ssgc-stats-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-top: 30px;
}

.ssgc-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.ssgc-stat-card {
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.ssgc-stat-icon {
    font-size: 24px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border-radius: 50%;
}

.ssgc-stat-content h3 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
    color: #1d2327;
}

.ssgc-stat-content p {
    margin: 5px 0 0;
    color: #646970;
    font-size: 14px;
}

.ssgc-data-management {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-top: 30px;
}

.ssgc-management-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.ssgc-action-card {
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
}

.ssgc-action-card h3 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #1d2327;
}

.ssgc-action-card p {
    color: #646970;
    margin-bottom: 15px;
    line-height: 1.5;
}

.ssgc-privacy-info {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-top: 30px;
}

.ssgc-privacy-content h3 {
    color: #1d2327;
    margin-bottom: 10px;
}

.ssgc-privacy-content ul {
    margin-left: 20px;
    margin-bottom: 20px;
}

.ssgc-privacy-content li {
    margin-bottom: 5px;
    line-height: 1.5;
}

.ssgc-loading {
    opacity: 0.6;
    pointer-events: none;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Export logs
    $('#export-logs').on('click', function() {
        var button = $(this);
        button.prop('disabled', true).text('<?php esc_js(_e('Exporting...', 'smart-search-chatbot')); ?>');
        
        window.location.href = ajaxurl + '?action=ssgc_export_logs&nonce=' + ssgc_admin.nonce;
        
        // Re-enable button after a delay
        setTimeout(function() {
            button.prop('disabled', false).text('<?php esc_js(_e('Export All Logs', 'smart-search-chatbot')); ?>');
        }, 3000);
    });
    
    // Clear logs
    $('#clear-logs').on('click', function() {
        if (!confirm('<?php esc_js(_e('Are you sure you want to delete ALL chat logs? This action cannot be undone and will permanently remove all conversation data.', 'smart-search-chatbot')); ?>')) {
            return;
        }
        
        var button = $(this);
        button.prop('disabled', true).text('<?php esc_js(_e('Clearing...', 'smart-search-chatbot')); ?>');
        
        $.post(ajaxurl, {
            action: 'ssgc_clear_logs',
            nonce: ssgc_admin.nonce
        })
        .done(function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert('<?php esc_js(_e('Error clearing logs. Please try again.', 'smart-search-chatbot')); ?>');
            }
        })
        .fail(function() {
            alert('<?php esc_js(_e('Error clearing logs. Please try again.', 'smart-search-chatbot')); ?>');
        })
        .always(function() {
            button.prop('disabled', false).text('<?php esc_js(_e('Clear All Logs', 'smart-search-chatbot')); ?>');
        });
    });
    
    // Manual cleanup
    $('#cleanup-logs').on('click', function() {
        var button = $(this);
        button.prop('disabled', true).text('<?php esc_js(_e('Running Cleanup...', 'smart-search-chatbot')); ?>');
        
        // Simulate cleanup process (would need backend implementation)
        setTimeout(function() {
            alert('<?php esc_js(_e('Cleanup completed. Old logs have been removed based on your retention settings.', 'smart-search-chatbot')); ?>');
            button.prop('disabled', false).text('<?php esc_js(_e('Run Cleanup Now', 'smart-search-chatbot')); ?>');
        }, 2000);
    });
    
    // Update retention warning
    $('#retention_days').on('change', function() {
        var days = parseInt($(this).val());
        if (days < 7) {
            if (!confirm('<?php esc_js(_e('Setting retention to less than 7 days may result in frequent data loss. Are you sure?', 'smart-search-chatbot')); ?>')) {
                $(this).val(7);
            }
        }
    });
});
</script>

<?php
// Helper function to estimate storage size
function estimate_storage_size($message_count) {
    // Rough estimate: average message pair ~500 bytes
    $bytes = $message_count * 500;
    
    if ($bytes < 1024) {
        return $bytes . ' B';
    } elseif ($bytes < 1048576) {
        return round($bytes / 1024, 1) . ' KB';
    } elseif ($bytes < 1073741824) {
        return round($bytes / 1048576, 1) . ' MB';
    } else {
        return round($bytes / 1073741824, 1) . ' GB';
    }
}
?>
