<?php
/**
 * Authentication Service
 *
 * Handles JWT token generation, password management, and authentication logic.
 *
 * @package    MADealRoom\Services
 * @since      2.0.0
 */

namespace MADealRoom\Services;

use MADealRoom\Models\CustomUser;
use MADealRoom\Models\UserRole;
use MADealRoom\Models\UserSession;
use MADealRoom\Repositories\CustomUserRepository;
use MADealRoom\Repositories\UserSessionRepository;
use MADealRoom\Repositories\UserRoleRepository;
use MADealRoom\Repositories\AccountRepository;
use WP_Error;
use WP_User;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AuthService Class
 *
 * Core authentication service for JWT-based authentication.
 */
class AuthService {

    /**
     * Custom user repository
     *
     * @var CustomUserRepository
     */
    private $user_repo;

    /**
     * Session repository
     *
     * @var UserSessionRepository
     */
    public $session_repo;

    /**
     * Role repository
     *
     * @var UserRoleRepository
     */
    private $role_repo;

    /**
     * Account repository
     *
     * @var AccountRepository
     */
    private $account_repo;

    /**
     * JWT secret for access tokens
     *
     * @var string
     */
    private $jwt_access_secret;

    /**
     * JWT secret for refresh tokens
     *
     * @var string
     */
    private $jwt_refresh_secret;

    /**
     * Access token expiration time (seconds)
     *
     * @var int
     */
    private $access_token_expiry = 900; // 15 minutes

    /**
     * Refresh token expiration time (seconds)
     *
     * @var int
     */
    private $refresh_token_expiry = 604800; // 7 days

    /**
     * Constructor
     */
    public function __construct() {
        $this->user_repo = new CustomUserRepository();
        $this->session_repo = new UserSessionRepository();
        $this->role_repo = new UserRoleRepository();
        $this->account_repo = new AccountRepository();

        // Get JWT secrets from WordPress options or environment
        $this->jwt_access_secret = $this->get_jwt_secret('access');
        $this->jwt_refresh_secret = $this->get_jwt_secret('refresh');

        // Allow customization via filters
        $this->access_token_expiry = apply_filters('ma_deal_access_token_expiry', $this->access_token_expiry);
        $this->refresh_token_expiry = apply_filters('ma_deal_refresh_token_expiry', $this->refresh_token_expiry);
    }

    /**
     * Register a new custom user
     *
     * @param array $data User registration data
     * @return array|WP_Error User data with tokens or error
     */
    public function register(array $data) {
        // Validate required fields
        $required = ['email', 'password', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return new WP_Error(
                    'missing_field',
                    sprintf(__('Field %s is required', 'ma-deal-room'), $field),
                    ['status' => 400]
                );
            }
        }

        // Validate email format
        if (!is_email($data['email'])) {
            return new WP_Error(
                'invalid_email',
                __('Invalid email address', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Check if email already exists
        if ($this->user_repo->email_exists($data['email'])) {
            return new WP_Error(
                'email_exists',
                __('Email address already registered', 'ma-deal-room'),
                ['status' => 409]
            );
        }

        // Validate password strength
        $password_validation = $this->validate_password_strength($data['password']);
        if (is_wp_error($password_validation)) {
            return $password_validation;
        }

        // Create user
        $user = new CustomUser();
        $user->email = sanitize_email($data['email']);
        $user->first_name = sanitize_text_field($data['first_name']);
        $user->last_name = sanitize_text_field($data['last_name']);
        $user->phone = !empty($data['phone']) ? sanitize_text_field($data['phone']) : null;
        $user->set_password($data['password']);
        $user->status = 'pending_verification';
        $user->email_verified = false;

        $user_id = $this->user_repo->create_user($user);

        if (!$user_id) {
            return new WP_Error(
                'registration_failed',
                __('Failed to create user account', 'ma-deal-room'),
                ['status' => 500]
            );
        }

        $user->id = $user_id;

        // Get or use default account for new user
        $default_account = $this->account_repo->findFirstActive();
        $account_id = $default_account ? $default_account->id : null;

        // Assign default role to new user (agent) with account association
        // This gives them basic capabilities like reading their own data
        try {
            $role = new UserRole([
                'user_id' => $user_id,
                'user_type' => 'custom',
                'role_type' => 'agent',
                'account_id' => $account_id,
                'is_primary' => true,
                'assigned_at' => current_time('mysql'),
            ]);
            $this->role_repo->assign_role($role);
        } catch (Exception $e) {
            // Log error but don't fail registration
            error_log('Failed to assign default role to user ' . $user_id . ': ' . $e->getMessage());
        }

        // Log registration event
        do_action('ma_deal_user_registered', $user);

        return [
            'user_id' => $user_id,
            'user_type' => 'custom',
            'user' => $this->format_user_response($user),
            'message' => __('Registration successful. Please verify your email.', 'ma-deal-room'),
        ];
    }

    /**
     * Login user (custom or WordPress)
     *
     * @param string $email Email address or username
     * @param string $password Password
     * @param array $device_info Device information
     * @return array|WP_Error Tokens and user data or error
     */
    public function login(string $email, string $password, array $device_info = []) {
        $ip_address = $this->get_client_ip();

        // Try custom user first
        $custom_user = $this->user_repo->find_by_email($email);

        if ($custom_user) {
            return $this->login_custom_user($custom_user, $password, $device_info, $ip_address);
        }

        // Try WordPress user
        $wp_user = get_user_by('email', $email);
        if (!$wp_user) {
            $wp_user = get_user_by('login', $email);
        }

        if ($wp_user) {
            return $this->login_wordpress_user($wp_user, $password, $device_info, $ip_address);
        }

        return new WP_Error(
            'invalid_credentials',
            __('Invalid email or password', 'ma-deal-room'),
            ['status' => 401]
        );
    }

    /**
     * Login custom user
     *
     * @param CustomUser $user User model
     * @param string $password Password
     * @param array $device_info Device info
     * @param string $ip_address IP address
     * @return array|WP_Error
     */
    private function login_custom_user(CustomUser $user, string $password, array $device_info, string $ip_address) {
        // Check if account is locked
        if ($user->is_locked()) {
            return new WP_Error(
                'account_locked',
                __('Account is temporarily locked due to too many failed login attempts. Please try again later.', 'ma-deal-room'),
                ['status' => 423, 'locked_until' => $user->locked_until]
            );
        }

        // Check if account is active
        if (!$user->is_active() && $user->status !== 'pending_verification') {
            return new WP_Error(
                'account_inactive',
                __('Account is inactive. Please contact support.', 'ma-deal-room'),
                ['status' => 403]
            );
        }

        // Verify password
        if (!$user->verify_password($password)) {
            // Record failed attempt
            $this->user_repo->record_login_attempt($user->id, false, $ip_address);

            return new WP_Error(
                'invalid_credentials',
                __('Invalid email or password', 'ma-deal-room'),
                ['status' => 401]
            );
        }

        // Record successful login
        $this->user_repo->record_login_attempt($user->id, true, $ip_address);

        // Check if email verification is required (MANDATORY as of T2.1.3)
        // Email verification is now enforced for all users for security
        if (!$user->is_email_verified()) {
            return new WP_Error(
                'email_not_verified',
                __('Please verify your email address before logging in. Check your inbox for the verification link.', 'ma-deal-room'),
                [
                    'status' => 403,
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'resend_verification_url' => rest_url('ma-deal/v1/auth/resend-verification')
                ]
            );
        }

        // Check if password change is required
        if ($user->needs_password_change()) {
            return [
                'requires_password_change' => true,
                'user_id' => $user->id,
                'temporary_token' => $this->generate_temporary_token($user->id, 'custom'),
                'message' => __('You must change your password before continuing.', 'ma-deal-room'),
            ];
        }

        // Generate tokens
        $tokens = $this->generate_tokens($user->id, 'custom', $device_info, $ip_address);

        if (is_wp_error($tokens)) {
            return $tokens;
        }

        // Get user roles
        $roles = $this->role_repo->get_user_roles($user->id, 'custom');

        // Log successful login
        do_action('ma_deal_user_logged_in', $user, 'custom');

        return [
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $this->access_token_expiry,
            'token_type' => 'Bearer',
            'user' => $this->format_user_response($user, $roles),
        ];
    }

    /**
     * Login WordPress user
     *
     * @param WP_User $wp_user WordPress user
     * @param string $password Password
     * @param array $device_info Device info
     * @param string $ip_address IP address
     * @return array|WP_Error
     */
    private function login_wordpress_user(WP_User $wp_user, string $password, array $device_info, string $ip_address) {
        // Verify password
        if (!wp_check_password($password, $wp_user->user_pass, $wp_user->ID)) {
            return new WP_Error(
                'invalid_credentials',
                __('Invalid username or password', 'ma-deal-room'),
                ['status' => 401]
            );
        }

        // Generate tokens
        $tokens = $this->generate_tokens($wp_user->ID, 'wordpress', $device_info, $ip_address);

        if (is_wp_error($tokens)) {
            return $tokens;
        }

        // Get user roles
        $roles = $this->role_repo->get_user_roles($wp_user->ID, 'wordpress');

        // Log successful login
        do_action('ma_deal_user_logged_in', $wp_user, 'wordpress');

        return [
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $this->access_token_expiry,
            'token_type' => 'Bearer',
            'user' => $this->format_wp_user_response($wp_user, $roles),
        ];
    }

    /**
     * Generate access and refresh tokens
     *
     * @param int $user_id User ID
     * @param string $user_type User type (custom|wordpress)
     * @param array $device_info Device information
     * @param string $ip_address IP address
     * @return array|WP_Error Tokens or error
     */
    public function generate_tokens(int $user_id, string $user_type, array $device_info = [], string $ip_address = '') {
        // Generate unique session ID for JWT (64 character hex string)
        $session_id = $this->generate_random_token(32); // 32 bytes = 64 hex chars

        // Generate access token with session_id
        $access_token = $this->generate_access_token($user_id, $user_type, $session_id);

        // Generate refresh token
        $refresh_token = $this->generate_random_token(64);
        $refresh_token_hash = hash('sha256', $refresh_token);

        // Get current IP and user agent
        $current_ip = $ip_address ?: $this->get_client_ip();
        $current_user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Parse browser and platform
        $browser_info = $this->parse_browser_info($current_user_agent);

        // Create session
        $session = new UserSession();
        $session->session_id = $session_id;
        $session->user_id = $user_id;
        $session->user_type = $user_type;
        $session->refresh_token_hash = $refresh_token_hash;
        $session->device_name = $device_info['device_name'] ?? $this->parse_device_name($current_user_agent);
        $session->device_type = $device_info['device_type'] ?? $this->parse_device_type($current_user_agent);
        $session->browser = $browser_info['browser'];
        $session->platform = $browser_info['platform'];
        $session->ip_address = $current_ip;
        $session->last_activity_ip = $current_ip;
        $session->user_agent = $current_user_agent;
        $session->last_activity_user_agent = $current_user_agent;
        $session->location = $device_info['location'] ?? null;
        $session->last_used_at = current_time('mysql');
        $session->expires_at = date('Y-m-d H:i:s', time() + $this->refresh_token_expiry);

        $db_session_id = $this->session_repo->create_session($session);

        if (!$db_session_id) {
            return new WP_Error(
                'session_creation_failed',
                __('Failed to create session', 'ma-deal-room'),
                ['status' => 500]
            );
        }

        return [
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
            'session_id' => $db_session_id,
        ];
    }

    /**
     * Generate access token (JWT)
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param string|null $session_id Session ID for tracking
     * @return string JWT token
     */
    public function generate_access_token(int $user_id, string $user_type, ?string $session_id = null): string {
        $issued_at = time();
        $expiration = $issued_at + $this->access_token_expiry;

        $payload = [
            'iat' => $issued_at,
            'exp' => $expiration,
            'sub' => $user_id,
            'user_type' => $user_type,
            'jti' => $this->generate_random_token(16), // JWT ID for uniqueness
        ];

        // Add session_id to payload for session tracking and regeneration (T2.1.4)
        if ($session_id !== null) {
            $payload['session_id'] = $session_id;
        }

        return $this->encode_jwt($payload, $this->jwt_access_secret);
    }

    /**
     * Refresh access token
     *
     * @param string $refresh_token Refresh token
     * @return array|WP_Error New access token or error
     */
    public function refresh_token(string $refresh_token) {
        $refresh_token_hash = hash('sha256', $refresh_token);

        // Find session
        $session = $this->session_repo->find_by_token($refresh_token_hash);

        if (!$session) {
            return new WP_Error(
                'invalid_token',
                __('Invalid refresh token', 'ma-deal-room'),
                ['status' => 401]
            );
        }

        // Check if session is active
        if (!$session->is_active()) {
            return new WP_Error(
                'token_expired',
                __('Refresh token has expired', 'ma-deal-room'),
                ['status' => 401]
            );
        }

        // Get current request info
        $current_ip = $this->get_client_ip();
        $current_user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // T2.1.4: Detect suspicious activity (IP or user agent change)
        $suspicious_activity = $session->detect_suspicious_activity($current_ip, $current_user_agent);

        if ($suspicious_activity) {
            // Log suspicious activity
            error_log(sprintf(
                '[Session Security] Suspicious activity detected for user %d (%s): %s',
                $session->user_id,
                $session->user_type,
                json_encode($suspicious_activity)
            ));

            // Revoke the session for security
            $this->session_repo->revoke_session($session->id, 'suspicious_activity');

            // TODO: Send notification email to user about suspicious activity
            // This can be implemented in T2.1.6 (Security Audit)

            return new WP_Error(
                'suspicious_activity',
                __('Session terminated due to suspicious activity. Please log in again.', 'ma-deal-room'),
                [
                    'status' => 403,
                    'suspicious_activity' => $suspicious_activity,
                ]
            );
        }

        // T2.1.4: Regenerate session ID for security (session fixation prevention)
        $new_session_id = $this->generate_random_token(32);

        // Update session with new session_id and activity tracking
        $this->session_repo->update($session->id, [
            'session_id' => $new_session_id,
            'last_activity_ip' => $current_ip,
            'last_activity_user_agent' => $current_user_agent,
            'last_used_at' => current_time('mysql'),
        ]);

        // Generate new access token with regenerated session_id
        $access_token = $this->generate_access_token($session->user_id, $session->user_type, $new_session_id);

        return [
            'access_token' => $access_token,
            'expires_in' => $this->access_token_expiry,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Logout user (revoke session)
     *
     * @param string $refresh_token Refresh token
     * @param string $reason Invalidation reason
     * @return bool Success
     */
    public function logout(string $refresh_token, string $reason = 'logout'): bool {
        $refresh_token_hash = hash('sha256', $refresh_token);
        return $this->session_repo->revoke_session_by_token($refresh_token_hash, $reason);
    }

    /**
     * Logout all sessions for user (T2.1.4)
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param int|null $except_session_id Keep this session active
     * @param string $reason Invalidation reason
     * @return bool Success
     */
    public function logout_all(int $user_id, string $user_type, ?int $except_session_id = null, string $reason = 'logout_all'): bool {
        return $this->session_repo->revoke_all_sessions($user_id, $user_type, $except_session_id, $reason);
    }

    /**
     * Verify access token
     *
     * @param string $token JWT token
     * @return array|WP_Error Decoded payload or error
     */
    public function verify_access_token(string $token) {
        try {
            $payload = $this->decode_jwt($token, $this->jwt_access_secret);

            // Validate required claims
            if (empty($payload['sub']) || empty($payload['user_type'])) {
                return new WP_Error(
                    'invalid_token',
                    __('Invalid token format', 'ma-deal-room'),
                    ['status' => 401]
                );
            }

            return $payload;
        } catch (\Exception $e) {
            return new WP_Error(
                'invalid_token',
                $e->getMessage(),
                ['status' => 401]
            );
        }
    }

    /**
     * Validate password strength
     *
     * @param string $password Password to validate
     * @return true|WP_Error True if valid, error otherwise
     */
    public function validate_password_strength(string $password) {
        $min_length = apply_filters('ma_deal_password_min_length', 8);

        if (strlen($password) < $min_length) {
            return new WP_Error(
                'weak_password',
                sprintf(__('Password must be at least %d characters long', 'ma-deal-room'), $min_length),
                ['status' => 400]
            );
        }

        // Check for complexity (at least one uppercase, one lowercase, one number)
        if (apply_filters('ma_deal_password_require_complexity', true)) {
            if (!preg_match('/[A-Z]/', $password)) {
                return new WP_Error(
                    'weak_password',
                    __('Password must contain at least one uppercase letter', 'ma-deal-room'),
                    ['status' => 400]
                );
            }

            if (!preg_match('/[a-z]/', $password)) {
                return new WP_Error(
                    'weak_password',
                    __('Password must contain at least one lowercase letter', 'ma-deal-room'),
                    ['status' => 400]
                );
            }

            if (!preg_match('/[0-9]/', $password)) {
                return new WP_Error(
                    'weak_password',
                    __('Password must contain at least one number', 'ma-deal-room'),
                    ['status' => 400]
                );
            }
        }

        return true;
    }

    /**
     * Encode JWT
     *
     * @param array $payload Payload data
     * @param string $secret Secret key
     * @return string JWT token
     */
    private function encode_jwt(array $payload, string $secret): string {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256',
        ];

        $header_encoded = $this->base64url_encode(json_encode($header));
        $payload_encoded = $this->base64url_encode(json_encode($payload));

        $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", $secret, true);
        $signature_encoded = $this->base64url_encode($signature);

        return "$header_encoded.$payload_encoded.$signature_encoded";
    }

    /**
     * Decode JWT
     *
     * @param string $token JWT token
     * @param string $secret Secret key
     * @return array Decoded payload
     * @throws \Exception If token is invalid
     */
    private function decode_jwt(string $token, string $secret): array {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new \Exception(__('Invalid token format', 'ma-deal-room'));
        }

        list($header_encoded, $payload_encoded, $signature_encoded) = $parts;

        // Verify signature
        $signature = $this->base64url_decode($signature_encoded);
        $expected_signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", $secret, true);

        if (!hash_equals($expected_signature, $signature)) {
            throw new \Exception(__('Invalid token signature', 'ma-deal-room'));
        }

        // Decode payload
        $payload = json_decode($this->base64url_decode($payload_encoded), true);

        if (!$payload) {
            throw new \Exception(__('Invalid token payload', 'ma-deal-room'));
        }

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new \Exception(__('Token has expired', 'ma-deal-room'));
        }

        return $payload;
    }

    /**
     * Base64 URL encode
     *
     * @param string $data Data to encode
     * @return string Encoded data
     */
    private function base64url_encode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     *
     * @param string $data Data to decode
     * @return string Decoded data
     */
    private function base64url_decode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Generate random token
     *
     * @param int $length Token length
     * @return string Random token
     */
    private function generate_random_token(int $length = 32): string {
        return bin2hex(random_bytes($length));
    }

    /**
     * Generate temporary token for password change
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return string Temporary token (5 minutes)
     */
    private function generate_temporary_token(int $user_id, string $user_type): string {
        $issued_at = time();
        $expiration = $issued_at + 300; // 5 minutes

        $payload = [
            'iat' => $issued_at,
            'exp' => $expiration,
            'sub' => $user_id,
            'user_type' => $user_type,
            'purpose' => 'password_change',
        ];

        return $this->encode_jwt($payload, $this->jwt_access_secret);
    }

    /**
     * Get JWT secret
     *
     * @param string $type Secret type (access|refresh)
     * @return string Secret key
     */
    private function get_jwt_secret(string $type): string {
        // Try multiple environment variable naming conventions
        $env_vars = [
            'JWT_' . strtoupper($type) . '_SECRET_KEY',  // JWT_ACCESS_SECRET_KEY, JWT_REFRESH_SECRET_KEY
            'MA_DEAL_JWT_' . strtoupper($type) . '_SECRET',  // MA_DEAL_JWT_ACCESS_SECRET, MA_DEAL_JWT_REFRESH_SECRET
        ];

        foreach ($env_vars as $env_var) {
            // Check both getenv() and $_ENV for maximum compatibility
            $secret = getenv($env_var) ?: ($_ENV[$env_var] ?? null);
            if ($secret) {
                return $secret;
            }
        }

        // Get from WordPress options as fallback
        $option_name = 'ma_deal_jwt_' . $type . '_secret';
        $secret = get_option($option_name);

        if (!$secret) {
            // Generate and save new secret
            $secret = bin2hex(random_bytes(32));
            update_option($option_name, $secret);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    'MA Deal Room: Generated new JWT %s secret. Consider setting %s in your .env file.',
                    $type,
                    $env_vars[0]
                ));
            }
        }

        return $secret;
    }

    /**
     * Get client IP address
     *
     * @return string IP address
     */
    private function get_client_ip(): string {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle X-Forwarded-For with multiple IPs
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Parse device name from user agent
     *
     * @param string $user_agent User agent string
     * @return string Device name
     */
    private function parse_device_name(string $user_agent): string {
        if (stripos($user_agent, 'Chrome') !== false) {
            return 'Chrome Browser';
        }
        if (stripos($user_agent, 'Firefox') !== false) {
            return 'Firefox Browser';
        }
        if (stripos($user_agent, 'Safari') !== false) {
            return 'Safari Browser';
        }
        if (stripos($user_agent, 'Edge') !== false) {
            return 'Edge Browser';
        }

        return 'Unknown Browser';
    }

    /**
     * Parse device type from user agent
     *
     * @param string $user_agent User agent string
     * @return string Device type (desktop|mobile|tablet)
     */
    private function parse_device_type(string $user_agent): string {
        if (stripos($user_agent, 'mobile') !== false) {
            return 'mobile';
        }
        if (stripos($user_agent, 'tablet') !== false || stripos($user_agent, 'ipad') !== false) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * Parse browser and platform information from user agent
     *
     * @param string $user_agent User agent string
     * @return array Array with 'browser' and 'platform' keys
     */
    private function parse_browser_info(string $user_agent): array {
        $browser = 'Unknown';
        $platform = 'Unknown';

        // Parse browser
        if (stripos($user_agent, 'Edg') !== false || stripos($user_agent, 'Edge') !== false) {
            $browser = 'Edge';
        } elseif (stripos($user_agent, 'Chrome') !== false) {
            $browser = 'Chrome';
        } elseif (stripos($user_agent, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (stripos($user_agent, 'Safari') !== false) {
            $browser = 'Safari';
        } elseif (stripos($user_agent, 'Opera') !== false || stripos($user_agent, 'OPR') !== false) {
            $browser = 'Opera';
        }

        // Parse platform/OS
        if (stripos($user_agent, 'Windows') !== false) {
            $platform = 'Windows';
        } elseif (stripos($user_agent, 'Macintosh') !== false || stripos($user_agent, 'Mac OS') !== false) {
            $platform = 'macOS';
        } elseif (stripos($user_agent, 'Linux') !== false) {
            $platform = 'Linux';
        } elseif (stripos($user_agent, 'iPhone') !== false || stripos($user_agent, 'iPad') !== false) {
            $platform = 'iOS';
        } elseif (stripos($user_agent, 'Android') !== false) {
            $platform = 'Android';
        }

        return [
            'browser' => $browser,
            'platform' => $platform,
        ];
    }

    /**
     * Format custom user response
     *
     * @param CustomUser $user User model
     * @param array $roles User roles
     * @return array Formatted user data
     */
    private function format_user_response(CustomUser $user, array $roles = []): array {
        return [
            'id' => $user->id,
            'user_type' => 'custom',
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->get_full_name(),
            'phone' => $user->phone,
            'status' => $user->status,
            'email_verified' => $user->email_verified,
            'roles' => array_map(function ($role) {
                return [
                    'role_type' => $role->role_type,
                    'is_primary' => $role->is_primary,
                    'transaction_id' => $role->transaction_id,
                ];
            }, $roles),
            'primary_role' => !empty($roles) ? $roles[0]->role_type : null,
        ];
    }

    /**
     * Format WordPress user response
     *
     * @param WP_User $wp_user WordPress user
     * @param array $roles User roles
     * @return array Formatted user data
     */
    private function format_wp_user_response(WP_User $wp_user, array $roles = []): array {
        return [
            'id' => $wp_user->ID,
            'user_type' => 'wordpress',
            'email' => $wp_user->user_email,
            'username' => $wp_user->user_login,
            'first_name' => $wp_user->first_name,
            'last_name' => $wp_user->last_name,
            'full_name' => trim($wp_user->first_name . ' ' . $wp_user->last_name) ?: $wp_user->display_name,
            'display_name' => $wp_user->display_name,
            'wp_roles' => $wp_user->roles,
            'roles' => array_map(function ($role) {
                return [
                    'role_type' => $role->role_type,
                    'is_primary' => $role->is_primary,
                    'transaction_id' => $role->transaction_id,
                ];
            }, $roles),
            'primary_role' => !empty($roles) ? $roles[0]->role_type : ($wp_user->roles[0] ?? null),
        ];
    }
}
