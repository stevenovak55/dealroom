<?php
/**
 * Email Verification Repository
 *
 * Database operations for email verification tokens.
 *
 * @package    MADealRoom\Repositories
 * @since      2.0.0
 */

namespace MADealRoom\Repositories;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * EmailVerificationRepository Class
 *
 * Handles database operations for email verification tokens.
 */
class EmailVerificationRepository extends BaseRepository {

    /**
     * Table name (without prefix)
     *
     * @var string
     */
    protected $table = 'ma_deal_email_verifications';

    /**
     * Allowed column names for SQL safety
     *
     * @var array
     */
    protected $allowed_columns = [
        'id',
        'user_id',
        'user_type',
        'email',
        'token_hash',
        'ip_address',
        'expires_at',
        'verified_at',
        'created_at',
    ];

    /**
     * Create email verification token
     *
     * @param int $user_id User ID
     * @param string $user_type User type (custom|wordpress)
     * @param string $email Email to verify
     * @param string $token_hash Hashed token
     * @param string $ip_address IP address
     * @param int $expires_in_hours Token expiration time in hours (default 24)
     * @return int|false Token ID or false on failure
     */
    public function create_verification_token(
        int $user_id,
        string $user_type,
        string $email,
        string $token_hash,
        string $ip_address,
        int $expires_in_hours = 24
    ) {
        $data = [
            'user_id' => $user_id,
            'user_type' => $user_type,
            'email' => $email,
            'token_hash' => $token_hash,
            'ip_address' => $ip_address,
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$expires_in_hours} hours")),
        ];

        return $this->create($data);
    }

    /**
     * Find verification token by token hash
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
                AND verified_at IS NULL
                AND expires_at > NOW()
                LIMIT 1",
                $token_hash
            )
        );

        return $result ?: null;
    }

    /**
     * Get pending verification for user
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return object|null Token record
     */
    public function get_pending_verification(int $user_id, string $user_type): ?object {
        $table = $this->get_table_name();

        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE user_id = %d
                AND user_type = %s
                AND verified_at IS NULL
                AND expires_at > NOW()
                ORDER BY created_at DESC
                LIMIT 1",
                $user_id,
                $user_type
            )
        );

        return $result ?: null;
    }

    /**
     * Mark token as verified
     *
     * @param string $token_hash Hashed token
     * @return bool Success
     */
    public function mark_as_verified(string $token_hash): bool {
        $table = $this->get_table_name();

        $result = $this->wpdb->update(
            $table,
            ['verified_at' => current_time('mysql')],
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
                AND verified_at IS NULL
                AND expires_at > NOW()",
                $token_hash
            )
        );

        return (int) $count > 0;
    }

    /**
     * Check if user has verified email
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return bool
     */
    public function is_email_verified(int $user_id, string $user_type): bool {
        $table = $this->get_table_name();

        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                WHERE user_id = %d
                AND user_type = %s
                AND verified_at IS NOT NULL",
                $user_id,
                $user_type
            )
        );

        return (int) $count > 0;
    }

    /**
     * Count recent verification requests for user
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param int $minutes Minutes to look back
     * @return int Count
     */
    public function count_recent_requests(int $user_id, string $user_type, int $minutes = 60): int {
        $table = $this->get_table_name();

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                WHERE user_id = %d
                AND user_type = %s
                AND created_at >= DATE_SUB(NOW(), INTERVAL %d MINUTE)",
                $user_id,
                $user_type,
                $minutes
            )
        );
    }

    /**
     * Revoke all tokens for user
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return bool Success
     */
    public function revoke_all_tokens(int $user_id, string $user_type): bool {
        $table = $this->get_table_name();

        $result = $this->wpdb->update(
            $table,
            ['verified_at' => current_time('mysql')], // Mark as verified to invalidate
            [
                'user_id' => $user_id,
                'user_type' => $user_type,
                'verified_at' => null,
            ]
        );

        return $result !== false;
    }

    /**
     * Get users with pending verification
     *
     * @param int $limit Limit
     * @return array Token records
     */
    public function get_pending_verifications(int $limit = 100): array {
        $table = $this->get_table_name();

        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE verified_at IS NULL
                AND expires_at > NOW()
                ORDER BY created_at ASC
                LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
    }

    /**
     * Get verifications expiring soon
     *
     * @param int $hours Hours until expiration
     * @return array Token records
     */
    public function get_expiring_verifications(int $hours = 6): array {
        $table = $this->get_table_name();

        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE verified_at IS NULL
                AND expires_at > NOW()
                AND expires_at <= DATE_ADD(NOW(), INTERVAL %d HOUR)
                ORDER BY expires_at ASC",
                $hours
            ),
            ARRAY_A
        );
    }

    /**
     * Clean up old tokens
     *
     * @param int $days_old Delete tokens older than this many days
     * @return int Number of tokens deleted
     */
    public function cleanup_old_tokens(int $days_old = 30): int {
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
            WHERE expires_at < NOW()
            AND verified_at IS NULL"
        );

        return (int) $result;
    }

    /**
     * Get email verification statistics
     *
     * @param int $days Number of days to analyze
     * @return array Statistics
     */
    public function get_verification_stats(int $days = 30): array {
        $table = $this->get_table_name();

        $stats = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT
                    COUNT(*) as total_sent,
                    SUM(CASE WHEN verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified_count,
                    SUM(CASE WHEN expires_at < NOW() AND verified_at IS NULL THEN 1 ELSE 0 END) as expired_count,
                    COUNT(DISTINCT user_id, user_type) as unique_users,
                    AVG(TIMESTAMPDIFF(HOUR, created_at, verified_at)) as avg_verification_hours
                FROM {$table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            ),
            ARRAY_A
        );

        // Calculate verification rate
        if ($stats && $stats['total_sent'] > 0) {
            $stats['verification_rate'] = ($stats['verified_count'] / $stats['total_sent']) * 100;
        } else {
            $stats['verification_rate'] = 0;
        }

        return $stats ?: [];
    }
}
