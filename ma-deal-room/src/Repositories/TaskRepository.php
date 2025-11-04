<?php
/**
 * Task Repository
 *
 * @package MADealRoom\Repositories
 * @since 1.0.0
 */

namespace MADealRoom\Repositories;

use MADealRoom\Models\Task;

/**
 * Task repository
 */
class TaskRepository extends BaseRepository {
	protected $table = 'ma_deal_tasks';
	protected $model_class = Task::class;

	/**
	 * Allowed columns for queries (SQL injection prevention)
	 *
	 * @var array
	 */
	protected $allowed_columns = [
		'id',
		'transaction_id',
		'template_id',
		'task_definition_id',
		'task_key',
		'title',
		'description',
		'status',
		'owner_role',
		'assigned_party_id',
		'due_at',
		'completed_at',
		'depends_on_task_ids',
		'applies_if_condition',
		'metadata',
		'sort_order',
		'created_at',
		'updated_at'
	];

	/**
	 * Find tasks by transaction
	 *
	 * @param int $transaction_id Transaction ID
	 * @param array $options Query options
	 * @return array
	 */
	public function findByTransaction(int $transaction_id, array $options = []): array {
		$default_options = ['order_by' => 'sort_order', 'order' => 'ASC'];
		$options = array_merge($default_options, $options);

		return $this->query(
			['transaction_id' => $transaction_id],
			$options
		);
	}

	/**
	 * Find overdue tasks
	 *
	 * @param int|null $account_id Optional account filter
	 * @return array
	 */
	public function findOverdue(?int $account_id = null): array {
		$table = $this->get_table_name();
		$transactions_table = $this->wpdb->prefix . 'ma_deal_transactions';

		$account_filter = $account_id ? $this->wpdb->prepare('AND t.account_id = %d', $account_id) : '';

		$sql = "SELECT tk.*
			FROM {$table} tk
			INNER JOIN {$transactions_table} t ON tk.transaction_id = t.id
			WHERE tk.status IN ('pending', 'in_progress')
			AND tk.due_at < NOW()
			{$account_filter}
			ORDER BY tk.due_at ASC";

		$results = $this->wpdb->get_results($sql, ARRAY_A);

		return $this->hydrate_models($results ?: []);
	}

	/**
	 * Mark task as complete
	 *
	 * @param int $task_id Task ID
	 * @return bool
	 */
	public function markComplete(int $task_id): bool {
		return $this->update($task_id, [
			'status' => 'completed',
			'completed_at' => current_time('mysql'),
		]);
	}

	/**
	 * Get task statistics for transaction
	 *
	 * @param int $transaction_id Transaction ID
	 * @return array
	 */
	public function getStatistics(int $transaction_id): array {
		$table = $this->get_table_name();

		$results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT
					status,
					COUNT(*) as count
				FROM {$table}
				WHERE transaction_id = %d
				GROUP BY status",
				$transaction_id
			),
			ARRAY_A
		);

		$stats = [
			'total' => 0,
			'pending' => 0,
			'in_progress' => 0,
			'completed' => 0,
			'blocked' => 0,
			'skipped' => 0,
			'cancelled' => 0,
		];

		foreach ($results ?: [] as $row) {
			$stats[$row['status']] = (int)$row['count'];
			$stats['total'] += (int)$row['count'];
		}

		return $stats;
	}

	/**
	 * Search tasks by title and description
	 *
	 * @param string $search_term Search term
	 * @param int|null $transaction_id Optional transaction filter
	 * @param string|null $status Optional status filter
	 * @param array $options Query options (limit, offset, order_by, order)
	 * @return array
	 */
	public function search(string $search_term, ?int $transaction_id = null, ?string $status = null, array $options = []): array {
		$table = $this->get_table_name();

		// Build WHERE clause
		$where_parts = [];
		$params = [];

		// Search in title and description
		$search_term = '%' . $this->wpdb->esc_like($search_term) . '%';
		$where_parts[] = '(title LIKE %s OR description LIKE %s)';
		$params[] = $search_term;
		$params[] = $search_term;

		// Optional transaction filter
		if ($transaction_id !== null) {
			$where_parts[] = 'transaction_id = %d';
			$params[] = $transaction_id;
		}

		// Optional status filter
		if ($status !== null) {
			$where_parts[] = 'status = %s';
			$params[] = $status;
		}

		$where_clause = 'WHERE ' . implode(' AND ', $where_parts);

		// Build ORDER BY clause
		$order_by = isset($options['order_by']) && $this->validate_column($options['order_by'])
			? $options['order_by']
			: 'sort_order';
		$order = isset($options['order']) && strtoupper($options['order']) === 'DESC' ? 'DESC' : 'ASC';

		// Build LIMIT clause
		$limit = isset($options['limit']) ? (int) $options['limit'] : 100;
		$limit = min($limit, $this->max_per_page); // Enforce max limit
		$offset = isset($options['offset']) ? (int) $options['offset'] : 0;

		// Build and execute query
		$sql = "SELECT * FROM {$table} {$where_clause} ORDER BY {$order_by} {$order} LIMIT %d OFFSET %d";
		$params[] = $limit;
		$params[] = $offset;

		$prepared_sql = $this->wpdb->prepare($sql, ...$params);
		$results = $this->wpdb->get_results($prepared_sql, ARRAY_A);

		return $this->hydrate_models($results ?: []);
	}
}
