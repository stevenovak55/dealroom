<?php
/**
 * Email Verification Service
 *
 * Handles email verification token generation and verification workflow.
 *
 * @package    MADealRoom\Services
 * @since      2.0.0
 */

namespace MADealRoom\Services;

use MADealRoom\Repositories\CustomUserRepository;
use MADealRoom\Repositories\EmailVerificationRepository;
use MADealRoom\Services\EmailService;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * EmailVerificationService Class
 *
 * Manages email verification tokens and sends verification emails.
 */
class EmailVerificationService {

    /**
     * User repository
     *
     * @var CustomUserRepository
     */
    private $user_repo;

    /**
     * Email verification repository
     *
     * @var EmailVerificationRepository
     */
    private $verification_repo;

    /**
     * Email service
     *
     * @var EmailService
     */
    private $email_service;

    /**
     * Token expiration time (hours)
     *
     * @var int
     */
    private $token_expiry_hours = 24;

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
        $this->verification_repo = new EmailVerificationRepository();
        $this->email_service = new EmailService();

        // Allow customization via filters
        $this->token_expiry_hours = apply_filters('ma_deal_email_verification_expiry_hours', $this->token_expiry_hours);
        $this->rate_limit = apply_filters('ma_deal_email_verification_rate_limit', $this->rate_limit);
    }

    /**
     * Send verification email
     *
     * @param int $user_id User ID
     * @param string $user_type User type (custom|wordpress)
     * @return true|WP_Error True on success, error on failure
     */
    public function send_verification_email(int $user_id, string $user_type) {
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
            $email = $user->email;
            $name = $user->get_full_name();
        } else {
            $wp_user = get_user_by('ID', $user_id);
            if (!$wp_user) {
                return new WP_Error(
                    'user_not_found',
                    __('User not found', 'ma-deal-room'),
                    ['status' => 404]
                );
            }
            $email = $wp_user->user_email;
            $name = $wp_user->display_name;
        }

        // Check if already verified
        if ($this->verification_repo->is_email_verified($user_id, $user_type)) {
            return new WP_Error(
                'already_verified',
                __('Email address is already verified', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Check rate limit
        $recent_requests = $this->verification_repo->count_recent_requests($user_id, $user_type, 60);
        if ($recent_requests >= $this->rate_limit) {
            return new WP_Error(
                'rate_limit_exceeded',
                sprintf(__('Too many verification emails sent. Please wait before requesting another. Limit: %d per hour.', 'ma-deal-room'), $this->rate_limit),
                ['status' => 429]
            );
        }

        // Generate verification token
        $token = $this->generate_verification_token();
        $token_hash = hash('sha256', $token);

        // Store token in database
        $ip_address = $this->get_client_ip();
        $token_id = $this->verification_repo->create_verification_token(
            $user_id,
            $user_type,
            $email,
            $token_hash,
            $ip_address,
            $this->token_expiry_hours
        );

        if (!$token_id) {
            return new WP_Error(
                'token_creation_failed',
                __('Failed to create verification token', 'ma-deal-room'),
                ['status' => 500]
            );
        }

        // Generate verification URL
        $verification_url = $this->get_verification_url($token);

        // Send verification email
        $email_sent = $this->email_service->send_email_verification(
            $email,
            $name,
            $verification_url,
            $this->token_expiry_hours
        );

        if (is_wp_error($email_sent)) {
            return $email_sent;
        }

        // Log event
        do_action('ma_deal_verification_email_sent', $user_id, $user_type, $email);

        return true;
    }

    /**
     * Verify email with token
     *
     * @param string $token Verification token
     * @return array|WP_Error User data on success, error on failure
     */
    public function verify_email(string $token) {
        $token_hash = hash('sha256', $token);

        // Find token
        $verification = $this->verification_repo->find_by_token($token_hash);

        if (!$verification) {
            return new WP_Error(
                'invalid_token',
                __('Invalid or expired verification token', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Mark token as verified
        $marked = $this->verification_repo->mark_as_verified($token_hash);

        if (!$marked) {
            return new WP_Error(
                'verification_failed',
                __('Failed to verify email', 'ma-deal-room'),
                ['status' => 500]
            );
        }

        // Update user's email verification status
        if ($verification->user_type === 'custom') {
            $verified = $this->user_repo->mark_email_verified($verification->user_id);

            if (!$verified) {
                return new WP_Error(
                    'user_update_failed',
                    __('Failed to update user verification status', 'ma-deal-room'),
                    ['status' => 500]
                );
            }

            $user = $this->user_repo->find($verification->user_id);
        } else {
            // For WordPress users, update user meta
            update_user_meta($verification->user_id, 'ma_deal_email_verified', true);
            update_user_meta($verification->user_id, 'ma_deal_email_verified_at', current_time('mysql'));
            $user = get_user_by('ID', $verification->user_id);
        }

        // Log event
        do_action('ma_deal_email_verified', $verification->user_id, $verification->user_type);

        return [
            'verified' => true,
            'user_id' => $verification->user_id,
            'user_type' => $verification->user_type,
            'message' => __('Email successfully verified', 'ma-deal-room'),
        ];
    }

    /**
     * Resend verification email
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return true|WP_Error True on success, error on failure
     */
    public function resend_verification_email(int $user_id, string $user_type) {
        // Revoke any existing tokens
        $this->verification_repo->revoke_all_tokens($user_id, $user_type);

        // Send new verification email
        return $this->send_verification_email($user_id, $user_type);
    }

    /**
     * Check if email is verified
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return bool
     */
    public function is_email_verified(int $user_id, string $user_type): bool {
        if ($user_type === 'custom') {
            $user = $this->user_repo->find($user_id);
            return $user && $user->is_email_verified();
        } else {
            return (bool) get_user_meta($user_id, 'ma_deal_email_verified', true);
        }
    }

    /**
     * Get pending verification for user
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return object|null Verification record
     */
    public function get_pending_verification(int $user_id, string $user_type): ?object {
        return $this->verification_repo->get_pending_verification($user_id, $user_type);
    }

    /**
     * Send reminder emails for pending verifications
     *
     * @param int $hours_before_expiry Send reminder this many hours before expiry
     * @return int Number of reminders sent
     */
    public function send_verification_reminders(int $hours_before_expiry = 6): int {
        $expiring = $this->verification_repo->get_expiring_verifications($hours_before_expiry);
        $sent_count = 0;

        foreach ($expiring as $verification) {
            // Get user info
            if ($verification['user_type'] === 'custom') {
                $user = $this->user_repo->find($verification['user_id']);
                if (!$user) {
                    continue;
                }
                $email = $user->email;
                $name = $user->get_full_name();
            } else {
                $wp_user = get_user_by('ID', $verification['user_id']);
                if (!$wp_user) {
                    continue;
                }
                $email = $wp_user->user_email;
                $name = $wp_user->display_name;
            }

            // Recreate verification URL from stored token
            // Note: In production, you might want to store the plain token temporarily or regenerate
            $verification_url = home_url('/verify-email'); // Simplified for reminder

            // Send reminder email
            $email_sent = $this->email_service->send_email_verification_reminder(
                $email,
                $name,
                $verification_url,
                $hours_before_expiry
            );

            if (!is_wp_error($email_sent)) {
                $sent_count++;
            }
        }

        return $sent_count;
    }

    /**
     * Cleanup expired verification tokens
     *
     * @return int Number of tokens cleaned up
     */
    public function cleanup_expired_tokens(): int {
        return $this->verification_repo->cleanup_expired_tokens();
    }

    /**
     * Get verification statistics
     *
     * @param int $days Number of days to analyze
     * @return array Statistics
     */
    public function get_verification_stats(int $days = 30): array {
        return $this->verification_repo->get_verification_stats($days);
    }

    /**
     * Generate verification token
     *
     * @return string Random token
     */
    private function generate_verification_token(): string {
        return bin2hex(random_bytes(32)); // 64 character hex string
    }

    /**
     * Get verification URL
     *
     * @param string $token Verification token
     * @return string Verification URL
     */
    private function get_verification_url(string $token): string {
        // Use a dedicated verification handler page
        // This is a simple non-authenticated page that verifies email and redirects
        $url = home_url('/index.php?ma_verify_email=' . urlencode($token));
        error_log('DEBUG: Generated verification URL: ' . $url);
        return $url;
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
