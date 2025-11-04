<?php
/**
 * VendorRating Model
 *
 * @package MADealRoom\Models
 * @since 2.1.0
 */

namespace MADealRoom\Models;

/**
 * VendorRating model representing agent ratings for vendor performance
 */
class VendorRating {
	public int $id;
	public int $vendor_request_id;
	public int $transaction_id;
	public string $vendor_email;
	public int $rated_by_user_id;
	public int $rating; // 1-5 stars (overall)
	public ?int $timeliness_rating = null;
	public ?int $quality_rating = null;
	public ?int $communication_rating = null;
	public ?string $review = null;
	public bool $would_recommend = true;
	public string $created_at;

	/**
	 * Constructor
	 *
	 * @param array $data Initial data
	 */
	public function __construct(array $data = []) {
		foreach ($data as $key => $value) {
			if (property_exists($this, $key)) {
				// Handle boolean conversion
				if ($key === 'would_recommend' && !is_bool($value)) {
					$this->$key = (bool) $value;
				}
				// Handle integer conversion for ratings
				else if (in_array($key, ['rating', 'timeliness_rating', 'quality_rating', 'communication_rating']) && $value !== null) {
					$this->$key = (int) $value;
				} else {
					$this->$key = $value;
				}
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
	 * Calculate average of detailed ratings
	 *
	 * @return float|null
	 */
	public function getDetailedAverage(): ?float {
		$ratings = array_filter([
			$this->timeliness_rating,
			$this->quality_rating,
			$this->communication_rating,
		]);

		if (empty($ratings)) {
			return null;
		}

		return round(array_sum($ratings) / count($ratings), 2);
	}

	/**
	 * Check if rating is positive (4 or 5 stars)
	 *
	 * @return bool
	 */
	public function isPositive(): bool {
		return $this->rating >= 4;
	}

	/**
	 * Check if rating is negative (1 or 2 stars)
	 *
	 * @return bool
	 */
	public function isNegative(): bool {
		return $this->rating <= 2;
	}

	/**
	 * Get star rating as string (e.g., "★★★★☆")
	 *
	 * @return string
	 */
	public function getStarString(): string {
		$filled = str_repeat('★', $this->rating);
		$empty = str_repeat('☆', 5 - $this->rating);
		return $filled . $empty;
	}

	/**
	 * Validate rating values
	 *
	 * @return bool
	 */
	public function isValid(): bool {
		if ($this->rating < 1 || $this->rating > 5) {
			return false;
		}

		$ratings = [$this->timeliness_rating, $this->quality_rating, $this->communication_rating];
		foreach ($ratings as $rating) {
			if ($rating !== null && ($rating < 1 || $rating > 5)) {
				return false;
			}
		}

		return true;
	}
}
