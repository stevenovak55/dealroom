<?php
/**
 * RETS (Real Estate Transaction Standard) Client
 *
 * Implementation of MLS client for RETS protocol.
 * RETS is the most common MLS data feed protocol used across North America.
 *
 * @package    MA_Deal_Room
 * @subpackage Services/Integration/MLS
 * @since      1.0.0
 */

namespace MADealRoom\Services\Integration\MLS;

use Exception;

/**
 * Class RETSClient
 *
 * RETS protocol implementation for MLS integration.
 */
class RETSClient implements MLSClientInterface {

    /**
     * RETS server URL
     *
     * @var string
     */
    private string $server_url;

    /**
     * Login URL (often different from server URL)
     *
     * @var string
     */
    private string $login_url;

    /**
     * RETS username
     *
     * @var string
     */
    private string $username;

    /**
     * RETS password
     *
     * @var string
     */
    private string $password;

    /**
     * User agent string (required by many RETS servers)
     *
     * @var string
     */
    private string $user_agent;

    /**
     * RETS version (1.5, 1.7, 1.8)
     *
     * @var string
     */
    private string $rets_version;

    /**
     * Resource name (e.g., 'Property', 'Residential')
     *
     * @var string
     */
    private string $resource;

    /**
     * Class name (e.g., 'RES', 'Residential')
     *
     * @var string
     */
    private string $class_name;

    /**
     * Authentication status
     *
     * @var bool
     */
    private bool $authenticated = false;

    /**
     * RETS session cookies
     *
     * @var array
     */
    private array $cookies = [];

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
     * cURL handle
     *
     * @var resource|null
     */
    private $curl_handle = null;

    /**
     * Capability URLs from RETS server
     *
     * @var array
     */
    private array $capabilities = [];

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
            'server_url' => '',
            'login_url' => '',
            'username' => '',
            'password' => '',
            'user_agent' => 'MA-Deal-Room/1.0',
            'rets_version' => '1.7.2',
            'resource' => 'Property',
            'class_name' => 'RES',
            'timeout' => 30,
            'use_cookies' => true,
            'debug' => false,
        ], $config);

        $this->server_url = $this->config['server_url'];
        $this->login_url = $this->config['login_url'] ?: $this->config['server_url'];
        $this->username = $this->config['username'];
        $this->password = $this->config['password'];
        $this->user_agent = $this->config['user_agent'];
        $this->rets_version = $this->config['rets_version'];
        $this->resource = $this->config['resource'];
        $this->class_name = $this->config['class_name'];
    }

    /**
     * Get configuration options
     *
     * @return array Current configuration
     */
    public function getConfig(): array {
        return $this->config;
    }

    /**
     * Authenticate with the RETS server
     *
     * @param array $credentials Array of credentials
     * @return bool True if authentication successful
     * @throws Exception If authentication fails
     */
    public function authenticate(array $credentials): bool {
        if (!empty($credentials['username'])) {
            $this->username = $credentials['username'];
        }
        if (!empty($credentials['password'])) {
            $this->password = $credentials['password'];
        }
        if (!empty($credentials['login_url'])) {
            $this->login_url = $credentials['login_url'];
        }

        try {
            $response = $this->retsRequest($this->login_url, 'GET');

            // Parse RETS response for capability URLs
            if (preg_match_all('/<RETS-RESPONSE>\s*(.+?)\s*<\/RETS-RESPONSE>/is', $response, $matches)) {
                $rets_data = $matches[1][0];

                // Extract capability URLs
                if (preg_match('/Search\s*=\s*(.+)/i', $rets_data, $search_match)) {
                    $this->capabilities['search'] = trim($search_match[1]);
                }
                if (preg_match('/GetObject\s*=\s*(.+)/i', $rets_data, $object_match)) {
                    $this->capabilities['getobject'] = trim($object_match[1]);
                }
                if (preg_match('/GetMetadata\s*=\s*(.+)/i', $rets_data, $metadata_match)) {
                    $this->capabilities['getmetadata'] = trim($metadata_match[1]);
                }
                if (preg_match('/Logout\s*=\s*(.+)/i', $rets_data, $logout_match)) {
                    $this->capabilities['logout'] = trim($logout_match[1]);
                }
            }

            // Check for reply code
            if (preg_match('/ReplyCode\s*=\s*(\d+)/i', $response, $code_match)) {
                $reply_code = (int) $code_match[1];

                if ($reply_code === 0) {
                    $this->authenticated = true;
                    return true;
                } else {
                    // Extract error message
                    if (preg_match('/ReplyText\s*=\s*(.+)/i', $response, $text_match)) {
                        $this->last_error = trim($text_match[1]);
                    } else {
                        $this->last_error = "Authentication failed with code: {$reply_code}";
                    }
                    throw new Exception($this->last_error);
                }
            }

            $this->last_error = "Invalid RETS response format";
            throw new Exception($this->last_error);

        } catch (Exception $e) {
            $this->authenticated = false;
            $this->last_error = $e->getMessage();
            throw $e;
        }
    }

    /**
     * Test connection to RETS server
     *
     * @return array Connection test results
     */
    public function testConnection(): array {
        try {
            $this->authenticate([]);

            return [
                'success' => true,
                'message' => 'Successfully connected to RETS server',
                'server_info' => [
                    'server_url' => $this->server_url,
                    'rets_version' => $this->rets_version,
                    'capabilities' => array_keys($this->capabilities),
                ],
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'server_info' => [],
            ];
        }
    }

    /**
     * Search for property listings using DMQL (Data Mining Query Language)
     *
     * @param array $criteria Search criteria
     * @return array Array of listing data
     */
    public function searchListings(array $criteria): array {
        if (!$this->isAuthenticated()) {
            $this->authenticate([]);
        }

        $dmql_query = $this->buildDMQLQuery($criteria);
        $limit = $criteria['limit'] ?? 100;
        $offset = $criteria['offset'] ?? 0;

        $search_url = $this->buildUrl($this->capabilities['search'] ?? '', [
            'SearchType' => $this->resource,
            'Class' => $this->class_name,
            'Query' => $dmql_query,
            'Limit' => $limit,
            'Offset' => $offset,
            'Format' => 'COMPACT-DECODED',
            'Select' => $this->getStandardFields(),
        ]);

        try {
            $response = $this->retsRequest($search_url, 'GET');
            return $this->parseSearchResponse($response);
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
        $results = $this->searchListings(['mls_number' => $mls_number]);

        if (empty($results)) {
            return null;
        }

        $listing = $results[0];

        // Fetch photos
        $listing['photos'] = $this->getListingPhotos($mls_number);

        return $listing;
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

        if (!isset($this->capabilities['getobject'])) {
            return [];
        }

        $object_url = $this->buildUrl($this->capabilities['getobject'], [
            'Type' => 'Photo',
            'Resource' => $this->resource,
            'ID' => $mls_number . ':*',
            'Location' => 1, // Request URLs instead of binary data
        ]);

        try {
            $response = $this->retsRequest($object_url, 'GET');
            return $this->parsePhotoResponse($response);
        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            return [];
        }
    }

    /**
     * Download a photo from RETS server
     *
     * @param string $photo_url Photo URL from RETS
     * @return string|false Binary image data or false on failure
     */
    public function downloadPhoto(string $photo_url) {
        try {
            return $this->retsRequest($photo_url, 'GET');
        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            return false;
        }
    }

    /**
     * Submit a new listing to RETS (not commonly supported)
     *
     * @param array $listing_data Listing data to submit
     * @return array Submission result
     */
    public function submitListing(array $listing_data): array {
        return [
            'success' => false,
            'mls_number' => '',
            'message' => 'RETS protocol does not support listing submission. Use web interface or contact MLS administrator.',
            'errors' => ['Listing submission not supported via RETS'],
        ];
    }

    /**
     * Update an existing MLS listing (not commonly supported)
     *
     * @param string $mls_number MLS listing number
     * @param array  $updates    Fields to update
     * @return array Update result
     */
    public function updateListing(string $mls_number, array $updates): array {
        return [
            'success' => false,
            'message' => 'RETS protocol does not support listing updates. Use web interface or contact MLS administrator.',
            'errors' => ['Listing updates not supported via RETS'],
        ];
    }

    /**
     * Update listing status (not commonly supported)
     *
     * @param string $mls_number MLS listing number
     * @param string $status     New status
     * @return array Status update result
     */
    public function updateStatus(string $mls_number, string $status): array {
        return [
            'success' => false,
            'message' => 'RETS protocol does not support status updates. Use web interface or contact MLS administrator.',
        ];
    }

    /**
     * Delete/withdraw a listing (not commonly supported)
     *
     * @param string $mls_number MLS listing number
     * @param string $reason     Reason for withdrawal
     * @return array Deletion result
     */
    public function deleteListing(string $mls_number, string $reason = ''): array {
        return [
            'success' => false,
            'message' => 'RETS protocol does not support listing deletion. Use web interface or contact MLS administrator.',
        ];
    }

    /**
     * Upload photos (not supported in RETS)
     *
     * @param string $mls_number  MLS listing number
     * @param array  $photo_paths Array of local file paths
     * @return array Upload result
     */
    public function uploadPhotos(string $mls_number, array $photo_paths): array {
        return [
            'success' => false,
            'uploaded' => 0,
            'failed' => count($photo_paths),
            'errors' => ['Photo upload not supported via RETS'],
        ];
    }

    /**
     * Get available RETS metadata
     *
     * @return array Metadata information
     */
    public function getMetadata(): array {
        if (!$this->isAuthenticated()) {
            $this->authenticate([]);
        }

        // For now, return basic metadata structure
        // Full implementation would fetch from RETS GetMetadata transaction
        return [
            'fields' => $this->getStandardFieldDefinitions(),
            'property_types' => ['Residential', 'Commercial', 'Land', 'Multi-Family'],
            'statuses' => ['Active', 'Pending', 'Sold', 'Expired', 'Withdrawn'],
            'required_fields' => ['ListPrice', 'StreetAddress', 'City', 'StateOrProvince', 'PostalCode'],
        ];
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
        return 'RETS (Real Estate Transaction Standard)';
    }

    /**
     * Get MLS provider type
     *
     * @return string Provider type
     */
    public function getProviderType(): string {
        return 'rets';
    }

    /**
     * Check if client is authenticated
     *
     * @return bool True if authenticated
     */
    public function isAuthenticated(): bool {
        return $this->authenticated;
    }

    /**
     * Disconnect from RETS server
     *
     * @return void
     */
    public function disconnect(): void {
        if ($this->isAuthenticated() && isset($this->capabilities['logout'])) {
            try {
                $this->retsRequest($this->capabilities['logout'], 'GET');
            } catch (Exception $e) {
                // Ignore logout errors
            }
        }

        $this->authenticated = false;
        $this->cookies = [];

        if ($this->curl_handle) {
            curl_close($this->curl_handle);
            $this->curl_handle = null;
        }
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
     * Make a RETS request
     *
     * @param string $url    Request URL
     * @param string $method HTTP method
     * @return string Response body
     * @throws Exception If request fails
     */
    private function retsRequest(string $url, string $method = 'GET'): string {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['timeout']);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);

        // Handle cookies for session
        if ($this->config['use_cookies']) {
            if (!empty($this->cookies)) {
                curl_setopt($ch, CURLOPT_COOKIE, implode('; ', $this->cookies));
            }
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, [$this, 'handleHeaderLine']);
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($response === false) {
            throw new Exception("RETS request failed: {$error}");
        }

        if ($http_code >= 400) {
            throw new Exception("RETS request returned HTTP {$http_code}");
        }

        return $response;
    }

    /**
     * Handle header line from cURL (for cookie extraction)
     *
     * @param resource $ch     cURL handle
     * @param string   $header Header line
     * @return int Header line length
     */
    private function handleHeaderLine($ch, string $header): int {
        if (preg_match('/^Set-Cookie:\s*([^;]+)/i', $header, $matches)) {
            $this->cookies[] = $matches[1];
        }
        return strlen($header);
    }

    /**
     * Build DMQL query from criteria
     *
     * @param array $criteria Search criteria
     * @return string DMQL query
     */
    private function buildDMQLQuery(array $criteria): string {
        $conditions = [];

        if (!empty($criteria['mls_number'])) {
            $conditions[] = "(ListingKey={$criteria['mls_number']})";
        }

        if (!empty($criteria['city'])) {
            $conditions[] = "(City={$criteria['city']})";
        }

        if (!empty($criteria['state'])) {
            $conditions[] = "(StateOrProvince={$criteria['state']})";
        }

        if (!empty($criteria['zip'])) {
            $conditions[] = "(PostalCode={$criteria['zip']})";
        }

        if (isset($criteria['min_price'])) {
            $conditions[] = "(ListPrice={$criteria['min_price']}+)";
        }

        if (isset($criteria['max_price'])) {
            $conditions[] = "(ListPrice={$criteria['max_price']}-)";
        }

        if (!empty($criteria['status'])) {
            $conditions[] = "(StandardStatus={$criteria['status']})";
        }

        if (!empty($criteria['property_type'])) {
            $conditions[] = "(PropertyType={$criteria['property_type']})";
        }

        // Default query if no conditions
        if (empty($conditions)) {
            return "(StandardStatus=Active)";
        }

        return implode(',', $conditions);
    }

    /**
     * Parse RETS search response
     *
     * @param string $response RETS response
     * @return array Array of listings
     */
    private function parseSearchResponse(string $response): array {
        $listings = [];

        // Parse COMPACT-DECODED format
        if (preg_match('/<DELIMITER value="(.)"/', $response, $delimiter_match)) {
            $delimiter = $delimiter_match[1];

            // Extract columns and data
            if (preg_match('/<COLUMNS>\s*(.+?)\s*<\/COLUMNS>/s', $response, $columns_match)) {
                $columns = explode($delimiter, trim($columns_match[1]));

                if (preg_match_all('/<DATA>\s*(.+?)\s*<\/DATA>/s', $response, $data_matches)) {
                    foreach ($data_matches[1] as $data_row) {
                        $values = explode($delimiter, trim($data_row));
                        $listing = [];

                        foreach ($columns as $index => $column) {
                            $listing[$column] = $values[$index] ?? '';
                        }

                        // Map to standard format
                        $listings[] = $this->mapRETSFieldsToStandard($listing);
                    }
                }
            }
        }

        return $listings;
    }

    /**
     * Parse photo response
     *
     * @param string $response RETS response
     * @return array Array of photo URLs
     */
    private function parsePhotoResponse(string $response): array {
        $photos = [];

        // Parse multipart response for photo URLs
        if (preg_match_all('/Location:\s*(.+)/i', $response, $matches)) {
            $photos = array_map('trim', $matches[1]);
        }

        return $photos;
    }

    /**
     * Map RETS fields to standard field names
     *
     * @param array $rets_data RETS listing data
     * @return array Standardized listing data
     */
    private function mapRETSFieldsToStandard(array $rets_data): array {
        return [
            'mls_number' => $rets_data['ListingKey'] ?? $rets_data['ListingId'] ?? '',
            'address' => $rets_data['StreetAddress'] ?? $rets_data['UnparsedAddress'] ?? '',
            'city' => $rets_data['City'] ?? '',
            'state' => $rets_data['StateOrProvince'] ?? '',
            'zip' => $rets_data['PostalCode'] ?? '',
            'price' => (float) ($rets_data['ListPrice'] ?? 0),
            'bedrooms' => (int) ($rets_data['BedroomsTotal'] ?? 0),
            'bathrooms' => (float) ($rets_data['BathroomsTotalInteger'] ?? $rets_data['BathroomsFull'] ?? 0),
            'square_feet' => (int) ($rets_data['LivingArea'] ?? $rets_data['BuildingAreaTotal'] ?? 0),
            'property_type' => $rets_data['PropertyType'] ?? $rets_data['PropertySubType'] ?? '',
            'status' => $rets_data['StandardStatus'] ?? $rets_data['MlsStatus'] ?? '',
            'description' => $rets_data['PublicRemarks'] ?? '',
            'listing_date' => $rets_data['ListingContractDate'] ?? $rets_data['OnMarketDate'] ?? '',
            'photos' => [],
            'documents' => [],
            'agent_info' => [
                'name' => $rets_data['ListAgentFullName'] ?? '',
                'email' => $rets_data['ListAgentEmail'] ?? '',
                'phone' => $rets_data['ListAgentDirectPhone'] ?? '',
            ],
            'raw_data' => $rets_data,
        ];
    }

    /**
     * Get standard field list for RETS Select parameter
     *
     * @return string Comma-separated field list
     */
    private function getStandardFields(): string {
        return implode(',', [
            'ListingKey',
            'StreetAddress',
            'City',
            'StateOrProvince',
            'PostalCode',
            'ListPrice',
            'BedroomsTotal',
            'BathroomsTotalInteger',
            'LivingArea',
            'PropertyType',
            'StandardStatus',
            'PublicRemarks',
            'ListingContractDate',
            'ListAgentFullName',
            'ListAgentEmail',
            'ListAgentDirectPhone',
        ]);
    }

    /**
     * Get standard field definitions
     *
     * @return array Field definitions
     */
    private function getStandardFieldDefinitions(): array {
        return [
            'ListingKey' => ['type' => 'string', 'label' => 'MLS Number'],
            'StreetAddress' => ['type' => 'string', 'label' => 'Address'],
            'City' => ['type' => 'string', 'label' => 'City'],
            'StateOrProvince' => ['type' => 'string', 'label' => 'State'],
            'PostalCode' => ['type' => 'string', 'label' => 'ZIP Code'],
            'ListPrice' => ['type' => 'decimal', 'label' => 'List Price'],
            'BedroomsTotal' => ['type' => 'integer', 'label' => 'Bedrooms'],
            'BathroomsTotalInteger' => ['type' => 'integer', 'label' => 'Bathrooms'],
            'LivingArea' => ['type' => 'integer', 'label' => 'Square Feet'],
            'PropertyType' => ['type' => 'string', 'label' => 'Property Type'],
            'StandardStatus' => ['type' => 'string', 'label' => 'Status'],
        ];
    }

    /**
     * Build URL with query parameters
     *
     * @param string $base_url Base URL
     * @param array  $params   Query parameters
     * @return string Complete URL
     */
    private function buildUrl(string $base_url, array $params): string {
        // If base_url is relative, make it absolute
        if (strpos($base_url, 'http') !== 0) {
            $base_url = rtrim($this->server_url, '/') . '/' . ltrim($base_url, '/');
        }

        $query = http_build_query($params);
        $separator = strpos($base_url, '?') !== false ? '&' : '?';

        return $base_url . $separator . $query;
    }

    /**
     * Destructor - ensure disconnect
     */
    public function __destruct() {
        $this->disconnect();
    }
}
