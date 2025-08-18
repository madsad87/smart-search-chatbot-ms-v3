<?php
/**
 * Plugin Name: Smart Search Chatbot
 * Plugin URI: https://github.com/madsad87/smart-search-chatbot-ms
 * Description: AI-powered chatbot with Smart Search integration, chat logs, personas, and site-agnostic widget support.
 * Version: 2.0.0
 * Author: Madison Sadler
 * License: GPL v2 or later
 * Text Domain: smart-search-chatbot
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
defined('ABSPATH') || exit;

// Define plugin constants
define('SSGC_PLUGIN_FILE', __FILE__);
define('SSGC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SSGC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SSGC_VERSION', '2.0.0');

/**
 * Main Smart Search Chatbot class
 */
class SmartSearchChatbot {
    
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
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('smart-search-chatbot', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        $this->includes();
        
        // Initialize components
        $this->init_components();
        
        // Add legacy settings redirect (tightly scoped)
        $this->add_legacy_redirect();
        
        // Add shortcode
        add_shortcode('smart_search_chat', array($this, 'shortcode_handler'));
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Core classes
        require_once SSGC_PLUGIN_DIR . 'includes/class-ssgc-admin-menu.php';
        require_once SSGC_PLUGIN_DIR . 'includes/class-ssgc-persona.php';
        require_once SSGC_PLUGIN_DIR . 'includes/class-ssgc-widget.php';
        require_once SSGC_PLUGIN_DIR . 'includes/class-ssgc-chat.php';
        require_once SSGC_PLUGIN_DIR . 'includes/class-ssgc-logs.php';
        require_once SSGC_PLUGIN_DIR . 'includes/class-ssgc-retrieval.php';
        
        // Admin pages are loaded only when needed in menu callbacks
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize admin menu
        if (is_admin()) {
            SSGC_Admin_Menu::get_instance();
        }
        
        // Initialize persona management
        SSGC_Persona::get_instance();
        
        // Initialize widget functionality
        SSGC_Widget::get_instance();
        
        // Initialize chat handler
        SSGC_Chat::get_instance();
        
        // Initialize logs
        SSGC_Logs::get_instance();
    }
    
    /**
     * Shortcode handler
     */
    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'height' => '500px',
            'width' => '100%',
            'placeholder' => 'Ask me anything...',
        ), $atts, 'smart_search_chat');
        
        // Enqueue scripts and styles
        wp_enqueue_script('ssgc-chatbot', SSGC_PLUGIN_URL . 'assets/js/chatbot.js', array('jquery'), SSGC_VERSION, true);
        wp_enqueue_style('ssgc-chatbot', SSGC_PLUGIN_URL . 'assets/css/chatbot.css', array(), SSGC_VERSION);
        
        // Localize script
        wp_localize_script('ssgc-chatbot', 'ssgc_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ssgc_chat_nonce'),
            'rest_url' => rest_url('ssgc/v1/'),
        ));
        
        ob_start();
        ?>
        <div class="ssgc-chatbot-container" style="height: <?php echo esc_attr($atts['height']); ?>; width: <?php echo esc_attr($atts['width']); ?>;">
            <div class="ssgc-messages" id="ssgc-messages"></div>
            <div class="ssgc-input-container">
                <input type="text" id="ssgc-input" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" />
                <button id="ssgc-send" type="button"><?php _e('Send', 'smart-search-chatbot'); ?></button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Chat logs table
        $table_name = $wpdb->prefix . 'ssgc_chat_logs';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            user_message text NOT NULL,
            bot_response text NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            user_ip varchar(45),
            user_agent text,
            response_time float,
            tokens_used int,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        // Default persona settings
        if (!get_option('ssgc_persona_settings')) {
            update_option('ssgc_persona_settings', array(
                'instructions' => 'You are a helpful AI assistant.',
                'style' => 'friendly',
                'enabled' => true,
            ));
        }
        
        // Default widget settings
        if (!get_option('ssgc_widget_settings')) {
            update_option('ssgc_widget_settings', array(
                'enabled' => false,
                'position' => 'br',
                'color' => '#007cba',
                'z_index' => 999999,
                'greet' => array(
                    'enabled' => false,
                    'delay_ms' => 8000,
                    'message' => 'Need help?'
                )
            ));
        }
        
        // Default log settings
        if (!get_option('ssgc_log_settings')) {
            update_option('ssgc_log_settings', array(
                'enabled' => true,
                'retention_days' => 30,
                'redact_pii' => true,
            ));
        }
    }
    
    /**
     * Add legacy settings redirect (tightly scoped)
     */
    private function add_legacy_redirect() {
        add_action('admin_init', function () {
            // Only redirect from options-general.php with specific page params
            if (empty($_GET['page'])) return;
            if (!isset($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'], 'options-general.php') === false) return;

            $map = [
                'ssgc-settings'           => 'ssgc-settings',
                'ssgc-persona'            => 'ssgc-persona',
                'ssc-chatbot-logs'        => 'ssc-chatbot-logs',
                'ssc-chat-logs-settings'  => 'ssc-chat-logs-settings',
            ];
            $page = sanitize_text_field(wp_unslash($_GET['page']));
            if (isset($map[$page])) {
                wp_safe_redirect(admin_url('admin.php?page=' . $map[$page]));
                exit;
            }
        }, 1);
    }
}

// Initialize the plugin
SmartSearchChatbot::get_instance();
