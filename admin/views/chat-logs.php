<?php
/**
 * Chat Logs Admin Page
 * 
 * View and manage chat conversation logs
 */

// Prevent direct access
defined('ABSPATH') || exit;

// Get logs instance
$logs = SSGC_Logs::get_instance();

// Handle pagination and search
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$session_filter = isset($_GET['session_id']) ? sanitize_text_field($_GET['session_id']) : '';
$per_page = 20;

// Get logs data
$logs_data = $logs->get_logs($current_page, $per_page, $search, $session_filter);
$stats = $logs->get_stats();
?>

<div class="wrap ssgc-admin-page">
    <h1 class="wp-heading-inline"><?php _e('Chat Logs', 'smart-search-chatbot'); ?></h1>
    
    <!-- Stats Summary -->
    <div class="ssgc-stats-summary">
        <div class="ssgc-stat-item">
            <span class="ssgc-stat-number"><?php echo number_format($stats['total_messages']); ?></span>
            <span class="ssgc-stat-label"><?php _e('Total Messages', 'smart-search-chatbot'); ?></span>
        </div>
        <div class="ssgc-stat-item">
            <span class="ssgc-stat-number"><?php echo number_format($stats['unique_sessions']); ?></span>
            <span class="ssgc-stat-label"><?php _e('Unique Sessions', 'smart-search-chatbot'); ?></span>
        </div>
        <div class="ssgc-stat-item">
            <span class="ssgc-stat-number"><?php echo number_format($stats['messages_today']); ?></span>
            <span class="ssgc-stat-label"><?php _e('Today', 'smart-search-chatbot'); ?></span>
        </div>
        <div class="ssgc-stat-item">
            <span class="ssgc-stat-number"><?php echo $stats['avg_response_time']; ?>s</span>
            <span class="ssgc-stat-label"><?php _e('Avg Response', 'smart-search-chatbot'); ?></span>
        </div>
    </div>
    
    <!-- Search and Filters -->
    <div class="ssgc-filters">
        <form method="get" class="ssgc-search-form">
            <input type="hidden" name="page" value="ssc-chatbot-logs" />
            
            <div class="ssgc-search-box">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search messages...', 'smart-search-chatbot'); ?>" />
                <input type="text" name="session_id" value="<?php echo esc_attr($session_filter); ?>" placeholder="<?php esc_attr_e('Session ID...', 'smart-search-chatbot'); ?>" />
                <button type="submit" class="button"><?php _e('Search', 'smart-search-chatbot'); ?></button>
                
                <?php if ($search || $session_filter): ?>
                <a href="<?php echo admin_url('admin.php?page=ssc-chatbot-logs'); ?>" class="button">
                    <?php _e('Clear', 'smart-search-chatbot'); ?>
                </a>
                <?php endif; ?>
            </div>
        </form>
        
        <div class="ssgc-actions">
            <button type="button" id="export-logs" class="button">
                <?php _e('Export CSV', 'smart-search-chatbot'); ?>
            </button>
            <button type="button" id="clear-logs" class="button button-secondary" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete all logs? This cannot be undone.', 'smart-search-chatbot'); ?>')">
                <?php _e('Clear All Logs', 'smart-search-chatbot'); ?>
            </button>
        </div>
    </div>
    
    <?php if (!empty($logs_data['logs'])): ?>
    <!-- Logs Table -->
    <div class="ssgc-logs-table-container">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="column-timestamp"><?php _e('Time', 'smart-search-chatbot'); ?></th>
                    <th scope="col" class="column-session"><?php _e('Session', 'smart-search-chatbot'); ?></th>
                    <th scope="col" class="column-message"><?php _e('User Message', 'smart-search-chatbot'); ?></th>
                    <th scope="col" class="column-response"><?php _e('Bot Response', 'smart-search-chatbot'); ?></th>
                    <th scope="col" class="column-meta"><?php _e('Meta', 'smart-search-chatbot'); ?></th>
                    <th scope="col" class="column-actions"><?php _e('Actions', 'smart-search-chatbot'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs_data['logs'] as $log): ?>
                <tr>
                    <td class="column-timestamp">
                        <strong><?php echo esc_html(mysql2date('M j, Y', $log->timestamp)); ?></strong><br>
                        <span class="ssgc-time"><?php echo esc_html(mysql2date('g:i A', $log->timestamp)); ?></span>
                    </td>
                    <td class="column-session">
                        <code class="ssgc-session-id" title="<?php echo esc_attr($log->session_id); ?>">
                            <?php echo esc_html(substr($log->session_id, 0, 8)); ?>...
                        </code>
                        <?php if ($log->user_ip): ?>
                        <br><small><?php echo esc_html($log->user_ip); ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="column-message">
                        <div class="ssgc-message-content">
                            <?php echo esc_html(wp_trim_words($log->user_message, 15)); ?>
                        </div>
                        <?php if (strlen($log->user_message) > 100): ?>
                        <button type="button" class="ssgc-expand-btn" data-full-text="<?php echo esc_attr($log->user_message); ?>">
                            <?php _e('Show full', 'smart-search-chatbot'); ?>
                        </button>
                        <?php endif; ?>
                    </td>
                    <td class="column-response">
                        <div class="ssgc-message-content">
                            <?php echo esc_html(wp_trim_words($log->bot_response, 15)); ?>
                        </div>
                        <?php if (strlen($log->bot_response) > 100): ?>
                        <button type="button" class="ssgc-expand-btn" data-full-text="<?php echo esc_attr($log->bot_response); ?>">
                            <?php _e('Show full', 'smart-search-chatbot'); ?>
                        </button>
                        <?php endif; ?>
                    </td>
                    <td class="column-meta">
                        <?php if ($log->response_time): ?>
                        <div class="ssgc-meta-item">
                            <strong><?php _e('Time:', 'smart-search-chatbot'); ?></strong> <?php echo esc_html($log->response_time); ?>s
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($log->tokens_used): ?>
                        <div class="ssgc-meta-item">
                            <strong><?php _e('Tokens:', 'smart-search-chatbot'); ?></strong> <?php echo esc_html(number_format($log->tokens_used)); ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="column-actions">
                        <button type="button" class="button button-small ssgc-view-session" data-session-id="<?php echo esc_attr($log->session_id); ?>">
                            <?php _e('View Session', 'smart-search-chatbot'); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($logs_data['total_pages'] > 1): ?>
    <div class="ssgc-pagination">
        <?php
        $pagination_args = array(
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'current' => $current_page,
            'total' => $logs_data['total_pages'],
            'prev_text' => '&laquo; ' . __('Previous', 'smart-search-chatbot'),
            'next_text' => __('Next', 'smart-search-chatbot') . ' &raquo;',
        );
        
        if ($search) {
            $pagination_args['add_args'] = array('s' => $search);
        }
        
        if ($session_filter) {
            $pagination_args['add_args']['session_id'] = $session_filter;
        }
        
        echo paginate_links($pagination_args);
        ?>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <!-- No Logs Found -->
    <div class="ssgc-no-logs">
        <div class="ssgc-no-logs-icon">💬</div>
        <h3><?php _e('No chat logs found', 'smart-search-chatbot'); ?></h3>
        <?php if ($search || $session_filter): ?>
        <p><?php _e('No logs match your search criteria.', 'smart-search-chatbot'); ?></p>
        <a href="<?php echo admin_url('admin.php?page=ssc-chatbot-logs'); ?>" class="button">
            <?php _e('View All Logs', 'smart-search-chatbot'); ?>
        </a>
        <?php else: ?>
        <p><?php _e('Chat conversations will appear here once users start interacting with your chatbot.', 'smart-search-chatbot'); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Session Details Modal -->
<div id="ssgc-session-modal" class="ssgc-modal" style="display: none;">
    <div class="ssgc-modal-content">
        <div class="ssgc-modal-header">
            <h2><?php _e('Session Details', 'smart-search-chatbot'); ?></h2>
            <button type="button" class="ssgc-modal-close">&times;</button>
        </div>
        <div class="ssgc-modal-body">
            <div id="ssgc-session-loading"><?php _e('Loading session details...', 'smart-search-chatbot'); ?></div>
            <div id="ssgc-session-content" style="display: none;"></div>
        </div>
    </div>
</div>

<!-- Message Details Modal -->
<div id="ssgc-message-modal" class="ssgc-modal" style="display: none;">
    <div class="ssgc-modal-content">
        <div class="ssgc-modal-header">
            <h2><?php _e('Full Message', 'smart-search-chatbot'); ?></h2>
            <button type="button" class="ssgc-modal-close">&times;</button>
        </div>
        <div class="ssgc-modal-body">
            <div id="ssgc-message-content"></div>
        </div>
    </div>
</div>

<style>
.ssgc-stats-summary {
    display: flex;
    gap: 20px;
    margin: 20px 0;
    flex-wrap: wrap;
}

.ssgc-stat-item {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 15px;
    text-align: center;
    min-width: 120px;
}

.ssgc-stat-number {
    display: block;
    font-size: 24px;
    font-weight: 600;
    color: #1d2327;
    line-height: 1.2;
}

.ssgc-stat-label {
    display: block;
    font-size: 12px;
    color: #646970;
    margin-top: 5px;
}

.ssgc-filters {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 20px 0;
    flex-wrap: wrap;
    gap: 15px;
}

.ssgc-search-form {
    flex: 1;
}

.ssgc-search-box {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.ssgc-search-box input[type="search"],
.ssgc-search-box input[type="text"] {
    min-width: 200px;
}

.ssgc-actions {
    display: flex;
    gap: 10px;
}

.ssgc-logs-table-container {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    overflow-x: auto;
}

.column-timestamp {
    width: 120px;
}

.column-session {
    width: 100px;
}

.column-message,
.column-response {
    width: 30%;
}

.column-meta {
    width: 100px;
}

.column-actions {
    width: 120px;
}

.ssgc-time {
    color: #646970;
    font-size: 12px;
}

.ssgc-session-id {
    font-family: monospace;
    font-size: 11px;
    background: #f6f7f7;
    padding: 2px 4px;
    border-radius: 2px;
    cursor: help;
}

.ssgc-message-content {
    line-height: 1.4;
    word-break: break-word;
}

.ssgc-expand-btn {
    background: none;
    border: none;
    color: #0073aa;
    cursor: pointer;
    font-size: 11px;
    text-decoration: underline;
    padding: 0;
    margin-top: 5px;
}

.ssgc-expand-btn:hover {
    color: #005a87;
}

.ssgc-meta-item {
    font-size: 11px;
    margin-bottom: 3px;
}

.ssgc-meta-item strong {
    color: #646970;
}

.ssgc-pagination {
    margin: 20px 0;
    text-align: center;
}

.ssgc-no-logs {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.ssgc-no-logs-icon {
    font-size: 48px;
    margin-bottom: 20px;
}

.ssgc-no-logs h3 {
    margin-bottom: 10px;
    color: #1d2327;
}

.ssgc-no-logs p {
    color: #646970;
    margin-bottom: 20px;
}

/* Modal Styles */
.ssgc-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ssgc-modal-content {
    background: #fff;
    border-radius: 4px;
    max-width: 800px;
    width: 90%;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.ssgc-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #c3c4c7;
}

.ssgc-modal-header h2 {
    margin: 0;
    font-size: 18px;
}

.ssgc-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #646970;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ssgc-modal-close:hover {
    color: #d63638;
}

.ssgc-modal-body {
    padding: 20px;
    max-height: 60vh;
    overflow-y: auto;
}

.ssgc-session-message {
    margin-bottom: 20px;
    padding: 15px;
    border-radius: 4px;
    border-left: 4px solid #0073aa;
}

.ssgc-session-message.user {
    background: #f0f6fc;
    border-left-color: #0073aa;
}

.ssgc-session-message.bot {
    background: #f6f7f7;
    border-left-color: #646970;
}

.ssgc-session-message-meta {
    font-size: 12px;
    color: #646970;
    margin-bottom: 8px;
}

.ssgc-session-message-content {
    line-height: 1.5;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Export logs
    $('#export-logs').on('click', function() {
        window.location.href = ajaxurl + '?action=ssgc_export_logs&nonce=' + ssgc_admin.nonce;
    });
    
    // Clear logs
    $('#clear-logs').on('click', function() {
        if (!confirm('<?php esc_js(_e('Are you sure you want to delete all logs? This cannot be undone.', 'smart-search-chatbot')); ?>')) {
            return;
        }
        
        $.post(ajaxurl, {
            action: 'ssgc_clear_logs',
            nonce: ssgc_admin.nonce
        })
        .done(function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('<?php esc_js(_e('Error clearing logs. Please try again.', 'smart-search-chatbot')); ?>');
            }
        })
        .fail(function() {
            alert('<?php esc_js(_e('Error clearing logs. Please try again.', 'smart-search-chatbot')); ?>');
        });
    });
    
    // Expand message
    $('.ssgc-expand-btn').on('click', function() {
        var fullText = $(this).data('full-text');
        $('#ssgc-message-content').text(fullText);
        $('#ssgc-message-modal').show();
    });
    
    // View session
    $('.ssgc-view-session').on('click', function() {
        var sessionId = $(this).data('session-id');
        $('#ssgc-session-modal').show();
        $('#ssgc-session-loading').show();
        $('#ssgc-session-content').hide();
        
        // Load session details (placeholder - would need backend implementation)
        setTimeout(function() {
            $('#ssgc-session-loading').hide();
            $('#ssgc-session-content').html('<p><?php esc_js(_e('Session details would be loaded here. This requires additional backend implementation.', 'smart-search-chatbot')); ?></p>').show();
        }, 1000);
    });
    
    // Close modals
    $('.ssgc-modal-close, .ssgc-modal').on('click', function(e) {
        if (e.target === this) {
            $('.ssgc-modal').hide();
        }
    });
    
    // Prevent modal content clicks from closing modal
    $('.ssgc-modal-content').on('click', function(e) {
        e.stopPropagation();
    });
});
</script>
