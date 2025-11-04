<?php
/**
 * User Session Repository
 *
 * Database operations for user sessions and refresh tokens.
 *
 * @package    MADealRoom\Repositories
 * @since      2.0.0
 */

namespace MADealRoom\Repositories;

use MADealRoom\Models\UserSession;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * UserSessionRepository Class
 *
 * Handles database operations for JWT refresh tokens and session management.
 */
class UserSessionRepository extends BaseRepository {

    /**
     * Table name (without prefix)
     *
     * @var string
     */
    protected $table = 'ma_deal_user_sessions';

    /**
     * Model class name
     *
     * @var string
     */
    protected $model_class = UserSession::class;

    /**
     * Allowed column names for SQL safety
     *
     * @var array
     */
    protected $allowed_columns = [
        'id',
        'session_id',
        'user_id',
        'user_type',
        'refresh_token_hash',
        'device_name',
        'device_type',
        'browser',
        'platform',
        'ip_address',
        'last_activity_ip',
        'user_agent',
        'last_activity_user_agent',
        'location',
        'last_used_at',
        'expires_at',
        'revoked_at',
        'invalidation_reason',
        'created_at',
    ];

    /**
     * Create a new session
     *
     * @param UserSession $session Session model
     * @return int|false Session ID or false on failure
     */
    public function create_session(UserSession $session) {
        $data = $session->get_database_data();
        $result = $this->create($data);

        if ($result) {
            $session->id = $result;
        }

        return $result;
    }

    /**
     * Find session by session_id
     *
     * @param string $session_id Session ID from JWT
     * @return UserSession|null
     */
    public function find_by_session_id(string $session_id): ?UserSession {
        $table = $this->get_table_name();

        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE session_id = %s
                LIMIT 1",
                $session_id
            ),
            ARRAY_A
        );

        return $result ? new UserSession($result) : null;
    }

    /**
     * Find session by refresh token hash
     *
     * @param string $token_hash Hashed refresh token
     * @return UserSession|null
     */
    public function find_by_token(string $token_hash): ?UserSession {
        $table = $this->get_table_name();

        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE refresh_token_hash = %s
                LIMIT 1",
                $token_hash
            ),
            ARRAY_A
        );

        return $result ? new UserSession($result) : null;
    }

    /**
     * Get active sessions for user
     *
     * @param int $user_id User ID
     * @param string $user_type User type (custom|wordpress)
     * @return array Array of UserSession instances
     */
    public function get_active_sessions(int $user_id, string $user_type): array {
        $table = $this->get_table_name();

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE user_id = %d AND user_type = %s
                AND revoked_at IS NULL
                AND expires_at > NOW()
                ORDER BY last_used_at DESC",
                $user_id,
                $user_type
            ),
            ARRAY_A
        );

        return $this->hydrate_sessions($results);
    }

    /**
     * Get all sessions for user (active and inactive)
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param int $limit Limit
     * @return array Array of UserSession instances
     */
    public function get_user_sessions(int $user_id, string $user_type, int $limit = 50): array {
        $table = $this->get_table_name();

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE user_id = %d AND user_type = %s
                ORDER BY last_used_at DESC
                LIMIT %d",
                $user_id,
                $user_type,
                $limit
            ),
            ARRAY_A
        );

        return $this->hydrate_sessions($results);
    }

    /**
     * Update session last used timestamp
     *
     * @param int $session_id Session ID
     * @return bool Success
     */
    public function touch_session(int $session_id): bool {
        return $this->update($session_id, [
            'last_used_at' => current_time('mysql'),
        ]);
    }

    /**
     * Revoke session
     *
     * @param int $session_id Session ID
     * @param string|null $reason Invalidation reason
     * @return bool Success
     */
    public function revoke_session(int $session_id, ?string $reason = 'manual_revoke'): bool {
        return $this->update($session_id, [
            'revoked_at' => current_time('mysql'),
            'invalidation_reason' => $reason,
        ]);
    }

    /**
     * Revoke session by token hash
     *
     * @param string $token_hash Hashed refresh token
     * @param string|null $reason Invalidation reason
     * @return bool Success
     */
    public function revoke_session_by_token(string $token_hash, ?string $reason = 'manual_revoke'): bool {
        $table = $this->get_table_name();

        $result = $this->wpdb->update(
            $table,
            [
                'revoked_at' => current_time('mysql'),
                'invalidation_reason' => $reason,
            ],
            ['refresh_token_hash' => $token_hash]
        );

        return $result !== false;
    }

    /**
     * Revoke all sessions for user
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param int|null $except_session_id Optional session ID to exclude from revocation
     * @param string|null $reason Invalidation reason
     * @return bool Success
     */
    public function revoke_all_sessions(int $user_id, string $user_type, ?int $except_session_id = null, ?string $reason = 'logout_all'): bool {
        $table = $this->get_table_name();

        $sql = "UPDATE {$table}
                SET revoked_at = %s, invalidation_reason = %s
                WHERE user_id = %d AND user_type = %s
                AND revoked_at IS NULL";

        $params = [current_time('mysql'), $reason, $user_id, $user_type];

        if ($except_session_id !== null) {
            $sql .= " AND id != %d";
            $params[] = $except_session_id;
        }

        $result = $this->wpdb->query(
            $this->wpdb->prepare($sql, ...$params)
        );

        return $result !== false;
    }

    /**
     * Clean up expired sessions
     *
     * @return int Number of sessions cleaned up
     */
    public function cleanup_expired_sessions(): int {
        $table = $this->get_table_name();

        // Delete expired sessions older than 30 days
        $result = $this->wpdb->query(
            "DELETE FROM {$table}
            WHERE expires_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        return (int) $result;
    }

    /**
     * Clean up revoked sessions
     *
     * @param int $days_old Delete revoked sessions older than this many days
     * @return int Number of sessions deleted
     */
    public function cleanup_revoked_sessions(int $days_old = 7): int {
        $table = $this->get_table_name();

        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$table}
                WHERE revoked_at IS NOT NULL
                AND revoked_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days_old
            )
        );

        return (int) $result;
    }

    /**
     * Get sessions expiring soon
     *
     * @param int $hours Hours until expiration
     * @return array Array of UserSession instances
     */
    public function get_expiring_sessions(int $hours = 24): array {
        $table = $this->get_table_name();

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE expires_at > NOW()
                AND expires_at <= DATE_ADD(NOW(), INTERVAL %d HOUR)
                AND revoked_at IS NULL
                ORDER BY expires_at ASC",
                $hours
            ),
            ARRAY_A
        );

        return $this->hydrate_sessions($results);
    }

    /**
     * Count active sessions for user
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return int Count
     */
    public function count_active_sessions(int $user_id, string $user_type): int {
        $table = $this->get_table_name();

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                WHERE user_id = %d AND user_type = %s
                AND revoked_at IS NULL
                AND expires_at > NOW()",
                $user_id,
                $user_type
            )
        );
    }

    /**
     * Get sessions by device type
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param string $device_type Device type (desktop|mobile|tablet)
     * @return array Array of UserSession instances
     */
    public function get_sessions_by_device(int $user_id, string $user_type, string $device_type): array {
        $table = $this->get_table_name();

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE user_id = %d AND user_type = %s
                AND device_type = %s
                AND revoked_at IS NULL
                AND expires_at > NOW()
                ORDER BY last_used_at DESC",
                $user_id,
                $user_type,
                $device_type
            ),
            ARRAY_A
        );

        return $this->hydrate_sessions($results);
    }

    /**
     * Get sessions from IP address
     *
     * @param string $ip_address IP address
     * @param int $limit Limit
     * @return array Array of UserSession instances
     */
    public function get_sessions_by_ip(string $ip_address, int $limit = 50): array {
        $table = $this->get_table_name();

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE ip_address = %s
                ORDER BY created_at DESC
                LIMIT %d",
                $ip_address,
                $limit
            ),
            ARRAY_A
        );

        return $this->hydrate_sessions($results);
    }

    /**
     * Get recently created sessions
     *
     * @param int $hours Hours ago
     * @param int $limit Limit
     * @return array Array of UserSession instances
     */
    public function get_recent_sessions(int $hours = 24, int $limit = 100): array {
        $table = $this->get_table_name();

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d HOUR)
                ORDER BY created_at DESC
                LIMIT %d",
                $hours,
                $limit
            ),
            ARRAY_A
        );

        return $this->hydrate_sessions($results);
    }

    /**
     * Get session statistics for user
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return array Statistics
     */
    public function get_session_stats(int $user_id, string $user_type): array {
        $table = $this->get_table_name();

        $stats = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT
                    COUNT(*) as total_sessions,
                    SUM(CASE WHEN revoked_at IS NULL AND expires_at > NOW() THEN 1 ELSE 0 END) as active_sessions,
                    SUM(CASE WHEN revoked_at IS NOT NULL THEN 1 ELSE 0 END) as revoked_sessions,
                    SUM(CASE WHEN expires_at <= NOW() THEN 1 ELSE 0 END) as expired_sessions,
                    MAX(last_used_at) as last_activity,
                    COUNT(DISTINCT ip_address) as unique_ips,
                    COUNT(DISTINCT device_type) as device_types
                FROM {$table}
                WHERE user_id = %d AND user_type = %s",
                $user_id,
                $user_type
            ),
            ARRAY_A
        );

        return $stats ?: [];
    }

    /**
     * Check if session is valid
     *
     * @param string $token_hash Hashed refresh token
     * @return bool
     */
    public function is_valid_session(string $token_hash): bool {
        $table = $this->get_table_name();

        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                WHERE refresh_token_hash = %s
                AND revoked_at IS NULL
                AND expires_at > NOW()",
                $token_hash
            )
        );

        return (int) $count > 0;
    }

    /**
     * Hydrate array of session data into UserSession instances
     *
     * @param array $results Array of session data arrays
     * @return array Array of UserSession instances
     */
    private function hydrate_sessions(array $results): array {
        return array_map(function ($data) {
            return new UserSession($data);
        }, $results);
    }
}
