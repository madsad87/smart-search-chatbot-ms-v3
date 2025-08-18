<?php
/**
 * Admin Menu Management Class
 * 
 * Handles all admin menu pages for Smart Search Chatbot
 */

// Prevent direct access
defined('ABSPATH') || exit;

/**
 * Helper function to get our admin screen IDs
 */
function ssgc_admin_screen_ids(): array {
    return [
        'toplevel_page_ssgc-hub',
        'smart-search-chatbot_page_ssc-chatbot-logs',
        'smart-search-chatbot_page_ssc-chat-logs-settings',
        'smart-search-chatbot_page_ssgc-persona',
        'smart-search-chatbot_page_ssgc-settings',
        'smart-search-chatbot_page_ssgc-widget',
    ];
}

class SSGC_Admin_Menu {
    
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Smart Search Chatbot', 'smart-search-chatbot'),
            __('Smart Search Chatbot', 'smart-search-chatbot'),
            'manage_options',
            'ssgc-hub',
            array($this, 'overview_page'),
            'dashicons-format-chat',
            30
        );
        
        // Overview (same as main page)
        add_submenu_page(
            'ssgc-hub',
            __('Overview', 'smart-search-chatbot'),
            __('Overview', 'smart-search-chatbot'),
            'manage_options',
            'ssgc-hub',
            array($this, 'overview_page')
        );
        
        // Chat Logs
        add_submenu_page(
            'ssgc-hub',
            __('Chat Logs', 'smart-search-chatbot'),
            __('Chat Logs', 'smart-search-chatbot'),
            'manage_options',
            'ssc-chatbot-logs',
            array($this, 'chat_logs_page')
        );
        
        // Log Settings
        add_submenu_page(
            'ssgc-hub',
            __('Log Settings', 'smart-search-chatbot'),
            __('Log Settings', 'smart-search-chatbot'),
            'manage_options',
            'ssc-chat-logs-settings',
            array($this, 'log_settings_page')
        );
        
        // Persona
        add_submenu_page(
            'ssgc-hub',
            __('Persona', 'smart-search-chatbot'),
            __('Persona', 'smart-search-chatbot'),
            'manage_options',
            'ssgc-persona',
            array($this, 'persona_page')
        );
        
        // Widget Settings
        add_submenu_page(
            'ssgc-hub',
            __('Widget', 'smart-search-chatbot'),
            __('Widget', 'smart-search-chatbot'),
            'manage_options',
            'ssgc-widget',
            array($this, 'widget_page')
        );
        
        // General Settings
        add_submenu_page(
            'ssgc-hub',
            __('Settings', 'smart-search-chatbot'),
            __('Settings', 'smart-search-chatbot'),
            'manage_options',
            'ssgc-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles - ONLY on our plugin screens
     */
    public function enqueue_admin_scripts($hook) {
        // Strict screen ID checking - only load on our screens
        if (!function_exists('get_current_screen')) return;
        $screen = get_current_screen();
        if (!$screen) return;
        if (!in_array($screen->id, ssgc_admin_screen_ids(), true)) return;

        // Enqueue ONLY on our screens
        wp_enqueue_style('ssgc-admin', SSGC_PLUGIN_URL . 'assets/css/admin.css', array(), SSGC_VERSION);
        wp_enqueue_script('ssgc-admin', SSGC_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), SSGC_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('ssgc-admin', 'ssgc_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ssgc_admin_nonce'),
            'rest_url' => rest_url('ssgc/v1/'),
        ));
    }
    
    /**
     * Overview page
     */
    public function overview_page() {
        self::render_overview();
    }
    
    /**
     * Chat logs page
     */
    public function chat_logs_page() {
        self::render_chat_logs();
    }
    
    /**
     * Log settings page
     */
    public function log_settings_page() {
        self::render_log_settings();
    }
    
    /**
     * Persona page
     */
    public function persona_page() {
        self::render_persona();
    }
    
    /**
     * Widget settings page
     */
    public function widget_page() {
        self::render_widget_settings();
    }
    
    /**
     * General settings page
     */
    public function settings_page() {
        self::render_settings();
    }
    
    /**
     * Guarded view renderers - only load views when needed
     */
    public static function render_overview() {
        $view = SSGC_PLUGIN_DIR . 'admin/views/overview.php';
        if (file_exists($view)) {
            require $view;
        } else {
            echo '<div class="wrap ssgc-admin-page"><p>Overview view missing.</p></div>';
        }
    }
    
    public static function render_chat_logs() {
        $view = SSGC_PLUGIN_DIR . 'admin/views/chat-logs.php';
        if (file_exists($view)) {
            require $view;
        } else {
            echo '<div class="wrap ssgc-admin-page"><p>Chat Logs view missing.</p></div>';
        }
    }
    
    public static function render_log_settings() {
        $view = SSGC_PLUGIN_DIR . 'admin/views/log-settings.php';
        if (file_exists($view)) {
            require $view;
        } else {
            echo '<div class="wrap ssgc-admin-page"><p>Log Settings view missing.</p></div>';
        }
    }
    
    public static function render_persona() {
        $view = SSGC_PLUGIN_DIR . 'admin/views/persona.php';
        if (file_exists($view)) {
            require $view;
        } else {
            echo '<div class="wrap ssgc-admin-page"><p>Persona view missing.</p></div>';
        }
    }
    
    public static function render_settings() {
        $view = SSGC_PLUGIN_DIR . 'admin/views/settings.php';
        if (file_exists($view)) {
            require $view;
        } else {
            echo '<div class="wrap ssgc-admin-page"><p>Settings view missing.</p></div>';
        }
    }
    
    public static function render_widget_settings() {
        $view = SSGC_PLUGIN_DIR . 'admin/views/widget.php';
        if (file_exists($view)) {
            require $view;
        } else {
            echo '<div class="wrap ssgc-admin-page"><p>Widget settings view missing.</p></div>';
        }
    }
}
