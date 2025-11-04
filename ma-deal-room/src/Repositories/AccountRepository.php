<?php
/**
 * Account Repository
 *
 * @package MADealRoom\Repositories
 * @since 1.0.0
 */

namespace MADealRoom\Repositories;

use MADealRoom\Models\Account;

/**
 * Account repository
 */
class AccountRepository extends BaseRepository {
	/**
	 * Table name
	 *
	 * @var string
	 */	
	protected $table = 'ma_deal_accounts';

	/**
	 * Model class
	 *
	 * @var string
	 */
	protected $model_class = Account::class;

	/**
	 * Allowed columns for queries (SQL injection prevention)
	 *
	 * @var array
	 */
	protected $allowed_columns = [
		'id',
		'name',
		'owner_user_id',
		'status',
		'settings',
		'subscription_tier',
		'subscription_expires_at',
		'created_at',
		'updated_at'
	];

	/**
	 * Find an active account by owner user ID
	 *
	 * @param int $owner_user_id WordPress user ID of the account owner
	 * @return object|null Account model instance or null
	 */
	public function findByOwnerUserId(int $owner_user_id): ?object {
		$results = $this->query(
			['owner_user_id' => $owner_user_id, 'status' => 'active'],
			['limit' => 1]
		);

		return empty($results) ? null : $results[0];
	}

	/**
	 * Find the first active account
	 *
	 * @return object|null Account model instance or null
	 */
	public function findFirstActive(): ?object {
		$results = $this->query(
			['status' => 'active'],
			['limit' => 1]
		);

		return empty($results) ? null : $results[0];
	}

	/**
	 * Find account by user ID (handles both WordPress and custom users)
	 *
	 * For WordPress users: Uses owner_user_id lookup
	 * For custom users: Joins with user_roles table
	 *
	 * @param int $user_id User ID
	 * @param string $user_type 'wordpress' or 'custom'
	 * @return object|null Account model instance or null
	 */
	public function findByUserId(int $user_id, string $user_type = 'wordpress'): ?object {
		if ($user_type === 'wordpress') {
			// WordPress users: use owner_user_id
			return $this->findByOwnerUserId($user_id);
		}

		// Custom users: join with user_roles table
		global $wpdb;
		$user_roles_table = $wpdb->prefix . 'ma_deal_user_roles';
		$accounts_table = $wpdb->prefix . $this->table;

		$sql = "SELECT a.* FROM {$accounts_table} a
				INNER JOIN {$user_roles_table} ur
					ON a.id = ur.account_id
				WHERE ur.user_id = %d
					AND ur.user_type = 'custom'
					AND a.status = 'active'
					AND (ur.revoked_at IS NULL OR ur.revoked_at > NOW())
					AND (ur.expires_at IS NULL OR ur.expires_at > NOW())
				LIMIT 1";

		$result = $wpdb->get_row($wpdb->prepare($sql, $user_id), ARRAY_A);

		if (!$result) {
			return null;
		}

		// Convert to model instance
		$model_class = $this->model_class;
		return new $model_class($result);
	}
}