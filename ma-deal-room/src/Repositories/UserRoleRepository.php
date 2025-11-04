<?php
/**
 * User Role Repository
 *
 * Database operations for user role assignments.
 *
 * @package    MADealRoom\Repositories
 * @since      2.0.0
 */

namespace MADealRoom\Repositories;

use MADealRoom\Models\UserRole;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * UserRoleRepository Class
 *
 * Handles database operations for user role assignments.
 */
class UserRoleRepository extends BaseRepository {

    /**
     * Table name (without prefix)
     *
     * @var string
     */
    protected $table = 'ma_deal_user_roles';

    /**
     * Model class name
     *
     * @var string
     */
    protected $model_class = UserRole::class;

    /**
     * Allowed column names for SQL safety
     *
     * @var array
     */
    protected $allowed_columns = [
        'id',
        'user_id',
        'user_type',
        'role_type',
        'account_id',
        'transaction_id',
        'is_primary',
        'assigned_by_user_id',
        'assigned_at',
        'expires_at',
        'revoked_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Get user roles
     *
     * @param int $user_id User ID
     * @param string $user_type User type (custom|wordpress)
     * @param bool $active_only Only return active (non-revoked, non-expired) roles
     * @return array Array of UserRole instances
     */
    public function get_user_roles(int $user_id, string $user_type, bool $active_only = true): array {
        $table = $this->get_table_name();

        $sql = "SELECT * FROM {$table} WHERE user_id = %d AND user_type = %s";

        if ($active_only) {
            $sql .= " AND (revoked_at IS NULL OR revoked_at > NOW())
                     AND (expires_at IS NULL OR expires_at > NOW())";
        }

        $sql .= " ORDER BY is_primary DESC, created_at ASC";

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $user_id, $user_type),
            ARRAY_A
        );

        return $this->hydrate_roles($results);
    }

    /**
     * Get primary role for user
     *
     * @param int $user_id User ID
     * @param string $user_type User type (custom|wordpress)
     * @return UserRole|null
     */
    public function get_primary_role(int $user_id, string $user_type): ?UserRole {
        $table = $this->get_table_name();

        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE user_id = %d AND user_type = %s
                AND is_primary = 1
                AND (revoked_at IS NULL OR revoked_at > NOW())
                AND (expires_at IS NULL OR expires_at > NOW())
                LIMIT 1",
                $user_id,
                $user_type
            ),
            ARRAY_A
        );

        return $result ? new UserRole($result) : null;
    }

    /**
     * Get roles by type
     *
     * @param int $user_id User ID
     * @param string $user_type User type (custom|wordpress)
     * @param string $role_type Role type (buyer, seller, agent, etc.)
     * @return array Array of UserRole instances
     */
    public function get_roles_by_type(int $user_id, string $user_type, string $role_type): array {
        $table = $this->get_table_name();

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE user_id = %d AND user_type = %s AND role_type = %s
                AND (revoked_at IS NULL OR revoked_at > NOW())
                AND (expires_at IS NULL OR expires_at > NOW())
                ORDER BY created_at DESC",
                $user_id,
                $user_type,
                $role_type
            ),
            ARRAY_A
        );

        return $this->hydrate_roles($results);
    }

    /**
     * Get role for transaction
     *
     * @param int $user_id User ID
     * @param string $user_type User type (custom|wordpress)
     * @param int $transaction_id Transaction ID
     * @return UserRole|null
     */
    public function get_transaction_role(int $user_id, string $user_type, int $transaction_id): ?UserRole {
        $table = $this->get_table_name();

        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE user_id = %d AND user_type = %s AND transaction_id = %d
                AND (revoked_at IS NULL OR revoked_at > NOW())
                AND (expires_at IS NULL OR expires_at > NOW())
                LIMIT 1",
                $user_id,
                $user_type,
                $transaction_id
            ),
            ARRAY_A
        );

        return $result ? new UserRole($result) : null;
    }

    /**
     * Get all roles for a transaction
     *
     * @param int $transaction_id Transaction ID
     * @param bool $active_only Only active roles
     * @return array Array of UserRole instances
     */
    public function get_transaction_roles(int $transaction_id, bool $active_only = true): array {
        $table = $this->get_table_name();

        $sql = "SELECT * FROM {$table} WHERE transaction_id = %d";

        if ($active_only) {
            $sql .= " AND (revoked_at IS NULL OR revoked_at > NOW())
                     AND (expires_at IS NULL OR expires_at > NOW())";
        }

        $sql .= " ORDER BY role_type ASC, created_at ASC";

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $transaction_id),
            ARRAY_A
        );

        return $this->hydrate_roles($results);
    }

    /**
     * Get all roles for an account
     *
     * @param int $account_id Account ID
     * @param bool $active_only Only active roles
     * @return array Array of UserRole instances
     */
    public function get_account_roles(int $account_id, bool $active_only = true): array {
        $table = $this->get_table_name();

        $sql = "SELECT * FROM {$table} WHERE account_id = %d";

        if ($active_only) {
            $sql .= " AND (revoked_at IS NULL OR revoked_at > NOW())
                     AND (expires_at IS NULL OR expires_at > NOW())";
        }

        $sql .= " ORDER BY role_type ASC, created_at ASC";

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $account_id),
            ARRAY_A
        );

        return $this->hydrate_roles($results);
    }

    /**
     * Assign role to user
     *
     * @param UserRole $role Role model
     * @return int|false Role ID or false on failure
     */
    public function assign_role(UserRole $role) {
        // Check if role already exists
        $existing = $this->find_existing_role(
            $role->user_id,
            $role->user_type,
            $role->role_type,
            $role->transaction_id,
            $role->account_id
        );

        if ($existing) {
            // If exists and revoked, restore it
            if ($existing->is_revoked) {
                $existing->restore();
                $this->update_role($existing);
                return $existing->id;
            }
            // Already exists and active
            return $existing->id;
        }

        // Create new role
        $data = $role->get_database_data();
        $result = $this->create($data);

        if ($result) {
            $role->id = $result;
        }

        return $result;
    }

    /**
     * Update role
     *
     * @param UserRole $role Role model
     * @return bool Success
     */
    public function update_role(UserRole $role): bool {
        if (!isset($role->id)) {
            return false;
        }

        $data = $role->get_database_data();
        return $this->update($role->id, $data);
    }

    /**
     * Revoke role
     *
     * @param int $role_id Role ID
     * @return bool Success
     */
    public function revoke_role(int $role_id): bool {
        return $this->update($role_id, [
            'revoked_at' => current_time('mysql'),
        ]);
    }

    /**
     * Revoke all roles for user
     *
     * @param int $user_id User ID
     * @param string $user_type User type (custom|wordpress)
     * @param int|null $transaction_id Optional: only revoke roles for specific transaction
     * @return bool Success
     */
    public function revoke_user_roles(int $user_id, string $user_type, ?int $transaction_id = null): bool {
        $table = $this->get_table_name();

        $sql = "UPDATE {$table} SET revoked_at = %s
                WHERE user_id = %d AND user_type = %s
                AND revoked_at IS NULL";

        $params = [current_time('mysql'), $user_id, $user_type];

        if ($transaction_id !== null) {
            $sql .= " AND transaction_id = %d";
            $params[] = $transaction_id;
        }

        $result = $this->wpdb->query(
            $this->wpdb->prepare($sql, ...$params)
        );

        return $result !== false;
    }

    /**
     * Set primary role
     *
     * @param int $role_id Role ID to set as primary
     * @param int $user_id User ID
     * @param string $user_type User type
     * @return bool Success
     */
    public function set_primary_role(int $role_id, int $user_id, string $user_type): bool {
        $table = $this->get_table_name();

        // First, remove primary flag from all user's roles
        $this->wpdb->update(
            $table,
            ['is_primary' => 0],
            [
                'user_id' => $user_id,
                'user_type' => $user_type,
            ]
        );

        // Then set the specified role as primary
        return $this->update($role_id, ['is_primary' => 1]);
    }

    /**
     * Find existing role
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param string $role_type Role type
     * @param int|null $transaction_id Transaction ID
     * @param int|null $account_id Account ID
     * @return UserRole|null
     */
    private function find_existing_role(
        int $user_id,
        string $user_type,
        string $role_type,
        ?int $transaction_id,
        ?int $account_id
    ): ?UserRole {
        $table = $this->get_table_name();

        $sql = "SELECT * FROM {$table}
                WHERE user_id = %d
                AND user_type = %s
                AND role_type = %s";

        $params = [$user_id, $user_type, $role_type];

        // Handle transaction_id (can be NULL)
        if ($transaction_id === null) {
            $sql .= " AND transaction_id IS NULL";
        } else {
            $sql .= " AND transaction_id = %d";
            $params[] = $transaction_id;
        }

        // Handle account_id (can be NULL)
        if ($account_id === null) {
            $sql .= " AND account_id IS NULL";
        } else {
            $sql .= " AND account_id = %d";
            $params[] = $account_id;
        }

        $sql .= " LIMIT 1";

        $result = $this->wpdb->get_row(
            $this->wpdb->prepare($sql, ...$params),
            ARRAY_A
        );

        return $result ? new UserRole($result) : null;
    }

    /**
     * Check if user has role
     *
     * @param int $user_id User ID
     * @param string $user_type User type
     * @param string $role_type Role type
     * @param int|null $transaction_id Optional transaction context
     * @return bool
     */
    public function user_has_role(
        int $user_id,
        string $user_type,
        string $role_type,
        ?int $transaction_id = null
    ): bool {
        $table = $this->get_table_name();

        $sql = "SELECT COUNT(*) FROM {$table}
                WHERE user_id = %d AND user_type = %s AND role_type = %s
                AND (revoked_at IS NULL OR revoked_at > NOW())
                AND (expires_at IS NULL OR expires_at > NOW())";

        $params = [$user_id, $user_type, $role_type];

        if ($transaction_id !== null) {
            $sql .= " AND transaction_id = %d";
            $params[] = $transaction_id;
        }

        $count = $this->wpdb->get_var(
            $this->wpdb->prepare($sql, ...$params)
        );

        return (int) $count > 0;
    }

    /**
     * Get users by role type
     *
     * @param string $role_type Role type
     * @param int|null $account_id Filter by account
     * @param int|null $transaction_id Filter by transaction
     * @return array Array of role records with user data
     */
    public function get_users_by_role(
        string $role_type,
        ?int $account_id = null,
        ?int $transaction_id = null
    ): array {
        $table = $this->get_table_name();

        $sql = "SELECT * FROM {$table}
                WHERE role_type = %s
                AND (revoked_at IS NULL OR revoked_at > NOW())
                AND (expires_at IS NULL OR expires_at > NOW())";

        $params = [$role_type];

        if ($account_id !== null) {
            $sql .= " AND account_id = %d";
            $params[] = $account_id;
        }

        if ($transaction_id !== null) {
            $sql .= " AND transaction_id = %d";
            $params[] = $transaction_id;
        }

        $sql .= " ORDER BY created_at ASC";

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, ...$params),
            ARRAY_A
        );

        return $this->hydrate_roles($results);
    }

    /**
     * Get expiring roles
     *
     * @param int $days_until_expiry Number of days until expiration
     * @return array Array of UserRole instances
     */
    public function get_expiring_roles(int $days_until_expiry = 7): array {
        $table = $this->get_table_name();

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE expires_at IS NOT NULL
                AND expires_at > NOW()
                AND expires_at <= DATE_ADD(NOW(), INTERVAL %d DAY)
                AND revoked_at IS NULL
                ORDER BY expires_at ASC",
                $days_until_expiry
            ),
            ARRAY_A
        );

        return $this->hydrate_roles($results);
    }

    /**
     * Clean up expired roles
     *
     * @return int Number of roles cleaned up
     */
    public function cleanup_expired_roles(): int {
        $table = $this->get_table_name();

        // We don't delete expired roles, just mark them as revoked
        $result = $this->wpdb->query(
            "UPDATE {$table}
            SET revoked_at = NOW()
            WHERE expires_at IS NOT NULL
            AND expires_at <= NOW()
            AND revoked_at IS NULL"
        );

        return (int) $result;
    }

    /**
     * Count roles by type
     *
     * @param string $role_type Role type
     * @param int|null $account_id Filter by account
     * @return int Count
     */
    public function count_by_role_type(string $role_type, ?int $account_id = null): int {
        $table = $this->get_table_name();

        $sql = "SELECT COUNT(*) FROM {$table}
                WHERE role_type = %s
                AND (revoked_at IS NULL OR revoked_at > NOW())
                AND (expires_at IS NULL OR expires_at > NOW())";

        $params = [$role_type];

        if ($account_id !== null) {
            $sql .= " AND account_id = %d";
            $params[] = $account_id;
        }

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare($sql, ...$params)
        );
    }

    /**
     * Hydrate array of role data into UserRole instances
     *
     * @param array $results Array of role data arrays
     * @return array Array of UserRole instances
     */
    private function hydrate_roles(array $results): array {
        return array_map(function ($data) {
            return new UserRole($data);
        }, $results);
    }
}
