<?php
/**
 * Password Reset Repository
 *
 * Database operations for password reset tokens.
 *
 * @package    MADealRoom\Repositories
 * @since      2.0.0
 */

namespace MADealRoom\Repositories;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * PasswordResetRepository Class
 *
 * Handles database operations for password reset tokens.
 */
class PasswordResetRepository extends BaseRepository {

    /**
     * Table name (without prefix)
     *
     * @var string
     */
    protected $table = 'ma_deal_password_resets';

    /**
     * Allowed column names for SQL safety
     *
     * @var array
     */
    protected $allowed_columns = [
        'id',
        'email',
        'token_hash',
        'user_type',
        'ip_address',
        'expires_at',
        'used_at',
        'created_at',
    ];

    /**
     * Create password reset token
     *
     * @param string $email Email address
     * @param string $token_hash Hashed token
     * @param string $user_type User type (custom|wordpress)
     * @param string $ip_address IP address
     * @param int $expires_in_minutes Token expiration time in minutes (default 60)
     * @return int|false Token ID or false on failure
     */
    public function create_reset_token(
        string $email,
        string $token_hash,
        string $user_type,
        string $ip_address,
        int $expires_in_minutes = 60
    ) {
        $data = [
            'email' => $email,
            'token_hash' => $token_hash,
            'user_type' => $user_type,
            'ip_address' => $ip_address,
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$expires_in_minutes} minutes")),
        ];

        return $this->create($data);
    }

    /**
     * Find reset token by token hash
     *
     * @param string $token_hash Hashed token
     * @return object|null Token record
     */
    public function find_by_token(string $token_hash): ?object {
        $table = $this->get_table_name();

        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE token_hash = %s
                AND used_at IS NULL
                AND expires_at > NOW()
                LIMIT 1",
                $token_hash
            )
        );

        return $result ?: null;
    }

    /**
     * Mark token as used
     *
     * @param string $token_hash Hashed token
     * @return bool Success
     */
    public function mark_as_used(string $token_hash): bool {
        $table = $this->get_table_name();

        $result = $this->wpdb->update(
            $table,
            ['used_at' => current_time('mysql')],
            ['token_hash' => $token_hash]
        );

        return $result !== false;
    }

    /**
     * Check if token is valid
     *
     * @param string $token_hash Hashed token
     * @return bool
     */
    public function is_valid_token(string $token_hash): bool {
        $table = $this->get_table_name();

        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                WHERE token_hash = %s
                AND used_at IS NULL
                AND expires_at > NOW()",
                $token_hash
            )
        );

        return (int) $count > 0;
    }

    /**
     * Get recent reset requests for email
     *
     * @param string $email Email address
     * @param int $minutes Minutes to look back
     * @return array Token records
     */
    public function get_recent_requests(string $email, int $minutes = 60): array {
        $table = $this->get_table_name();

        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE email = %s
                AND created_at >= DATE_SUB(NOW(), INTERVAL %d MINUTE)
                ORDER BY created_at DESC",
                $email,
                $minutes
            ),
            ARRAY_A
        );
    }

    /**
     * Count recent reset requests for email
     *
     * @param string $email Email address
     * @param int $minutes Minutes to look back
     * @return int Count
     */
    public function count_recent_requests(string $email, int $minutes = 60): int {
        $table = $this->get_table_name();

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                WHERE email = %s
                AND created_at >= DATE_SUB(NOW(), INTERVAL %d MINUTE)",
                $email,
                $minutes
            )
        );
    }

    /**
     * Revoke all tokens for email
     *
     * @param string $email Email address
     * @return bool Success
     */
    public function revoke_all_tokens(string $email): bool {
        $table = $this->get_table_name();

        $result = $this->wpdb->update(
            $table,
            ['used_at' => current_time('mysql')],
            [
                'email' => $email,
                'used_at' => null,
            ]
        );

        return $result !== false;
    }

    /**
     * Clean up old tokens
     *
     * @param int $days_old Delete tokens older than this many days
     * @return int Number of tokens deleted
     */
    public function cleanup_old_tokens(int $days_old = 7): int {
        $table = $this->get_table_name();

        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$table}
                WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days_old
            )
        );

        return (int) $result;
    }

    /**
     * Clean up expired tokens
     *
     * @return int Number of tokens deleted
     */
    public function cleanup_expired_tokens(): int {
        $table = $this->get_table_name();

        $result = $this->wpdb->query(
            "DELETE FROM {$table}
            WHERE expires_at < NOW()"
        );

        return (int) $result;
    }

    /**
     * Get password reset statistics
     *
     * @param int $days Number of days to analyze
     * @return array Statistics
     */
    public function get_reset_stats(int $days = 30): array {
        $table = $this->get_table_name();

        $stats = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN used_at IS NOT NULL THEN 1 ELSE 0 END) as completed_resets,
                    SUM(CASE WHEN expires_at < NOW() AND used_at IS NULL THEN 1 ELSE 0 END) as expired_tokens,
                    COUNT(DISTINCT email) as unique_users,
                    AVG(TIMESTAMPDIFF(MINUTE, created_at, used_at)) as avg_completion_minutes
                FROM {$table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            ),
            ARRAY_A
        );

        // Calculate completion rate
        if ($stats && $stats['total_requests'] > 0) {
            $stats['completion_rate'] = ($stats['completed_resets'] / $stats['total_requests']) * 100;
        } else {
            $stats['completion_rate'] = 0;
        }

        return $stats ?: [];
    }
}
