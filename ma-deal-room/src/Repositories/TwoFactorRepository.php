<?php
/**
 * Two-Factor Authentication Repository
 *
 * Database operations for 2FA secrets and backup codes.
 *
 * @package    MADealRoom\Repositories
 * @since      2.0.0
 */

namespace MADealRoom\Repositories;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * TwoFactorRepository Class
 *
 * Handles database operations for two-factor authentication secrets.
 */
class TwoFactorRepository extends BaseRepository {

    /**
     * Table name (without prefix)
     *
     * @var string
     */
    protected $table = 'ma_deal_2fa_secrets';

    /**
     * Allowed column names for SQL safety
     *
     * @var array
     */
    protected $allowed_columns = [
        'id',
        'user_id',
        'user_type',
        'secret',
        'backup_codes',
        'enabled_at',
        'last_used_at',
        'disabled_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Create 2FA secret for user
     *
     * @param int $user_id User ID
     * @param string $user_type User type (custom|wordpress)
     * @param string $secret Encrypted TOTP secret
     * @param array $backup_codes Array of hashed backup codes
     * @return int|false Secret ID or false on failure
     */
    public function create_secret(int $user_id, string $user_type, string $secret, array $backup_codes) {
        $data = [
            'user_id' => $user_id,
            'user_type' => $user_type,
            'secret' => $secret,
            'backup_codes' => json_encode($backup_codes),
        ];

        return $this->create($data);
    }

    /**
     * Get 2FA secret for user
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return object|null Secret record
     */
    public function get_user_secret(int $user_id, string $user_type): ?object {
        $table = $this->get_table_name();

        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE user_id = %d
                AND user_type = %s
                AND disabled_at IS NULL
                LIMIT 1",
                $user_id,
                $user_type
            )
        );

        // Decode backup_codes JSON
        if ($result && $result->backup_codes) {
            $result->backup_codes = json_decode($result->backup_codes, true);
        }

        return $result ?: null;
    }

    /**
     * Check if user has 2FA enabled
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return bool
     */
    public function is_2fa_enabled(int $user_id, string $user_type): bool {
        $table = $this->get_table_name();

        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                WHERE user_id = %d
                AND user_type = %s
                AND enabled_at IS NOT NULL
                AND disabled_at IS NULL",
                $user_id,
                $user_type
            )
        );

        return (int) $count > 0;
    }

    /**
     * Mark 2FA as enabled
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return bool Success
     */
    public function enable_2fa(int $user_id, string $user_type): bool {
        $table = $this->get_table_name();

        $result = $this->wpdb->update(
            $table,
            ['enabled_at' => current_time('mysql')],
            [
                'user_id' => $user_id,
                'user_type' => $user_type,
            ]
        );

        return $result !== false;
    }

    /**
     * Disable 2FA for user
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return bool Success
     */
    public function disable_2fa(int $user_id, string $user_type): bool {
        $table = $this->get_table_name();

        $result = $this->wpdb->update(
            $table,
            ['disabled_at' => current_time('mysql')],
            [
                'user_id' => $user_id,
                'user_type' => $user_type,
                'disabled_at' => null,
            ]
        );

        return $result !== false;
    }

    /**
     * Update last used timestamp
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return bool Success
     */
    public function mark_as_used(int $user_id, string $user_type): bool {
        $table = $this->get_table_name();

        $result = $this->wpdb->update(
            $table,
            ['last_used_at' => current_time('mysql')],
            [
                'user_id' => $user_id,
                'user_type' => $user_type,
            ]
        );

        return $result !== false;
    }

    /**
     * Update backup codes
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param array $backup_codes Array of hashed backup codes
     * @return bool Success
     */
    public function update_backup_codes(int $user_id, string $user_type, array $backup_codes): bool {
        $table = $this->get_table_name();

        $result = $this->wpdb->update(
            $table,
            ['backup_codes' => json_encode($backup_codes)],
            [
                'user_id' => $user_id,
                'user_type' => $user_type,
            ]
        );

        return $result !== false;
    }

    /**
     * Get users with 2FA enabled
     *
     * @param int $limit Limit
     * @return array Secret records
     */
    public function get_users_with_2fa(int $limit = 100): array {
        $table = $this->get_table_name();

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE enabled_at IS NOT NULL
                AND disabled_at IS NULL
                ORDER BY enabled_at DESC
                LIMIT %d",
                $limit
            ),
            ARRAY_A
        );

        // Decode backup_codes JSON for each result
        return array_map(function ($row) {
            if (isset($row['backup_codes'])) {
                $row['backup_codes'] = json_decode($row['backup_codes'], true);
            }
            return $row;
        }, $results);
    }

    /**
     * Count users with 2FA enabled
     *
     * @return int Count
     */
    public function count_users_with_2fa(): int {
        $table = $this->get_table_name();

        return (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$table}
            WHERE enabled_at IS NOT NULL
            AND disabled_at IS NULL"
        );
    }

    /**
     * Get inactive 2FA users (enabled but not used recently)
     *
     * @param int $days Days of inactivity
     * @return array Secret records
     */
    public function get_inactive_2fa_users(int $days = 90): array {
        $table = $this->get_table_name();

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE enabled_at IS NOT NULL
                AND disabled_at IS NULL
                AND (
                    last_used_at IS NULL
                    OR last_used_at < DATE_SUB(NOW(), INTERVAL %d DAY)
                )
                ORDER BY enabled_at ASC",
                $days
            ),
            ARRAY_A
        );

        return array_map(function ($row) {
            if (isset($row['backup_codes'])) {
                $row['backup_codes'] = json_decode($row['backup_codes'], true);
            }
            return $row;
        }, $results);
    }

    /**
     * Delete 2FA secret for user
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return bool Success
     */
    public function delete_secret(int $user_id, string $user_type): bool {
        $table = $this->get_table_name();

        $result = $this->wpdb->delete(
            $table,
            [
                'user_id' => $user_id,
                'user_type' => $user_type,
            ]
        );

        return $result !== false;
    }

    /**
     * Get 2FA statistics
     *
     * @return array Statistics
     */
    public function get_2fa_stats(): array {
        $table = $this->get_table_name();

        $stats = $this->wpdb->get_row(
            "SELECT
                COUNT(*) as total_configured,
                SUM(CASE WHEN enabled_at IS NOT NULL AND disabled_at IS NULL THEN 1 ELSE 0 END) as currently_enabled,
                SUM(CASE WHEN disabled_at IS NOT NULL THEN 1 ELSE 0 END) as disabled_count,
                SUM(CASE WHEN last_used_at IS NOT NULL THEN 1 ELSE 0 END) as ever_used,
                AVG(TIMESTAMPDIFF(DAY, enabled_at, last_used_at)) as avg_days_to_first_use,
                COUNT(DISTINCT CASE WHEN last_used_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN user_id END) as active_30_days,
                COUNT(DISTINCT CASE WHEN last_used_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN user_id END) as active_7_days
            FROM {$table}",
            ARRAY_A
        );

        // Calculate adoption rate (if we had total users, this would be more meaningful)
        // For now, just provide the raw numbers
        return $stats ?: [
            'total_configured' => 0,
            'currently_enabled' => 0,
            'disabled_count' => 0,
            'ever_used' => 0,
            'avg_days_to_first_use' => 0,
            'active_30_days' => 0,
            'active_7_days' => 0,
        ];
    }

    /**
     * Clean up disabled 2FA secrets
     *
     * @param int $days_old Delete disabled secrets older than this many days
     * @return int Number of secrets deleted
     */
    public function cleanup_disabled_secrets(int $days_old = 90): int {
        $table = $this->get_table_name();

        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$table}
                WHERE disabled_at IS NOT NULL
                AND disabled_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days_old
            )
        );

        return (int) $result;
    }
}
