<?php
/**
 * Document Model
 *
 * @package MADealRoom\Models
 * @since 1.0.0
 */

namespace MADealRoom\Models;

/**
 * Document model representing an uploaded file
 */
class Document {
	public int $id;
	public int $transaction_id;
	public int $account_id;
	public int $uploaded_by_user_id;
	public string $file_name;
	public string $file_path;
	public int $file_size = 0;
	public string $mime_type;
	public ?string $document_type = null; // contract, inspection, disclosure, etc.
	public ?string $title = null;
	public ?string $description = null;
	public ?array $metadata = null;
	public bool $is_public = false;
	public ?string $public_token = null;
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
				// Handle JSON fields
				if ($key === 'metadata' && is_string($value)) {
					$this->$key = json_decode($value, true);
				}
				// Handle boolean fields
				else if ($key === 'is_public' && is_string($value)) {
					$this->$key = (bool) $value;
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
		$data = get_object_vars($this);

		// Convert JSON fields to strings if needed
		if (isset($data['metadata']) && is_array($data['metadata'])) {
			$data['metadata'] = json_encode($data['metadata']);
		}

		return $data;
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
	 * Get file URL
	 *
	 * @return string File URL
	 */
	public function getFileUrl(): string {
		$upload_dir = wp_upload_dir();
		return $upload_dir['baseurl'] . '/' . $this->file_path;
	}

	/**
	 * Get file full path
	 *
	 * @return string Full file path on disk
	 */
	public function getFilePath(): string {
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'] . '/' . $this->file_path;
	}

	/**
	 * Get formatted file size
	 *
	 * @return string Formatted file size (e.g., "2.5 MB")
	 */
	public function getFormattedSize(): string {
		$size = $this->file_size;
		$units = ['B', 'KB', 'MB', 'GB'];
		$i = 0;

		while ($size >= 1024 && $i < count($units) - 1) {
			$size /= 1024;
			$i++;
		}

		return round($size, 2) . ' ' . $units[$i];
	}
}
