<?php
/**
 * Document Repository
 *
 * @package MADealRoom\Repositories
 * @since 1.0.0
 */

namespace MADealRoom\Repositories;

use MADealRoom\Models\Document;

/**
 * Document repository
 */
class DocumentRepository extends BaseRepository {
	protected $table = 'ma_deal_documents';
	protected $model_class = Document::class;

	/**
	 * Find documents by transaction
	 *
	 * @param int $transaction_id Transaction ID
	 * @param array $options Query options
	 * @return array
	 */
	public function findByTransaction(int $transaction_id, array $options = []): array {
		$default_options = ['order_by' => 'created_at', 'order' => 'DESC'];
		$options = array_merge($default_options, $options);

		return $this->query(
			['transaction_id' => $transaction_id],
			$options
		);
	}

	/**
	 * Find documents by account
	 *
	 * @param int $account_id Account ID
	 * @param array $options Query options
	 * @return array
	 */
	public function findByAccount(int $account_id, array $options = []): array {
		$default_options = ['order_by' => 'created_at', 'order' => 'DESC'];
		$options = array_merge($default_options, $options);

		return $this->query(
			['account_id' => $account_id],
			$options
		);
	}

	/**
	 * Find documents by type
	 *
	 * @param int $transaction_id Transaction ID
	 * @param string $document_type Document type
	 * @param array $options Query options
	 * @return array
	 */
	public function findByType(int $transaction_id, string $document_type, array $options = []): array {
		$default_options = ['order_by' => 'created_at', 'order' => 'DESC'];
		$options = array_merge($default_options, $options);

		return $this->query(
			[
				'transaction_id' => $transaction_id,
				'document_type' => $document_type,
			],
			$options
		);
	}

	/**
	 * Find document by public token
	 *
	 * @param string $token Public token
	 * @return Document|null
	 */
	public function findByPublicToken(string $token): ?Document {
		$results = $this->query(['public_token' => $token, 'is_public' => 1]);
		return $results[0] ?? null;
	}

	/**
	 * Get document statistics for transaction
	 *
	 * @param int $transaction_id Transaction ID
	 * @return array
	 */
	public function getStatistics(int $transaction_id): array {
		$table = $this->get_table_name();

		$results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT
					document_type,
					COUNT(*) as count,
					SUM(file_size) as total_size
				FROM {$table}
				WHERE transaction_id = %d
				GROUP BY document_type",
				$transaction_id
			),
			ARRAY_A
		);

		$stats = [
			'total_documents' => 0,
			'total_size' => 0,
			'by_type' => [],
		];

		foreach ($results as $row) {
			$stats['total_documents'] += (int) $row['count'];
			$stats['total_size'] += (int) $row['total_size'];
			$stats['by_type'][$row['document_type'] ?? 'other'] = [
				'count' => (int) $row['count'],
				'size' => (int) $row['total_size'],
			];
		}

		return $stats;
	}

	/**
	 * Delete document and its file
	 *
	 * @param int $id Document ID
	 * @return bool
	 */
	public function deleteWithFile(int $id): bool {
		$document = $this->find($id);

		if (!$document) {
			return false;
		}

		// Delete file from disk
		$file_path = $document->getFilePath();
		if (file_exists($file_path)) {
			@unlink($file_path);
		}

		// Delete database record
		return $this->delete($id);
	}

	/**
	 * Generate unique public token
	 *
	 * @return string
	 */
	public function generatePublicToken(): string {
		do {
			$token = bin2hex(random_bytes(32));
			$existing = $this->findByPublicToken($token);
		} while ($existing !== null);

		return $token;
	}
}
