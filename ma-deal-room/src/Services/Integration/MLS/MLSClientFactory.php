<?php
/**
 * MLS Client Factory
 *
 * Factory class for creating MLS client instances based on provider type.
 * Supports multiple MLS providers: RETS, Bridge Interactive, ListHub, etc.
 *
 * @package    MA_Deal_Room
 * @subpackage Services/Integration/MLS
 * @since      1.0.0
 */

namespace MADealRoom\Services\Integration\MLS;

use Exception;

/**
 * Class MLSClientFactory
 *
 * Creates appropriate MLS client instances based on provider configuration.
 */
class MLSClientFactory {

    /**
     * Supported MLS provider types
     *
     * @var array
     */
    private const SUPPORTED_PROVIDERS = [
        'rets' => RETSClient::class,
        'bridge' => BridgeClient::class,
        // Future providers can be added here:
        // 'listhub' => ListHubClient::class,
        // 'corelogic' => CoreLogicClient::class,
    ];

    /**
     * Create MLS client from configuration
     *
     * @param array $config Configuration array containing provider_type and credentials
     *                      [
     *                          'provider_type' => string ('rets', 'bridge', etc.),
     *                          'credentials' => array (provider-specific credentials),
     *                          'server_url' => string,
     *                          'config_options' => array (additional config)
     *                      ]
     * @return MLSClientInterface MLS client instance
     * @throws Exception If provider type is not supported or configuration is invalid
     */
    public static function create(array $config): MLSClientInterface {
        if (empty($config['provider_type'])) {
            throw new Exception('Missing provider_type in MLS configuration');
        }

        $provider_type = strtolower($config['provider_type']);

        if (!isset(self::SUPPORTED_PROVIDERS[$provider_type])) {
            throw new Exception("Unsupported MLS provider type: {$provider_type}");
        }

        $client_class = self::SUPPORTED_PROVIDERS[$provider_type];

        // Build provider-specific configuration
        $provider_config = self::buildProviderConfig($provider_type, $config);

        // Instantiate the client
        $client = new $client_class($provider_config);

        // Authenticate if credentials are provided
        if (!empty($config['credentials']) && is_array($config['credentials'])) {
            try {
                $client->authenticate($config['credentials']);
            } catch (Exception $e) {
                // Log authentication failure but still return the client
                // Caller can check isAuthenticated() or handle the exception
                error_log("MLS authentication failed for {$provider_type}: " . $e->getMessage());
            }
        }

        return $client;
    }

    /**
     * Create MLS client from database configuration
     *
     * @param int $account_id Account ID
     * @param int|null $config_id Optional specific configuration ID
     * @return MLSClientInterface|null MLS client instance or null if no config found
     * @throws Exception If configuration is invalid
     */
    public static function createFromDatabase(int $account_id, ?int $config_id = null): ?MLSClientInterface {
        global $wpdb;

        $table = $wpdb->prefix . 'ma_deal_mls_config';

        // Build query
        $query = "SELECT * FROM {$table} WHERE account_id = %d AND is_active = 1";
        $params = [$account_id];

        if ($config_id !== null) {
            $query .= " AND id = %d";
            $params[] = $config_id;
        }

        $query .= " ORDER BY id DESC LIMIT 1";

        $config_row = $wpdb->get_row($wpdb->prepare($query, $params), ARRAY_A);

        if (!$config_row) {
            return null;
        }

        // Decrypt credentials
        $credentials = self::decryptCredentials($config_row['credentials']);

        // Build configuration array
        $config = [
            'provider_type' => $config_row['provider_type'],
            'credentials' => $credentials,
            'server_url' => $config_row['server_url'] ?? '',
            'config_options' => [
                'login_url' => $config_row['login_url'] ?? '',
                'resource' => $config_row['resource_name'] ?? 'Property',
                'class_name' => $config_row['class_name'] ?? 'RES',
            ],
        ];

        return self::create($config);
    }

    /**
     * Get list of supported provider types
     *
     * @return array Provider types with display names
     *               [
     *                   'rets' => 'RETS (Real Estate Transaction Standard)',
     *                   'bridge' => 'Bridge Interactive',
     *                   ...
     *               ]
     */
    public static function getSupportedProviders(): array {
        return [
            'rets' => [
                'name' => 'RETS (Real Estate Transaction Standard)',
                'description' => 'Most common MLS data feed protocol used across North America',
                'capabilities' => [
                    'search' => true,
                    'details' => true,
                    'photos' => true,
                    'submit' => false,
                    'update' => false,
                    'delete' => false,
                ],
            ],
            'bridge' => [
                'name' => 'Bridge Interactive',
                'description' => 'Modern REST API platform used by many MLSs',
                'capabilities' => [
                    'search' => true,
                    'details' => true,
                    'photos' => true,
                    'submit' => true,
                    'update' => true,
                    'delete' => true,
                ],
            ],
        ];
    }

    /**
     * Get required configuration fields for a provider type
     *
     * @param string $provider_type Provider type
     * @return array Required fields
     *               [
     *                   'field_name' => [
     *                       'label' => string,
     *                       'type' => 'text'|'password'|'url',
     *                       'required' => bool,
     *                       'description' => string
     *                   ]
     *               ]
     */
    public static function getRequiredFields(string $provider_type): array {
        $provider_type = strtolower($provider_type);

        switch ($provider_type) {
            case 'rets':
                return [
                    'server_url' => [
                        'label' => 'RETS Server URL',
                        'type' => 'url',
                        'required' => true,
                        'description' => 'Base URL of the RETS server',
                        'placeholder' => 'https://rets.example.com/rets/',
                    ],
                    'login_url' => [
                        'label' => 'Login URL',
                        'type' => 'url',
                        'required' => false,
                        'description' => 'Login URL (if different from server URL)',
                        'placeholder' => 'https://rets.example.com/rets/login',
                    ],
                    'username' => [
                        'label' => 'Username',
                        'type' => 'text',
                        'required' => true,
                        'description' => 'RETS account username',
                    ],
                    'password' => [
                        'label' => 'Password',
                        'type' => 'password',
                        'required' => true,
                        'description' => 'RETS account password',
                    ],
                    'resource_name' => [
                        'label' => 'Resource Name',
                        'type' => 'text',
                        'required' => true,
                        'description' => 'RETS resource name (e.g., Property)',
                        'placeholder' => 'Property',
                        'default' => 'Property',
                    ],
                    'class_name' => [
                        'label' => 'Class Name',
                        'type' => 'text',
                        'required' => true,
                        'description' => 'RETS class name (e.g., RES for Residential)',
                        'placeholder' => 'RES',
                        'default' => 'RES',
                    ],
                ];

            case 'bridge':
                return [
                    'api_url' => [
                        'label' => 'API Base URL',
                        'type' => 'url',
                        'required' => true,
                        'description' => 'Full Bridge Interactive API URL including OData path',
                        'placeholder' => 'https://api.bridgedataoutput.com/api/v2/OData/shared_mlspin_XXXXX',
                        'default' => '',
                    ],
                    'server_token' => [
                        'label' => 'Server Token',
                        'type' => 'password',
                        'required' => false,
                        'description' => 'Server Access Token (use this OR Client ID/Secret)',
                    ],
                    'client_id' => [
                        'label' => 'Client ID',
                        'type' => 'text',
                        'required' => false,
                        'description' => 'OAuth 2.0 Client ID (if not using Server Token)',
                    ],
                    'client_secret' => [
                        'label' => 'Client Secret',
                        'type' => 'password',
                        'required' => false,
                        'description' => 'OAuth 2.0 Client Secret (if not using Server Token)',
                    ],
                ];

            default:
                return [];
        }
    }

    /**
     * Validate provider configuration
     *
     * @param string $provider_type Provider type
     * @param array  $config        Configuration to validate
     * @return array Validation result
     *               [
     *                   'valid' => bool,
     *                   'errors' => array,
     *                   'warnings' => array
     *               ]
     */
    public static function validateConfig(string $provider_type, array $config): array {
        $errors = [];
        $warnings = [];

        $provider_type = strtolower($provider_type);

        if (!isset(self::SUPPORTED_PROVIDERS[$provider_type])) {
            $errors[] = "Unsupported provider type: {$provider_type}";
            return [
                'valid' => false,
                'errors' => $errors,
                'warnings' => $warnings,
            ];
        }

        $required_fields = self::getRequiredFields($provider_type);

        foreach ($required_fields as $field_name => $field_config) {
            if ($field_config['required'] && empty($config[$field_name])) {
                $errors[] = "Required field '{$field_config['label']}' is missing";
            }

            // Validate URL fields
            if ($field_config['type'] === 'url' && !empty($config[$field_name])) {
                if (!filter_var($config[$field_name], FILTER_VALIDATE_URL)) {
                    $errors[] = "'{$field_config['label']}' must be a valid URL";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Build provider-specific configuration
     *
     * @param string $provider_type Provider type
     * @param array  $config        Raw configuration
     * @return array Provider-specific configuration
     */
    private static function buildProviderConfig(string $provider_type, array $config): array {
        $provider_config = $config['config_options'] ?? [];

        switch ($provider_type) {
            case 'rets':
                return array_merge([
                    'server_url' => $config['server_url'] ?? '',
                    'login_url' => $config['login_url'] ?? $config['server_url'] ?? '',
                    'username' => $config['credentials']['username'] ?? '',
                    'password' => $config['credentials']['password'] ?? '',
                    'resource' => $provider_config['resource'] ?? 'Property',
                    'class_name' => $provider_config['class_name'] ?? 'RES',
                    'user_agent' => 'MA-Deal-Room/1.0',
                    'rets_version' => '1.7.2',
                ], $provider_config);

            case 'bridge':
                return array_merge([
                    'api_url' => $config['credentials']['api_url'] ?? $config['server_url'] ?? 'https://api.bridgedataoutput.com/api/v2',
                    'client_id' => $config['credentials']['client_id'] ?? '',
                    'client_secret' => $config['credentials']['client_secret'] ?? '',
                    'access_token' => $config['credentials']['server_token'] ?? $config['credentials']['access_token'] ?? null,
                ], $provider_config);

            default:
                return $provider_config;
        }
    }

    /**
     * Encrypt credentials for database storage
     *
     * @param array $credentials Credentials array
     * @return string Encrypted credentials
     */
    public static function encryptCredentials(array $credentials): string {
        // Use WordPress salts for encryption key
        $key = defined('AUTH_KEY') ? AUTH_KEY : '';

        if (empty($key)) {
            throw new Exception('WordPress AUTH_KEY not defined');
        }

        $json = json_encode($credentials);
        $iv = openssl_random_pseudo_bytes(16);

        $encrypted = openssl_encrypt(
            $json,
            'AES-256-CBC',
            hash('sha256', $key, true),
            0,
            $iv
        );

        // Combine IV and encrypted data
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt credentials from database
     *
     * @param string $encrypted_credentials Encrypted credentials
     * @return array Decrypted credentials
     */
    private static function decryptCredentials(string $encrypted_credentials): array {
        // Use WordPress salts for encryption key
        $key = defined('AUTH_KEY') ? AUTH_KEY : '';

        if (empty($key)) {
            throw new Exception('WordPress AUTH_KEY not defined');
        }

        $data = base64_decode($encrypted_credentials);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);

        $decrypted = openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            hash('sha256', $key, true),
            0,
            $iv
        );

        if ($decrypted === false) {
            throw new Exception('Failed to decrypt credentials');
        }

        $credentials = json_decode($decrypted, true);

        if ($credentials === null) {
            throw new Exception('Invalid credentials format');
        }

        return $credentials;
    }

    /**
     * Test MLS connection
     *
     * @param string $provider_type Provider type
     * @param array  $config        Configuration to test
     * @return array Test result
     *               [
     *                   'success' => bool,
     *                   'message' => string,
     *                   'server_info' => array
     *               ]
     */
    public static function testConnection(string $provider_type, array $config): array {
        try {
            $full_config = [
                'provider_type' => $provider_type,
                'credentials' => $config,
                'server_url' => $config['server_url'] ?? $config['api_url'] ?? '',
                'config_options' => $config,
            ];

            $client = self::create($full_config);

            return $client->testConnection();

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'server_info' => [],
            ];
        }
    }
}
