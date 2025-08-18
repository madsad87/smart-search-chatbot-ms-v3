<?php
/**
 * System Health Monitoring
 * 
 * Provides real-time status checking for OpenAI and MVDB integrations
 * with caching to prevent excessive API calls
 */

// Prevent direct access
defined('ABSPATH') || exit;

class SSGC_Health {
    const TX_OPENAI = 'ssgc_health_openai';
    const TX_MVDB   = 'ssgc_health_mvdb';
    const TTL       = 300; // 5 minutes

    /**
     * Get all health statuses
     */
    public static function get_all(): array {
        return array(
            'openai' => self::openai_status(),
            'mvdb'   => self::mvdb_status(),
        );
    }

    /**
     * Check OpenAI API status
     */
    public static function openai_status(): array {
        $cached = get_transient(self::TX_OPENAI);
        if ($cached) return $cached;

        $opts = get_option('ssgc_general_settings', array());
        $apiKey = trim($opts['api_key'] ?? '');
        $model  = trim($opts['model'] ?? 'gpt-3.5-turbo');

        if (!$apiKey) {
            $out = array('state' => 'not_configured', 'label' => 'Not Available', 'detail' => 'No OpenAI API key saved.');
            set_transient(self::TX_OPENAI, $out, self::TTL);
            return $out;
        }

        // Lightweight connectivity probe
        $resp = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'timeout' => 12,
            'headers' => array(
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ),
            // tiny call; if blocked, we still show "Configured" but warn
            'body' => wp_json_encode(array(
                'model' => $model,
                'messages' => array(array('role' => 'user', 'content' => 'ping')),
                'max_tokens' => 1,
            )),
        ));
        
        if (is_wp_error($resp)) {
            $out = array('state' => 'error', 'label' => 'Error', 'detail' => 'Connection failed: ' . $resp->get_error_message());
            set_transient(self::TX_OPENAI, $out, self::TTL);
            return $out;
        }
        
        $code = wp_remote_retrieve_response_code($resp);

        if ($code === 200 || $code === 401 || $code === 429) {
            // 200 = perfect; 401/429 means reaching service (key/limits), so show "Configured"
            $label  = ($code === 200) ? 'OK' : 'Configured';
            $detail = ($code === 200) ? 'Connected' : 'Reachable (auth/limits)';
            $out = array('state' => ($code === 200 ? 'ok' : 'warn'), 'label' => $label, 'detail' => $detail);
        } else {
            $body = wp_remote_retrieve_body($resp);
            $short = $body ? substr($body, 0, 140) : 'No response';
            $out = array('state' => 'error', 'label' => 'Error', 'detail' => "HTTP $code • $short");
        }

        set_transient(self::TX_OPENAI, $out, self::TTL);
        return $out;
    }

    /**
     * Check MVDB status
     */
    public static function mvdb_status(): array {
        $cached = get_transient(self::TX_MVDB);
        if ($cached) return $cached;

        $tk = get_option('ssgc_toolkit', array());
        $endpoint = trim($tk['search_endpoint'] ?? '');
        $token    = trim($tk['api_key'] ?? '');

        if (!$endpoint || !$token) {
            $out = array('state' => 'not_configured', 'label' => 'Not Available', 'detail' => 'No endpoint/token saved.');
            set_transient(self::TX_MVDB, $out, self::TTL);
            return $out;
        }

        // Reuse the same probing logic shape we already use (no subfields on Map)
        $query = 'query($q:String!){ similarity(query:$q){ docs { score data } } }';
        $resp = wp_remote_post($endpoint, array(
            'timeout' => 12,
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode(array('query' => $query, 'variables' => array('q' => 'health'))),
        ));
        
        if (is_wp_error($resp)) {
            $out = array('state' => 'error', 'label' => 'Error', 'detail' => 'Connection failed: ' . $resp->get_error_message());
            set_transient(self::TX_MVDB, $out, self::TTL);
            return $out;
        }
        
        $code = wp_remote_retrieve_response_code($resp);
        $body = wp_remote_retrieve_body($resp);

        if ($code === 200) {
            $out = array('state' => 'ok', 'label' => 'OK', 'detail' => 'Connected');
        } else {
            // even if blocked by schema/args, we reached host — treat as warn
            if ($code >= 400 && $code < 500 && !empty($body)) {
                $out = array('state' => 'warn', 'label' => 'Configured', 'detail' => 'Reachable (schema/auth check)');
            } else {
                $short = $body ? substr($body, 0, 140) : 'No response';
                $out = array('state' => 'error', 'label' => 'Error', 'detail' => "HTTP $code • $short");
            }
        }

        set_transient(self::TX_MVDB, $out, self::TTL);
        return $out;
    }

    /**
     * Clear all health caches
     */
    public static function clear_cache() {
        delete_transient(self::TX_OPENAI);
        delete_transient(self::TX_MVDB);
    }
}
