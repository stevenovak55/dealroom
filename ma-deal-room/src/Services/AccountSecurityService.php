<?php
/**
 * Account Security Service
 *
 * Handles security event tracking, failed login monitoring, and suspicious activity detection.
 *
 * @package    MADealRoom\Services
 * @since      2.0.0
 */

namespace MADealRoom\Services;

use MADealRoom\Repositories\CustomUserRepository;
use MADealRoom\Services\EmailService;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AccountSecurityService Class
 *
 * Monitors and manages account security events and suspicious activity.
 */
class AccountSecurityService {

    /**
     * User repository
     *
     * @var CustomUserRepository
     */
    private $user_repo;

    /**
     * Email service
     *
     * @var EmailService
     */
    private $email_service;

    /**
     * Max failed login attempts before lockout
     *
     * @var int
     */
    private $max_failed_attempts = 5;

    /**
     * Lockout duration (minutes)
     *
     * @var int
     */
    private $lockout_duration = 30;

    /**
     * Failed attempt window (minutes)
     *
     * @var int
     */
    private $attempt_window = 15;

    /**
     * Security event types
     *
     * @var array
     */
    private $event_types = [
        'login_success',
        'login_failure',
        'password_changed',
        'password_reset_requested',
        'password_reset_completed',
        'email_verified',
        '2fa_enabled',
        '2fa_disabled',
        '2fa_backup_used',
        'account_locked',
        'account_unlocked',
        'suspicious_activity',
        'session_revoked',
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->user_repo = new CustomUserRepository();
        $this->email_service = new EmailService();

        // Allow customization via filters
        $this->max_failed_attempts = apply_filters('ma_deal_max_failed_attempts', $this->max_failed_attempts);
        $this->lockout_duration = apply_filters('ma_deal_lockout_duration', $this->lockout_duration);
        $this->attempt_window = apply_filters('ma_deal_attempt_window', $this->attempt_window);
    }

    /**
     * Record security event
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param string $event_type Event type
     * @param array $metadata Event metadata
     * @param string $ip_address IP address
     * @return int|false Event ID or false on failure
     */
    public function record_event(int $user_id, string $user_type, string $event_type, array $metadata = [], ?string $ip_address = null): int|false {
        global $wpdb;

        if (!in_array($event_type, $this->event_types)) {
            return false;
        }

        $ip_address = $ip_address ?? $this->get_client_ip();
        $table = $wpdb->prefix . 'ma_deal_security_events';

        $result = $wpdb->insert(
            $table,
            [
                'user_id' => $user_id,
                'user_type' => $user_type,
                'event_type' => $event_type,
                'ip_address' => $ip_address,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'metadata' => !empty($metadata) ? wp_json_encode($metadata) : null,
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Record failed login attempt
     *
     * @param string $email Email or username
     * @param string $ip_address IP address
     * @param string $reason Failure reason
     * @return array Status and lockout info
     */
    public function record_failed_login(string $email, ?string $ip_address = null, string $reason = 'invalid_credentials'): array {
        $ip_address = $ip_address ?? $this->get_client_ip();

        // Get or create failed attempt record
        $attempts = $this->get_failed_attempts($email, $ip_address);

        // Check if account should be locked
        if ($attempts >= $this->max_failed_attempts) {
            $this->lock_account($email);

            return [
                'locked' => true,
                'attempts' => $attempts,
                'lockout_duration' => $this->lockout_duration,
                'message' => sprintf(
                    __('Account locked due to too many failed login attempts. Please try again in %d minutes.', 'ma-deal-room'),
                    $this->lockout_duration
                ),
            ];
        }

        return [
            'locked' => false,
            'attempts' => $attempts,
            'remaining' => $this->max_failed_attempts - $attempts,
            'message' => sprintf(
                __('Invalid credentials. %d attempts remaining before account lockout.', 'ma-deal-room'),
                $this->max_failed_attempts - $attempts
            ),
        ];
    }

    /**
     * Record successful login
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param string $ip_address IP address
     * @return void
     */
    public function record_successful_login(int $user_id, string $user_type, ?string $ip_address = null): void {
        $ip_address = $ip_address ?? $this->get_client_ip();

        // Clear failed attempts
        $this->clear_failed_attempts_by_user($user_id, $user_type);

        // Record security event
        $this->record_event($user_id, $user_type, 'login_success', [], $ip_address);

        // Check for suspicious activity
        $this->check_suspicious_login($user_id, $user_type, $ip_address);
    }

    /**
     * Check if account is locked
     *
     * @param string $email Email
     * @return array|false Lockout info or false if not locked
     */
    public function is_account_locked(string $email): array|false {
        // Check custom users
        $custom_user = $this->user_repo->find_by_email($email);
        if ($custom_user && $custom_user->is_locked()) {
            $locked_until = strtotime($custom_user->locked_until);
            $now = time();

            if ($locked_until > $now) {
                return [
                    'locked' => true,
                    'locked_until' => $custom_user->locked_until,
                    'minutes_remaining' => ceil(($locked_until - $now) / 60),
                ];
            } else {
                // Lockout expired, unlock account
                $this->unlock_account($email);
                return false;
            }
        }

        // Check WordPress users
        $wp_user = get_user_by('email', $email);
        if ($wp_user) {
            $locked_until = get_user_meta($wp_user->ID, 'ma_deal_locked_until', true);
            if ($locked_until) {
                $locked_timestamp = strtotime($locked_until);
                $now = time();

                if ($locked_timestamp > $now) {
                    return [
                        'locked' => true,
                        'locked_until' => $locked_until,
                        'minutes_remaining' => ceil(($locked_timestamp - $now) / 60),
                    ];
                } else {
                    delete_user_meta($wp_user->ID, 'ma_deal_locked_until');
                    delete_user_meta($wp_user->ID, 'ma_deal_failed_attempts');
                    return false;
                }
            }
        }

        return false;
    }

    /**
     * Lock account
     *
     * @param string $email Email
     * @return bool Success
     */
    public function lock_account(string $email): bool {
        $locked_until = date('Y-m-d H:i:s', strtotime("+{$this->lockout_duration} minutes"));

        // Lock custom user
        $custom_user = $this->user_repo->find_by_email($email);
        if ($custom_user) {
            global $wpdb;
            $table = $wpdb->prefix . 'ma_deal_custom_users';

            $wpdb->update(
                $table,
                [
                    'locked_until' => $locked_until,
                    'updated_at' => current_time('mysql'),
                ],
                ['id' => $custom_user->id],
                ['%s', '%s'],
                ['%d']
            );

            // Record security event
            $this->record_event($custom_user->id, 'custom', 'account_locked', [
                'reason' => 'max_failed_attempts',
                'locked_until' => $locked_until,
            ]);

            // Send notification email
            $this->email_service->send_account_locked_notification(
                $email,
                $custom_user->get_full_name(),
                $this->lockout_duration
            );

            return true;
        }

        // Lock WordPress user
        $wp_user = get_user_by('email', $email);
        if ($wp_user) {
            update_user_meta($wp_user->ID, 'ma_deal_locked_until', $locked_until);

            // Record security event
            $this->record_event($wp_user->ID, 'wordpress', 'account_locked', [
                'reason' => 'max_failed_attempts',
                'locked_until' => $locked_until,
            ]);

            // Send notification email
            $this->email_service->send_account_locked_notification(
                $email,
                $wp_user->display_name,
                $this->lockout_duration
            );

            return true;
        }

        return false;
    }

    /**
     * Unlock account
     *
     * @param string $email Email
     * @return bool Success
     */
    public function unlock_account(string $email): bool {
        // Unlock custom user
        $custom_user = $this->user_repo->find_by_email($email);
        if ($custom_user) {
            global $wpdb;
            $table = $wpdb->prefix . 'ma_deal_custom_users';

            $wpdb->update(
                $table,
                [
                    'locked_until' => null,
                    'failed_login_attempts' => 0,
                    'updated_at' => current_time('mysql'),
                ],
                ['id' => $custom_user->id],
                ['%s', '%d', '%s'],
                ['%d']
            );

            // Record security event
            $this->record_event($custom_user->id, 'custom', 'account_unlocked');

            // Clear failed attempts
            $this->clear_failed_attempts_by_user($custom_user->id, 'custom');

            return true;
        }

        // Unlock WordPress user
        $wp_user = get_user_by('email', $email);
        if ($wp_user) {
            delete_user_meta($wp_user->ID, 'ma_deal_locked_until');
            delete_user_meta($wp_user->ID, 'ma_deal_failed_attempts');

            // Record security event
            $this->record_event($wp_user->ID, 'wordpress', 'account_unlocked');

            return true;
        }

        return false;
    }

    /**
     * Get failed login attempts count
     *
     * @param string $email Email
     * @param string $ip_address IP address
     * @return int Number of failed attempts
     */
    public function get_failed_attempts(string $email, ?string $ip_address = null): int {
        global $wpdb;

        $ip_address = $ip_address ?? $this->get_client_ip();
        $table = $wpdb->prefix . 'ma_deal_login_attempts';
        $cutoff_time = date('Y-m-d H:i:s', strtotime("-{$this->attempt_window} minutes"));

        // Count recent failed attempts by email OR IP
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
            WHERE (email = %s OR ip_address = %s)
            AND success = 0
            AND attempted_at >= %s",
            $email,
            $ip_address,
            $cutoff_time
        ));

        return (int) $count;
    }

    /**
     * Clear failed attempts for user
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return bool Success
     */
    private function clear_failed_attempts_by_user(int $user_id, string $user_type): bool {
        global $wpdb;

        // Get user email
        if ($user_type === 'custom') {
            $user = $this->user_repo->find($user_id);
            if (!$user) {
                return false;
            }
            $email = $user->email;
        } else {
            $wp_user = get_user_by('ID', $user_id);
            if (!$wp_user) {
                return false;
            }
            $email = $wp_user->user_email;
        }

        // Clear attempts from database
        $table = $wpdb->prefix . 'ma_deal_login_attempts';
        $wpdb->delete($table, ['email' => $email], ['%s']);

        // Clear attempts from user record
        if ($user_type === 'custom') {
            $users_table = $wpdb->prefix . 'ma_deal_custom_users';
            $wpdb->update(
                $users_table,
                ['failed_login_attempts' => 0],
                ['id' => $user_id],
                ['%d'],
                ['%d']
            );
        } else {
            delete_user_meta($user_id, 'ma_deal_failed_attempts');
        }

        return true;
    }

    /**
     * Check for suspicious login activity
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param string $ip_address IP address
     * @return void
     */
    private function check_suspicious_login(int $user_id, string $user_type, string $ip_address): void {
        global $wpdb;

        $table = $wpdb->prefix . 'ma_deal_security_events';
        $cutoff_time = date('Y-m-d H:i:s', strtotime('-1 hour'));

        // Check for login from new location
        $previous_ips = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT ip_address FROM {$table}
            WHERE user_id = %d
            AND user_type = %s
            AND event_type = 'login_success'
            AND created_at < %s
            ORDER BY created_at DESC
            LIMIT 10",
            $user_id,
            $user_type,
            $cutoff_time
        ));

        if (!empty($previous_ips) && !in_array($ip_address, $previous_ips)) {
            // New IP detected
            $this->record_event($user_id, $user_type, 'suspicious_activity', [
                'reason' => 'new_ip_address',
                'ip_address' => $ip_address,
                'previous_ips' => $previous_ips,
            ]);

            // Send notification
            $this->send_suspicious_activity_notification($user_id, $user_type, 'new_location', $ip_address);
        }

        // Check for rapid successive logins
        $recent_logins = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
            WHERE user_id = %d
            AND user_type = %s
            AND event_type = 'login_success'
            AND created_at >= %s",
            $user_id,
            $user_type,
            date('Y-m-d H:i:s', strtotime('-5 minutes'))
        ));

        if ($recent_logins > 3) {
            $this->record_event($user_id, $user_type, 'suspicious_activity', [
                'reason' => 'rapid_successive_logins',
                'count' => $recent_logins,
            ]);
        }
    }

    /**
     * Send suspicious activity notification
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param string $activity_type Activity type
     * @param string $ip_address IP address
     * @return void
     */
    private function send_suspicious_activity_notification(int $user_id, string $user_type, string $activity_type, string $ip_address): void {
        // Get user info
        if ($user_type === 'custom') {
            $user = $this->user_repo->find($user_id);
            if (!$user) {
                return;
            }
            $email = $user->email;
            $name = $user->get_full_name();
        } else {
            $wp_user = get_user_by('ID', $user_id);
            if (!$wp_user) {
                return;
            }
            $email = $wp_user->user_email;
            $name = $wp_user->display_name;
        }

        // Send email notification
        $this->email_service->send_suspicious_activity_alert(
            $email,
            $name,
            $activity_type,
            $ip_address,
            current_time('mysql')
        );
    }

    /**
     * Get security events for user
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Events
     */
    public function get_user_security_events(int $user_id, string $user_type, int $limit = 50, int $offset = 0): array {
        global $wpdb;

        $table = $wpdb->prefix . 'ma_deal_security_events';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
            WHERE user_id = %d AND user_type = %s
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d",
            $user_id,
            $user_type,
            $limit,
            $offset
        ), ARRAY_A);

        // Decode metadata
        foreach ($results as &$event) {
            if (!empty($event['metadata'])) {
                $event['metadata'] = json_decode($event['metadata'], true);
            }
        }

        return $results;
    }

    /**
     * Get security statistics
     *
     * @param int $days Number of days
     * @return array Statistics
     */
    public function get_security_stats(int $days = 30): array {
        global $wpdb;

        $events_table = $wpdb->prefix . 'ma_deal_security_events';
        $attempts_table = $wpdb->prefix . 'ma_deal_login_attempts';
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Get event counts by type
        $event_counts = $wpdb->get_results($wpdb->prepare(
            "SELECT event_type, COUNT(*) as count
            FROM {$events_table}
            WHERE created_at >= %s
            GROUP BY event_type",
            $cutoff
        ), ARRAY_A);

        $events_by_type = [];
        foreach ($event_counts as $row) {
            $events_by_type[$row['event_type']] = (int) $row['count'];
        }

        // Get failed login attempts
        $failed_attempts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$attempts_table}
            WHERE success = 0 AND attempted_at >= %s",
            $cutoff
        ));

        // Get successful logins
        $successful_logins = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$attempts_table}
            WHERE success = 1 AND attempted_at >= %s",
            $cutoff
        ));

        // Get locked accounts count
        $locked_accounts = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ma_deal_custom_users
            WHERE locked_until IS NOT NULL AND locked_until > NOW()"
        );

        // Get suspicious activities
        $suspicious_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$events_table}
            WHERE event_type = 'suspicious_activity' AND created_at >= %s",
            $cutoff
        ));

        return [
            'period_days' => $days,
            'events_by_type' => $events_by_type,
            'failed_login_attempts' => (int) $failed_attempts,
            'successful_logins' => (int) $successful_logins,
            'locked_accounts' => (int) $locked_accounts,
            'suspicious_activities' => (int) $suspicious_count,
            'success_rate' => $successful_logins > 0
                ? round(($successful_logins / ($successful_logins + $failed_attempts)) * 100, 2)
                : 0,
        ];
    }

    /**
     * Cleanup old security events
     *
     * @param int $days Keep events for this many days
     * @return int Number of events deleted
     */
    public function cleanup_old_events(int $days = 90): int {
        global $wpdb;

        $table = $wpdb->prefix . 'ma_deal_security_events';
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE created_at < %s",
            $cutoff
        ));

        return $deleted ?: 0;
    }

    /**
     * Cleanup old login attempts
     *
     * @param int $days Keep attempts for this many days
     * @return int Number of attempts deleted
     */
    public function cleanup_old_attempts(int $days = 30): int {
        global $wpdb;

        $table = $wpdb->prefix . 'ma_deal_login_attempts';
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE attempted_at < %s",
            $cutoff
        ));

        return $deleted ?: 0;
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
