<?php
/**
 * Salesforce CRM Client
 *
 * Implements Salesforce REST API integration with OAuth 2.0 authentication.
 * Supports contacts, opportunities, tasks, and SOQL queries.
 *
 * @package MA_Deal_Room\Services\Integration\CRM
 * @since 1.0.0
 */

namespace MADealRoom\Services\Integration\CRM;

class SalesforceClient implements CRMClientInterface {
    private $access_token;
    private $refresh_token;
    private $instance_url;
    private $client_id;
    private $client_secret;
    private $is_authenticated = false;
    private $api_version = 'v59.0'; // Current Salesforce API version

    /**
     * Constructor
     *
     * @param array $config Configuration with client_id, client_secret
     */
    public function __construct(array $config = []) {
        $this->client_id = $config['client_id'] ?? getenv('SALESFORCE_CLIENT_ID');
        $this->client_secret = $config['client_secret'] ?? getenv('SALESFORCE_CLIENT_SECRET');

        if (isset($config['access_token'])) {
            $this->access_token = $config['access_token'];
            $this->is_authenticated = true;
        }

        if (isset($config['refresh_token'])) {
            $this->refresh_token = $config['refresh_token'];
        }

        if (isset($config['instance_url'])) {
            $this->instance_url = $config['instance_url'];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(array $credentials): bool {
        // OAuth 2.0 Web Server Flow (Authorization Code Grant)
        if (isset($credentials['code'])) {
            return $this->authenticateWithCode($credentials['code'], $credentials['redirect_uri'] ?? '');
        }

        // Use refresh token if available
        if (isset($credentials['refresh_token'])) {
            $this->refresh_token = $credentials['refresh_token'];
            return $this->refreshToken();
        }

        // Direct token authentication (for testing)
        if (isset($credentials['access_token']) && isset($credentials['instance_url'])) {
            $this->access_token = $credentials['access_token'];
            $this->instance_url = $credentials['instance_url'];
            $this->is_authenticated = true;
            return true;
        }

        throw new \Exception('Invalid credentials. Provide either authorization code, refresh token, or access token.');
    }

    /**
     * Authenticate using authorization code
     */
    private function authenticateWithCode(string $code, string $redirect_uri): bool {
        $token_url = 'https://login.salesforce.com/services/oauth2/token';

        $response = wp_remote_post($token_url, [
            'body' => [
                'grant_type' => 'authorization_code',
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'code' => $code,
                'redirect_uri' => $redirect_uri,
            ],
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('OAuth token request failed: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            throw new \Exception('OAuth error: ' . $body['error_description']);
        }

        $this->access_token = $body['access_token'];
        $this->refresh_token = $body['refresh_token'] ?? null;
        $this->instance_url = $body['instance_url'];
        $this->is_authenticated = true;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function refreshToken(): bool {
        if (!$this->refresh_token) {
            throw new \Exception('No refresh token available');
        }

        $token_url = 'https://login.salesforce.com/services/oauth2/token';

        $response = wp_remote_post($token_url, [
            'body' => [
                'grant_type' => 'refresh_token',
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'refresh_token' => $this->refresh_token,
            ],
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('Token refresh failed: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            throw new \Exception('Token refresh error: ' . $body['error_description']);
        }

        $this->access_token = $body['access_token'];
        $this->instance_url = $body['instance_url'];
        $this->is_authenticated = true;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function testConnection(): array {
        try {
            $this->checkAuthentication();

            // Test with a simple identity query
            $url = $this->instance_url . '/services/oauth2/userinfo';
            $response = $this->makeRequest('GET', $url);

            return [
                'success' => true,
                'message' => 'Connection successful',
                'details' => [
                    'user_id' => $response['user_id'] ?? null,
                    'organization_id' => $response['organization_id'] ?? null,
                    'display_name' => $response['name'] ?? null,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'details' => [],
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getContacts(array $filters = [], int $limit = 100, int $offset = 0): array {
        $this->checkAuthentication();

        $query = "SELECT Id, FirstName, LastName, Email, Phone, MailingStreet, MailingCity, MailingState, MailingPostalCode, MailingCountry, CreatedDate, LastModifiedDate FROM Contact";

        $where_clauses = [];
        foreach ($filters as $field => $value) {
            $where_clauses[] = "$field = '" . $this->escapeSoql($value) . "'";
        }

        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(' AND ', $where_clauses);
        }

        $query .= " ORDER BY CreatedDate DESC LIMIT $limit OFFSET $offset";

        return $this->query($query);
    }

    /**
     * {@inheritDoc}
     */
    public function getContact(string $contactId): ?array {
        $this->checkAuthentication();

        try {
            $url = $this->instance_url . "/services/data/{$this->api_version}/sobjects/Contact/{$contactId}";
            return $this->makeRequest('GET', $url);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), '404') !== false) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createContact(array $contactData): array {
        $this->checkAuthentication();

        $url = $this->instance_url . "/services/data/{$this->api_version}/sobjects/Contact";

        $salesforce_data = $this->mapToSalesforceContact($contactData);

        $response = $this->makeRequest('POST', $url, $salesforce_data);

        return [
            'id' => $response['id'],
            'success' => $response['success'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function updateContact(string $contactId, array $contactData): array {
        $this->checkAuthentication();

        $url = $this->instance_url . "/services/data/{$this->api_version}/sobjects/Contact/{$contactId}";

        $salesforce_data = $this->mapToSalesforceContact($contactData);

        $this->makeRequest('PATCH', $url, $salesforce_data);

        return ['id' => $contactId, 'success' => true];
    }

    /**
     * {@inheritDoc}
     */
    public function deleteContact(string $contactId): bool {
        $this->checkAuthentication();

        $url = $this->instance_url . "/services/data/{$this->api_version}/sobjects/Contact/{$contactId}";

        $this->makeRequest('DELETE', $url);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getDeals(array $filters = [], int $limit = 100, int $offset = 0): array {
        $this->checkAuthentication();

        $query = "SELECT Id, Name, Amount, StageName, CloseDate, Probability, OwnerId, AccountId, CreatedDate, LastModifiedDate FROM Opportunity";

        $where_clauses = [];
        foreach ($filters as $field => $value) {
            $where_clauses[] = "$field = '" . $this->escapeSoql($value) . "'";
        }

        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(' AND ', $where_clauses);
        }

        $query .= " ORDER BY CreatedDate DESC LIMIT $limit OFFSET $offset";

        return $this->query($query);
    }

    /**
     * {@inheritDoc}
     */
    public function getDeal(string $dealId): ?array {
        $this->checkAuthentication();

        try {
            $url = $this->instance_url . "/services/data/{$this->api_version}/sobjects/Opportunity/{$dealId}";
            return $this->makeRequest('GET', $url);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), '404') !== false) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createDeal(array $dealData): array {
        $this->checkAuthentication();

        $url = $this->instance_url . "/services/data/{$this->api_version}/sobjects/Opportunity";

        $salesforce_data = $this->mapToSalesforceOpportunity($dealData);

        $response = $this->makeRequest('POST', $url, $salesforce_data);

        return [
            'id' => $response['id'],
            'success' => $response['success'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function updateDeal(string $dealId, array $dealData): array {
        $this->checkAuthentication();

        $url = $this->instance_url . "/services/data/{$this->api_version}/sobjects/Opportunity/{$dealId}";

        $salesforce_data = $this->mapToSalesforceOpportunity($dealData);

        $this->makeRequest('PATCH', $url, $salesforce_data);

        return ['id' => $dealId, 'success' => true];
    }

    /**
     * {@inheritDoc}
     */
    public function deleteDeal(string $dealId): bool {
        $this->checkAuthentication();

        $url = $this->instance_url . "/services/data/{$this->api_version}/sobjects/Opportunity/{$dealId}";

        $this->makeRequest('DELETE', $url);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function logActivity(string $objectType, string $objectId, array $activityData): array {
        $this->checkAuthentication();

        $url = $this->instance_url . "/services/data/{$this->api_version}/sobjects/Task";

        $task_data = [
            'Subject' => $activityData['subject'] ?? 'Deal Room Activity',
            'Description' => $activityData['description'] ?? '',
            'Status' => $activityData['status'] ?? 'Completed',
            'Priority' => $activityData['priority'] ?? 'Normal',
            'ActivityDate' => $activityData['date'] ?? date('Y-m-d'),
        ];

        // Link to appropriate object
        if ($objectType === 'contact') {
            $task_data['WhoId'] = $objectId;
        } elseif ($objectType === 'deal' || $objectType === 'opportunity') {
            $task_data['WhatId'] = $objectId;
        }

        $response = $this->makeRequest('POST', $url, $task_data);

        return [
            'id' => $response['id'],
            'success' => $response['success'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function searchRecords(string $objectType, string $query, int $limit = 20): array {
        $this->checkAuthentication();

        $object_map = [
            'contact' => 'Contact',
            'deal' => 'Opportunity',
            'opportunity' => 'Opportunity',
            'account' => 'Account',
        ];

        $sobject = $object_map[$objectType] ?? 'Contact';

        // Use SOSL (Salesforce Object Search Language)
        $sosl = "FIND {" . $this->escapeSoql($query) . "*} IN ALL FIELDS RETURNING $sobject LIMIT $limit";

        return $this->search($sosl);
    }

    /**
     * Execute SOQL query
     *
     * @param string $soql SOQL query
     * @return array Query results
     */
    private function query(string $soql): array {
        $url = $this->instance_url . "/services/data/{$this->api_version}/query";
        $url .= '?' . http_build_query(['q' => $soql]);

        $response = $this->makeRequest('GET', $url);

        return $response['records'] ?? [];
    }

    /**
     * Execute SOSL search
     *
     * @param string $sosl SOSL query
     * @return array Search results
     */
    private function search(string $sosl): array {
        $url = $this->instance_url . "/services/data/{$this->api_version}/search";
        $url .= '?' . http_build_query(['q' => $sosl]);

        $response = $this->makeRequest('GET', $url);

        return $response['searchRecords'] ?? [];
    }

    /**
     * Make HTTP request to Salesforce API
     *
     * @param string $method HTTP method
     * @param string $url Full URL
     * @param array|null $body Request body
     * @return array Response data
     */
    private function makeRequest(string $method, string $url, ?array $body = null): array {
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ];

        if ($body !== null && in_array($method, ['POST', 'PATCH', 'PUT'])) {
            $args['body'] = json_encode($body);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \Exception('Salesforce API request failed: ' . $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        // Handle errors
        if ($status_code >= 400) {
            $error_message = isset($data[0]['message']) ? $data[0]['message'] : 'Unknown error';
            throw new \Exception("Salesforce API error ($status_code): $error_message");
        }

        // DELETE returns 204 with no content
        if ($status_code === 204) {
            return [];
        }

        return $data ?? [];
    }

    /**
     * Map deal room contact data to Salesforce Contact fields
     */
    private function mapToSalesforceContact(array $contactData): array {
        $sf_data = [];

        if (isset($contactData['first_name'])) $sf_data['FirstName'] = $contactData['first_name'];
        if (isset($contactData['last_name'])) $sf_data['LastName'] = $contactData['last_name'];
        if (isset($contactData['email'])) $sf_data['Email'] = $contactData['email'];
        if (isset($contactData['phone'])) $sf_data['Phone'] = $contactData['phone'];

        if (isset($contactData['address'])) {
            $sf_data['MailingStreet'] = $contactData['address']['street'] ?? '';
            $sf_data['MailingCity'] = $contactData['address']['city'] ?? '';
            $sf_data['MailingState'] = $contactData['address']['state'] ?? '';
            $sf_data['MailingPostalCode'] = $contactData['address']['zip'] ?? '';
            $sf_data['MailingCountry'] = $contactData['address']['country'] ?? 'USA';
        }

        return array_filter($sf_data); // Remove null values
    }

    /**
     * Map deal room transaction data to Salesforce Opportunity fields
     */
    private function mapToSalesforceOpportunity(array $dealData): array {
        $sf_data = [];

        if (isset($dealData['name'])) $sf_data['Name'] = $dealData['name'];
        if (isset($dealData['amount'])) $sf_data['Amount'] = (float)$dealData['amount'];
        if (isset($dealData['stage'])) $sf_data['StageName'] = $dealData['stage'];
        if (isset($dealData['close_date'])) $sf_data['CloseDate'] = $dealData['close_date'];
        if (isset($dealData['probability'])) $sf_data['Probability'] = (int)$dealData['probability'];
        if (isset($dealData['description'])) $sf_data['Description'] = $dealData['description'];

        return array_filter($sf_data);
    }

    /**
     * Escape string for SOQL/SOSL
     */
    private function escapeSoql(string $value): string {
        return str_replace("'", "\\'", $value);
    }

    /**
     * Check if authenticated, throw exception if not
     */
    private function checkAuthentication(): void {
        if (!$this->is_authenticated || !$this->access_token) {
            throw new \Exception('Not authenticated. Call authenticate() first.');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getProviderType(): string {
        return 'salesforce';
    }

    /**
     * {@inheritDoc}
     */
    public function isAuthenticated(): bool {
        return $this->is_authenticated;
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessToken(): ?string {
        return $this->access_token;
    }

    /**
     * {@inheritDoc}
     */
    public function getRefreshToken(): ?string {
        return $this->refresh_token;
    }

    /**
     * {@inheritDoc}
     */
    public function getInstanceUrl(): ?string {
        return $this->instance_url;
    }
}
