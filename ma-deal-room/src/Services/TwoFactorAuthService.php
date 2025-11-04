<?php
/**
 * Two-Factor Authentication Service
 *
 * Handles TOTP (Time-based One-Time Password) generation, validation, and backup codes.
 *
 * @package    MADealRoom\Services
 * @since      2.0.0
 */

namespace MADealRoom\Services;

use MADealRoom\Repositories\TwoFactorRepository;
use MADealRoom\Repositories\CustomUserRepository;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * TwoFactorAuthService Class
 *
 * Manages two-factor authentication using TOTP (compatible with Google Authenticator, Authy, etc.).
 */
class TwoFactorAuthService {

    /**
     * Two-factor repository
     *
     * @var TwoFactorRepository
     */
    private $twofa_repo;

    /**
     * User repository
     *
     * @var CustomUserRepository
     */
    private $user_repo;

    /**
     * TOTP period (seconds)
     *
     * @var int
     */
    private $period = 30;

    /**
     * TOTP digits
     *
     * @var int
     */
    private $digits = 6;

    /**
     * Number of backup codes to generate
     *
     * @var int
     */
    private $backup_codes_count = 10;

    /**
     * Constructor
     */
    public function __construct() {
        $this->twofa_repo = new TwoFactorRepository();
        $this->user_repo = new CustomUserRepository();
    }

    /**
     * Enable 2FA for user (setup phase)
     *
     * @param int $user_id User ID
     * @param string $user_type User type (custom|wordpress)
     * @return array|WP_Error Setup data with QR code or error
     */
    public function enable_2fa(int $user_id, string $user_type) {
        // Check if 2FA is already enabled
        if ($this->twofa_repo->is_2fa_enabled($user_id, $user_type)) {
            return new WP_Error(
                '2fa_already_enabled',
                __('Two-factor authentication is already enabled', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Get user info
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

        // Generate TOTP secret
        $secret = $this->generate_secret();

        // Generate backup codes
        $backup_codes = $this->generate_backup_codes();
        $hashed_backup_codes = array_map(function ($code) {
            return password_hash($code, PASSWORD_BCRYPT, ['cost' => 10]);
        }, $backup_codes);

        // Store in database (not enabled yet - requires verification)
        $secret_id = $this->twofa_repo->create_secret(
            $user_id,
            $user_type,
            $this->encrypt_secret($secret),
            $hashed_backup_codes
        );

        if (!$secret_id) {
            return new WP_Error(
                '2fa_setup_failed',
                __('Failed to setup two-factor authentication', 'ma-deal-room'),
                ['status' => 500]
            );
        }

        // Generate QR code data URL
        $qr_code_url = $this->get_qr_code_url($secret, $email, $name);

        // Log event
        do_action('ma_deal_2fa_setup_initiated', $user_id, $user_type);

        return [
            'secret' => $secret,
            'qr_code_url' => $qr_code_url,
            'backup_codes' => $backup_codes,
            'manual_entry_key' => $this->format_secret_for_manual_entry($secret),
            'message' => __('Scan the QR code with your authenticator app and verify with a code to complete setup.', 'ma-deal-room'),
        ];
    }

    /**
     * Verify 2FA setup (complete enable process)
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param string $code TOTP code from authenticator app
     * @return true|WP_Error True on success, error on failure
     */
    public function verify_2fa_setup(int $user_id, string $user_type, string $code) {
        // Get secret
        $secret_record = $this->twofa_repo->get_user_secret($user_id, $user_type);

        if (!$secret_record) {
            return new WP_Error(
                '2fa_not_setup',
                __('Two-factor authentication setup not found. Please start the setup process.', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Decrypt secret
        $secret = $this->decrypt_secret($secret_record->secret);

        // Verify code
        if (!$this->verify_totp($secret, $code)) {
            return new WP_Error(
                'invalid_code',
                __('Invalid verification code. Please try again.', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Enable 2FA
        $enabled = $this->twofa_repo->enable_2fa($user_id, $user_type);

        if (!$enabled) {
            return new WP_Error(
                '2fa_enable_failed',
                __('Failed to enable two-factor authentication', 'ma-deal-room'),
                ['status' => 500]
            );
        }

        // Log event
        do_action('ma_deal_2fa_enabled', $user_id, $user_type);

        return true;
    }

    /**
     * Verify 2FA code during login
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param string $code TOTP code or backup code
     * @return true|WP_Error True on success, error on failure
     */
    public function verify_2fa_code(int $user_id, string $user_type, string $code) {
        // Get secret
        $secret_record = $this->twofa_repo->get_user_secret($user_id, $user_type);

        if (!$secret_record || !$secret_record->enabled_at) {
            return new WP_Error(
                '2fa_not_enabled',
                __('Two-factor authentication is not enabled', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Decrypt secret
        $secret = $this->decrypt_secret($secret_record->secret);

        // Try TOTP verification first
        if ($this->verify_totp($secret, $code)) {
            // TOTP Replay Prevention: Check if this time window was already used
            $current_totp_timestamp = floor(time() / $this->period);
            $last_totp_timestamp = $this->get_last_totp_timestamp($user_id, $user_type);

            if ($last_totp_timestamp && $current_totp_timestamp <= $last_totp_timestamp) {
                return new WP_Error(
                    'totp_already_used',
                    __('This verification code was already used. Please wait for a new code.', 'ma-deal-room'),
                    ['status' => 400]
                );
            }

            // Store current TOTP timestamp to prevent replay
            $this->store_totp_timestamp($user_id, $user_type, $current_totp_timestamp);

            // Update last used timestamp
            $this->twofa_repo->mark_as_used($user_id, $user_type);

            // Log event
            do_action('ma_deal_2fa_verified', $user_id, $user_type, 'totp');

            return true;
        }

        // Try backup codes
        if ($this->verify_backup_code($secret_record->backup_codes, $code, $user_id, $user_type)) {
            // Log event
            do_action('ma_deal_2fa_verified', $user_id, $user_type, 'backup_code');

            return true;
        }

        return new WP_Error(
            'invalid_code',
            __('Invalid verification code', 'ma-deal-room'),
            ['status' => 401]
        );
    }

    /**
     * Disable 2FA for user
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param string $password User's password for confirmation
     * @return true|WP_Error True on success, error on failure
     */
    public function disable_2fa(int $user_id, string $user_type, string $password) {
        // Verify password
        if ($user_type === 'custom') {
            $user = $this->user_repo->find($user_id);
            if (!$user || !$user->verify_password($password)) {
                return new WP_Error(
                    'invalid_password',
                    __('Invalid password', 'ma-deal-room'),
                    ['status' => 401]
                );
            }
        } else {
            $wp_user = get_user_by('ID', $user_id);
            if (!$wp_user || !wp_check_password($password, $wp_user->user_pass, $wp_user->ID)) {
                return new WP_Error(
                    'invalid_password',
                    __('Invalid password', 'ma-deal-room'),
                    ['status' => 401]
                );
            }
        }

        // Disable 2FA
        $disabled = $this->twofa_repo->disable_2fa($user_id, $user_type);

        if (!$disabled) {
            return new WP_Error(
                '2fa_disable_failed',
                __('Failed to disable two-factor authentication', 'ma-deal-room'),
                ['status' => 500]
            );
        }

        // Log event
        do_action('ma_deal_2fa_disabled', $user_id, $user_type);

        return true;
    }

    /**
     * Regenerate backup codes
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param string $password User's password for confirmation
     * @return array|WP_Error New backup codes or error
     */
    public function regenerate_backup_codes(int $user_id, string $user_type, string $password) {
        // Verify password
        if ($user_type === 'custom') {
            $user = $this->user_repo->find($user_id);
            if (!$user || !$user->verify_password($password)) {
                return new WP_Error(
                    'invalid_password',
                    __('Invalid password', 'ma-deal-room'),
                    ['status' => 401]
                );
            }
        } else {
            $wp_user = get_user_by('ID', $user_id);
            if (!$wp_user || !wp_check_password($password, $wp_user->user_pass, $wp_user->ID)) {
                return new WP_Error(
                    'invalid_password',
                    __('Invalid password', 'ma-deal-room'),
                    ['status' => 401]
                );
            }
        }

        // Check if 2FA is enabled
        if (!$this->twofa_repo->is_2fa_enabled($user_id, $user_type)) {
            return new WP_Error(
                '2fa_not_enabled',
                __('Two-factor authentication is not enabled', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Generate new backup codes
        $backup_codes = $this->generate_backup_codes();
        $hashed_backup_codes = array_map(function ($code) {
            return password_hash($code, PASSWORD_BCRYPT, ['cost' => 10]);
        }, $backup_codes);

        // Update in database
        $updated = $this->twofa_repo->update_backup_codes($user_id, $user_type, $hashed_backup_codes);

        if (!$updated) {
            return new WP_Error(
                'backup_codes_failed',
                __('Failed to regenerate backup codes', 'ma-deal-room'),
                ['status' => 500]
            );
        }

        // Log event
        do_action('ma_deal_2fa_backup_codes_regenerated', $user_id, $user_type);

        return [
            'backup_codes' => $backup_codes,
            'message' => __('New backup codes generated. Please save them in a secure location.', 'ma-deal-room'),
        ];
    }

    /**
     * Check if 2FA is enabled for user
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return bool
     */
    public function is_2fa_enabled(int $user_id, string $user_type): bool {
        return $this->twofa_repo->is_2fa_enabled($user_id, $user_type);
    }

    /**
     * Generate TOTP secret
     *
     * @return string Base32 encoded secret
     */
    private function generate_secret(): string {
        $secret = random_bytes(20);
        return $this->base32_encode($secret);
    }

    /**
     * Generate backup codes
     *
     * @return array Array of backup codes
     */
    private function generate_backup_codes(): array {
        $codes = [];
        for ($i = 0; $i < $this->backup_codes_count; $i++) {
            $codes[] = $this->generate_backup_code();
        }
        return $codes;
    }

    /**
     * Generate single backup code
     *
     * @return string 8-digit backup code
     */
    private function generate_backup_code(): string {
        return str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    }

    /**
     * Verify TOTP code
     *
     * @param string $secret TOTP secret
     * @param string $code User-provided code
     * @param int $window Time window to check (±window periods)
     * @return bool
     */
    private function verify_totp(string $secret, string $code, int $window = 1): bool {
        $current_time = time();

        // Check current time and ±window periods
        for ($i = -$window; $i <= $window; $i++) {
            $timestamp = $current_time + ($i * $this->period);
            $valid_code = $this->generate_totp($secret, $timestamp);

            if (hash_equals($valid_code, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate TOTP code
     *
     * @param string $secret TOTP secret
     * @param int|null $timestamp Timestamp (null = current time)
     * @return string TOTP code
     */
    private function generate_totp(string $secret, ?int $timestamp = null): string {
        $timestamp = $timestamp ?? time();
        $time = pack('N*', 0) . pack('N*', floor($timestamp / $this->period));

        $secret_key = $this->base32_decode($secret);
        $hash = hash_hmac('sha1', $time, $secret_key, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0xf;
        $code = (
            ((ord($hash[$offset + 0]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % pow(10, $this->digits);

        return str_pad((string) $code, $this->digits, '0', STR_PAD_LEFT);
    }

    /**
     * Verify backup code
     *
     * @param array $hashed_codes Array of hashed backup codes
     * @param string $code User-provided code
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return bool
     */
    private function verify_backup_code(array $hashed_codes, string $code, int $user_id, string $user_type): bool {
        foreach ($hashed_codes as $index => $hashed_code) {
            if (password_verify($code, $hashed_code)) {
                // Remove used backup code
                unset($hashed_codes[$index]);
                $this->twofa_repo->update_backup_codes($user_id, $user_type, array_values($hashed_codes));
                return true;
            }
        }

        return false;
    }

    /**
     * Get QR code URL for TOTP setup
     *
     * @param string $secret TOTP secret
     * @param string $email User email
     * @param string $name User name
     * @return string QR code image URL
     */
    private function get_qr_code_url(string $secret, string $email, string $name): string {
        $issuer = apply_filters('ma_deal_2fa_issuer', get_bloginfo('name'));
        $label = urlencode($issuer) . ':' . urlencode($email);

        $otpauth = sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            $label,
            $secret,
            urlencode($issuer),
            $this->digits,
            $this->period
        );

        // Use Google Charts API for QR code generation
        return sprintf(
            'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=%s',
            urlencode($otpauth)
        );
    }

    /**
     * Format secret for manual entry
     *
     * @param string $secret Secret key
     * @return string Formatted secret (with spaces every 4 characters)
     */
    private function format_secret_for_manual_entry(string $secret): string {
        return chunk_split($secret, 4, ' ');
    }

    /**
     * Encrypt TOTP secret for storage
     *
     * @param string $secret Plain secret
     * @return string Encrypted secret
     */
    private function encrypt_secret(string $secret): string {
        // Use WordPress AUTH_KEY as encryption key
        $key = hash('sha256', AUTH_KEY, true);
        $iv = random_bytes(16);

        $encrypted = openssl_encrypt($secret, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt TOTP secret
     *
     * @param string $encrypted_secret Encrypted secret
     * @return string Plain secret
     */
    private function decrypt_secret(string $encrypted_secret): string {
        $key = hash('sha256', AUTH_KEY, true);
        $data = base64_decode($encrypted_secret);

        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);

        return openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * Base32 encode
     *
     * @param string $data Data to encode
     * @return string Base32 encoded string
     */
    private function base32_encode(string $data): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;

        for ($i = 0, $j = strlen($data); $i < $j; $i++) {
            $v <<= 8;
            $v += ord($data[$i]);
            $vbits += 8;

            while ($vbits >= 5) {
                $vbits -= 5;
                $output .= $alphabet[$v >> $vbits & 0x1f];
            }
        }

        if ($vbits > 0) {
            $v <<= (5 - $vbits);
            $output .= $alphabet[$v & 0x1f];
        }

        return $output;
    }

    /**
     * Base32 decode
     *
     * @param string $data Base32 encoded string
     * @return string Decoded data
     */
    private function base32_decode(string $data): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;
        $data = strtoupper($data);

        for ($i = 0, $j = strlen($data); $i < $j; $i++) {
            $v <<= 5;
            $v += stripos($alphabet, $data[$i]);
            $vbits += 5;

            while ($vbits >= 8) {
                $vbits -= 8;
                $output .= chr($v >> $vbits & 0xff);
            }
        }

        return $output;
    }

    /**
     * Get last TOTP timestamp for user (replay prevention)
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return int|null Last TOTP timestamp or null
     */
    private function get_last_totp_timestamp(int $user_id, string $user_type): ?int {
        global $wpdb;

        $table = $wpdb->prefix . 'ma_deal_2fa_secrets';
        $timestamp = $wpdb->get_var($wpdb->prepare(
            "SELECT last_totp_timestamp FROM {$table} WHERE user_id = %d AND user_type = %s",
            $user_id,
            $user_type
        ));

        return $timestamp ? (int) $timestamp : null;
    }

    /**
     * Store TOTP timestamp for user (replay prevention)
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param int $timestamp TOTP timestamp
     * @return bool Success status
     */
    private function store_totp_timestamp(int $user_id, string $user_type, int $timestamp): bool {
        global $wpdb;

        $table = $wpdb->prefix . 'ma_deal_2fa_secrets';
        $result = $wpdb->update(
            $table,
            ['last_totp_timestamp' => $timestamp],
            [
                'user_id' => $user_id,
                'user_type' => $user_type
            ],
            ['%d'],
            ['%d', '%s']
        );

        return $result !== false;
    }
}
