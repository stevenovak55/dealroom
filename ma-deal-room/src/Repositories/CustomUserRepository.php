<?php
/**
 * Custom User Repository
 *
 * Database operations for custom users.
 *
 * @package    MADealRoom\Repositories
 * @since      2.0.0
 */

namespace MADealRoom\Repositories;

use MADealRoom\Models\CustomUser;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CustomUserRepository Class
 *
 * Handles database operations for custom user accounts.
 */
class CustomUserRepository extends BaseRepository {

    /**
     * Table name (without prefix)
     *
     * @var string
     */
    protected $table = 'ma_deal_custom_users';

    /**
     * Model class name
     *
     * @var string
     */
    protected $model_class = CustomUser::class;

    /**
     * Allowed column names for SQL safety
     *
     * @var array
     */
    protected $allowed_columns = [
        'id',
        'email',
        'first_name',
        'last_name',
        'phone',
        'status',
        'email_verified',
        'email_verified_at',
        'last_login_at',
        'last_login_ip',
        'failed_login_attempts',
        'locked_until',
        'password_changed_at',
        'must_change_password',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Find user by email
     *
     * @param string $email Email address
     * @param bool $include_deleted Include soft-deleted users
     * @return CustomUser|null
     */
    public function find_by_email(string $email, bool $include_deleted = false): ?CustomUser {
        $table = $this->get_table_name();

        $sql = "SELECT * FROM {$table} WHERE email = %s";

        if (!$include_deleted) {
            $sql .= " AND deleted_at IS NULL";
        }

        $sql .= " LIMIT 1";

        $result = $this->wpdb->get_row(
            $this->wpdb->prepare($sql, $email),
            ARRAY_A
        );

        if (!$result) {
            return null;
        }

        return new CustomUser($result);
    }

    /**
     * Find user by ID (excluding soft-deleted by default)
     *
     * @param int $id User ID
     * @param bool $include_deleted Include soft-deleted users
     * @return CustomUser|null
     */
    public function find(int $id, bool $include_deleted = false): ?CustomUser {
        $table = $this->get_table_name();

        $sql = "SELECT * FROM {$table} WHERE id = %d";

        if (!$include_deleted) {
            $sql .= " AND deleted_at IS NULL";
        }

        $sql .= " LIMIT 1";

        $result = $this->wpdb->get_row(
            $this->wpdb->prepare($sql, $id),
            ARRAY_A
        );

        if (!$result) {
            return null;
        }

        return new CustomUser($result);
    }

    /**
     * Create a new user
     *
     * @param CustomUser $user User model
     * @return int|false Inserted ID or false on failure
     */
    public function create_user(CustomUser $user) {
        $data = $user->get_database_data();
        $result = $this->create($data);

        if ($result) {
            $user->id = $result;
        }

        return $result;
    }

    /**
     * Update user
     *
     * @param CustomUser $user User model
     * @return bool Success
     */
    public function update_user(CustomUser $user): bool {
        if (!isset($user->id)) {
            return false;
        }

        $data = $user->get_database_data();
        return $this->update($user->id, $data);
    }

    /**
     * Find active users by status
     *
     * @param string $status User status
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Array of CustomUser instances
     */
    public function find_by_status(string $status, int $limit = 100, int $offset = 0): array {
        $table = $this->get_table_name();

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE status = %s AND deleted_at IS NULL
                ORDER BY created_at DESC
                LIMIT %d OFFSET %d",
                $status,
                $limit,
                $offset
            ),
            ARRAY_A
        );

        return $this->hydrate_users($results);
    }

    /**
     * Search users by name or email
     *
     * @param string $search Search term
     * @param int $limit Limit
     * @return array Array of CustomUser instances
     */
    public function search(string $search, int $limit = 20): array {
        $table = $this->get_table_name();
        $search_term = '%' . $this->wpdb->esc_like($search) . '%';

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE (
                    email LIKE %s
                    OR first_name LIKE %s
                    OR last_name LIKE %s
                    OR CONCAT(first_name, ' ', last_name) LIKE %s
                )
                AND deleted_at IS NULL
                ORDER BY created_at DESC
                LIMIT %d",
                $search_term,
                $search_term,
                $search_term,
                $search_term,
                $limit
            ),
            ARRAY_A
        );

        return $this->hydrate_users($results);
    }

    /**
     * Get users with locked accounts
     *
     * @return array Array of CustomUser instances
     */
    public function get_locked_users(): array {
        $table = $this->get_table_name();

        $results = $this->wpdb->get_results(
            "SELECT * FROM {$table}
            WHERE locked_until IS NOT NULL
            AND locked_until > NOW()
            AND deleted_at IS NULL
            ORDER BY locked_until DESC",
            ARRAY_A
        );

        return $this->hydrate_users($results);
    }

    /**
     * Get users pending email verification
     *
     * @param int $limit Limit
     * @return array Array of CustomUser instances
     */
    public function get_pending_verification(int $limit = 100): array {
        $table = $this->get_table_name();

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE email_verified = 0
                AND status = 'pending_verification'
                AND deleted_at IS NULL
                ORDER BY created_at ASC
                LIMIT %d",
                $limit
            ),
            ARRAY_A
        );

        return $this->hydrate_users($results);
    }

    /**
     * Get recently registered users
     *
     * @param int $days Number of days
     * @param int $limit Limit
     * @return array Array of CustomUser instances
     */
    public function get_recent_registrations(int $days = 7, int $limit = 100): array {
        $table = $this->get_table_name();

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                AND deleted_at IS NULL
                ORDER BY created_at DESC
                LIMIT %d",
                $days,
                $limit
            ),
            ARRAY_A
        );

        return $this->hydrate_users($results);
    }

    /**
     * Count users by status
     *
     * @param string $status User status
     * @return int Count
     */
    public function count_by_status(string $status): int {
        $table = $this->get_table_name();

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                WHERE status = %s AND deleted_at IS NULL",
                $status
            )
        );
    }

    /**
     * Soft delete user
     *
     * @param int $user_id User ID
     * @return bool Success
     */
    public function soft_delete(int $user_id): bool {
        return $this->update($user_id, [
            'deleted_at' => current_time('mysql'),
            'status' => 'inactive',
        ]);
    }

    /**
     * Restore soft-deleted user
     *
     * @param int $user_id User ID
     * @return bool Success
     */
    public function restore(int $user_id): bool {
        return $this->update($user_id, [
            'deleted_at' => null,
            'status' => 'active',
        ]);
    }

    /**
     * Permanently delete user (hard delete)
     *
     * @param int $user_id User ID
     * @return bool Success
     */
    public function hard_delete(int $user_id): bool {
        return $this->delete($user_id);
    }

    /**
     * Record login attempt
     *
     * @param int $user_id User ID
     * @param bool $successful Whether login was successful
     * @param string $ip_address IP address
     * @return bool Success
     */
    public function record_login_attempt(int $user_id, bool $successful, string $ip_address): bool {
        $user = $this->find($user_id);

        if (!$user) {
            return false;
        }

        $user->record_login_attempt($successful, $ip_address);
        return $this->update_user($user);
    }

    /**
     * Unlock account
     *
     * @param int $user_id User ID
     * @return bool Success
     */
    public function unlock_account(int $user_id): bool {
        return $this->update($user_id, [
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * Mark email as verified
     *
     * @param int $user_id User ID
     * @return bool Success
     */
    public function mark_email_verified(int $user_id): bool {
        $user = $this->find($user_id);

        if (!$user) {
            return false;
        }

        $user->mark_email_verified();
        return $this->update_user($user);
    }

    /**
     * Update password
     *
     * @param int $user_id User ID
     * @param string $new_password Plain text password
     * @return bool Success
     */
    public function update_password(int $user_id, string $new_password): bool {
        $user = $this->find($user_id);

        if (!$user) {
            return false;
        }

        $user->set_password($new_password);
        return $this->update_user($user);
    }

    /**
     * Hydrate array of user data into CustomUser instances
     *
     * @param array $results Array of user data arrays
     * @return array Array of CustomUser instances
     */
    private function hydrate_users(array $results): array {
        return array_map(function ($data) {
            return new CustomUser($data);
        }, $results);
    }

    /**
     * Check if email exists
     *
     * @param string $email Email address
     * @param int|null $exclude_user_id Exclude this user ID from check
     * @return bool
     */
    public function email_exists(string $email, ?int $exclude_user_id = null): bool {
        $table = $this->get_table_name();

        $sql = "SELECT COUNT(*) FROM {$table} WHERE email = %s AND deleted_at IS NULL";
        $params = [$email];

        if ($exclude_user_id) {
            $sql .= " AND id != %d";
            $params[] = $exclude_user_id;
        }

        $count = $this->wpdb->get_var(
            $this->wpdb->prepare($sql, ...$params)
        );

        return (int) $count > 0;
    }
}
