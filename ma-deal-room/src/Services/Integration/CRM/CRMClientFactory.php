<?php
/**
 * CRM Client Factory
 *
 * Factory pattern implementation for creating CRM client instances.
 * Instantiates the correct CRM client based on provider type and configuration.
 *
 * @package MA_Deal_Room\Services\Integration\CRM
 * @since 1.0.0
 */

namespace MADealRoom\Services\Integration\CRM;

use MADealRoom\Repositories\CRMConfigRepository;

class CRMClientFactory {
    private $repository;
    private static $instances = [];

    public function __construct() {
        $this->repository = new CRMConfigRepository();
    }

    /**
     * Create CRM client from account configuration
     *
     * @param int $account_id Account ID
     * @param string $provider_type Provider type (salesforce, hubspot)
     * @return CRMClientInterface CRM client instance
     * @throws \Exception If configuration not found or provider not supported
     */
    public function createFromAccount(int $account_id, string $provider_type): CRMClientInterface {
        // Check for cached instance
        $cache_key = "{$account_id}_{$provider_type}";
        if (isset(self::$instances[$cache_key])) {
            return self::$instances[$cache_key];
        }

        // Load configuration from database
        $config = $this->repository->getByAccountAndProvider($account_id, $provider_type);

        if (!$config) {
            throw new \Exception("CRM configuration not found for account {$account_id} and provider {$provider_type}");
        }

        if (!$config['is_active']) {
            throw new \Exception("CRM configuration is inactive for account {$account_id} and provider {$provider_type}");
        }

        // Create client instance
        $client = $this->create($provider_type, $config);

        // Cache instance
        self::$instances[$cache_key] = $client;

        return $client;
    }

    /**
     * Create CRM client from configuration array
     *
     * @param string $provider_type Provider type (salesforce, hubspot)
     * @param array $config Configuration array
     * @return CRMClientInterface CRM client instance
     * @throws \Exception If provider not supported
     */
    public function create(string $provider_type, array $config = []): CRMClientInterface {
        $provider_type = strtolower($provider_type);

        switch ($provider_type) {
            case 'salesforce':
                return $this->createSalesforceClient($config);

            case 'hubspot':
                return $this->createHubSpotClient($config);

            default:
                throw new \Exception("Unsupported CRM provider: {$provider_type}");
        }
    }

    /**
     * Create Salesforce client instance
     *
     * @param array $config Configuration array
     * @return SalesforceClient
     */
    private function createSalesforceClient(array $config): SalesforceClient {
        $credentials = $config['credentials_decrypted'] ?? [];

        $client_config = [
            'client_id' => $credentials['client_id'] ?? null,
            'client_secret' => $credentials['client_secret'] ?? null,
        ];

        // Add tokens if available
        if (isset($credentials['access_token'])) {
            $client_config['access_token'] = $credentials['access_token'];
        }

        if (!empty($config['refresh_token_decrypted'])) {
            $client_config['refresh_token'] = $config['refresh_token_decrypted'];
        }

        if (!empty($config['instance_url'])) {
            $client_config['instance_url'] = $config['instance_url'];
        }

        $client = new SalesforceClient($client_config);

        // Attempt authentication if we have tokens
        if (isset($client_config['access_token'])) {
            try {
                $client->authenticate([
                    'access_token' => $client_config['access_token'],
                    'instance_url' => $client_config['instance_url'],
                ]);
            } catch (\Exception $e) {
                // Try to refresh token if authentication fails
                if (isset($client_config['refresh_token'])) {
                    try {
                        $client->authenticate(['refresh_token' => $client_config['refresh_token']]);

                        // Update tokens in database
                        if (isset($config['id'])) {
                            $this->repository->updateTokens(
                                $config['id'],
                                $client->getAccessToken(),
                                $client->getRefreshToken()
                            );
                        }
                    } catch (\Exception $refresh_error) {
                        error_log('Salesforce token refresh failed: ' . $refresh_error->getMessage());
                        throw $e; // Re-throw original error
                    }
                }
            }
        }

        return $client;
    }

    /**
     * Create HubSpot client instance
     *
     * @param array $config Configuration array
     * @return HubSpotClient
     */
    private function createHubSpotClient(array $config): HubSpotClient {
        $credentials = $config['credentials_decrypted'] ?? [];

        $client_config = [
            'client_id' => $credentials['client_id'] ?? null,
            'client_secret' => $credentials['client_secret'] ?? null,
            'api_key' => $credentials['api_key'] ?? null, // HubSpot supports API key auth
        ];

        // Add tokens if available
        if (isset($credentials['access_token'])) {
            $client_config['access_token'] = $credentials['access_token'];
        }

        if (!empty($config['refresh_token_decrypted'])) {
            $client_config['refresh_token'] = $config['refresh_token_decrypted'];
        }

        $client = new HubSpotClient($client_config);

        // Attempt authentication
        if (isset($client_config['access_token'])) {
            try {
                $client->authenticate(['access_token' => $client_config['access_token']]);
            } catch (\Exception $e) {
                // Try to refresh token if authentication fails
                if (isset($client_config['refresh_token'])) {
                    try {
                        $client->authenticate(['refresh_token' => $client_config['refresh_token']]);

                        // Update tokens in database
                        if (isset($config['id'])) {
                            $this->repository->updateTokens(
                                $config['id'],
                                $client->getAccessToken(),
                                $client->getRefreshToken()
                            );
                        }
                    } catch (\Exception $refresh_error) {
                        error_log('HubSpot token refresh failed: ' . $refresh_error->getMessage());
                        throw $e; // Re-throw original error
                    }
                }
            }
        } elseif (isset($client_config['api_key'])) {
            $client->authenticate(['api_key' => $client_config['api_key']]);
        }

        return $client;
    }

    /**
     * Get supported CRM providers
     *
     * @return array Array of supported provider types
     */
    public static function getSupportedProviders(): array {
        return [
            'salesforce' => [
                'name' => 'Salesforce',
                'auth_type' => 'oauth2',
                'supports_api_key' => false,
            ],
            'hubspot' => [
                'name' => 'HubSpot',
                'auth_type' => 'oauth2',
                'supports_api_key' => true,
            ],
        ];
    }

    /**
     * Check if provider is supported
     *
     * @param string $provider_type Provider type
     * @return bool True if supported
     */
    public static function isProviderSupported(string $provider_type): bool {
        $providers = self::getSupportedProviders();
        return isset($providers[strtolower($provider_type)]);
    }

    /**
     * Clear cached client instances
     *
     * @param int|null $account_id Optional account ID to clear specific account cache
     * @param string|null $provider_type Optional provider type to clear specific provider cache
     */
    public static function clearCache(?int $account_id = null, ?string $provider_type = null): void {
        if ($account_id && $provider_type) {
            $cache_key = "{$account_id}_{$provider_type}";
            unset(self::$instances[$cache_key]);
        } elseif ($account_id) {
            // Clear all instances for this account
            foreach (self::$instances as $key => $instance) {
                if (strpos($key, "{$account_id}_") === 0) {
                    unset(self::$instances[$key]);
                }
            }
        } else {
            // Clear all instances
            self::$instances = [];
        }
    }

    /**
     * Test CRM connection
     *
     * @param int $account_id Account ID
     * @param string $provider_type Provider type
     * @return array Connection test result
     */
    public function testConnection(int $account_id, string $provider_type): array {
        try {
            $client = $this->createFromAccount($account_id, $provider_type);
            return $client->testConnection();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'details' => [],
            ];
        }
    }

    /**
     * Get or create singleton instance of factory
     *
     * @return self
     */
    public static function getInstance(): self {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }
}
