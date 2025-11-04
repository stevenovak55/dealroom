<?php
/**
 * DocuSign API Client Implementation
 *
 * @package    MADealRoom
 * @subpackage Services\Integration\DocuSign
 * @since      1.0.0
 */

namespace MADealRoom\Services\Integration\DocuSign;

use Firebase\JWT\JWT;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use MADealRoom\Core\Logger;

/**
 * Class DocuSignClient
 *
 * DocuSign REST API v2.1 implementation with OAuth 2.0 JWT authentication
 */
class DocuSignClient implements DocuSignClientInterface {
    /**
     * DocuSign base URLs
     */
    private const PRODUCTION_BASE_URL = 'https://www.docusign.net/restapi';
    private const SANDBOX_BASE_URL = 'https://demo.docusign.net/restapi';
    private const PRODUCTION_AUTH_URL = 'https://account.docusign.com/oauth/token';
    private const SANDBOX_AUTH_URL = 'https://account-d.docusign.com/oauth/token';

    /**
     * Token expiration buffer (5 minutes)
     */
    private const TOKEN_EXPIRATION_BUFFER = 300;

    /**
     * HTTP client
     *
     * @var HttpClient
     */
    private HttpClient $http_client;

    /**
     * Integration key (Client ID)
     *
     * @var string
     */
    private string $integration_key;

    /**
     * User ID (GUID)
     *
     * @var string
     */
    private string $user_id;

    /**
     * Private key (RSA)
     *
     * @var string
     */
    private string $private_key;

    /**
     * DocuSign Account ID
     *
     * @var string
     */
    private string $account_id;

    /**
     * Environment (production or sandbox)
     *
     * @var string
     */
    private string $environment;

    /**
     * Access token
     *
     * @var string|null
     */
    private ?string $access_token = null;

    /**
     * Token expiration timestamp
     *
     * @var int|null
     */
    private ?int $token_expires_at = null;

    /**
     * Logger instance
     *
     * @var Logger
     */
    private Logger $logger;

    /**
     * Constructor
     *
     * @param string $integration_key Integration key (OAuth Client ID)
     * @param string $user_id         User ID (GUID)
     * @param string $private_key     Private key (PEM format)
     * @param string $account_id      DocuSign account ID
     * @param string $environment     Environment ('production' or 'sandbox')
     * @param Logger|null $logger     Logger instance
     */
    public function __construct(
        string $integration_key,
        string $user_id,
        string $private_key,
        string $account_id,
        string $environment = 'sandbox',
        ?Logger $logger = null
    ) {
        $this->integration_key = $integration_key;
        $this->user_id = $user_id;
        $this->private_key = $private_key;
        $this->account_id = $account_id;
        $this->environment = strtolower($environment);
        $this->logger = $logger ?? new Logger();

        $this->http_client = new HttpClient([
            'timeout' => 30,
            'verify' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(): array {
        try {
            $jwt_token = $this->generateJWT();
            $auth_url = $this->getAuthUrl();

            $response = $this->http_client->post($auth_url, [
                'form_params' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt_token,
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (!isset($result['access_token'])) {
                throw new \Exception('No access token in response');
            }

            // Store token and expiration
            $this->access_token = $result['access_token'];
            $this->token_expires_at = time() + ($result['expires_in'] ?? 3600);

            $this->logger->info('DocuSign authentication successful', [
                'environment' => $this->environment,
                'expires_in' => $result['expires_in'] ?? 3600,
            ]);

            return $result;
        } catch (GuzzleException $e) {
            $this->logger->error('DocuSign authentication failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw new \Exception('DocuSign authentication failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function refreshToken(): array {
        // JWT grant doesn't use refresh tokens - just re-authenticate
        return $this->authenticate();
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessToken(): ?string {
        if (!$this->isAuthenticated()) {
            $this->authenticate();
        }

        return $this->access_token;
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticated(): bool {
        if (!$this->access_token || !$this->token_expires_at) {
            return false;
        }

        // Check if token is expired (with buffer)
        return time() < ($this->token_expires_at - self::TOKEN_EXPIRATION_BUFFER);
    }

    /**
     * {@inheritdoc}
     */
    public function listTemplates(array $options = []): array {
        $query_params = [];

        if (isset($options['folder_id'])) {
            $query_params['folder_ids'] = $options['folder_id'];
        }

        if (isset($options['search_text'])) {
            $query_params['search_text'] = $options['search_text'];
        }

        return $this->apiRequest('GET', "/v2.1/accounts/{$this->account_id}/templates", [
            'query' => $query_params,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate(string $template_id): array {
        return $this->apiRequest('GET', "/v2.1/accounts/{$this->account_id}/templates/{$template_id}");
    }

    /**
     * {@inheritdoc}
     */
    public function createEnvelope(array $envelope_definition): array {
        return $this->apiRequest('POST', "/v2.1/accounts/{$this->account_id}/envelopes", [
            'json' => $envelope_definition,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function createEnvelopeFromTemplate(string $template_id, array $envelope_data): array {
        $envelope_definition = array_merge([
            'templateId' => $template_id,
            'status' => 'created', // Don't send immediately
        ], $envelope_data);

        return $this->createEnvelope($envelope_definition);
    }

    /**
     * {@inheritdoc}
     */
    public function sendEnvelope(string $envelope_id): array {
        return $this->apiRequest('PUT', "/v2.1/accounts/{$this->account_id}/envelopes/{$envelope_id}", [
            'json' => ['status' => 'sent'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvelopeStatus(string $envelope_id): array {
        return $this->apiRequest('GET', "/v2.1/accounts/{$this->account_id}/envelopes/{$envelope_id}");
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvelopeRecipients(string $envelope_id): array {
        return $this->apiRequest('GET', "/v2.1/accounts/{$this->account_id}/envelopes/{$envelope_id}/recipients");
    }

    /**
     * {@inheritdoc}
     */
    public function downloadDocument(string $envelope_id, string $document_id = 'combined'): string {
        $response = $this->apiRequest(
            'GET',
            "/v2.1/accounts/{$this->account_id}/envelopes/{$envelope_id}/documents/{$document_id}",
            ['decode' => false]
        );

        return $response; // Binary content
    }

    /**
     * {@inheritdoc}
     */
    public function downloadCertificate(string $envelope_id): string {
        $response = $this->apiRequest(
            'GET',
            "/v2.1/accounts/{$this->account_id}/envelopes/{$envelope_id}/documents/certificate",
            ['decode' => false]
        );

        return $response; // Binary PDF content
    }

    /**
     * {@inheritdoc}
     */
    public function voidEnvelope(string $envelope_id, string $void_reason): array {
        return $this->apiRequest('PUT', "/v2.1/accounts/{$this->account_id}/envelopes/{$envelope_id}", [
            'json' => [
                'status' => 'voided',
                'voidedReason' => $void_reason,
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function resendEnvelope(string $envelope_id): array {
        return $this->apiRequest(
            'PUT',
            "/v2.1/accounts/{$this->account_id}/envelopes/{$envelope_id}/recipients",
            [
                'json' => ['resendEnvelope' => true],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function testConnection(): array {
        try {
            $this->authenticate();

            // Try to get account info
            $account_info = $this->apiRequest('GET', "/v2.1/accounts/{$this->account_id}");

            return [
                'success' => true,
                'message' => 'Successfully connected to DocuSign',
                'account_info' => $account_info,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate JWT token for authentication
     *
     * @return string JWT token
     * @throws \Exception If JWT generation fails
     */
    private function generateJWT(): string {
        $now = time();

        $payload = [
            'iss' => $this->integration_key, // Integration key (client ID)
            'sub' => $this->user_id,         // User ID (GUID)
            'aud' => $this->getAudience(),   // Account server
            'iat' => $now,                   // Issued at
            'exp' => $now + 3600,            // Expires in 1 hour
            'scope' => 'signature impersonation', // Required scopes
        ];

        try {
            return JWT::encode($payload, $this->private_key, 'RS256');
        } catch (\Exception $e) {
            $this->logger->error('JWT generation failed', [
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to generate JWT: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get audience URL for JWT
     *
     * @return string Audience URL
     */
    private function getAudience(): string {
        return $this->environment === 'production'
            ? 'account.docusign.com'
            : 'account-d.docusign.com';
    }

    /**
     * Get authentication URL
     *
     * @return string Auth URL
     */
    private function getAuthUrl(): string {
        return $this->environment === 'production'
            ? self::PRODUCTION_AUTH_URL
            : self::SANDBOX_AUTH_URL;
    }

    /**
     * Get base API URL
     *
     * @return string Base URL
     */
    private function getBaseUrl(): string {
        return $this->environment === 'production'
            ? self::PRODUCTION_BASE_URL
            : self::SANDBOX_BASE_URL;
    }

    /**
     * Make API request
     *
     * @param string $method  HTTP method
     * @param string $path    API path
     * @param array  $options Request options
     * @return array|string Response data (array for JSON, string for binary)
     * @throws \Exception If API request fails
     */
    private function apiRequest(string $method, string $path, array $options = []) {
        // Ensure we have a valid token
        if (!$this->isAuthenticated()) {
            $this->authenticate();
        }

        $url = $this->getBaseUrl() . $path;

        // Add authorization header
        $headers = [
            'Authorization' => 'Bearer ' . $this->access_token,
            'Accept' => 'application/json',
        ];

        if (isset($options['json'])) {
            $headers['Content-Type'] = 'application/json';
        }

        $options['headers'] = array_merge($headers, $options['headers'] ?? []);

        try {
            $response = $this->http_client->request($method, $url, $options);
            $body = $response->getBody()->getContents();

            // Return raw content for binary downloads
            if (isset($options['decode']) && $options['decode'] === false) {
                return $body;
            }

            // Decode JSON response
            $result = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
            }

            return $result;
        } catch (GuzzleException $e) {
            $error_message = $this->parseErrorResponse($e);

            $this->logger->error('DocuSign API request failed', [
                'method' => $method,
                'path' => $path,
                'error' => $error_message,
                'code' => $e->getCode(),
            ]);

            throw new \Exception("DocuSign API error: {$error_message}", $e->getCode(), $e);
        }
    }

    /**
     * Parse error response from Guzzle exception
     *
     * @param GuzzleException $e Exception
     * @return string Error message
     */
    private function parseErrorResponse(GuzzleException $e): string {
        if (method_exists($e, 'getResponse') && $e->getResponse()) {
            $body = $e->getResponse()->getBody()->getContents();
            $error = json_decode($body, true);

            if (isset($error['message'])) {
                return $error['message'];
            }

            if (isset($error['errorCode'])) {
                return $error['errorCode'];
            }

            return $body;
        }

        return $e->getMessage();
    }
}
