<?php
/**
 * Widget Management Class
 * 
 * Handles widget settings, REST endpoints, and frontend integration
 */

// Prevent direct access
defined('ABSPATH') || exit;

class SSGC_Widget {
    
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
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('wp_footer', array($this, 'maybe_inject_widget'));
        add_action('admin_init', array($this, 'handle_widget_settings'));
        add_action('rest_api_init', array($this, 'setup_cors'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        // Widget configuration endpoint
        register_rest_route('ssgc/v1', '/widget-config', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_widget_config'),
            'permission_callback' => '__return_true',
        ));
        
        // Health check endpoint
        register_rest_route('ssgc/v1', '/health', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_health_status'),
            'permission_callback' => '__return_true',
        ));
    }
    
    /**
     * Setup CORS headers for widget endpoints
     */
    public function setup_cors() {
        remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
        add_filter('rest_pre_serve_request', array($this, 'handle_cors'), 11, 4);
    }
    
    /**
     * Handle CORS for widget endpoints
     */
    public function handle_cors($served, $result, $request, $server) {
        $route = $request->get_route();
        
        // Only apply CORS to our widget endpoints
        if (strpos($route, '/ssgc/v1/widget-config') !== false || 
            strpos($route, '/ssgc/v1/chat') !== false ||
            strpos($route, '/ssgc/v1/health') !== false) {
            
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            $site_url = get_site_url();
            
            // Allow same origin and configured origins
            $allowed_origins = array($site_url);
            $allowed_origins = apply_filters('ssgc_widget_allowed_origins', $allowed_origins);
            
            if (in_array($origin, $allowed_origins, true)) {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Vary: Origin');
                header('Access-Control-Allow-Methods: GET,POST,OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization');
            }
            
            // Set caching headers for config endpoint
            if (strpos($route, '/widget-config') !== false) {
                header('Cache-Control: public, max-age=60');
            }
            
            // Set frame options
            header('X-Frame-Options: SAMEORIGIN');
            header('Content-Security-Policy: frame-ancestors \'self\'');
        }
        
        return $served;
    }
    
    /**
     * Get widget configuration
     */
    public function get_widget_config($request) {
        $settings = get_option('ssgc_widget_settings', array());
        $persona_settings = get_option('ssgc_persona_settings', array());
        
        $config = array(
            'site_name' => get_bloginfo('name'),
            'brand' => array(
                'color' => $settings['color'] ?? '#007cba'
            ),
            'position' => $settings['position'] ?? 'br',
            'api_base' => rest_url('ssgc/v1'),
            'persona_preview' => true,
            'features' => array(
                'citations' => true
            ),
            'greet' => array(
                'enabled' => $settings['greet']['enabled'] ?? false,
                'delay_ms' => $settings['greet']['delay_ms'] ?? 8000,
                'message' => $settings['greet']['message'] ?? 'Need help?'
            ),
            'title' => 'Smart Search Chat',
            'welcomeMessage' => 'Ask me anything and I\'ll help you find the information you need.',
            'placeholder' => 'Type your message...'
        );
        
        return rest_ensure_response($config);
    }
    
    /**
     * Get health status
     */
    public function get_health_status($request) {
        $health = array(
            'ok' => true,
            'has_ai_toolkit' => $this->has_ai_toolkit(),
            'has_smart_search' => $this->has_smart_search(),
            'timestamp' => current_time('mysql')
        );
        
        return rest_ensure_response($health);
    }
    
    /**
     * Check if AI Toolkit is available
     */
    private function has_ai_toolkit() {
        return class_exists('WPEngine_AI_Toolkit') || function_exists('wpengine_ai_toolkit_init');
    }
    
    /**
     * Check if Smart Search is available
     */
    private function has_smart_search() {
        return class_exists('WPEngine_Smart_Search') || function_exists('wpengine_smart_search_init');
    }
    
    /**
     * Maybe inject widget script into frontend - NEVER in admin
     */
    public function maybe_inject_widget() {
        self::maybe_enqueue();
    }
    
    /**
     * Properly enqueue widget assets with localized config
     */
    public static function maybe_enqueue() {
        if (is_admin()) return;
        if (!self::is_enabled_for_request()) return;

        // Enqueue CSS/JS
        wp_enqueue_style(
            'ssgc-widget',
            plugins_url('../assets/css/widget.css', __FILE__),
            [],
            SSGC_VERSION
        );
        wp_enqueue_script(
            'ssgc-widget',
            plugins_url('../assets/js/widget-loader.js', __FILE__),
            [],
            SSGC_VERSION,
            true
        );

        // Use static plugin file for iframe shell (ABSOLUTE URL)
        $iframe_src = plugins_url('../dist/ssgc-widget.html', __FILE__);

        $settings = SSGC_Widget::get_settings(); // now static
        wp_localize_script('ssgc-widget', 'SSGC_WIDGET', [
            'iframeSrc'     => esc_url_raw($iframe_src),
            'themeColor'    => $settings['color'] ?? '#007cba',
            'position'      => $settings['position'] ?? 'br',
            'zIndex'        => (int)($settings['z_index'] ?? 999999),
            'proactive'     => $settings['greet'] ?? ['enabled'=>false,'delay_ms'=>8000,'message'=>'Need help?'],
            'sessionPrefix' => 'ssgc:',
            'configUrl'     => esc_url_raw(rest_url('ssgc/v1/widget-config')),
            'chatUrl'       => esc_url_raw(rest_url('ssgc/v1/chat')),
        ]);
    }
    
    /**
     * Check if widget should be enabled for current request
     */
    private static function is_enabled_for_request() {
        $settings = SSGC_Widget::get_settings();
        return ($settings['enabled'] ?? false);
    }
    
    /**
     * Handle widget settings form submission
     */
    public function handle_widget_settings() {
        if (!isset($_POST['ssgc_widget_settings_nonce']) || 
            !wp_verify_nonce($_POST['ssgc_widget_settings_nonce'], 'ssgc_widget_settings')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $settings = array(
            'enabled' => isset($_POST['widget_enabled']),
            'position' => sanitize_text_field($_POST['widget_position'] ?? 'br'),
            'color' => sanitize_hex_color($_POST['widget_color'] ?? '#007cba'),
            'z_index' => intval($_POST['widget_z_index'] ?? 999999),
            'greet' => array(
                'enabled' => isset($_POST['greet_enabled']),
                'delay_ms' => intval($_POST['greet_delay'] ?? 8000),
                'message' => sanitize_text_field($_POST['greet_message'] ?? 'Need help?')
            )
        );
        
        update_option('ssgc_widget_settings', $settings);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 __('Widget settings saved successfully.', 'smart-search-chatbot') . 
                 '</p></div>';
        });
    }
    
    /**
     * Get current widget settings (static method)
     */
    public static function get_settings() {
        $defaults = [
            'enabled'  => false,
            'position' => 'br',
            'color'    => '#007cba',
            'z_index'  => 999999,
            'greet'    => ['enabled'=>false, 'delay_ms'=>8000, 'message'=>'Need help?'],
        ];
        $saved = get_option('ssgc_widget_settings', []);
        return is_array($saved) ? array_replace($defaults, $saved) : $defaults;
    }
}
