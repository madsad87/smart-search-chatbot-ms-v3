<?php
/**
 * Logs Management Class
 * 
 * Handles chat logs, log settings, and log cleanup
 */

// Prevent direct access
defined('ABSPATH') || exit;

class SSGC_Logs {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_init', array($this, 'handle_log_settings'));
        add_action('wp_ajax_ssgc_export_logs', array($this, 'export_logs'));
        add_action('wp_ajax_ssgc_clear_logs', array($this, 'clear_logs'));
        add_action('ssgc_daily_cleanup', array($this, 'cleanup_old_logs'));
        
        // Schedule daily cleanup if not already scheduled
        if (!wp_next_scheduled('ssgc_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'ssgc_daily_cleanup');
        }
    }
    
    /**
     * Handle log settings form submission
     */
    public function handle_log_settings() {
        if (!isset($_POST['ssgc_log_settings_nonce']) || 
            !wp_verify_nonce($_POST['ssgc_log_settings_nonce'], 'ssgc_log_settings')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $settings = array(
            'enabled' => isset($_POST['logs_enabled']),
            'retention_days' => intval($_POST['retention_days'] ?? 30),
            'redact_pii' => isset($_POST['redact_pii']),
            'log_ip' => isset($_POST['log_ip']),
            'log_user_agent' => isset($_POST['log_user_agent']),
        );
        
        // Validate retention days
        if ($settings['retention_days'] < 1) {
            $settings['retention_days'] = 30;
        }
        
        update_option('ssgc_log_settings', $settings);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 __('Log settings saved successfully.', 'smart-search-chatbot') . 
                 '</p></div>';
        });
    }
    
    /**
     * Get chat logs with pagination
     */
    public function get_logs($page = 1, $per_page = 20, $search = '', $session_id = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ssgc_chat_logs';
        $offset = ($page - 1) * $per_page;
        
        // Build WHERE clause
        $where_conditions = array();
        $where_values = array();
        
        if (!empty($search)) {
            $where_conditions[] = "(user_message LIKE %s OR bot_response LIKE %s)";
            $where_values[] = '%' . $wpdb->esc_like($search) . '%';
            $where_values[] = '%' . $wpdb->esc_like($search) . '%';
        }
        
        if (!empty($session_id)) {
            $where_conditions[] = "session_id = %s";
            $where_values[] = $session_id;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM $table_name $where_clause";
        if (!empty($where_values)) {
            $count_query = $wpdb->prepare($count_query, $where_values);
        }
        $total_items = $wpdb->get_var($count_query);
        
        // Get logs
        $query = "SELECT * FROM $table_name $where_clause ORDER BY timestamp DESC LIMIT %d OFFSET %d";
        $query_values = array_merge($where_values, array($per_page, $offset));
        $logs = $wpdb->get_results($wpdb->prepare($query, $query_values));
        
        return array(
            'logs' => $logs,
            'total_items' => $total_items,
            'total_pages' => ceil($total_items / $per_page),
            'current_page' => $page,
            'per_page' => $per_page
        );
    }
    
    /**
     * Get log statistics
     */
    public function get_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ssgc_chat_logs';
        
        // Total conversations
        $total_messages = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // Unique sessions
        $unique_sessions = $wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM $table_name");
        
        // Messages today
        $today = current_time('Y-m-d');
        $messages_today = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE DATE(timestamp) = %s",
            $today
        ));
        
        // Average response time
        $avg_response_time = $wpdb->get_var("SELECT AVG(response_time) FROM $table_name WHERE response_time > 0");
        
        // Total tokens used
        $total_tokens = $wpdb->get_var("SELECT SUM(tokens_used) FROM $table_name WHERE tokens_used > 0");
        
        return array(
            'total_messages' => intval($total_messages),
            'unique_sessions' => intval($unique_sessions),
            'messages_today' => intval($messages_today),
            'avg_response_time' => round(floatval($avg_response_time), 3),
            'total_tokens' => intval($total_tokens)
        );
    }
    
    /**
     * Export logs to CSV
     */
    public function export_logs() {
        check_ajax_referer('ssgc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'ssgc_chat_logs';
        
        // Get all logs
        $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC");
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="ssgc-chat-logs-' . date('Y-m-d') . '.csv"');
        
        // Create CSV output
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            'ID',
            'Session ID',
            'User Message',
            'Bot Response',
            'Timestamp',
            'User IP',
            'User Agent',
            'Response Time',
            'Tokens Used'
        ));
        
        // CSV data
        foreach ($logs as $log) {
            fputcsv($output, array(
                $log->id,
                $log->session_id,
                $log->user_message,
                $log->bot_response,
                $log->timestamp,
                $log->user_ip,
                $log->user_agent,
                $log->response_time,
                $log->tokens_used
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Clear all logs
     */
    public function clear_logs() {
        check_ajax_referer('ssgc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'ssgc_chat_logs';
        
        $deleted = $wpdb->query("DELETE FROM $table_name");
        
        wp_send_json_success(array(
            'message' => sprintf(__('Successfully deleted %d log entries.', 'smart-search-chatbot'), $deleted)
        ));
    }
    
    /**
     * Cleanup old logs based on retention settings
     */
    public function cleanup_old_logs() {
        $settings = get_option('ssgc_log_settings', array());
        $retention_days = $settings['retention_days'] ?? 30;
        
        if ($retention_days <= 0) {
            return; // Don't delete if retention is 0 or negative
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'ssgc_chat_logs';
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE timestamp < %s",
            $cutoff_date
        ));
        
        if ($deleted > 0) {
            error_log("SSGC: Cleaned up {$deleted} old log entries older than {$retention_days} days");
        }
    }
    
    /**
     * Get current log settings
     */
    public function get_settings() {
        return get_option('ssgc_log_settings', array(
            'enabled' => true,
            'retention_days' => 30,
            'redact_pii' => true,
            'log_ip' => true,
            'log_user_agent' => true
        ));
    }
    
    /**
     * Get session details
     */
    public function get_session_details($session_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ssgc_chat_logs';
        
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE session_id = %s ORDER BY timestamp ASC",
            $session_id
        ));
        
        if (empty($logs)) {
            return null;
        }
        
        $first_log = $logs[0];
        $last_log = end($logs);
        
        return array(
            'session_id' => $session_id,
            'message_count' => count($logs),
            'first_message' => $first_log->timestamp,
            'last_message' => $last_log->timestamp,
            'user_ip' => $first_log->user_ip,
            'user_agent' => $first_log->user_agent,
            'total_tokens' => array_sum(array_column($logs, 'tokens_used')),
            'avg_response_time' => array_sum(array_column($logs, 'response_time')) / count($logs),
            'messages' => $logs
        );
    }
}
