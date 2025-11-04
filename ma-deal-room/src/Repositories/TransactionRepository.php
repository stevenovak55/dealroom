<?php
/**
 * Transaction Repository
 *
 * @package MADealRoom\Repositories
 * @since 1.0.0
 */

namespace MADealRoom\Repositories;

use MADealRoom\Models\Transaction;

/**
 * Transaction repository
 */
class TransactionRepository extends BaseRepository {
	/**
	 * Table name
	 *
	 * @var string
	 */
	protected $table = 'ma_deal_transactions';

	/**
	 * Model class
	 *
	 * @var string
	 */
	protected $model_class = Transaction::class;

	/**
	 * Allowed columns for queries (SQL injection prevention)
	 *
	 * @var array
	 */
	protected $allowed_columns = [
		'id',
		'account_id',
		'property_address',
		'property_city',
		'property_state',
		'property_zip',
		'property_type',
		'property_year_built',
		'property_metadata',
		'sale_price',
		'transaction_side',
		'status',
		'listing_date',
		'offer_accepted_date',
		'ps_agreement_date',
		'closing_date',
		'actual_closing_date',
		'assigned_agent_id',
		'template_id',
		'notes',
		'created_at',
		'updated_at'
	];

	/**
	 * Find transactions by account
	 *
	 * @param int $account_id Account ID
	 * @param array $options Query options
	 * @return array
	 */
	public function findByAccount(int $account_id, array $options = []): array {
		return $this->query(
			['account_id' => $account_id],
			$options
		);
	}

	/**
	 * Find active transactions by account
	 *
	 * @param int $account_id Account ID
	 * @return array
	 */
	public function findActive(int $account_id): array {
		$table = $this->get_table_name();

		$results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE account_id = %d
				AND status IN ('listing_active', 'under_agreement')
				ORDER BY closing_date ASC",
				$account_id
			),
			ARRAY_A
		);

		return $this->hydrate_models($results ?: []);
	}

	/**
	 * Find transactions by status
	 *
	 * @param string $status Transaction status
	 * @param int|null $account_id Optional account filter
	 * @return array
	 */
	public function findByStatus(string $status, ?int $account_id = null): array {
		$conditions = ['status' => $status];

		if ($account_id !== null) {
			$conditions['account_id'] = $account_id;
		}

		return $this->query($conditions);
	}

	/**
	 * Get upcoming closings
	 *
	 * @param int $account_id Account ID
	 * @param int $days_ahead Number of days to look ahead
	 * @return array
	 */
	public function getUpcomingClosings(int $account_id, int $days_ahead = 30): array {
		$table = $this->get_table_name();

		$results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE account_id = %d
				AND status = 'under_agreement'
				AND closing_date IS NOT NULL
				AND closing_date <= DATE_ADD(CURDATE(), INTERVAL %d DAY)
				ORDER BY closing_date ASC",
				$account_id,
				$days_ahead
			),
			ARRAY_A
		);

		return $this->hydrate_models($results ?: []);
	}

	/**
	 * Count transactions using a specific template
	 *
	 * @param int $template_id Template ID
	 * @return int Number of transactions using the template
	 */
	public function countByTemplateId(int $template_id): int {
		return $this->count(['template_id' => $template_id]);
	}
}
