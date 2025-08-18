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
            'callback' => array($this, 'rest_chat'),
            'permission_callback' => '__return_true', // public; we will rate-limit
        ));
        
        // Test API connection endpoint
        register_rest_route('ssgc/v1', '/test', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_test_connection'),
            'permission_callback' => function() { return current_user_can('manage_options'); }, // admin-only
        ));
        
        // Health check endpoint
        register_rest_route('ssgc/v1', '/health', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_health_check'),
            'permission_callback' => '__return_true',
        ));
        
        // Search test endpoint (primary)
        register_rest_route('ssgc/v1', '/search-test', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_search_debug'),
            'permission_callback' => function() { return current_user_can('manage_options'); },
        ));
        
        // Search debug endpoint (backward compatibility)
        register_rest_route('ssgc/v1', '/search-debug', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_search_debug'),
            'permission_callback' => function() { return current_user_can('manage_options'); },
        ));
    }
    
    /**
     * Handle chat request
     */
    public function rest_chat($request) {
        $body = json_decode($request->get_body(), true);
        $prompt = isset($body['prompt']) ? trim(wp_strip_all_tags((string)$body['prompt'])) : '';
        $session = isset($body['session_id']) ? (string)$body['session_id'] : '';

        if ($prompt === '' || $session === '' || !preg_match('/^[a-f0-9-]{36}$/i', $session)) {
            return new WP_REST_Response(array('error' => 'Invalid input'), 400);
        }
        
        if (!self::rate_limit_ok($session)) {
            return new WP_REST_Response(array('error' => 'rate_limited'), 429);
        }

        // (1) Persona
        $system = self::build_persona_system();

        // (2) Retrieval
        $chunks = SSGC_Retrieval::similarity_search($prompt, 5);
        list($contextTxt, $rawCitations) = SSGC_Retrieval::format_context($chunks);

        // Check if we should restrict to context only
        $tk = get_option('ssgc_toolkit', array());
        $restrict = !empty($tk['restrict_to_context']);

        if ($restrict && empty($rawCitations)) {
            // No context found → do not call provider; return controlled message
            $msg = "I don't have enough information in the site's content to answer that. Try asking about specific pages, products, or topics that exist on this site.";
            
            // Optional: log as a handled miss
            if (class_exists('SSGC_Logs')) {
                try {
                    SSGC_Logs::insert(array(
                        'session_id' => $session,
                        'user_message' => $prompt,
                        'bot_response' => $msg,
                        'response_time' => 0,
                        'tokens_used' => 0,
                        'user_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    ));
                } catch (Exception $e) {
                    // swallow logging errors
                }
            }
            
            return new WP_REST_Response(array(
                'text' => $msg,
                'citations' => array(),
                'usage' => null
            ), 200);
        }

        // (3) Build messages
        $messages = array();
        $messages[] = array('role' => 'system', 'content' => $system);
        if (!empty($contextTxt)) {
            $messages[] = array('role' => 'system', 'content' => "Context (from site content):\n{$contextTxt}");
        }
        $messages[] = array('role' => 'user', 'content' => $prompt);

        // (4) Call provider (OpenAI for now)
        $ai = get_option('ssgc_general_settings', array());
        $key = isset($ai['api_key']) ? trim($ai['api_key']) : '';
        $model = !empty($ai['model']) ? $ai['model'] : 'gpt-3.5-turbo';
        
        if (!$key) {
            return new WP_REST_Response(array('error' => 'missing_api_key'), 400);
        }
        
        $t0 = microtime(true);
        $resp = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $key,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.2,
                'max_tokens' => 600,
            )),
            'timeout' => 20,
        ));
        
        if (is_wp_error($resp)) {
            return new WP_REST_Response(array('error' => 'provider_http', 'detail' => $resp->get_error_message()), 502);
        }
        
        $code = wp_remote_retrieve_response_code($resp);
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        
        if ($code < 200 || $code >= 300) {
            $msg = isset($json['error']['message']) ? $json['error']['message'] : ('Provider error ' . $code);
            return new WP_REST_Response(array('error' => 'provider_' . $code, 'detail' => $msg), 502);
        }
        
        $text = $json['choices'][0]['message']['content'] ?? '';
        $usage = $json['usage'] ?? null;
        $lat_ms = (int) round((microtime(true) - $t0) * 1000);

        // (5) Citations (from retrieval stub)
        $cites = array();
        foreach ($rawCitations as $c) {
            if (!empty($c['title']) && !empty($c['url'])) {
                $cites[] = array('title' => $c['title'], 'url' => $c['url']);
            }
        }

        // (6) Log (reuse your SSGC_Logs if available)
        if (class_exists('SSGC_Logs')) {
            try {
                SSGC_Logs::insert(array(
                    'session_id' => $session,
                    'user_message' => $prompt,
                    'bot_response' => $text,
                    'response_time' => $lat_ms / 1000.0,
                    'tokens_used' => isset($usage['total_tokens']) ? (int)$usage['total_tokens'] : null,
                    'user_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                ));
            } catch (Exception $e) {
                // swallow logging errors
                error_log('SSGC Logging Error: ' . $e->getMessage());
            }
        }

        return new WP_REST_Response(array(
            'text' => $text,
            'citations' => $cites,
            'usage' => $usage,
        ), 200);
    }
    
    /**
     * Simple rate limiting
     */
    private static function rate_limit_ok(string $sessionId): bool {
        // Simple transient-based throttle: 1 req / 2 seconds per session + IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $key = 'ssgc_rl_' . md5($sessionId . '|' . $ip);
        if (get_transient($key)) {
            return false;
        }
        set_transient($key, 1, 2); // 2 seconds
        return true;
    }
    
    /**
     * Build persona system message
     */
    private static function build_persona_system(): string {
        $persona = get_option('ssgc_persona_settings', array());
        $tk = get_option('ssgc_toolkit', array());
        $restrict = !empty($tk['restrict_to_context']);

        $instructions = isset($persona['instructions']) ? trim($persona['instructions']) : 'You are a helpful AI assistant.';
        $style = isset($persona['style']) ? trim($persona['style']) : 'friendly, concise.';
        $site = get_bloginfo('name');

        $rules = array(
            'Prefer bullet points and short paragraphs.',
            'If context includes URLs, include them as sources in the answer.',
        );
        
        if ($restrict) {
            $rules[] = 'Important: Only answer using the provided Context. If the answer is not in Context, say you do not know and suggest a related query.';
        } else {
            $rules[] = 'Ground answers in the provided Context when present. If missing, you may use general knowledge but avoid speculation.';
        }

        return "Role & Voice:\n{$instructions}\n\nStyle:\n{$style}\n\nSite:\n{$site}\n\nRules:\n- " . implode("\n- ", $rules) . "\n";
    }
    
    /**
     * Test API connection endpoint
     */
    public function rest_test_connection($request) {
        $settings = get_option('ssgc_general_settings', array());
        $provider = isset($settings['api_provider']) ? strtolower(sanitize_text_field($settings['api_provider'])) : 'openai';
        $model = !empty($settings['model']) ? sanitize_text_field($settings['model']) : 'gpt-3.5-turbo';
        $result = array('ok' => false, 'provider' => $provider, 'model' => $model);

        $t0 = microtime(true);

        if ($provider === 'openai') {
            $key = isset($settings['api_key']) ? trim($settings['api_key']) : '';
            if (!$key) {
                return new WP_REST_Response(array_merge($result, array('error' => 'Missing OpenAI API key in settings.')), 400);
            }
            
            $body = array(
                'model' => $model,
                'messages' => array(array('role' => 'user', 'content' => 'ping')),
                'max_tokens' => 4,
                'temperature' => 0,
            );
            
            $resp = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $key,
                    'Content-Type' => 'application/json',
                ),
                'body' => wp_json_encode($body),
                'timeout' => 12,
            ));
            
            if (is_wp_error($resp)) {
                return new WP_REST_Response(array_merge($result, array(
                    'error' => 'HTTP error: ' . $resp->get_error_message(),
                )), 502);
            }
            
            $code = wp_remote_retrieve_response_code($resp);
            $json = json_decode(wp_remote_retrieve_body($resp), true);
            
            if ($code >= 200 && $code < 300 && !empty($json['choices'][0]['message']['content'])) {
                $lat = (int) round(1000 * (microtime(true) - $t0));
                return new WP_REST_Response(array(
                    'ok' => true,
                    'provider' => 'openai',
                    'model' => $model,
                    'latency_ms' => $lat,
                ), 200);
            }
            
            // Extract best available error info
            $err = isset($json['error']['message']) ? $json['error']['message'] : ('Unexpected status ' . $code);
            return new WP_REST_Response(array_merge($result, array('error' => $err)), 502);
        }

        // Placeholder for other providers
        return new WP_REST_Response(array_merge($result, array('error' => 'Unsupported provider: ' . $provider)), 400);
    }
    
    /**
     * Health check endpoint
     */
    public function rest_health_check($request) {
        $health = array(
            'ok' => true,
            'timestamp' => current_time('mysql'),
            'version' => SSGC_VERSION ?? '1.0.0'
        );
        
        // Check AI Toolkit configuration
        $tk = SSGC_Retrieval::options();
        $has_toolkit_cfg = !empty($tk['search_endpoint']);
        $health['has_ai_toolkit'] = $has_toolkit_cfg;
        $health['toolkit_mode'] = $tk['mode'] ?? 'similarity';
        
        // Optional lightweight probe to endpoint
        if ($has_toolkit_cfg) {
            $connectivity = SSGC_Retrieval::test_connectivity();
            $health['toolkit_reachable'] = $connectivity['success'];
            if (!$connectivity['success']) {
                $health['toolkit_error'] = $connectivity['error'];
            }
        }
        
        // Check if OpenAI is configured
        $ai_settings = get_option('ssgc_general_settings', array());
        $health['has_openai_key'] = !empty($ai_settings['api_key']);
        
        // Check if logging is enabled
        $log_settings = get_option('ssgc_log_settings', array());
        $health['logging_enabled'] = $log_settings['enabled'] ?? true;
        
        return new WP_REST_Response($health, 200);
    }
    
    /**
     * Search debug endpoint
     */
    public function rest_search_debug($request) {
        $q = sanitize_text_field($request->get_param('q') ?: '');
        $tk = get_option('ssgc_toolkit', array());
        $endpoint = trim($tk['search_endpoint'] ?? '');
        $token    = trim($tk['api_key'] ?? '');
        if (!$endpoint || !$token) {
            return new WP_REST_Response(array('error' => 'Toolkit endpoint not configured'), 400);
        }

        $vars = array('q' => $q ?: 'test');
        $cands = $this->get_candidate_queries();

        $last = array('status' => 0, 'body' => null);
        foreach ($cands as $cand) {
            $resp = wp_remote_post($endpoint, array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json'
                ),
                'timeout' => 20,
                'body'    => wp_json_encode(array('query' => $cand['query'], 'variables' => $vars)),
            ));
            $code = wp_remote_retrieve_response_code($resp);
            $body = wp_remote_retrieve_body($resp);
            $last = array('status' => $code, 'body' => $body, 'probe' => $cand['name']);

            if ($code !== 200 || empty($body)) continue;

            $json = json_decode($body, true);
            $node = $json;
            foreach ($cand['path'] as $p) {
                if (!isset($node[$p])) { $node = null; break; }
                $node = $node[$p];
            }
            if (!empty($node) && is_array($node)) {
                // Parse using similarity_search() (which already normalizes)
                $parsed = $this->parse_via_similarity($q, (int)($vars['top'] ?? 5), (float)($vars['min'] ?? 0));
                return new WP_REST_Response(array(
                    'status' => 200,
                    'mode'   => $cand['name'],
                    'endpoint' => $endpoint,
                    'variables' => $vars,
                    'raw'    => $json,
                    'parsed' => $parsed,
                ), 200);
            }
        }

        // If we reach here, none worked; surface GraphQL errors if any
        $err = null;
        if (!empty($last['body'])) {
            $try = json_decode($last['body'], true);
            if (isset($try['errors'])) $err = $try['errors'];
        }
        return new WP_REST_Response(array(
            'status' => (int)($last['status'] ?: 400),
            'endpoint' => $endpoint,
            'variables' => $vars,
            'error' => $err ?: 'GraphQL request failed',
            'rawText' => is_string($last['body']) ? substr($last['body'], 0, 2000) : null,
        ), (int)($last['status'] ?: 400));
    }
    
    /**
     * Get candidate GraphQL queries for debug probing
     */
    private function get_candidate_queries(): array {
        return array(
            array(
                'name'  => 'similarity_docs',
                'query' => 'query($q:String!){
                    similarity(query:$q){
                      docs { score data }
                    }
                }',
                'path'  => array('data','similarity','docs')
            ),
            array(
                'name'  => 'similarity_documents',
                'query' => 'query($q:String!){
                    similarity(query:$q){
                      documents { score data }
                    }
                }',
                'path'  => array('data','similarity','documents')
            ),
            array(
                'name'  => 'find_documents',
                'query' => 'query($q:String!){
                    find(query:$q){
                      documents { score data }
                    }
                }',
                'path'  => array('data','find','documents')
            ),
        );
    }

    /**
     * Helper wrapper that just calls similarity_search()
     */
    private function parse_via_similarity($q, $top, $min) {
        return SSGC_Retrieval::similarity_search($q, $top, $min);
    }
}
