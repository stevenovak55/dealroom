<?php
/**
 * MLS Client Interface
 *
 * Standard interface for all MLS (Multiple Listing Service) provider implementations.
 * Supports RETS, Bridge Interactive, ListHub, and other MLS data feed protocols.
 *
 * @package    MA_Deal_Room
 * @subpackage Services/Integration/MLS
 * @since      1.0.0
 */

namespace MADealRoom\Services\Integration\MLS;

/**
 * Interface MLSClientInterface
 *
 * Defines the contract for all MLS client implementations.
 */
interface MLSClientInterface {

    /**
     * Authenticate with the MLS provider
     *
     * @param array $credentials Array of credentials (username, password, certificate, etc.)
     * @return bool True if authentication successful
     * @throws \Exception If authentication fails
     */
    public function authenticate(array $credentials): bool;

    /**
     * Test connection to MLS provider
     *
     * @return array Connection test results
     *               [
     *                   'success' => bool,
     *                   'message' => string,
     *                   'server_info' => array
     *               ]
     */
    public function testConnection(): array;

    /**
     * Search for property listings
     *
     * @param array $criteria Search criteria
     *                        [
     *                            'mls_number' => string,
     *                            'address' => string,
     *                            'city' => string,
     *                            'state' => string,
     *                            'zip' => string,
     *                            'min_price' => float,
     *                            'max_price' => float,
     *                            'property_type' => string,
     *                            'status' => string,
     *                            'limit' => int,
     *                            'offset' => int
     *                        ]
     * @return array Array of listing data
     */
    public function searchListings(array $criteria): array;

    /**
     * Get detailed information for a specific listing
     *
     * @param string $mls_number MLS listing number
     * @return array|null Listing details or null if not found
     *                    [
     *                        'mls_number' => string,
     *                        'address' => string,
     *                        'city' => string,
     *                        'state' => string,
     *                        'zip' => string,
     *                        'price' => float,
     *                        'bedrooms' => int,
     *                        'bathrooms' => float,
     *                        'square_feet' => int,
     *                        'property_type' => string,
     *                        'status' => string,
     *                        'description' => string,
     *                        'listing_date' => string,
     *                        'photos' => array,
     *                        'documents' => array,
     *                        'agent_info' => array,
     *                        'raw_data' => array
     *                    ]
     */
    public function getListingDetails(string $mls_number): ?array;

    /**
     * Get photos for a listing
     *
     * @param string $mls_number MLS listing number
     * @return array Array of photo URLs
     */
    public function getListingPhotos(string $mls_number): array;

    /**
     * Download a photo from MLS
     *
     * @param string $photo_url Photo URL from MLS
     * @return string|false Binary image data or false on failure
     */
    public function downloadPhoto(string $photo_url);

    /**
     * Submit a new listing to MLS
     *
     * @param array $listing_data Listing data to submit
     *                            [
     *                                'address' => string,
     *                                'city' => string,
     *                                'state' => string,
     *                                'zip' => string,
     *                                'price' => float,
     *                                'bedrooms' => int,
     *                                'bathrooms' => float,
     *                                'square_feet' => int,
     *                                'property_type' => string,
     *                                'description' => string,
     *                                'photos' => array,
     *                                'agent_id' => string,
     *                                ...
     *                            ]
     * @return array Submission result
     *               [
     *                   'success' => bool,
     *                   'mls_number' => string,
     *                   'message' => string,
     *                   'errors' => array
     *               ]
     */
    public function submitListing(array $listing_data): array;

    /**
     * Update an existing MLS listing
     *
     * @param string $mls_number MLS listing number
     * @param array  $updates    Fields to update
     * @return array Update result
     *               [
     *                   'success' => bool,
     *                   'message' => string,
     *                   'errors' => array
     *               ]
     */
    public function updateListing(string $mls_number, array $updates): array;

    /**
     * Update listing status
     *
     * @param string $mls_number MLS listing number
     * @param string $status     New status (active, pending, sold, expired, withdrawn)
     * @return array Status update result
     *               [
     *                   'success' => bool,
     *                   'message' => string
     *               ]
     */
    public function updateStatus(string $mls_number, string $status): array;

    /**
     * Delete/withdraw a listing from MLS
     *
     * @param string $mls_number MLS listing number
     * @param string $reason     Reason for withdrawal
     * @return array Deletion result
     *               [
     *                   'success' => bool,
     *                   'message' => string
     *               ]
     */
    public function deleteListing(string $mls_number, string $reason = ''): array;

    /**
     * Upload photos to an MLS listing
     *
     * @param string $mls_number  MLS listing number
     * @param array  $photo_paths Array of local file paths to upload
     * @return array Upload result
     *               [
     *                   'success' => bool,
     *                   'uploaded' => int,
     *                   'failed' => int,
     *                   'errors' => array
     *               ]
     */
    public function uploadPhotos(string $mls_number, array $photo_paths): array;

    /**
     * Get available MLS metadata (fields, property types, statuses)
     *
     * @return array Metadata information
     *               [
     *                   'fields' => array,
     *                   'property_types' => array,
     *                   'statuses' => array,
     *                   'required_fields' => array
     *               ]
     */
    public function getMetadata(): array;

    /**
     * Validate listing data before submission
     *
     * @param array $listing_data Listing data to validate
     * @return array Validation result
     *               [
     *                   'valid' => bool,
     *                   'errors' => array,
     *                   'warnings' => array
     *               ]
     */
    public function validateListingData(array $listing_data): array;

    /**
     * Get MLS provider name
     *
     * @return string Provider name (e.g., 'RETS', 'Bridge Interactive', 'ListHub')
     */
    public function getProviderName(): string;

    /**
     * Get MLS provider type identifier
     *
     * @return string Provider type (e.g., 'rets', 'bridge', 'listhub')
     */
    public function getProviderType(): string;

    /**
     * Check if client is authenticated
     *
     * @return bool True if authenticated
     */
    public function isAuthenticated(): bool;

    /**
     * Disconnect from MLS provider
     *
     * @return void
     */
    public function disconnect(): void;

    /**
     * Get last error message
     *
     * @return string|null Last error message or null
     */
    public function getLastError(): ?string;

    /**
     * Set configuration options
     *
     * @param array $config Configuration array
     * @return void
     */
    public function setConfig(array $config): void;

    /**
     * Get configuration options
     *
     * @return array Current configuration
     */
    public function getConfig(): array;
}
