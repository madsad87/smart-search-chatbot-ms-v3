<?php
/**
 * Overview Admin Page
 * 
 * Main dashboard for Smart Search Chatbot
 */

// Prevent direct access
defined('ABSPATH') || exit;

// Get instances
$logs = SSGC_Logs::get_instance();
$widget = SSGC_Widget::get_instance();
$persona = SSGC_Persona::get_instance();

// Get statistics
$stats = $logs->get_stats();
$widget_settings = $widget->get_settings();
$persona_settings = $persona->get_settings();
?>

<div class="wrap ssgc-admin-page">
    <h1><?php _e('Smart Search Chatbot Overview', 'smart-search-chatbot'); ?></h1>
    
    <div class="ssgc-overview-grid">
        <!-- Statistics Cards -->
        <div class="ssgc-stats-grid">
            <div class="ssgc-stat-card">
                <div class="ssgc-stat-icon">📊</div>
                <div class="ssgc-stat-content">
                    <h3><?php echo number_format($stats['total_messages']); ?></h3>
                    <p><?php _e('Total Messages', 'smart-search-chatbot'); ?></p>
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
                <div class="ssgc-stat-icon">⚡</div>
                <div class="ssgc-stat-content">
                    <h3><?php echo $stats['avg_response_time']; ?>s</h3>
                    <p><?php _e('Avg Response Time', 'smart-search-chatbot'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Widget Status -->
        <div class="ssgc-card">
            <h2><?php _e('Widget Status', 'smart-search-chatbot'); ?></h2>
            <div class="ssgc-widget-status">
                <div class="ssgc-status-item">
                    <span class="ssgc-status-label"><?php _e('Enabled:', 'smart-search-chatbot'); ?></span>
                    <span class="ssgc-status-value <?php echo $widget_settings['enabled'] ? 'enabled' : 'disabled'; ?>">
                        <?php echo $widget_settings['enabled'] ? __('Yes', 'smart-search-chatbot') : __('No', 'smart-search-chatbot'); ?>
                    </span>
                </div>
                
                <?php if ($widget_settings['enabled']): ?>
                <div class="ssgc-status-item">
                    <span class="ssgc-status-label"><?php _e('Position:', 'smart-search-chatbot'); ?></span>
                    <span class="ssgc-status-value">
                        <?php echo $widget_settings['position'] === 'br' ? __('Bottom Right', 'smart-search-chatbot') : __('Bottom Left', 'smart-search-chatbot'); ?>
                    </span>
                </div>
                
                <div class="ssgc-status-item">
                    <span class="ssgc-status-label"><?php _e('Color:', 'smart-search-chatbot'); ?></span>
                    <span class="ssgc-status-value">
                        <span class="ssgc-color-preview" style="background-color: <?php echo esc_attr($widget_settings['color']); ?>"></span>
                        <?php echo esc_html($widget_settings['color']); ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <div class="ssgc-status-actions">
                    <a href="<?php echo admin_url('admin.php?page=ssgc-widget'); ?>" class="button">
                        <?php _e('Configure Widget', 'smart-search-chatbot'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Health Status -->
        <?php
        // Expect $ssgc_health from the controller
        $hOpenAI = $ssgc_health['openai'] ?? array('state' => 'not_configured', 'label' => 'Not Available', 'detail' => '');
        $hMVDB   = $ssgc_health['mvdb']   ?? array('state' => 'not_configured', 'label' => 'Not Available', 'detail' => '');

        function ssgc_badge($state, $label) {
            $clr = array(
                'ok'             => '#1a7f37',   // green
                'warn'           => '#9a6700',   // amber
                'error'          => '#d1242f',   // red
                'not_configured' => '#6e7781',   // gray
            );
            $color = $clr[$state] ?? '#6e7781';
            return '<span style="display:inline-block;padding:.2em .6em;border-radius:.5em;background:' . $color . ';color:white;font-weight:600;">' . $label . '</span>';
        }
        ?>
        <div class="ssgc-card">
            <h2><?php _e('System Health', 'smart-search-chatbot'); ?></h2>
            <table class="widefat striped">
                <tbody>
                    <tr>
                        <td style="width:180px;"><?php _e('System Status:', 'smart-search-chatbot'); ?></td>
                        <td><?php
                            $overall = ($hOpenAI['state'] === 'ok' && $hMVDB['state'] === 'ok') ? 'OK' : 'Checking';
                            echo esc_html($overall);
                        ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('AI Toolkit (OpenAI):', 'smart-search-chatbot'); ?></td>
                        <td>
                            <?php echo ssgc_badge($hOpenAI['state'], $hOpenAI['label']); ?>
                            <span style="margin-left:8px;color:#555;"><?php echo esc_html($hOpenAI['detail']); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e('Smart Search (MVDB):', 'smart-search-chatbot'); ?></td>
                        <td>
                            <?php echo ssgc_badge($hMVDB['state'], $hMVDB['label']); ?>
                            <span style="margin-left:8px;color:#555;"><?php echo esc_html($hMVDB['detail']); ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p style="margin-top:8px;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=ssgc-settings')); ?>" class="button"><?php _e('Open Settings', 'smart-search-chatbot'); ?></a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ssgc-hub&ssgc_health_clear=1')); ?>" class="button" onclick="fetch('<?php echo esc_url(admin_url('admin.php')); ?>?ssgc_health_clear=1',{credentials:'same-origin'}).then(()=>location.reload()); return false;"><?php _e('Re-check', 'smart-search-chatbot'); ?></a>
            </p>
        </div>
        
        <!-- Persona Status -->
        <div class="ssgc-card">
            <h2><?php _e('Persona Configuration', 'smart-search-chatbot'); ?></h2>
            <div class="ssgc-persona-status">
                <div class="ssgc-status-item">
                    <span class="ssgc-status-label"><?php _e('Enabled:', 'smart-search-chatbot'); ?></span>
                    <span class="ssgc-status-value <?php echo $persona_settings['enabled'] ? 'enabled' : 'disabled'; ?>">
                        <?php echo $persona_settings['enabled'] ? __('Yes', 'smart-search-chatbot') : __('No', 'smart-search-chatbot'); ?>
                    </span>
                </div>
                
                <?php if ($persona_settings['enabled']): ?>
                <div class="ssgc-status-item">
                    <span class="ssgc-status-label"><?php _e('Name:', 'smart-search-chatbot'); ?></span>
                    <span class="ssgc-status-value"><?php echo esc_html($persona_settings['name'] ?? 'AI Assistant'); ?></span>
                </div>
                
                <div class="ssgc-status-item">
                    <span class="ssgc-status-label"><?php _e('Style:', 'smart-search-chatbot'); ?></span>
                    <span class="ssgc-status-value"><?php echo esc_html($persona_settings['style'] ?? 'Friendly'); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="ssgc-status-actions">
                    <a href="<?php echo admin_url('admin.php?page=ssgc-persona'); ?>" class="button">
                        <?php _e('Configure Persona', 'smart-search-chatbot'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="ssgc-card">
            <h2><?php _e('Quick Actions', 'smart-search-chatbot'); ?></h2>
            <div class="ssgc-quick-actions">
                <a href="<?php echo admin_url('admin.php?page=ssc-chatbot-logs'); ?>" class="button button-primary">
                    <?php _e('View Chat Logs', 'smart-search-chatbot'); ?>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=ssgc-settings'); ?>" class="button">
                    <?php _e('General Settings', 'smart-search-chatbot'); ?>
                </a>
                
                <a href="<?php echo rest_url('ssgc/v1/widget-config'); ?>" class="button" target="_blank">
                    <?php _e('Test Widget Config', 'smart-search-chatbot'); ?>
                </a>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="ssgc-card ssgc-full-width">
            <h2><?php _e('Recent Activity', 'smart-search-chatbot'); ?></h2>
            <div class="ssgc-recent-activity">
                <?php
                $recent_logs = $logs->get_logs(1, 5);
                if (!empty($recent_logs['logs'])):
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Time', 'smart-search-chatbot'); ?></th>
                            <th><?php _e('Session', 'smart-search-chatbot'); ?></th>
                            <th><?php _e('User Message', 'smart-search-chatbot'); ?></th>
                            <th><?php _e('Response Time', 'smart-search-chatbot'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_logs['logs'] as $log): ?>
                        <tr>
                            <td><?php echo esc_html(mysql2date('M j, Y g:i A', $log->timestamp)); ?></td>
                            <td>
                                <code><?php echo esc_html(substr($log->session_id, 0, 8)); ?>...</code>
                            </td>
                            <td><?php echo esc_html(wp_trim_words($log->user_message, 10)); ?></td>
                            <td><?php echo esc_html($log->response_time); ?>s</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="ssgc-view-all">
                    <a href="<?php echo admin_url('admin.php?page=ssc-chatbot-logs'); ?>" class="button">
                        <?php _e('View All Logs', 'smart-search-chatbot'); ?>
                    </a>
                </div>
                <?php else: ?>
                <p><?php _e('No chat activity yet.', 'smart-search-chatbot'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.ssgc-overview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.ssgc-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    grid-column: 1 / -1;
}

.ssgc-stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
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
    background: #f0f0f1;
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

.ssgc-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.ssgc-card h2 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 18px;
}

.ssgc-full-width {
    grid-column: 1 / -1;
}

.ssgc-status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f1;
}

.ssgc-status-item:last-child {
    border-bottom: none;
}

.ssgc-status-label {
    font-weight: 500;
}

.ssgc-status-value.enabled {
    color: #00a32a;
    font-weight: 500;
}

.ssgc-status-value.disabled {
    color: #d63638;
    font-weight: 500;
}

.ssgc-color-preview {
    display: inline-block;
    width: 16px;
    height: 16px;
    border-radius: 2px;
    border: 1px solid #ccd0d4;
    vertical-align: middle;
    margin-right: 5px;
}

.ssgc-status-actions {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f1;
}

.ssgc-quick-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.ssgc-view-all {
    margin-top: 15px;
    text-align: center;
}

.ssgc-loading {
    text-align: center;
    color: #646970;
    font-style: italic;
}

.ssgc-health-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f1;
}

.ssgc-health-item:last-child {
    border-bottom: none;
}

.ssgc-health-status-ok {
    color: #00a32a;
    font-weight: 500;
}

.ssgc-health-status-warning {
    color: #dba617;
    font-weight: 500;
}

.ssgc-health-status-error {
    color: #d63638;
    font-weight: 500;
}
</style>
