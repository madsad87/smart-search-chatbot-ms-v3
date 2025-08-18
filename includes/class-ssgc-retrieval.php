<?php
/**
 * Smart Search Retrieval Helper
 * 
 * Handles context retrieval and formatting for chat responses
 * Supports WP Engine AI Toolkit Smart Search integration
 */

// Prevent direct access
defined('ABSPATH') || exit;

class SSGC_Retrieval {
    
    /**
     * Get AI Toolkit configuration options
     */
    public static function options(): array {
        $opt = get_option('ssgc_toolkit', array());
        $defaults = array(
            'search_endpoint' => '',   // e.g. https://your-site/wp-json/ai-toolkit/v1/search
            'api_key'         => '',   // if required
            'index_id'        => '',   // if required
            'top_k'           => 5,
            'min_score'       => 0.0,
        );
        return is_array($opt) ? array_replace($defaults, $opt) : $defaults;
    }
    
    /**
     * Query WP Engine AI Toolkit Smart Search.
     * Return shape: [ ['title'=>'','url'=>'','snippet'=>'','score'=>float], ... ]
     */
    public static function similarity_search(string $query, int $k = 5): array {
        $cfg = self::options();
        $endpoint = trim((string)$cfg['search_endpoint']);
        
        if ($endpoint === '' || !preg_match('#^https?://#i', $endpoint)) {
            // Toolkit not configured; return empty
            return array();
        }
        
        $k = max(1, (int)$k);
        $topK = (int)($cfg['top_k'] ?: $k);
        $minScore = (float)$cfg['min_score'];

        $headers = array('Content-Type' => 'application/json');
        if (!empty($cfg['api_key'])) {
            $headers['Authorization'] = 'Bearer ' . $cfg['api_key'];
        }

        // Generic request body; adjust keys in settings if your endpoint differs.
        $body = array(
            'query' => $query,
            'topK'  => $topK,
        );
        if (!empty($cfg['index_id'])) {
            $body['index'] = $cfg['index_id'];
        }

        $resp = wp_remote_post($endpoint, array(
            'headers' => $headers,
            'body'    => wp_json_encode($body),
            'timeout' => 12,
        ));
        
        if (is_wp_error($resp)) {
            error_log('SSGC Toolkit Error: ' . $resp->get_error_message());
            return array();
        }
        
        $code = wp_remote_retrieve_response_code($resp);
        if ($code < 200 || $code >= 300) {
            error_log('SSGC Toolkit HTTP Error: ' . $code);
            return array();
        }
        
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        if (!is_array($json)) {
            return array();
        }

        // Try several common shapes:
        $items = array();
        if (isset($json['results']) && is_array($json['results'])) {
            $items = $json['results'];
        } elseif (isset($json['data']) && is_array($json['data'])) {
            $items = $json['data'];
        } elseif (isset($json['hits']) && is_array($json['hits'])) {
            $items = $json['hits'];
        } else {
            // Fallback: assume the root is the array
            $items = $json;
        }

        $out = array();
        foreach ($items as $it) {
            if (!is_array($it)) continue;
            
            // Common fields seen in RAG examples; adjust if your endpoint differs
            $title   = $it['title']   ?? ($it['metadata']['title'] ?? '');
            $url     = $it['url']     ?? ($it['metadata']['url'] ?? '');
            $snippet = $it['snippet'] ?? ($it['content'] ?? ($it['text'] ?? ''));
            $score   = (float)($it['score'] ?? ($it['_score'] ?? 0.0));

            if ($minScore > 0 && $score < $minScore) {
                continue;
            }

            if (!$title && isset($it['id'])) {
                $title = 'Document ' . $it['id'];
            }
            
            $out[] = array(
                'title'   => wp_strip_all_tags((string)$title),
                'url'     => esc_url_raw((string)$url),
                'snippet' => wp_strip_all_tags((string)$snippet),
                'score'   => $score,
            );
            
            if (count($out) >= $topK) {
                break;
            }
        }
        
        return $out;
    }
    
    /**
     * Format chunks into a compact context block for the LLM.
     */
    public static function format_context(array $chunks, int $maxChars = 2000): array {
        $out = array();
        foreach ($chunks as $c) {
            $title = isset($c['title']) ? wp_strip_all_tags($c['title']) : '';
            $url   = isset($c['url']) ? esc_url_raw($c['url']) : '';
            $snip  = isset($c['snippet']) ? wp_strip_all_tags($c['snippet']) : '';
            $out[] = "- Title: {$title}\n  URL: {$url}\n  Excerpt: {$snip}";
        }
        $txt = implode("\n", $out);
        if (strlen($txt) > $maxChars) {
            $txt = substr($txt, 0, $maxChars) . "…";
        }
        return array($txt, $chunks);
    }
    
    /**
     * Check if AI Toolkit is configured
     */
    public static function is_configured(): bool {
        $cfg = self::options();
        return !empty($cfg['search_endpoint']) && preg_match('#^https?://#i', $cfg['search_endpoint']);
    }
    
    /**
     * Test AI Toolkit endpoint connectivity
     */
    public static function test_connectivity(): array {
        $cfg = self::options();
        $endpoint = trim((string)$cfg['search_endpoint']);
        
        if (!self::is_configured()) {
            return array(
                'success' => false,
                'error' => 'No search endpoint configured'
            );
        }
        
        // Try a HEAD request first for basic connectivity
        $resp = wp_remote_head($endpoint, array('timeout' => 4));
        
        if (is_wp_error($resp)) {
            return array(
                'success' => false,
                'error' => 'Connection failed: ' . $resp->get_error_message()
            );
        }
        
        $code = wp_remote_retrieve_response_code($resp);
        if ($code >= 500) {
            return array(
                'success' => false,
                'error' => 'Server error: ' . $code
            );
        }
        
        return array(
            'success' => true,
            'status_code' => $code
        );
    }
    
    /**
     * Legacy compatibility methods
     */
    public static function is_available(): bool {
        return self::is_configured();
    }
    
    /**
     * Normalize search results to consistent format (legacy)
     */
    private static function normalize_search_results($results): array {
        if (!is_array($results)) {
            return array();
        }
        
        $normalized = array();
        foreach ($results as $result) {
            $normalized[] = array(
                'title' => $result['title'] ?? 'Untitled',
                'url' => $result['url'] ?? '#',
                'snippet' => $result['excerpt'] ?? ($result['content'] ?? ''),
                'score' => $result['score'] ?? 0.0
            );
        }
        
        return $normalized;
    }
}
