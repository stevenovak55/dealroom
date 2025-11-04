<?php
/**
 * PropertyAttributes Model
 *
 * @package MADealRoom\Models
 * @since 2.0.0
 */

namespace MADealRoom\Models;

/**
 * PropertyAttributes model representing property-specific characteristics
 * Used for conditional task application based on property features
 */
class PropertyAttributes {
	public int $id;
	public int $transaction_id;

	// Septic & Water Systems
	public bool $has_septic = false;
	public bool $has_well = false;
	public bool $has_shared_well = false;
	public bool $has_city_water = true;
	public bool $has_city_sewer = true;

	// Property Features
	public bool $has_pool = false;
	public bool $has_fireplace = false;
	public bool $has_garage = false;
	public bool $has_basement = false;
	public bool $has_attic = false;
	public bool $has_deck_patio = false;
	public bool $has_shed = false;

	// Occupancy & Status
	public bool $tenant_occupied = false;
	public bool $is_new_construction = false;
	public bool $is_historical = false;
	public bool $is_foreclosure = false;
	public bool $is_short_sale = false;

	// Access & Location
	public bool $on_private_road = false;
	public bool $has_easements = false;
	public bool $is_waterfront = false;
	public bool $in_flood_zone = false;

	// Systems & Utilities
	public ?string $heat_type = null;
	public ?string $cooling_type = null;
	public ?string $roof_type = null;
	public ?string $siding_type = null;

	// Multi-unit Properties
	public int $number_of_units = 1;
	public ?int $number_of_bedrooms = null;
	public ?float $number_of_bathrooms = null;

	// Additional
	public ?int $year_built = null;
	public ?int $square_feet = null;
	public ?float $lot_size_acres = null;

	public string $created_at;
	public string $updated_at;

	/**
	 * Constructor
	 *
	 * @param array $data Initial data
	 */
	public function __construct(array $data = []) {
		foreach ($data as $key => $value) {
			if (property_exists($this, $key)) {
				// Handle boolean conversion for TINYINT(1) from database
				if (is_numeric($value) && $this->isBooleanProperty($key)) {
					$this->$key = (bool) $value;
				} else {
					$this->$key = $value;
				}
			}
		}
	}

	/**
	 * Check if a property should be treated as boolean
	 *
	 * @param string $property Property name
	 * @return bool
	 */
	private function isBooleanProperty(string $property): bool {
		$boolean_properties = [
			'has_septic', 'has_well', 'has_shared_well', 'has_city_water', 'has_city_sewer',
			'has_pool', 'has_fireplace', 'has_garage', 'has_basement', 'has_attic',
			'has_deck_patio', 'has_shed', 'tenant_occupied', 'is_new_construction',
			'is_historical', 'is_foreclosure', 'is_short_sale', 'on_private_road',
			'has_easements', 'is_waterfront', 'in_flood_zone'
		];

		return in_array($property, $boolean_properties);
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
	 * Get the related transaction
	 *
	 * @return Transaction|null
	 */
	public function getTransaction(): ?Transaction {
		global $wpdb;
		$prefix = $wpdb->prefix;

		$transaction_data = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$prefix}ma_deal_transactions WHERE id = %d",
			$this->transaction_id
		), ARRAY_A);

		return $transaction_data ? new Transaction($transaction_data) : null;
	}

	/**
	 * Check if property requires Title 5 septic inspection
	 *
	 * @return bool
	 */
	public function requiresTitle5(): bool {
		return $this->has_septic;
	}

	/**
	 * Check if property requires well water testing
	 *
	 * @return bool
	 */
	public function requiresWellTesting(): bool {
		return $this->has_well || $this->has_shared_well;
	}

	/**
	 * Check if property has pre-1978 construction (lead paint disclosure)
	 *
	 * @return bool
	 */
	public function requiresLeadPaintDisclosure(): bool {
		return $this->year_built !== null && $this->year_built < 1978;
	}

	/**
	 * Check if property is multifamily
	 *
	 * @return bool
	 */
	public function isMultifamily(): bool {
		return $this->number_of_units > 1;
	}

	/**
	 * Check if property has any special features requiring additional tasks
	 *
	 * @return array List of special features
	 */
	public function getSpecialFeatures(): array {
		$features = [];

		if ($this->has_septic) $features[] = 'septic';
		if ($this->has_well) $features[] = 'well';
		if ($this->has_pool) $features[] = 'pool';
		if ($this->tenant_occupied) $features[] = 'tenant_occupied';
		if ($this->on_private_road) $features[] = 'private_road';
		if ($this->is_waterfront) $features[] = 'waterfront';
		if ($this->in_flood_zone) $features[] = 'flood_zone';
		if ($this->is_historical) $features[] = 'historical';
		if ($this->requiresLeadPaintDisclosure()) $features[] = 'lead_paint';

		return $features;
	}

	/**
	 * Get property attributes as associative array for task filtering
	 * Returns simplified boolean array for use in TaskDefinition::appliesToTransaction()
	 *
	 * @return array
	 */
	public function toFilterArray(): array {
		return [
			'has_septic' => $this->has_septic,
			'has_well' => $this->has_well,
			'has_shared_well' => $this->has_shared_well,
			'has_city_water' => $this->has_city_water,
			'has_city_sewer' => $this->has_city_sewer,
			'has_pool' => $this->has_pool,
			'has_fireplace' => $this->has_fireplace,
			'has_garage' => $this->has_garage,
			'has_basement' => $this->has_basement,
			'has_attic' => $this->has_attic,
			'has_deck_patio' => $this->has_deck_patio,
			'has_shed' => $this->has_shed,
			'tenant_occupied' => $this->tenant_occupied,
			'is_new_construction' => $this->is_new_construction,
			'is_historical' => $this->is_historical,
			'is_foreclosure' => $this->is_foreclosure,
			'is_short_sale' => $this->is_short_sale,
			'on_private_road' => $this->on_private_road,
			'has_easements' => $this->has_easements,
			'is_waterfront' => $this->is_waterfront,
			'in_flood_zone' => $this->in_flood_zone,
		];
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
	 * Validate property attributes
	 *
	 * @return array Array of error messages
	 */
	public function validate(): array {
		$errors = [];

		// Validate logical consistency
		if ($this->has_septic && $this->has_city_sewer) {
			$errors[] = 'Property cannot have both septic and city sewer';
		}

		if ($this->has_well && $this->has_city_water && !$this->has_shared_well) {
			$errors[] = 'Property typically has either well or city water, not both';
		}

		if ($this->number_of_units < 1) {
			$errors[] = 'Number of units must be at least 1';
		}

		if ($this->year_built !== null && ($this->year_built < 1600 || $this->year_built > date('Y') + 2)) {
			$errors[] = 'Year built is outside reasonable range';
		}

		if ($this->square_feet !== null && $this->square_feet < 1) {
			$errors[] = 'Square feet must be positive';
		}

		if ($this->lot_size_acres !== null && $this->lot_size_acres <= 0) {
			$errors[] = 'Lot size must be positive';
		}

		return $errors;
	}
}
