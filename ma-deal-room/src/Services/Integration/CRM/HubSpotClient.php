<?php
/**
 * HubSpot CRM Client
 *
 * Implements HubSpot REST API v3 integration with OAuth 2.0 authentication.
 * Supports contacts, deals, companies, and timeline events.
 *
 * @package MA_Deal_Room\Services\Integration\CRM
 * @since 1.0.0
 */

namespace MADealRoom\Services\Integration\CRM;

class HubSpotClient implements CRMClientInterface {
    private $access_token;
    private $refresh_token;
    private $client_id;
    private $client_secret;
    private $api_key;
    private $is_authenticated = false;
    private $base_url = 'https://api.hubapi.com';
    private $rate_limit_remaining = 100;
    private $rate_limit_reset = 0;

    /**
     * Constructor
     *
     * @param array $config Configuration with client_id, client_secret, or api_key
     */
    public function __construct(array $config = []) {
        $this->client_id = $config['client_id'] ?? getenv('HUBSPOT_CLIENT_ID');
        $this->client_secret = $config['client_secret'] ?? getenv('HUBSPOT_CLIENT_SECRET');
        $this->api_key = $config['api_key'] ?? getenv('HUBSPOT_API_KEY');

        if (isset($config['access_token'])) {
            $this->access_token = $config['access_token'];
            $this->is_authenticated = true;
        }

        if (isset($config['refresh_token'])) {
            $this->refresh_token = $config['refresh_token'];
        }

        // If API key is provided, use it for simple authentication
        if ($this->api_key) {
            $this->is_authenticated = true;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(array $credentials): bool {
        // OAuth 2.0 authorization code flow
        if (isset($credentials['code'])) {
            return $this->authenticateWithCode($credentials['code'], $credentials['redirect_uri'] ?? '');
        }

        // Use refresh token if available
        if (isset($credentials['refresh_token'])) {
            $this->refresh_token = $credentials['refresh_token'];
            return $this->refreshToken();
        }

        // Direct token authentication
        if (isset($credentials['access_token'])) {
            $this->access_token = $credentials['access_token'];
            $this->is_authenticated = true;
            return true;
        }

        // API key authentication
        if (isset($credentials['api_key'])) {
            $this->api_key = $credentials['api_key'];
            $this->is_authenticated = true;
            return true;
        }

        throw new \Exception('Invalid credentials. Provide authorization code, refresh token, access token, or API key.');
    }

    /**
     * Authenticate using authorization code
     */
    private function authenticateWithCode(string $code, string $redirect_uri): bool {
        $token_url = 'https://api.hubapi.com/oauth/v1/token';

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
            throw new \Exception('OAuth error: ' . ($body['error_description'] ?? $body['message'] ?? 'Unknown error'));
        }

        $this->access_token = $body['access_token'];
        $this->refresh_token = $body['refresh_token'] ?? null;
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

        $token_url = 'https://api.hubapi.com/oauth/v1/token';

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
            throw new \Exception('Token refresh error: ' . ($body['error_description'] ?? $body['message'] ?? 'Unknown error'));
        }

        $this->access_token = $body['access_token'];
        $this->refresh_token = $body['refresh_token'] ?? $this->refresh_token;
        $this->is_authenticated = true;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function testConnection(): array {
        try {
            $this->checkAuthentication();

            // Test with access token info endpoint
            $url = $this->base_url . '/oauth/v1/access-tokens/' . $this->access_token;
            $response = $this->makeRequest('GET', $url);

            return [
                'success' => true,
                'message' => 'Connection successful',
                'details' => [
                    'hub_id' => $response['hub_id'] ?? null,
                    'user' => $response['user'] ?? null,
                    'hub_domain' => $response['hub_domain'] ?? null,
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

        $url = $this->base_url . '/crm/v3/objects/contacts';

        $params = [
            'limit' => $limit,
            'after' => $offset > 0 ? $offset : null,
            'properties' => 'firstname,lastname,email,phone,address,city,state,zip,country',
        ];

        // Add filters if provided
        if (!empty($filters)) {
            $params['filters'] = json_encode($filters);
        }

        $url .= '?' . http_build_query(array_filter($params));

        $response = $this->makeRequest('GET', $url);

        return $response['results'] ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function getContact(string $contactId): ?array {
        $this->checkAuthentication();

        try {
            $url = $this->base_url . "/crm/v3/objects/contacts/{$contactId}";
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

        $url = $this->base_url . '/crm/v3/objects/contacts';

        $hubspot_data = [
            'properties' => $this->mapToHubSpotContact($contactData),
        ];

        $response = $this->makeRequest('POST', $url, $hubspot_data);

        return [
            'id' => $response['id'],
            'success' => true,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function updateContact(string $contactId, array $contactData): array {
        $this->checkAuthentication();

        $url = $this->base_url . "/crm/v3/objects/contacts/{$contactId}";

        $hubspot_data = [
            'properties' => $this->mapToHubSpotContact($contactData),
        ];

        $response = $this->makeRequest('PATCH', $url, $hubspot_data);

        return ['id' => $contactId, 'success' => true];
    }

    /**
     * {@inheritDoc}
     */
    public function deleteContact(string $contactId): bool {
        $this->checkAuthentication();

        $url = $this->base_url . "/crm/v3/objects/contacts/{$contactId}";

        $this->makeRequest('DELETE', $url);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getDeals(array $filters = [], int $limit = 100, int $offset = 0): array {
        $this->checkAuthentication();

        $url = $this->base_url . '/crm/v3/objects/deals';

        $params = [
            'limit' => $limit,
            'after' => $offset > 0 ? $offset : null,
            'properties' => 'dealname,amount,dealstage,closedate,pipeline,hubspot_owner_id',
        ];

        $url .= '?' . http_build_query(array_filter($params));

        $response = $this->makeRequest('GET', $url);

        return $response['results'] ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function getDeal(string $dealId): ?array {
        $this->checkAuthentication();

        try {
            $url = $this->base_url . "/crm/v3/objects/deals/{$dealId}";
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

        $url = $this->base_url . '/crm/v3/objects/deals';

        $hubspot_data = [
            'properties' => $this->mapToHubSpotDeal($dealData),
        ];

        // Associate with contact if provided
        if (isset($dealData['contact_id'])) {
            $hubspot_data['associations'] = [
                [
                    'to' => ['id' => $dealData['contact_id']],
                    'types' => [
                        ['associationCategory' => 'HUBSPOT_DEFINED', 'associationTypeId' => 3], // Deal to Contact
                    ],
                ],
            ];
        }

        $response = $this->makeRequest('POST', $url, $hubspot_data);

        return [
            'id' => $response['id'],
            'success' => true,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function updateDeal(string $dealId, array $dealData): array {
        $this->checkAuthentication();

        $url = $this->base_url . "/crm/v3/objects/deals/{$dealId}";

        $hubspot_data = [
            'properties' => $this->mapToHubSpotDeal($dealData),
        ];

        $response = $this->makeRequest('PATCH', $url, $hubspot_data);

        return ['id' => $dealId, 'success' => true];
    }

    /**
     * {@inheritDoc}
     */
    public function deleteDeal(string $dealId): bool {
        $this->checkAuthentication();

        $url = $this->base_url . "/crm/v3/objects/deals/{$dealId}";

        $this->makeRequest('DELETE', $url);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function logActivity(string $objectType, string $objectId, array $activityData): array {
        $this->checkAuthentication();

        // HubSpot uses Engagements API (Timeline events)
        $url = $this->base_url . '/crm/v3/objects/notes';

        $note_data = [
            'properties' => [
                'hs_note_body' => $activityData['description'] ?? '',
                'hs_timestamp' => isset($activityData['date']) ? strtotime($activityData['date']) * 1000 : time() * 1000,
            ],
        ];

        // Associate with the object
        $association_type_map = [
            'contact' => 3, // Note to Contact
            'deal' => 4, // Note to Deal
        ];

        if (isset($association_type_map[$objectType])) {
            $note_data['associations'] = [
                [
                    'to' => ['id' => $objectId],
                    'types' => [
                        [
                            'associationCategory' => 'HUBSPOT_DEFINED',
                            'associationTypeId' => $association_type_map[$objectType],
                        ],
                    ],
                ],
            ];
        }

        $response = $this->makeRequest('POST', $url, $note_data);

        return [
            'id' => $response['id'],
            'success' => true,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function searchRecords(string $objectType, string $query, int $limit = 20): array {
        $this->checkAuthentication();

        $object_map = [
            'contact' => 'contacts',
            'deal' => 'deals',
            'company' => 'companies',
        ];

        $object = $object_map[$objectType] ?? 'contacts';

        $url = $this->base_url . "/crm/v3/objects/{$object}/search";

        $search_data = [
            'query' => $query,
            'limit' => $limit,
        ];

        $response = $this->makeRequest('POST', $url, $search_data);

        return $response['results'] ?? [];
    }

    /**
     * Make HTTP request to HubSpot API
     *
     * @param string $method HTTP method
     * @param string $url Full URL
     * @param array|null $body Request body
     * @return array Response data
     */
    private function makeRequest(string $method, string $url, ?array $body = null): array {
        // Rate limiting check
        if ($this->rate_limit_remaining <= 0 && time() < $this->rate_limit_reset) {
            $wait_time = $this->rate_limit_reset - time();
            throw new \Exception("Rate limit exceeded. Try again in {$wait_time} seconds.");
        }

        $headers = ['Content-Type' => 'application/json'];

        // Use OAuth token if available, otherwise use API key
        if ($this->access_token) {
            $headers['Authorization'] = 'Bearer ' . $this->access_token;
        } elseif ($this->api_key) {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . 'hapikey=' . $this->api_key;
        }

        $args = [
            'method' => $method,
            'headers' => $headers,
            'timeout' => 30,
        ];

        if ($body !== null && in_array($method, ['POST', 'PATCH', 'PUT'])) {
            $args['body'] = json_encode($body);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \Exception('HubSpot API request failed: ' . $response->get_error_message());
        }

        // Update rate limit info from headers
        $headers_response = wp_remote_retrieve_headers($response);
        if (isset($headers_response['x-hubspot-ratelimit-remaining'])) {
            $this->rate_limit_remaining = (int)$headers_response['x-hubspot-ratelimit-remaining'];
        }
        if (isset($headers_response['x-hubspot-ratelimit-secondly-remaining'])) {
            $this->rate_limit_reset = time() + 1;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        // Handle errors
        if ($status_code >= 400) {
            $error_message = isset($data['message']) ? $data['message'] : 'Unknown error';
            throw new \Exception("HubSpot API error ($status_code): $error_message");
        }

        // DELETE returns 204 with no content
        if ($status_code === 204) {
            return [];
        }

        return $data ?? [];
    }

    /**
     * Map deal room contact data to HubSpot Contact properties
     */
    private function mapToHubSpotContact(array $contactData): array {
        $hs_data = [];

        if (isset($contactData['first_name'])) $hs_data['firstname'] = $contactData['first_name'];
        if (isset($contactData['last_name'])) $hs_data['lastname'] = $contactData['last_name'];
        if (isset($contactData['email'])) $hs_data['email'] = $contactData['email'];
        if (isset($contactData['phone'])) $hs_data['phone'] = $contactData['phone'];

        if (isset($contactData['address'])) {
            $hs_data['address'] = $contactData['address']['street'] ?? '';
            $hs_data['city'] = $contactData['address']['city'] ?? '';
            $hs_data['state'] = $contactData['address']['state'] ?? '';
            $hs_data['zip'] = $contactData['address']['zip'] ?? '';
            $hs_data['country'] = $contactData['address']['country'] ?? 'USA';
        }

        return array_filter($hs_data);
    }

    /**
     * Map deal room transaction data to HubSpot Deal properties
     */
    private function mapToHubSpotDeal(array $dealData): array {
        $hs_data = [];

        if (isset($dealData['name'])) $hs_data['dealname'] = $dealData['name'];
        if (isset($dealData['amount'])) $hs_data['amount'] = (float)$dealData['amount'];
        if (isset($dealData['stage'])) $hs_data['dealstage'] = $dealData['stage'];
        if (isset($dealData['close_date'])) $hs_data['closedate'] = strtotime($dealData['close_date']) * 1000; // HubSpot uses milliseconds
        if (isset($dealData['pipeline'])) $hs_data['pipeline'] = $dealData['pipeline'];

        return array_filter($hs_data);
    }

    /**
     * Check if authenticated, throw exception if not
     */
    private function checkAuthentication(): void {
        if (!$this->is_authenticated || (!$this->access_token && !$this->api_key)) {
            throw new \Exception('Not authenticated. Call authenticate() first.');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getProviderType(): string {
        return 'hubspot';
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
        return $this->base_url;
    }
}
