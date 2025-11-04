<?php
/**
 * User Invitation Repository
 *
 * Database operations for user invitations.
 *
 * @package    MADealRoom\Repositories
 * @since      2.0.0
 */

namespace MADealRoom\Repositories;

use MADealRoom\Models\UserInvitation;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * UserInvitationRepository Class
 *
 * Handles database operations for role-based user invitations.
 */
class UserInvitationRepository extends BaseRepository {

    /**
     * Table name (without prefix)
     *
     * @var string
     */
    protected $table = 'ma_deal_user_invitations';

    /**
     * Model class name
     *
     * @var string
     */
    protected $model_class = UserInvitation::class;

    /**
     * Allowed column names for SQL safety
     *
     * @var array
     */
    protected $allowed_columns = [
        'id',
        'email',
        'role_type',
        'invited_by_user_id',
        'invited_by_user_type',
        'account_id',
        'transaction_id',
        'token_hash',
        'message',
        'expires_at',
        'accepted_at',
        'accepted_by_user_id',
        'declined_at',
        'cancelled_at',
        'created_at',
    ];

    /**
     * Create an invitation
     *
     * @param UserInvitation $invitation Invitation model
     * @return int|false Invitation ID or false on failure
     */
    public function create_invitation(UserInvitation $invitation) {
        $data = $invitation->get_database_data();
        $result = $this->create($data);

        if ($result) {
            $invitation->id = $result;
        }

        return $result;
    }

    /**
     * Find invitation by token hash
     *
     * @param string $token_hash Hashed invitation token
     * @return UserInvitation|null
     */
    public function find_by_token(string $token_hash): ?UserInvitation {
        $table = $this->get_table_name();

        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE token_hash = %s
                LIMIT 1",
                $token_hash
            ),
            ARRAY_A
        );

        return $result ? new UserInvitation($result) : null;
    }

    /**
     * Get pending invitations
     *
     * @param int|null $account_id Filter by account
     * @param int|null $transaction_id Filter by transaction
     * @return array Array of UserInvitation instances
     */
    public function get_pending_invitations(?int $account_id = null, ?int $transaction_id = null): array {
        $table = $this->get_table_name();

        $sql = "SELECT * FROM {$table}
                WHERE accepted_at IS NULL
                AND declined_at IS NULL
                AND cancelled_at IS NULL
                AND expires_at > NOW()";

        $params = [];

        if ($account_id !== null) {
            $sql .= " AND account_id = %d";
            $params[] = $account_id;
        }

        if ($transaction_id !== null) {
            $sql .= " AND transaction_id = %d";
            $params[] = $transaction_id;
        }

        $sql .= " ORDER BY created_at DESC";

        if (!empty($params)) {
            $sql = $this->wpdb->prepare($sql, ...$params);
        }

        $results = $this->wpdb->get_results($sql, ARRAY_A);

        return $this->hydrate_invitations($results);
    }

    /**
     * Get invitations for email
     *
     * @param string $email Email address
     * @param bool $pending_only Only pending invitations
     * @return array Array of UserInvitation instances
     */
    public function get_invitations_by_email(string $email, bool $pending_only = true): array {
        $table = $this->get_table_name();

        $sql = "SELECT * FROM {$table} WHERE email = %s";

        if ($pending_only) {
            $sql .= " AND accepted_at IS NULL
                     AND declined_at IS NULL
                     AND cancelled_at IS NULL
                     AND expires_at > NOW()";
        }

        $sql .= " ORDER BY created_at DESC";

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $email),
            ARRAY_A
        );

        return $this->hydrate_invitations($results);
    }

    /**
     * Get invitations sent by user
     *
     * @param int $user_id User ID who sent invitations
     * @param string $user_type User type
     * @param string|null $status Filter by status (pending|accepted|declined|cancelled|expired)
     * @return array Array of UserInvitation instances
     */
    public function get_sent_invitations(int $user_id, string $user_type, ?string $status = null): array {
        $table = $this->get_table_name();

        $sql = "SELECT * FROM {$table}
                WHERE invited_by_user_id = %d
                AND invited_by_user_type = %s";

        $params = [$user_id, $user_type];

        if ($status === 'pending') {
            $sql .= " AND accepted_at IS NULL
                     AND declined_at IS NULL
                     AND cancelled_at IS NULL
                     AND expires_at > NOW()";
        } elseif ($status === 'accepted') {
            $sql .= " AND accepted_at IS NOT NULL";
        } elseif ($status === 'declined') {
            $sql .= " AND declined_at IS NOT NULL";
        } elseif ($status === 'cancelled') {
            $sql .= " AND cancelled_at IS NOT NULL";
        } elseif ($status === 'expired') {
            $sql .= " AND expires_at <= NOW()
                     AND accepted_at IS NULL
                     AND declined_at IS NULL
                     AND cancelled_at IS NULL";
        }

        $sql .= " ORDER BY created_at DESC";

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, ...$params),
            ARRAY_A
        );

        return $this->hydrate_invitations($results);
    }

    /**
     * Get invitations for transaction
     *
     * @param int $transaction_id Transaction ID
     * @param bool $pending_only Only pending invitations
     * @return array Array of UserInvitation instances
     */
    public function get_transaction_invitations(int $transaction_id, bool $pending_only = true): array {
        $table = $this->get_table_name();

        $sql = "SELECT * FROM {$table} WHERE transaction_id = %d";

        if ($pending_only) {
            $sql .= " AND accepted_at IS NULL
                     AND declined_at IS NULL
                     AND cancelled_at IS NULL
                     AND expires_at > NOW()";
        }

        $sql .= " ORDER BY created_at DESC";

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $transaction_id),
            ARRAY_A
        );

        return $this->hydrate_invitations($results);
    }

    /**
     * Accept invitation
     *
     * @param int $invitation_id Invitation ID
     * @param int $user_id User who accepted
     * @return bool Success
     */
    public function accept_invitation(int $invitation_id, int $user_id): bool {
        return $this->update($invitation_id, [
            'accepted_at' => current_time('mysql'),
            'accepted_by_user_id' => $user_id,
        ]);
    }

    /**
     * Decline invitation
     *
     * @param int $invitation_id Invitation ID
     * @return bool Success
     */
    public function decline_invitation(int $invitation_id): bool {
        return $this->update($invitation_id, [
            'declined_at' => current_time('mysql'),
        ]);
    }

    /**
     * Cancel invitation
     *
     * @param int $invitation_id Invitation ID
     * @return bool Success
     */
    public function cancel_invitation(int $invitation_id): bool {
        return $this->update($invitation_id, [
            'cancelled_at' => current_time('mysql'),
        ]);
    }

    /**
     * Check if pending invitation exists for email and role
     *
     * @param string $email Email address
     * @param string $role_type Role type
     * @param int|null $transaction_id Transaction ID
     * @return bool
     */
    public function has_pending_invitation(string $email, string $role_type, ?int $transaction_id = null): bool {
        $table = $this->get_table_name();

        $sql = "SELECT COUNT(*) FROM {$table}
                WHERE email = %s
                AND role_type = %s
                AND accepted_at IS NULL
                AND declined_at IS NULL
                AND cancelled_at IS NULL
                AND expires_at > NOW()";

        $params = [$email, $role_type];

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
     * Get expired invitations
     *
     * @param int|null $account_id Filter by account
     * @return array Array of UserInvitation instances
     */
    public function get_expired_invitations(?int $account_id = null): array {
        $table = $this->get_table_name();

        $sql = "SELECT * FROM {$table}
                WHERE expires_at <= NOW()
                AND accepted_at IS NULL
                AND declined_at IS NULL
                AND cancelled_at IS NULL";

        $params = [];

        if ($account_id !== null) {
            $sql .= " AND account_id = %d";
            $params[] = $account_id;
        }

        $sql .= " ORDER BY expires_at DESC";

        if (!empty($params)) {
            $sql = $this->wpdb->prepare($sql, ...$params);
        }

        $results = $this->wpdb->get_results($sql, ARRAY_A);

        return $this->hydrate_invitations($results);
    }

    /**
     * Get expiring invitations
     *
     * @param int $hours Hours until expiration
     * @return array Array of UserInvitation instances
     */
    public function get_expiring_invitations(int $hours = 24): array {
        $table = $this->get_table_name();

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE expires_at > NOW()
                AND expires_at <= DATE_ADD(NOW(), INTERVAL %d HOUR)
                AND accepted_at IS NULL
                AND declined_at IS NULL
                AND cancelled_at IS NULL
                ORDER BY expires_at ASC",
                $hours
            ),
            ARRAY_A
        );

        return $this->hydrate_invitations($results);
    }

    /**
     * Clean up old invitations
     *
     * @param int $days_old Delete invitations older than this many days
     * @return int Number of invitations deleted
     */
    public function cleanup_old_invitations(int $days_old = 30): int {
        $table = $this->get_table_name();

        // Delete expired/declined/cancelled invitations older than specified days
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$table}
                WHERE (
                    expires_at < DATE_SUB(NOW(), INTERVAL %d DAY)
                    OR declined_at < DATE_SUB(NOW(), INTERVAL %d DAY)
                    OR cancelled_at < DATE_SUB(NOW(), INTERVAL %d DAY)
                )",
                $days_old,
                $days_old,
                $days_old
            )
        );

        return (int) $result;
    }

    /**
     * Count invitations by status
     *
     * @param int|null $account_id Filter by account
     * @return array Status counts
     */
    public function count_by_status(?int $account_id = null): array {
        $table = $this->get_table_name();

        $sql = "SELECT
                    SUM(CASE WHEN accepted_at IS NULL AND declined_at IS NULL AND cancelled_at IS NULL AND expires_at > NOW() THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN accepted_at IS NOT NULL THEN 1 ELSE 0 END) as accepted,
                    SUM(CASE WHEN declined_at IS NOT NULL THEN 1 ELSE 0 END) as declined,
                    SUM(CASE WHEN cancelled_at IS NOT NULL THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN expires_at <= NOW() AND accepted_at IS NULL AND declined_at IS NULL AND cancelled_at IS NULL THEN 1 ELSE 0 END) as expired
                FROM {$table}";

        $params = [];

        if ($account_id !== null) {
            $sql .= " WHERE account_id = %d";
            $params[] = $account_id;
        }

        if (!empty($params)) {
            $sql = $this->wpdb->prepare($sql, ...$params);
        }

        $result = $this->wpdb->get_row($sql, ARRAY_A);

        return $result ?: [
            'pending' => 0,
            'accepted' => 0,
            'declined' => 0,
            'cancelled' => 0,
            'expired' => 0,
        ];
    }

    /**
     * Get invitation statistics for account
     *
     * @param int $account_id Account ID
     * @param int $days Number of days to analyze
     * @return array Statistics
     */
    public function get_invitation_stats(int $account_id, int $days = 30): array {
        $table = $this->get_table_name();

        $stats = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT
                    COUNT(*) as total_sent,
                    SUM(CASE WHEN accepted_at IS NOT NULL THEN 1 ELSE 0 END) as accepted_count,
                    SUM(CASE WHEN declined_at IS NOT NULL THEN 1 ELSE 0 END) as declined_count,
                    SUM(CASE WHEN cancelled_at IS NOT NULL THEN 1 ELSE 0 END) as cancelled_count,
                    SUM(CASE WHEN expires_at <= NOW() AND accepted_at IS NULL THEN 1 ELSE 0 END) as expired_count,
                    AVG(CASE WHEN accepted_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, created_at, accepted_at) END) as avg_acceptance_hours,
                    COUNT(DISTINCT email) as unique_recipients,
                    COUNT(DISTINCT role_type) as role_types_invited
                FROM {$table}
                WHERE account_id = %d
                AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
                $account_id,
                $days
            ),
            ARRAY_A
        );

        // Calculate acceptance rate
        if ($stats && $stats['total_sent'] > 0) {
            $stats['acceptance_rate'] = ($stats['accepted_count'] / $stats['total_sent']) * 100;
        } else {
            $stats['acceptance_rate'] = 0;
        }

        return $stats ?: [];
    }

    /**
     * Update invitation
     *
     * @param UserInvitation $invitation Invitation model
     * @return bool Success
     */
    public function update_invitation(UserInvitation $invitation): bool {
        if (!isset($invitation->id)) {
            return false;
        }

        $data = $invitation->get_database_data();
        return $this->update($invitation->id, $data);
    }

    /**
     * Hydrate array of invitation data into UserInvitation instances
     *
     * @param array $results Array of invitation data arrays
     * @return array Array of UserInvitation instances
     */
    private function hydrate_invitations(array $results): array {
        return array_map(function ($data) {
            return new UserInvitation($data);
        }, $results);
    }
}
