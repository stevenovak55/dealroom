<?php
/**
 * MLS Configuration Model
 *
 * @package MADealRoom\Models
 * @since 1.0.0
 */

namespace MADealRoom\Models;

/**
 * MLS Configuration model representing an MLS provider configuration
 */
class MLSConfig {
	public int $id;
	public ?int $account_id = null;
	public string $name;
	public string $provider_type; // bridge, mlspin, etc.
	public string $credentials; // JSON encrypted credentials
	public ?string $settings = null; // JSON additional settings
	public bool $is_active = true;
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
				// Convert string booleans to actual booleans
				if ($key === 'is_active' && is_string($value)) {
					$this->$key = filter_var($value, FILTER_VALIDATE_BOOLEAN);
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
		$array = get_object_vars($this);

		// Parse credentials if JSON
		if (isset($array['credentials']) && is_string($array['credentials'])) {
			$decoded = json_decode($array['credentials'], true);
			if (json_last_error() === JSON_ERROR_NONE) {
				$array['credentials'] = $decoded;
			}
		}

		// Parse settings if JSON
		if (isset($array['settings']) && is_string($array['settings']) && !empty($array['settings'])) {
			$decoded = json_decode($array['settings'], true);
			if (json_last_error() === JSON_ERROR_NONE) {
				$array['settings'] = $decoded;
			}
		}

		return $array;
	}

	/**
	 * Get decrypted credentials
	 *
	 * @return array
	 */
	public function getCredentials(): array {
		if (empty($this->credentials)) {
			return [];
		}

		$decoded = json_decode($this->credentials, true);
		return json_last_error() === JSON_ERROR_NONE ? $decoded : [];
	}

	/**
	 * Get settings
	 *
	 * @return array
	 */
	public function getSettings(): array {
		if (empty($this->settings)) {
			return [];
		}

		$decoded = json_decode($this->settings, true);
		return json_last_error() === JSON_ERROR_NONE ? $decoded : [];
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
}
