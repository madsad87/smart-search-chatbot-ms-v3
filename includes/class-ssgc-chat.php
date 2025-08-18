<?php
/**
 * Chat Handler Class
 * 
 * Handles chat requests, Smart Search integration, and LLM communication
 */

// Prevent direct access
defined('ABSPATH') || exit;

class SSGC_Chat {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Rate limiting cache
     */
    private $rate_limit_cache = array();
    
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
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        // Chat endpoint
        register_rest_route('ssgc/v1', '/chat', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_chat_request'),
            'permission_callback' => '__return_true',
            'args' => array(
                'session_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'validate_callback' => array($this, 'validate_session_id'),
                ),
                'prompt' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
            ),
        ));
    }
    
    /**
     * Validate session ID format
     */
    public function validate_session_id($value) {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value);
    }
    
    /**
     * Handle chat request
     */
    public function handle_chat_request($request) {
        $start_time = microtime(true);
        
        // Get parameters
        $session_id = $request->get_param('session_id');
        $prompt = $request->get_param('prompt');
        
        // Rate limiting
        if (!$this->check_rate_limit($session_id)) {
            return new WP_Error('rate_limit', 'Too many requests. Please wait before sending another message.', array('status' => 429));
        }
        
        // Get log settings for PII redaction
        $log_settings = get_option('ssgc_log_settings', array());
        if ($log_settings['redact_pii'] ?? true) {
            $prompt = $this->redact_pii($prompt);
        }
        
        try {
            // Get persona settings
            $persona_settings = get_option('ssgc_persona_settings', array());
            
            // Build messages with persona
            $messages = $this->build_messages($prompt, $persona_settings);
            
            // Get context from Smart Search if available
            $context = $this->get_smart_search_context($prompt);
            
            // Add context to messages if available
            if (!empty($context['chunks'])) {
                $context_text = $this->format_context($context['chunks']);
                $messages[0]['content'] .= "\n\nContext from site content:\n" . $context_text;
            }
            
            // Call LLM
            $response = $this->call_llm($messages);
            
            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }
            
            // Calculate response time
            $response_time = microtime(true) - $start_time;
            
            // Log the conversation
            $this->log_conversation($session_id, $prompt, $response['text'], $response_time, $response['usage']['tokens'] ?? 0);
            
            // Prepare response with citations
            $chat_response = array(
                'text' => $response['text'],
                'citations' => $context['citations'] ?? array(),
                'usage' => $response['usage'] ?? array(),
                'response_time' => round($response_time, 3)
            );
            
            return rest_ensure_response($chat_response);
            
        } catch (Exception $e) {
            error_log('SSGC Chat Error: ' . $e->getMessage());
            
            return new WP_Error('chat_error', 'Sorry, I encountered an error processing your request. Please try again.', array('status' => 500));
        }
    }
    
    /**
     * Check rate limiting
     */
    private function check_rate_limit($session_id) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $key = $ip . '_' . $session_id;
        $now = time();
        
        // Clean old entries
        foreach ($this->rate_limit_cache as $cache_key => $timestamp) {
            if ($now - $timestamp > 60) { // 1 minute window
                unset($this->rate_limit_cache[$cache_key]);
            }
        }
        
        // Count recent requests
        $recent_requests = 0;
        foreach ($this->rate_limit_cache as $cache_key => $timestamp) {
            if (strpos($cache_key, $key) === 0 && $now - $timestamp <= 60) {
                $recent_requests++;
            }
        }
        
        // Allow 10 requests per minute
        if ($recent_requests >= 10) {
            return false;
        }
        
        // Record this request
        $this->rate_limit_cache[$key . '_' . $now] = $now;
        
        return true;
    }
    
    /**
     * Build messages array with persona
     */
    private function build_messages($prompt, $persona_settings) {
        $system_message = $persona_settings['instructions'] ?? 'You are a helpful AI assistant.';
        
        // Add style guidance
        if (!empty($persona_settings['style'])) {
            $system_message .= "\n\nStyle: " . $persona_settings['style'];
        }
        
        $messages = array(
            array(
                'role' => 'system',
                'content' => $system_message
            ),
            array(
                'role' => 'user',
                'content' => $prompt
            )
        );
        
        return apply_filters('ssgc_build_messages', $messages, $prompt, $persona_settings);
    }
    
    /**
     * Get context from Smart Search
     */
    private function get_smart_search_context($query) {
        $context = array(
            'chunks' => array(),
            'citations' => array()
        );
        
        // Check if Smart Search is available
        if (!$this->has_smart_search()) {
            return $context;
        }
        
        try {
            $results = $this->ssgc_similarity_search($query, 5);
            
            foreach ($results as $result) {
                $context['chunks'][] = array(
                    'title' => $result['title'],
                    'content' => $result['snippet'],
                    'url' => $result['url'],
                    'score' => $result['score']
                );
                
                $context['citations'][] = array(
                    'title' => $result['title'],
                    'url' => $result['url']
                );
            }
            
        } catch (Exception $e) {
            error_log('SSGC Smart Search Error: ' . $e->getMessage());
        }
        
        return $context;
    }
    
    /**
     * Smart Search similarity search helper
     */
    private function ssgc_similarity_search($query, $limit = 5) {
        // This is a placeholder implementation
        // In a real implementation, you would integrate with WP Engine's Smart Search API
        
        if (function_exists('wpengine_smart_search_query')) {
            // Use WP Engine Smart Search if available
            $results = wpengine_smart_search_query($query, array('limit' => $limit));
            return $this->normalize_search_results($results);
        }
        
        // Fallback: return empty results
        return array();
    }
    
    /**
     * Normalize search results to consistent format
     */
    private function normalize_search_results($results) {
        $normalized = array();
        
        foreach ($results as $result) {
            $normalized[] = array(
                'title' => $result['title'] ?? 'Untitled',
                'url' => $result['url'] ?? '#',
                'snippet' => $result['excerpt'] ?? $result['content'] ?? '',
                'score' => $result['score'] ?? 0
            );
        }
        
        return $normalized;
    }
    
    /**
     * Format context for LLM
     */
    private function format_context($chunks) {
        $context_text = '';
        
        foreach ($chunks as $chunk) {
            $context_text .= "Title: " . $chunk['title'] . "\n";
            $context_text .= "Content: " . $chunk['content'] . "\n";
            $context_text .= "URL: " . $chunk['url'] . "\n\n";
        }
        
        return $context_text;
    }
    
    /**
     * Call LLM (placeholder implementation)
     */
    private function call_llm($messages) {
        // This is a placeholder implementation
        // In a real implementation, you would integrate with OpenAI, Gemini, or other LLM APIs
        
        // For now, return a mock response
        $response = array(
            'text' => 'I\'m a placeholder response. To enable full functionality, please configure your LLM API settings.',
            'usage' => array(
                'tokens' => 50
            )
        );
        
        // Check if we have Smart Search context
        $has_context = false;
        foreach ($messages as $message) {
            if (strpos($message['content'], 'Context from site content:') !== false) {
                $has_context = true;
                break;
            }
        }
        
        if (!$has_context && !$this->has_smart_search()) {
            $response['text'] = 'I\'d be happy to help! However, to provide the most accurate and relevant information from your site, please install and enable the WP Engine AI Toolkit or Smart Search plugin.';
        }
        
        return $response;
    }
    
    /**
     * Check if Smart Search is available
     */
    private function has_smart_search() {
        return class_exists('WPEngine_Smart_Search') || 
               function_exists('wpengine_smart_search_init') ||
               function_exists('wpengine_smart_search_query');
    }
    
    /**
     * Redact PII from prompt
     */
    private function redact_pii($text) {
        // Basic PII redaction patterns
        $patterns = array(
            '/\b\d{3}-\d{2}-\d{4}\b/' => '[SSN]',           // SSN
            '/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/' => '[CARD]', // Credit card
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/' => '[EMAIL]', // Email
            '/\b\d{3}[\s.-]?\d{3}[\s.-]?\d{4}\b/' => '[PHONE]', // Phone
        );
        
        foreach ($patterns as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }
        
        return $text;
    }
    
    /**
     * Log conversation
     */
    private function log_conversation($session_id, $user_message, $bot_response, $response_time, $tokens_used) {
        $log_settings = get_option('ssgc_log_settings', array());
        
        if (!($log_settings['enabled'] ?? true)) {
            return;
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ssgc_chat_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'session_id' => $session_id,
                'user_message' => $user_message,
                'bot_response' => $bot_response,
                'timestamp' => current_time('mysql'),
                'user_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'response_time' => $response_time,
                'tokens_used' => $tokens_used
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%f', '%d')
        );
    }
}
