<?php
/**
 * HTTPS Middleware
 *
 * Forces HTTPS connections in production and sets HSTS headers
 *
 * @package MADealRoom
 * @subpackage Middleware
 * @since 1.0.0
 */

namespace MADealRoom\Middleware;

class HTTPSMiddleware {
    /**
     * Initialize the middleware
     */
    public function init() {
        // Only enforce HTTPS in production
        if ($this->is_production()) {
            add_action('template_redirect', [$this, 'force_https'], 1);
            add_action('admin_init', [$this, 'force_https'], 1);
            add_action('send_headers', [$this, 'add_hsts_header']);
        }
    }

    /**
     * Check if we're in production environment
     *
     * @return bool
     */
    private function is_production() {
        $environment = getenv('ENVIRONMENT') ?: getenv('MA_DEAL_ENV');
        return $environment === 'production';
    }

    /**
     * Force HTTPS redirect if not already on HTTPS
     */
    public function force_https() {
        // Skip if already on HTTPS
        if ($this->is_https()) {
            return;
        }

        // Skip for local development IPs
        if ($this->is_local_request()) {
            return;
        }

        // Redirect to HTTPS
        $redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        wp_safe_redirect($redirect_url, 301);
        exit;
    }

    /**
     * Check if current request is HTTPS
     *
     * @return bool
     */
    private function is_https() {
        // Check standard HTTPS indicator
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        // Check for load balancer/proxy forwarded protocol
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }

        // Check for CloudFlare
        if (!empty($_SERVER['HTTP_CF_VISITOR'])) {
            $cf_visitor = json_decode($_SERVER['HTTP_CF_VISITOR'], true);
            if (isset($cf_visitor['scheme']) && $cf_visitor['scheme'] === 'https') {
                return true;
            }
        }

        // Check standard port
        if (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
            return true;
        }

        return false;
    }

    /**
     * Check if request is from local/development environment
     *
     * @return bool
     */
    private function is_local_request() {
        $remote_addr = $_SERVER['REMOTE_ADDR'] ?? '';

        $local_ips = [
            '127.0.0.1',
            '::1',
            'localhost'
        ];

        return in_array($remote_addr, $local_ips) ||
               strpos($remote_addr, '192.168.') === 0 ||
               strpos($remote_addr, '10.') === 0;
    }

    /**
     * Add HTTP Strict Transport Security (HSTS) header
     */
    public function add_hsts_header() {
        // Only add HSTS if we're on HTTPS
        if (!$this->is_https()) {
            return;
        }

        // HSTS header: max-age=31536000 (1 year), includeSubDomains
        // preload is optional and requires submission to HSTS preload list
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload', true);
    }
}
