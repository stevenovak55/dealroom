<?php
/**
 * Security Headers Middleware
 *
 * Adds security headers to all responses to protect against common web vulnerabilities
 *
 * @package MADealRoom
 * @subpackage Middleware
 * @since 1.0.0
 */

namespace MADealRoom\Middleware;

class SecurityHeadersMiddleware {
    /**
     * Initialize the middleware
     */
    public function init() {
        add_action('send_headers', [$this, 'add_security_headers'], 10);
    }

    /**
     * Add all security headers
     */
    public function add_security_headers() {
        // Prevent if headers already sent
        if (headers_sent()) {
            return;
        }

        $this->add_content_security_policy();
        $this->add_frame_options();
        $this->add_content_type_options();
        $this->add_referrer_policy();
        $this->add_permissions_policy();
        $this->add_xss_protection();
    }

    /**
     * Add Content Security Policy header
     *
     * Protects against XSS, clickjacking, and other code injection attacks
     */
    private function add_content_security_policy() {
        $environment = getenv('ENVIRONMENT') ?: getenv('MA_DEAL_ENV') ?: 'development';

        // Build script-src based on environment
        $script_src = "'self' 'unsafe-inline' 'unsafe-eval' blob:"; // blob: needed for web workers
        $connect_src = "'self' https://api.sentry.io https://*.sentry.io"; // API calls and Sentry

        // In development, allow localhost for hot reload
        if ($environment === 'development') {
            $script_src .= " localhost:* 127.0.0.1:*";
            $connect_src .= " ws://localhost:* ws://127.0.0.1:* http://localhost:* http://127.0.0.1:*";
        }

        // CSP directives
        $directives = [
            "default-src 'self'",
            "script-src $script_src",
            "worker-src 'self' blob:", // Allow web workers from blob URLs
            "style-src 'self' 'unsafe-inline'", // unsafe-inline needed for styled-components, WordPress admin
            "img-src 'self' data: https: blob:", // Allow images from CDNs
            "font-src 'self' data:",
            "connect-src $connect_src",
            "media-src 'self'",
            "object-src 'none'", // Disable plugins like Flash
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'", // Prevent embedding (same as X-Frame-Options: DENY)
        ];

        $csp = implode('; ', $directives);
        header("Content-Security-Policy: $csp", true);
    }

    /**
     * Add X-Frame-Options header
     *
     * Prevents clickjacking by disallowing the page to be embedded in iframes
     */
    private function add_frame_options() {
        header('X-Frame-Options: DENY', true);
    }

    /**
     * Add X-Content-Type-Options header
     *
     * Prevents MIME type sniffing
     */
    private function add_content_type_options() {
        header('X-Content-Type-Options: nosniff', true);
    }

    /**
     * Add Referrer-Policy header
     *
     * Controls how much referrer information should be included with requests
     */
    private function add_referrer_policy() {
        // strict-origin-when-cross-origin: Send full URL for same-origin,
        // only origin for cross-origin HTTPS, nothing for HTTP
        header('Referrer-Policy: strict-origin-when-cross-origin', true);
    }

    /**
     * Add Permissions-Policy header (formerly Feature-Policy)
     *
     * Controls which browser features can be used
     */
    private function add_permissions_policy() {
        $policies = [
            'geolocation=()',        // Disable geolocation
            'microphone=()',         // Disable microphone
            'camera=()',             // Disable camera
            'payment=()',            // Disable payment API
            'usb=()',                // Disable USB
            'magnetometer=()',       // Disable magnetometer
            'gyroscope=()',          // Disable gyroscope
            'accelerometer=()',      // Disable accelerometer
        ];

        $policy = implode(', ', $policies);
        header("Permissions-Policy: $policy", true);
    }

    /**
     * Add X-XSS-Protection header
     *
     * Enables browser's XSS filtering (legacy but still useful for older browsers)
     */
    private function add_xss_protection() {
        // mode=block: Block the page if XSS attack detected
        header('X-XSS-Protection: 1; mode=block', true);
    }

    /**
     * Get current environment
     *
     * @return string
     */
    private function get_environment() {
        return getenv('ENVIRONMENT') ?: getenv('MA_DEAL_ENV') ?: 'development';
    }
}
