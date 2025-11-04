<?php
/**
 * Bridge Interactive MLS Client
 *
 * Implementation of MLS client for Bridge Interactive API.
 * Bridge is a modern REST API platform used by many MLSs.
 *
 * @package    MA_Deal_Room
 * @subpackage Services/Integration/MLS
 * @since      1.0.0
 */

namespace MADealRoom\Services\Integration\MLS;

use Exception;

/**
 * Class BridgeClient
 *
 * Bridge Interactive API implementation for MLS integration.
 */
class BridgeClient implements MLSClientInterface {

    /**
     * Bridge API base URL
     *
     * @var string
     */
    private string $api_url;

    /**
     * OAuth 2.0 client ID
     *
     * @var string
     */
    private string $client_id;

    /**
     * OAuth 2.0 client secret
     *
     * @var string
     */
    private string $client_secret;

    /**
     * OAuth 2.0 access token
     *
     * @var string|null
     */
    private ?string $access_token = null;

    /**
     * OAuth 2.0 refresh token
     *
     * @var string|null
     */
    private ?string $refresh_token = null;

    /**
     * Token expiration timestamp
     *
     * @var int|null
     */
    private ?int $token_expires_at = null;

    /**
     * Authentication status
     *
     * @var bool
     */
    private bool $authenticated = false;

    /**
     * Last error message
     *
     * @var string|null
     */
    private ?string $last_error = null;

    /**
     * Configuration array
     *
     * @var array
     */
    private array $config = [];

    /**
     * Constructor
     *
     * @param array $config Configuration options
     */
    public function __construct(array $config = []) {
        $this->setConfig($config);
    }

    /**
     * Set configuration options
     *
     * @param array $config Configuration array
     * @return void
     */
    public function setConfig(array $config): void {
        $this->config = array_merge([
            'api_url' => 'https://api.bridgedataoutput.com/api/v2',
            'oauth_url' => 'https://api.bridgedataoutput.com/api/v2/OAuthServer/token',
            'client_id' => '',
            'client_secret' => '',
            'access_token' => null,
            'refresh_token' => null,
            'timeout' => 30,
            'debug' => false,
        ], $config);

        $this->api_url = rtrim($this->config['api_url'], '/');
        $this->client_id = $this->config['client_id'];
        $this->client_secret = $this->config['client_secret'];
        $this->access_token = $this->config['access_token'];
        $this->refresh_token = $this->config['refresh_token'];

        if ($this->access_token) {
            $this->authenticated = true;
        }
    }

    /**
     * Get configuration options
     *
     * @return array Current configuration
     */
    public function getConfig(): array {
        // Don't expose sensitive data
        $safe_config = $this->config;
        unset($safe_config['client_secret']);
        unset($safe_config['access_token']);
        unset($safe_config['refresh_token']);

        return $safe_config;
    }

    /**
     * Authenticate using OAuth 2.0 client credentials flow
     *
     * @param array $credentials Array of credentials
     * @return bool True if authentication successful
     * @throws Exception If authentication fails
     */
    public function authenticate(array $credentials): bool {
        // Check if we have a server token (direct access token)
        if (!empty($credentials['server_token']) || !empty($credentials['access_token'])) {
            $this->access_token = $credentials['server_token'] ?? $credentials['access_token'];
            $this->authenticated = true;
            return true;
        }

        // If already authenticated with access token, skip OAuth
        if ($this->access_token) {
            $this->authenticated = true;
            return true;
        }

        // Update credentials if provided
        if (!empty($credentials['client_id'])) {
            $this->client_id = $credentials['client_id'];
        }
        if (!empty($credentials['client_secret'])) {
            $this->client_secret = $credentials['client_secret'];
        }

        // Perform OAuth authentication
        try {
            $oauth_url = $this->config['oauth_url'];

            $response = $this->httpRequest($oauth_url, 'POST', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
            ], false); // Don't use bearer auth for token request

            if (!isset($response['access_token'])) {
                throw new Exception('OAuth response missing access_token');
            }

            $this->access_token = $response['access_token'];
            $this->refresh_token = $response['refresh_token'] ?? null;
            $this->token_expires_at = isset($response['expires_in'])
                ? time() + $response['expires_in']
                : null;

            $this->authenticated = true;

            return true;

        } catch (Exception $e) {
            $this->authenticated = false;
            $this->last_error = $e->getMessage();
            throw $e;
        }
    }

    /**
     * Test connection to Bridge API
     *
     * @return array Connection test results
     */
    public function testConnection(): array {
        try {
            if (!$this->isAuthenticated()) {
                $this->authenticate([]);
            }

            // Test with a simple Property request to verify connection
            // Just fetch the first property without using $top parameter
            $url = $this->api_url . '/Property';

            error_log("[BridgeClient] Testing connection to: {$url}");
            error_log("[BridgeClient] Authenticated: " . ($this->isAuthenticated() ? 'YES' : 'NO'));
            error_log("[BridgeClient] Has access token: " . ($this->access_token ? 'YES' : 'NO'));

            $response = $this->httpRequest($url, 'GET');

            return [
                'success' => true,
                'message' => 'Successfully connected to Bridge Interactive API',
                'server_info' => [
                    'api_url' => $this->api_url,
                    'property_count' => isset($response['value']) ? count($response['value']) : 0,
                ],
            ];
        } catch (Exception $e) {
            error_log("[BridgeClient] Test connection failed: " . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'server_info' => [
                    'api_url' => $this->api_url,
                    'error_details' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Search for property listings
     *
     * @param array $criteria Search criteria
     * @return array Array of listing data
     */
    public function searchListings(array $criteria): array {
        if (!$this->isAuthenticated()) {
            $this->authenticate([]);
        }

        $query_params = $this->buildQueryParams($criteria);
        $url = $this->api_url . '/Property?' . http_build_query($query_params);

        try {
            $response = $this->httpRequest($url, 'GET');

            // Handle OData response format
            if (!isset($response['value']) && !isset($response['bundle'])) {
                return [];
            }

            $data = $response['value'] ?? $response['bundle'] ?? [];
            $listings = [];
            foreach ($data as $listing_data) {
                $listings[] = $this->mapBridgeFieldsToStandard($listing_data);
            }

            return $listings;

        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            return [];
        }
    }

    /**
     * Get detailed information for a specific listing
     *
     * @param string $mls_number MLS listing number
     * @return array|null Listing details or null if not found
     */
    public function getListingDetails(string $mls_number): ?array {
        if (!$this->isAuthenticated()) {
            $this->authenticate([]);
        }

        $url = $this->api_url . "/Property('{$mls_number}')";

        try {
            $response = $this->httpRequest($url, 'GET');

            // OData single entity response doesn't have value array
            if (empty($response)) {
                return null;
            }

            $listing = $this->mapBridgeFieldsToStandard($response);

            // Fetch photos separately
            $listing['photos'] = $this->getListingPhotos($mls_number);

            return $listing;

        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            return null;
        }
    }

    /**
     * Get photos for a listing
     *
     * @param string $mls_number MLS listing number
     * @return array Array of photo URLs
     */
    public function getListingPhotos(string $mls_number): array {
        if (!$this->isAuthenticated()) {
            $this->authenticate([]);
        }

        $url = $this->api_url . '/media?listingId=' . urlencode($mls_number);

        try {
            $response = $this->httpRequest($url, 'GET');

            $photos = [];
            if (isset($response['bundle'])) {
                foreach ($response['bundle'] as $media) {
                    if ($media['MediaType'] === 'Photo' && isset($media['MediaURL'])) {
                        $photos[] = $media['MediaURL'];
                    }
                }
            }

            return $photos;

        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            return [];
        }
    }

    /**
     * Download a photo from Bridge API
     *
     * @param string $photo_url Photo URL
     * @return string|false Binary image data or false on failure
     */
    public function downloadPhoto(string $photo_url) {
        try {
            $ch = curl_init($photo_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['timeout']);

            $data = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code === 200 && $data !== false) {
                return $data;
            }

            return false;

        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            return false;
        }
    }

    /**
     * Submit a new listing to Bridge API
     *
     * @param array $listing_data Listing data to submit
     * @return array Submission result
     */
    public function submitListing(array $listing_data): array {
        if (!$this->isAuthenticated()) {
            try {
                $this->authenticate([]);
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'mls_number' => '',
                    'message' => 'Authentication failed: ' . $e->getMessage(),
                    'errors' => [$e->getMessage()],
                ];
            }
        }

        $validation = $this->validateListingData($listing_data);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'mls_number' => '',
                'message' => 'Validation failed',
                'errors' => $validation['errors'],
            ];
        }

        try {
            $url = $this->api_url . '/listings';
            $bridge_data = $this->mapStandardFieldsToBridge($listing_data);

            $response = $this->httpRequest($url, 'POST', $bridge_data);

            return [
                'success' => true,
                'mls_number' => $response['ListingKey'] ?? $response['ListingId'] ?? '',
                'message' => 'Listing submitted successfully',
                'errors' => [],
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'mls_number' => '',
                'message' => 'Submission failed: ' . $e->getMessage(),
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Update an existing MLS listing
     *
     * @param string $mls_number MLS listing number
     * @param array  $updates    Fields to update
     * @return array Update result
     */
    public function updateListing(string $mls_number, array $updates): array {
        if (!$this->isAuthenticated()) {
            try {
                $this->authenticate([]);
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Authentication failed: ' . $e->getMessage(),
                    'errors' => [$e->getMessage()],
                ];
            }
        }

        try {
            $url = $this->api_url . '/listings/' . urlencode($mls_number);
            $bridge_data = $this->mapStandardFieldsToBridge($updates);

            $this->httpRequest($url, 'PATCH', $bridge_data);

            return [
                'success' => true,
                'message' => 'Listing updated successfully',
                'errors' => [],
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage(),
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Update listing status
     *
     * @param string $mls_number MLS listing number
     * @param string $status     New status
     * @return array Status update result
     */
    public function updateStatus(string $mls_number, string $status): array {
        return $this->updateListing($mls_number, ['status' => $status]);
    }

    /**
     * Delete/withdraw a listing
     *
     * @param string $mls_number MLS listing number
     * @param string $reason     Reason for withdrawal
     * @return array Deletion result
     */
    public function deleteListing(string $mls_number, string $reason = ''): array {
        if (!$this->isAuthenticated()) {
            try {
                $this->authenticate([]);
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Authentication failed: ' . $e->getMessage(),
                ];
            }
        }

        try {
            $url = $this->api_url . '/listings/' . urlencode($mls_number);

            $this->httpRequest($url, 'DELETE');

            return [
                'success' => true,
                'message' => 'Listing deleted successfully',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Deletion failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Upload photos to an MLS listing
     *
     * @param string $mls_number  MLS listing number
     * @param array  $photo_paths Array of local file paths
     * @return array Upload result
     */
    public function uploadPhotos(string $mls_number, array $photo_paths): array {
        if (!$this->isAuthenticated()) {
            try {
                $this->authenticate([]);
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'uploaded' => 0,
                    'failed' => count($photo_paths),
                    'errors' => [$e->getMessage()],
                ];
            }
        }

        $uploaded = 0;
        $failed = 0;
        $errors = [];

        foreach ($photo_paths as $photo_path) {
            if (!file_exists($photo_path)) {
                $failed++;
                $errors[] = "File not found: {$photo_path}";
                continue;
            }

            try {
                $url = $this->api_url . '/media';

                $file_data = [
                    'ListingKey' => $mls_number,
                    'MediaType' => 'Photo',
                    'File' => new \CURLFile($photo_path),
                ];

                $this->httpRequest($url, 'POST', $file_data, true, true);
                $uploaded++;

            } catch (Exception $e) {
                $failed++;
                $errors[] = "Upload failed for {$photo_path}: " . $e->getMessage();
            }
        }

        return [
            'success' => $uploaded > 0,
            'uploaded' => $uploaded,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Get available Bridge metadata
     *
     * @return array Metadata information
     */
    public function getMetadata(): array {
        if (!$this->isAuthenticated()) {
            try {
                $this->authenticate([]);
            } catch (Exception $e) {
                return [
                    'fields' => [],
                    'property_types' => [],
                    'statuses' => [],
                    'required_fields' => [],
                ];
            }
        }

        try {
            $url = $this->api_url . '/metadata/tables/Property';
            $response = $this->httpRequest($url, 'GET');

            $fields = [];
            if (isset($response['bundle'][0]['fields'])) {
                foreach ($response['bundle'][0]['fields'] as $field) {
                    $fields[$field['SystemName']] = [
                        'type' => $field['DataType'] ?? 'string',
                        'label' => $field['LongName'] ?? $field['SystemName'],
                        'required' => ($field['Required'] ?? false),
                    ];
                }
            }

            return [
                'fields' => $fields,
                'property_types' => ['Residential', 'Commercial', 'Land', 'Multi-Family'],
                'statuses' => ['Active', 'Pending', 'Closed', 'Expired', 'Withdrawn', 'Canceled'],
                'required_fields' => ['ListPrice', 'UnparsedAddress', 'City', 'StateOrProvince', 'PostalCode'],
            ];

        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            return [
                'fields' => [],
                'property_types' => [],
                'statuses' => [],
                'required_fields' => [],
            ];
        }
    }

    /**
     * Validate listing data
     *
     * @param array $listing_data Listing data to validate
     * @return array Validation result
     */
    public function validateListingData(array $listing_data): array {
        $errors = [];
        $warnings = [];

        $required_fields = ['address', 'city', 'state', 'zip', 'price'];

        foreach ($required_fields as $field) {
            if (empty($listing_data[$field])) {
                $errors[] = "Required field '{$field}' is missing";
            }
        }

        if (!empty($listing_data['price']) && !is_numeric($listing_data['price'])) {
            $errors[] = "Price must be a numeric value";
        }

        if (!empty($listing_data['bedrooms']) && !is_int($listing_data['bedrooms'])) {
            $warnings[] = "Bedrooms should be an integer value";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Get MLS provider name
     *
     * @return string Provider name
     */
    public function getProviderName(): string {
        return 'Bridge Interactive';
    }

    /**
     * Get MLS provider type
     *
     * @return string Provider type
     */
    public function getProviderType(): string {
        return 'bridge';
    }

    /**
     * Check if client is authenticated
     *
     * @return bool True if authenticated
     */
    public function isAuthenticated(): bool {
        // Check if token is expired
        if ($this->authenticated && $this->token_expires_at) {
            if (time() >= $this->token_expires_at) {
                $this->authenticated = false;
                $this->access_token = null;
            }
        }

        return $this->authenticated;
    }

    /**
     * Disconnect from Bridge API
     *
     * @return void
     */
    public function disconnect(): void {
        $this->authenticated = false;
        $this->access_token = null;
        $this->refresh_token = null;
        $this->token_expires_at = null;
    }

    /**
     * Get last error message
     *
     * @return string|null Last error message
     */
    public function getLastError(): ?string {
        return $this->last_error;
    }

    /**
     * Make HTTP request to Bridge API
     *
     * @param string $url         Request URL
     * @param string $method      HTTP method
     * @param array  $data        Request data
     * @param bool   $use_auth    Use bearer token authentication
     * @param bool   $multipart   Send as multipart/form-data
     * @return array Response data
     * @throws Exception If request fails
     */
    private function httpRequest(
        string $url,
        string $method = 'GET',
        array $data = [],
        bool $use_auth = true,
        bool $multipart = false
    ): array {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['timeout']);

        $headers = [];

        if ($use_auth && $this->access_token) {
            $headers[] = 'Authorization: Bearer ' . $this->access_token;
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                if ($multipart) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                } else {
                    $headers[] = 'Content-Type: application/json';
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
            }
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            if (!empty($data)) {
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($response === false) {
            throw new Exception("Bridge API request failed: {$error}");
        }

        if ($http_code >= 400) {
            $error_data = json_decode($response, true);
            $error_message = $error_data['error_description'] ?? $error_data['error'] ?? "HTTP {$http_code}";

            // Handle array error messages
            if (is_array($error_message)) {
                $error_message = json_encode($error_message);
            }

            throw new Exception("Bridge API error: {$error_message}");
        }

        $decoded = json_decode($response, true);

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response from Bridge API");
        }

        return $decoded ?? [];
    }

    /**
     * Build query parameters from search criteria
     *
     * @param array $criteria Search criteria
     * @return array Query parameters
     */
    private function buildQueryParams(array $criteria): array {
        $params = [];

        if (!empty($criteria['mls_number'])) {
            // Try both ListingId and ListingKey since different MLSs use different fields
            $params['$filter'] = "(ListingId eq '{$criteria['mls_number']}' or ListingKey eq '{$criteria['mls_number']}')";
        } else {
            $filters = [];

            if (!empty($criteria['city'])) {
                $filters[] = "City eq '{$criteria['city']}'";
            }

            if (!empty($criteria['state'])) {
                $filters[] = "StateOrProvince eq '{$criteria['state']}'";
            }

            if (!empty($criteria['zip'])) {
                $filters[] = "PostalCode eq '{$criteria['zip']}'";
            }

            if (isset($criteria['min_price'])) {
                $filters[] = "ListPrice ge {$criteria['min_price']}";
            }

            if (isset($criteria['max_price'])) {
                $filters[] = "ListPrice le {$criteria['max_price']}";
            }

            if (!empty($criteria['status'])) {
                $filters[] = "StandardStatus eq '{$criteria['status']}'";
            }

            if (!empty($filters)) {
                $params['$filter'] = implode(' and ', $filters);
            } else {
                $params['$filter'] = "StandardStatus eq 'Active'";
            }
        }

        $params['$top'] = $criteria['limit'] ?? 100;
        $params['$skip'] = $criteria['offset'] ?? 0;

        return $params;
    }

    /**
     * Map Bridge fields to standard field names
     *
     * @param array $bridge_data Bridge listing data
     * @return array Standardized listing data
     */
    private function mapBridgeFieldsToStandard(array $bridge_data): array {
        return [
            // Identifiers
            'mls_number' => $bridge_data['ListingId'] ?? $bridge_data['ListingKey'] ?? '',
            'listing_key' => $bridge_data['ListingKey'] ?? '',

            // Address
            'address' => $bridge_data['UnparsedAddress'] ?? $bridge_data['StreetAddress'] ?? '',
            'street_number' => $bridge_data['StreetNumber'] ?? '',
            'street_name' => $bridge_data['StreetName'] ?? '',
            'unit_number' => $bridge_data['UnitNumber'] ?? '',
            'city' => $bridge_data['City'] ?? '',
            'state' => $bridge_data['StateOrProvince'] ?? '',
            'zip' => $bridge_data['PostalCode'] ?? '',
            'county' => $bridge_data['CountyOrParish'] ?? '',
            'subdivision' => $bridge_data['SubdivisionName'] ?? '',

            // Pricing
            'price' => (float) ($bridge_data['ListPrice'] ?? 0),
            'original_price' => (float) ($bridge_data['OriginalListPrice'] ?? 0),
            'close_price' => (float) ($bridge_data['ClosePrice'] ?? 0),
            'price_per_sqft' => (float) ($bridge_data['MLSPIN_PRICE_PER_SQFT'] ?? 0),

            // Property Details
            'property_type' => $bridge_data['PropertyType'] ?? '',
            'property_sub_type' => $bridge_data['PropertySubType'] ?? '',
            'year_built' => (int) ($bridge_data['YearBuilt'] ?? 0),
            'stories' => (int) ($bridge_data['Stories'] ?? $bridge_data['StoriesTotal'] ?? 0),

            // Size
            'bedrooms' => (int) ($bridge_data['BedroomsTotal'] ?? 0),
            'bathrooms_full' => (int) ($bridge_data['BathroomsFull'] ?? 0),
            'bathrooms_half' => (int) ($bridge_data['BathroomsHalf'] ?? 0),
            'bathrooms' => (float) ($bridge_data['BathroomsTotalDecimal'] ?? $bridge_data['BathroomsTotalInteger'] ?? 0),
            'square_feet' => (int) ($bridge_data['LivingArea'] ?? $bridge_data['BuildingAreaTotal'] ?? 0),
            'lot_size_acres' => (float) ($bridge_data['LotSizeAcres'] ?? 0),
            'lot_size_sqft' => (int) ($bridge_data['LotSizeSquareFeet'] ?? 0),

            // Features
            'garage_spaces' => (int) ($bridge_data['GarageSpaces'] ?? 0),
            'parking_total' => (int) ($bridge_data['ParkingTotal'] ?? 0),
            'fireplaces' => (int) ($bridge_data['FireplacesTotal'] ?? 0),
            'heating' => is_array($bridge_data['Heating'] ?? null) ? implode(', ', $bridge_data['Heating']) : ($bridge_data['Heating'] ?? ''),
            'cooling' => is_array($bridge_data['Cooling'] ?? null) ? implode(', ', $bridge_data['Cooling']) : ($bridge_data['Cooling'] ?? ''),
            'basement' => is_array($bridge_data['Basement'] ?? null) ? implode(', ', $bridge_data['Basement']) : ($bridge_data['Basement'] ?? ''),
            'flooring' => is_array($bridge_data['Flooring'] ?? null) ? implode(', ', $bridge_data['Flooring']) : ($bridge_data['Flooring'] ?? ''),
            'roof' => is_array($bridge_data['Roof'] ?? null) ? implode(', ', $bridge_data['Roof']) : ($bridge_data['Roof'] ?? ''),
            'appliances' => is_array($bridge_data['Appliances'] ?? null) ? implode(', ', $bridge_data['Appliances']) : ($bridge_data['Appliances'] ?? ''),

            // Financial
            'tax_annual_amount' => (float) ($bridge_data['TaxAnnualAmount'] ?? 0),
            'tax_year' => (int) ($bridge_data['TaxYear'] ?? 0),
            'association_fee' => (float) ($bridge_data['AssociationFee'] ?? 0),
            'association_fee_frequency' => $bridge_data['AssociationFeeFrequency'] ?? '',

            // Status & Dates
            'status' => $bridge_data['StandardStatus'] ?? $bridge_data['MlsStatus'] ?? '',
            'listing_date' => $bridge_data['ListingContractDate'] ?? $bridge_data['OriginalEntryTimestamp'] ?? '',
            'modification_date' => $bridge_data['ModificationTimestamp'] ?? '',
            'close_date' => $bridge_data['CloseDate'] ?? '',
            'days_on_market' => (int) ($bridge_data['MLSPIN_MARKET_TIME'] ?? 0),

            // Marketing
            'description' => $bridge_data['PublicRemarks'] ?? '',
            'photos_count' => (int) ($bridge_data['PhotosCount'] ?? 0),
            'virtual_tour_url' => $bridge_data['VirtualTourURLUnbranded'] ?? '',

            // Schools
            'elementary_school' => $bridge_data['ElementarySchool'] ?? '',
            'middle_school' => $bridge_data['MiddleOrJuniorSchool'] ?? '',
            'high_school' => $bridge_data['HighSchool'] ?? '',

            // Location
            'latitude' => (float) ($bridge_data['Latitude'] ?? 0),
            'longitude' => (float) ($bridge_data['Longitude'] ?? 0),

            // Additional Info
            'zoning' => $bridge_data['Zoning'] ?? '',
            'water_source' => is_array($bridge_data['WaterSource'] ?? null) ? implode(', ', $bridge_data['WaterSource']) : ($bridge_data['WaterSource'] ?? ''),
            'sewer' => is_array($bridge_data['Sewer'] ?? null) ? implode(', ', $bridge_data['Sewer']) : ($bridge_data['Sewer'] ?? ''),
            'view' => is_array($bridge_data['View'] ?? null) ? implode(', ', $bridge_data['View']) : ($bridge_data['View'] ?? ''),
            'waterfront' => !empty($bridge_data['WaterfrontYN']) && $bridge_data['WaterfrontYN'] === true,

            // Photos & Media
            'photos' => [],
            'documents' => [],

            // Agent Info
            'agent_info' => [
                'name' => $bridge_data['ListAgentFullName'] ?? '',
                'email' => $bridge_data['ListAgentEmail'] ?? '',
                'phone' => $bridge_data['ListAgentDirectPhone'] ?? '',
                'mls_id' => $bridge_data['ListAgentMlsId'] ?? '',
            ],

            // Office Info
            'office_info' => [
                'name' => $bridge_data['ListOfficeName'] ?? '',
                'mls_id' => $bridge_data['ListOfficeMlsId'] ?? '',
            ],

            // Raw data for future use
            'raw_data' => $bridge_data,
        ];
    }

    /**
     * Map standard fields to Bridge field names
     *
     * @param array $standard_data Standard listing data
     * @return array Bridge-formatted data
     */
    private function mapStandardFieldsToBridge(array $standard_data): array {
        return [
            'UnparsedAddress' => $standard_data['address'] ?? '',
            'City' => $standard_data['city'] ?? '',
            'StateOrProvince' => $standard_data['state'] ?? '',
            'PostalCode' => $standard_data['zip'] ?? '',
            'ListPrice' => $standard_data['price'] ?? 0,
            'BedroomsTotal' => $standard_data['bedrooms'] ?? 0,
            'BathroomsTotalInteger' => $standard_data['bathrooms'] ?? 0,
            'LivingArea' => $standard_data['square_feet'] ?? 0,
            'PropertyType' => $standard_data['property_type'] ?? '',
            'StandardStatus' => $standard_data['status'] ?? 'Active',
            'PublicRemarks' => $standard_data['description'] ?? '',
        ];
    }
}
