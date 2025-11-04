<?php
/**
 * VendorAvailability Repository
 *
 * @package MADealRoom\Repositories
 * @since 2.1.0
 */

namespace MADealRoom\Repositories;

use MADealRoom\Models\VendorAvailability;

class VendorAvailabilityRepository extends BaseRepository {
	protected $table = 'ma_deal_vendor_availability';
	protected $model_class = VendorAvailability::class;
	protected $allowed_columns = [
		'id', 'vendor_request_id', 'available_date', 'start_time',
		'end_time', 'timezone', 'notes', 'created_at', 'updated_at'
	];

	/**
	 * Get availability windows for a vendor request
	 *
	 * @param int $vendor_request_id Vendor request ID
	 * @param bool $future_only Only return future availability
	 * @return array
	 */
	public function getByVendorRequest(int $vendor_request_id, bool $future_only = true): array {
		$conditions = ['vendor_request_id' => $vendor_request_id];
		return $this->query($conditions, ['use_cache' => !$future_only]);
	}

	/**
	 * Get availability for a specific date
	 *
	 * @param int $vendor_request_id Vendor request ID
	 * @param string $date Date in Y-m-d format
	 * @return array
	 */
	public function getByDate(int $vendor_request_id, string $date): array {
		return $this->query([
			'vendor_request_id' => $vendor_request_id,
			'available_date' => $date,
		], [
			'order' => 'start_time',
			'direction' => 'ASC',
		]);
	}

	/**
	 * Get availability within a date range
	 *
	 * @param int $vendor_request_id Vendor request ID
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @return array
	 */
	public function getByDateRange(int $vendor_request_id, string $start_date, string $end_date): array {
		global $wpdb;
		$table = $wpdb->prefix . $this->table;

		$sql = $wpdb->prepare(
			"SELECT * FROM {$table}
			WHERE vendor_request_id = %d
			AND available_date BETWEEN %s AND %s
			ORDER BY available_date ASC, start_time ASC",
			$vendor_request_id,
			$start_date,
			$end_date
		);

		$results = $wpdb->get_results($sql, ARRAY_A);
		return $this->hydrate_models($results ?: []);
	}

	/**
	 * Add availability window
	 *
	 * @param array $data Availability data
	 * @return int|false Availability ID or false
	 */
	public function addAvailability(array $data) {
		$required = ['vendor_request_id', 'available_date', 'start_time', 'end_time'];
		foreach ($required as $field) {
			if (!isset($data[$field])) {
				return false;
			}
		}

		// Set default timezone if not provided
		if (!isset($data['timezone'])) {
			$data['timezone'] = 'America/New_York';
		}

		return $this->create($data);
	}

	/**
	 * Clear all availability for a vendor request
	 *
	 * @param int $vendor_request_id Vendor request ID
	 * @return bool
	 */
	public function clearAvailability(int $vendor_request_id): bool {
		global $wpdb;
		$table = $wpdb->prefix . $this->table;

		$result = $wpdb->query($wpdb->prepare(
			"DELETE FROM {$table} WHERE vendor_request_id = %d",
			$vendor_request_id
		));

		return $result !== false;
	}

	/**
	 * Check if there's a conflict with existing availability
	 *
	 * @param int $vendor_request_id Vendor request ID
	 * @param string $date Date
	 * @param string $start_time Start time
	 * @param string $end_time End time
	 * @param int|null $exclude_id ID to exclude from conflict check
	 * @return bool True if conflict exists
	 */
	public function hasConflict(int $vendor_request_id, string $date, string $start_time, string $end_time, ?int $exclude_id = null): bool {
		global $wpdb;
		$table = $wpdb->prefix . $this->table;

		$sql = "SELECT COUNT(*) FROM {$table}
				WHERE vendor_request_id = %d
				AND available_date = %s
				AND (
					(start_time <= %s AND end_time > %s) OR
					(start_time < %s AND end_time >= %s) OR
					(start_time >= %s AND end_time <= %s)
				)";

		$params = [$vendor_request_id, $date, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time];

		if ($exclude_id) {
			$sql .= " AND id != %d";
			$params[] = $exclude_id;
		}

		$count = $wpdb->get_var($wpdb->prepare($sql, ...$params));

		return $count > 0;
	}

	/**
	 * Get grouped availability by date
	 *
	 * @param int $vendor_request_id Vendor request ID
	 * @param bool $future_only Only future dates
	 * @return array Availability grouped by date
	 */
	public function getGroupedByDate(int $vendor_request_id, bool $future_only = true): array {
		$availability = $this->getByVendorRequest($vendor_request_id, $future_only);

		$grouped = [];
		foreach ($availability as $slot) {
			$date = $slot->available_date;
			if (!isset($grouped[$date])) {
				$grouped[$date] = [];
			}
			$grouped[$date][] = $slot;
		}

		return $grouped;
	}
}
