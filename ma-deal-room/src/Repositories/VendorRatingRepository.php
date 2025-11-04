<?php
/**
 * VendorRating Repository
 *
 * @package MADealRoom\Repositories
 * @since 2.1.0
 */

namespace MADealRoom\Repositories;

use MADealRoom\Models\VendorRating;

class VendorRatingRepository extends BaseRepository {
	protected $table = 'ma_deal_vendor_ratings';
	protected $model_class = VendorRating::class;

	/**
	 * Get rating for a vendor request
	 *
	 * @param int $vendor_request_id Vendor request ID
	 * @return object|null
	 */
	public function getByVendorRequest(int $vendor_request_id): ?object {
		$results = $this->query(['vendor_request_id' => $vendor_request_id], ['limit' => 1]);
		return $results[0] ?? null;
	}

	/**
	 * Get all ratings for a vendor (by email)
	 *
	 * @param string $vendor_email Vendor email
	 * @param array $options Query options
	 * @return array
	 */
	public function getByVendorEmail(string $vendor_email, array $options = []): array {
		$defaults = [
			'order' => 'created_at',
			'direction' => 'DESC',
		];
		$options = array_merge($defaults, $options);

		return $this->query(['vendor_email' => $vendor_email], $options);
	}

	/**
	 * Calculate average rating for a vendor
	 *
	 * @param string $vendor_email Vendor email
	 * @return float|null
	 */
	public function getAverageRating(string $vendor_email): ?float {
		global $wpdb;
		$table = $wpdb->prefix . $this->table;

		$avg = $wpdb->get_var($wpdb->prepare(
			"SELECT AVG(rating) FROM {$table} WHERE vendor_email = %s",
			$vendor_email
		));

		return $avg ? round((float) $avg, 2) : null;
	}

	/**
	 * Get vendor statistics
	 *
	 * @param string $vendor_email Vendor email
	 * @return array Statistics array
	 */
	public function getVendorStats(string $vendor_email): array {
		global $wpdb;
		$table = $wpdb->prefix . $this->table;

		$stats = $wpdb->get_row($wpdb->prepare(
			"SELECT
				COUNT(*) as total_ratings,
				AVG(rating) as avg_overall,
				AVG(timeliness_rating) as avg_timeliness,
				AVG(quality_rating) as avg_quality,
				AVG(communication_rating) as avg_communication,
				SUM(would_recommend) as recommend_count,
				COUNT(*) as total_count
			FROM {$table}
			WHERE vendor_email = %s",
			$vendor_email
		), ARRAY_A);

		if (!$stats || $stats['total_ratings'] == 0) {
			return [
				'total_ratings' => 0,
				'average_rating' => null,
				'avg_timeliness' => null,
				'avg_quality' => null,
				'avg_communication' => null,
				'recommend_percentage' => null,
			];
		}

		return [
			'total_ratings' => (int) $stats['total_ratings'],
			'average_rating' => $stats['avg_overall'] ? round((float) $stats['avg_overall'], 2) : null,
			'avg_timeliness' => $stats['avg_timeliness'] ? round((float) $stats['avg_timeliness'], 2) : null,
			'avg_quality' => $stats['avg_quality'] ? round((float) $stats['avg_quality'], 2) : null,
			'avg_communication' => $stats['avg_communication'] ? round((float) $stats['avg_communication'], 2) : null,
			'recommend_percentage' => round(((int) $stats['recommend_count'] / (int) $stats['total_count']) * 100, 1),
		];
	}

	/**
	 * Get rating distribution for a vendor
	 *
	 * @param string $vendor_email Vendor email
	 * @return array Distribution of ratings (1-5 stars)
	 */
	public function getRatingDistribution(string $vendor_email): array {
		global $wpdb;
		$table = $wpdb->prefix . $this->table;

		$results = $wpdb->get_results($wpdb->prepare(
			"SELECT rating, COUNT(*) as count
			FROM {$table}
			WHERE vendor_email = %s
			GROUP BY rating
			ORDER BY rating DESC",
			$vendor_email
		), ARRAY_A);

		$distribution = [
			5 => 0,
			4 => 0,
			3 => 0,
			2 => 0,
			1 => 0,
		];

		foreach ($results as $row) {
			$distribution[(int) $row['rating']] = (int) $row['count'];
		}

		return $distribution;
	}

	/**
	 * Create a rating
	 *
	 * @param array $data Rating data
	 * @return int|false Rating ID or false
	 */
	public function createRating(array $data) {
		$required = ['vendor_request_id', 'transaction_id', 'vendor_email', 'rated_by_user_id', 'rating'];
		foreach ($required as $field) {
			if (!isset($data[$field])) {
				return false;
			}
		}

		// Validate rating values
		if ($data['rating'] < 1 || $data['rating'] > 5) {
			return false;
		}

		$optional_ratings = ['timeliness_rating', 'quality_rating', 'communication_rating'];
		foreach ($optional_ratings as $field) {
			if (isset($data[$field])) {
				if ($data[$field] < 1 || $data[$field] > 5) {
					return false;
				}
			}
		}

		return $this->create($data);
	}

	/**
	 * Check if a vendor request already has a rating
	 *
	 * @param int $vendor_request_id Vendor request ID
	 * @return bool
	 */
	public function hasRating(int $vendor_request_id): bool {
		return $this->getByVendorRequest($vendor_request_id) !== null;
	}

	/**
	 * Get recent reviews for a vendor
	 *
	 * @param string $vendor_email Vendor email
	 * @param int $limit Number of reviews to return
	 * @return array
	 */
	public function getRecentReviews(string $vendor_email, int $limit = 5): array {
		return $this->query([
			'vendor_email' => $vendor_email,
		], [
			'order' => 'created_at',
			'direction' => 'DESC',
			'limit' => $limit,
		]);
	}

	/**
	 * Update vendor request with new average rating
	 *
	 * @param string $vendor_email Vendor email
	 * @return bool
	 */
	public function updateVendorRequestRatings(string $vendor_email): bool {
		$average = $this->getAverageRating($vendor_email);

		if ($average === null) {
			return true; // No ratings yet, nothing to update
		}

		global $wpdb;
		$vendor_requests_table = $wpdb->prefix . 'ma_deal_vendor_requests';

		$result = $wpdb->query($wpdb->prepare(
			"UPDATE {$vendor_requests_table}
			SET average_rating = %f
			WHERE vendor_email = %s",
			$average,
			$vendor_email
		));

		return $result !== false;
	}
}
