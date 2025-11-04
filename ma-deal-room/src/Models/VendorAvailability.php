<?php
/**
 * VendorAvailability Model
 *
 * @package MADealRoom\Models
 * @since 2.1.0
 */

namespace MADealRoom\Models;

/**
 * VendorAvailability model representing vendor availability windows
 */
class VendorAvailability {
	public int $id;
	public int $vendor_request_id;
	public string $available_date;
	public string $start_time;
	public string $end_time;
	public string $timezone = 'America/New_York';
	public ?string $notes = null;
	public string $created_at;

	/**
	 * Constructor
	 *
	 * @param array $data Initial data
	 */
	public function __construct(array $data = []) {
		foreach ($data as $key => $value) {
			if (property_exists($this, $key)) {
				$this->$key = $value;
			}
		}
	}

	/**
	 * Convert model to array
	 *
	 * @return array
	 */
	public function toArray(): array {
		return get_object_vars($this);
	}

	/**
	 * Create instance from array
	 *
	 * @param array $data Data array
	 * @return self
	 */
	public static function fromArray(array $data): self {
		return new self($data);
	}

	/**
	 * Get formatted date range
	 *
	 * @return string
	 */
	public function getFormattedTimeRange(): string {
		$start = date('g:i A', strtotime($this->start_time));
		$end = date('g:i A', strtotime($this->end_time));
		return "{$start} - {$end}";
	}

	/**
	 * Get full datetime for start
	 *
	 * @return string
	 */
	public function getStartDateTime(): string {
		return "{$this->available_date} {$this->start_time}";
	}

	/**
	 * Get full datetime for end
	 *
	 * @return string
	 */
	public function getEndDateTime(): string {
		return "{$this->available_date} {$this->end_time}";
	}

	/**
	 * Check if availability is in the past
	 *
	 * @return bool
	 */
	public function isPast(): bool {
		return strtotime($this->getEndDateTime()) < time();
	}

	/**
	 * Check if availability is today
	 *
	 * @return bool
	 */
	public function isToday(): bool {
		return $this->available_date === date('Y-m-d');
	}
}
