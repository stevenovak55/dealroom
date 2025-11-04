<?php
/**
 * Password Reset Service
 *
 * Handles password reset token generation and password reset workflow.
 *
 * @package    MADealRoom\Services
 * @since      2.0.0
 */

namespace MADealRoom\Services;

use MADealRoom\Repositories\CustomUserRepository;
use MADealRoom\Repositories\PasswordResetRepository;
use MADealRoom\Services\EmailService;
use MADealRoom\Services\AuthService;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * PasswordResetService Class
 *
 * Manages password reset tokens and sends reset emails.
 */
class PasswordResetService {

    /**
     * User repository
     *
     * @var CustomUserRepository
     */
    private $user_repo;

    /**
     * Password reset repository
     *
     * @var PasswordResetRepository
     */
    private $reset_repo;

    /**
     * Email service
     *
     * @var EmailService
     */
    private $email_service;

    /**
     * Auth service
     *
     * @var AuthService
     */
    private $auth_service;

    /**
     * Token expiration time (minutes)
     *
     * @var int
     */
    private $token_expiry_minutes = 60;

    /**
     * Rate limit (max requests per hour)
     *
     * @var int
     */
    private $rate_limit = 3;

    /**
     * Constructor
     */
    public function __construct() {
        $this->user_repo = new CustomUserRepository();
        $this->reset_repo = new PasswordResetRepository();
        $this->email_service = new EmailService();
        $this->auth_service = new AuthService();

        // Allow customization via filters
        $this->token_expiry_minutes = apply_filters('ma_deal_password_reset_expiry_minutes', $this->token_expiry_minutes);
        $this->rate_limit = apply_filters('ma_deal_password_reset_rate_limit', $this->rate_limit);
    }

    /**
     * Request password reset
     *
     * @param string $email Email address
     * @return true|WP_Error True on success, error on failure
     */
    public function request_password_reset(string $email) {
        // Validate email format
        if (!is_email($email)) {
            return new WP_Error(
                'invalid_email',
                __('Invalid email address', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Check rate limit
        $recent_requests = $this->reset_repo->count_recent_requests($email, 60);
        if ($recent_requests >= $this->rate_limit) {
            return new WP_Error(
                'rate_limit_exceeded',
                sprintf(__('Too many password reset requests. Please wait before trying again. Limit: %d per hour.', 'ma-deal-room'), $this->rate_limit),
                ['status' => 429]
            );
        }

        // Find user (custom or WordPress)
        $custom_user = $this->user_repo->find_by_email($email);
        $wp_user = get_user_by('email', $email);

        if (!$custom_user && !$wp_user) {
            // Don't reveal if email exists or not (security best practice)
            // Return success to prevent email enumeration
            return true;
        }

        $user_type = $custom_user ? 'custom' : 'wordpress';
        $user_name = $custom_user ? $custom_user->get_full_name() : $wp_user->display_name;

        // Generate reset token
        $token = $this->generate_reset_token();
        $token_hash = hash('sha256', $token);

        // Store token in database
        $ip_address = $this->get_client_ip();
        $token_id = $this->reset_repo->create_reset_token(
            $email,
            $token_hash,
            $user_type,
            $ip_address,
            $this->token_expiry_minutes
        );

        if (!$token_id) {
            return new WP_Error(
                'token_creation_failed',
                __('Failed to create reset token', 'ma-deal-room'),
                ['status' => 500]
            );
        }

        // Generate reset URL
        $reset_url = $this->get_reset_url($token);

        // Send reset email
        $email_sent = $this->email_service->send_password_reset(
            $email,
            $user_name,
            $reset_url,
            $this->token_expiry_minutes
        );

        if (is_wp_error($email_sent)) {
            return $email_sent;
        }

        // Log event
        do_action('ma_deal_password_reset_requested', $email, $user_type);

        return true;
    }

    /**
     * Validate reset token
     *
     * @param string $token Reset token
     * @return true|WP_Error True if valid, error otherwise
     */
    public function validate_reset_token(string $token) {
        $token_hash = hash('sha256', $token);

        // Check if token is valid
        if (!$this->reset_repo->is_valid_token($token_hash)) {
            return new WP_Error(
                'invalid_token',
                __('Invalid or expired reset token', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        return true;
    }

    /**
     * Reset password with token
     *
     * @param string $token Reset token
     * @param string $new_password New password
     * @return array|WP_Error Success data or error
     */
    public function reset_password(string $token, string $new_password) {
        $token_hash = hash('sha256', $token);

        // Find token
        $reset = $this->reset_repo->find_by_token($token_hash);

        if (!$reset) {
            return new WP_Error(
                'invalid_token',
                __('Invalid or expired reset token', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Validate password strength
        $password_validation = $this->auth_service->validate_password_strength($new_password);
        if (is_wp_error($password_validation)) {
            return $password_validation;
        }

        // Update password based on user type
        if ($reset->user_type === 'custom') {
            $user = $this->user_repo->find_by_email($reset->email);

            if (!$user) {
                return new WP_Error(
                    'user_not_found',
                    __('User not found', 'ma-deal-room'),
                    ['status' => 404]
                );
            }

            // Update password
            $updated = $this->user_repo->update_password($user->id, $new_password);

            if (!$updated) {
                return new WP_Error(
                    'password_update_failed',
                    __('Failed to update password', 'ma-deal-room'),
                    ['status' => 500]
                );
            }

            $user_id = $user->id;
            $user_name = $user->get_full_name();
            $user_email = $user->email;
        } else {
            // WordPress user
            $wp_user = get_user_by('email', $reset->email);

            if (!$wp_user) {
                return new WP_Error(
                    'user_not_found',
                    __('User not found', 'ma-deal-room'),
                    ['status' => 404]
                );
            }

            // Update password
            wp_set_password($new_password, $wp_user->ID);

            $user_id = $wp_user->ID;
            $user_name = $wp_user->display_name;
            $user_email = $wp_user->user_email;
        }

        // Mark token as used
        $this->reset_repo->mark_as_used($token_hash);

        // Revoke all user sessions (force re-login with new password)
        $this->auth_service->logout_all($user_id, $reset->user_type);

        // Send confirmation email
        $this->email_service->send_password_changed_notification($user_email, $user_name);

        // Log event
        do_action('ma_deal_password_reset_completed', $user_id, $reset->user_type);

        return [
            'success' => true,
            'message' => __('Password successfully reset. You can now log in with your new password.', 'ma-deal-room'),
        ];
    }

    /**
     * Change password (when user is logged in)
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param string $current_password Current password
     * @param string $new_password New password
     * @return true|WP_Error True on success, error on failure
     */
    public function change_password(int $user_id, string $user_type, string $current_password, string $new_password) {
        // Get user
        if ($user_type === 'custom') {
            $user = $this->user_repo->find($user_id);

            if (!$user) {
                return new WP_Error(
                    'user_not_found',
                    __('User not found', 'ma-deal-room'),
                    ['status' => 404]
                );
            }

            // Verify current password
            if (!$user->verify_password($current_password)) {
                return new WP_Error(
                    'invalid_password',
                    __('Current password is incorrect', 'ma-deal-room'),
                    ['status' => 400]
                );
            }

            $user_email = $user->email;
            $user_name = $user->get_full_name();
        } else {
            $wp_user = get_user_by('ID', $user_id);

            if (!$wp_user) {
                return new WP_Error(
                    'user_not_found',
                    __('User not found', 'ma-deal-room'),
                    ['status' => 404]
                );
            }

            // Verify current password
            if (!wp_check_password($current_password, $wp_user->user_pass, $wp_user->ID)) {
                return new WP_Error(
                    'invalid_password',
                    __('Current password is incorrect', 'ma-deal-room'),
                    ['status' => 400]
                );
            }

            $user_email = $wp_user->user_email;
            $user_name = $wp_user->display_name;
        }

        // Validate new password strength
        $password_validation = $this->auth_service->validate_password_strength($new_password);
        if (is_wp_error($password_validation)) {
            return $password_validation;
        }

        // Check if new password is same as current
        if ($current_password === $new_password) {
            return new WP_Error(
                'same_password',
                __('New password must be different from current password', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Update password
        if ($user_type === 'custom') {
            $updated = $this->user_repo->update_password($user_id, $new_password);
        } else {
            wp_set_password($new_password, $user_id);
            $updated = true;
        }

        if (!$updated) {
            return new WP_Error(
                'password_update_failed',
                __('Failed to update password', 'ma-deal-room'),
                ['status' => 500]
            );
        }

        // Send confirmation email
        $this->email_service->send_password_changed_notification($user_email, $user_name);

        // Log event
        do_action('ma_deal_password_changed', $user_id, $user_type);

        return true;
    }

    /**
     * Cleanup expired reset tokens
     *
     * @return int Number of tokens cleaned up
     */
    public function cleanup_expired_tokens(): int {
        return $this->reset_repo->cleanup_expired_tokens();
    }

    /**
     * Get password reset statistics
     *
     * @param int $days Number of days to analyze
     * @return array Statistics
     */
    public function get_reset_stats(int $days = 30): array {
        return $this->reset_repo->get_reset_stats($days);
    }

    /**
     * Generate reset token
     *
     * @return string Random token
     */
    private function generate_reset_token(): string {
        return bin2hex(random_bytes(32)); // 64 character hex string
    }

    /**
     * Get reset URL
     *
     * @param string $token Reset token
     * @return string Reset URL
     */
    private function get_reset_url(string $token): string {
        $base_url = apply_filters('ma_deal_reset_password_url_base', home_url('/reset-password'));
        return add_query_arg('token', $token, $base_url);
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
}
