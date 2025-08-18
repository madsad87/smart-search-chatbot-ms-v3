<?php
/**
 * Smart Search Retrieval Helper
 * 
 * Handles context retrieval and formatting for chat responses
 * Supports WP Engine AI Toolkit GraphQL integration with similarity and find modes
 */

// Prevent direct access
defined('ABSPATH') || exit;

class SSGC_Retrieval {
    
    /**
     * Get candidate GraphQL queries for different endpoint shapes
     */
    private static function candidate_queries(): array {
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
     * Get AI Toolkit configuration options
     */
    public static function options(): array {
        $opt = get_option('ssgc_toolkit', array());
        $defaults = array(
            'search_endpoint' => '',
            'api_key'         => '',
            'index_id'        => '',
            'mode'            => 'similarity', // 'similarity' | 'find'
            'fields'          => 'post_title:8, post_content:1',
            'top_k'           => 5,
            'min_score'       => 0.0,
        );
        return is_array($opt) ? array_replace($defaults, $opt) : $defaults;
    }
    
    /**
     * Parse field boosts from CSV string
     */
    private static function parse_field_boosts(string $csv): array {
        // "post_title:8, post_content:1" -> [ ['name'=>'post_title','boost'=>8], ... ]
        $out = array();
        foreach (explode(',', $csv) as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') continue;
            $parts = array_map('trim', explode(':', $chunk, 2));
            $name  = $parts[0] ?? '';
            $boost = isset($parts[1]) ? (float)$parts[1] : 1.0;
            if ($name !== '') {
                $out[] = array('name' => $name, 'boost' => $boost);
            }
        }
        return $out ?: array(
            array('name' => 'post_title', 'boost' => 8), 
            array('name' => 'post_content', 'boost' => 1)
        );
    }
    
    /**
     * Query WP Engine AI Toolkit Smart Search using GraphQL.
     * Return shape: [ ['title'=>'','url'=>'','snippet'=>'','score'=>float], ... ]
     */
    public static function similarity_search(string $q, int $topK = 5, float $minScore = 0.0): array {
        $tk = get_option('ssgc_toolkit', array());
        $endpoint = trim($tk['search_endpoint'] ?? '');
        $token    = trim($tk['api_key'] ?? '');
        if (!$endpoint || !$token) return array();

        $vars = array('q' => $q);
        $cands = self::candidate_queries();

        $docs = array();
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
            if ($code !== 200 || empty($body)) continue;

            $json = json_decode($body, true);
            if (!is_array($json)) continue;

            $node = $json;
            foreach ($cand['path'] as $p) {
                if (!isset($node[$p])) { $node = null; break; }
                $node = $node[$p];
            }
            if (!empty($node) && is_array($node)) { $docs = $node; break; }
        }

        $out = array();
        if (!empty($docs)) {
            foreach ($docs as $doc) {
                $data = $doc['data'] ?? array(); // Map<any,any>
                if (!is_array($data)) $data = array();

                // Heuristics over Map keys
                $pick = function($arr, $keys) {
                    foreach ($keys as $k) {
                        if (!array_key_exists($k, $arr)) continue;
                        $v = $arr[$k];
                        if (is_array($v) && isset($v['rendered'])) $v = $v['rendered'];
                        if (is_string($v) && $v !== '') return $v;
                    }
                    // fallback: first non-empty string value
                    foreach ($arr as $v) {
                        if (is_array($v) && isset($v['rendered']) && is_string($v['rendered']) && $v['rendered'] !== '') return $v['rendered'];
                        if (is_string($v) && $v !== '') return $v;
                    }
                    return '';
                };

                $title   = $pick($data, array('post_title','title','name','heading'));
                $url     = $pick($data, array('post_url','url','permalink','link'));
                $snippet = $pick($data, array('post_content','content','excerpt','summary','text','description','body'));

                $score = (float)($doc['score'] ?? $doc['_score'] ?? 0.0);
                if ($minScore > 0 && $score < $minScore) continue;

                $title   = wp_strip_all_tags((string)$title);
                $snippet = wp_strip_all_tags((string)$snippet);
                if (strlen($snippet) > 500) $snippet = substr($snippet, 0, 500) . '…';
                $url     = esc_url_raw((string)$url);

                $out[] = compact('title','url','snippet','score');
                if (count($out) >= $topK) break;
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
}
