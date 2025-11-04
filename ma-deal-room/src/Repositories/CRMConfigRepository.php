<?php
/**
 * CRM Configuration Repository
 *
 * Handles database operations for CRM integration configurations.
 * Manages secure storage of OAuth credentials and sync settings.
 *
 * @package MA_Deal_Room\Repositories
 * @since 1.0.0
 */

namespace MADealRoom\Repositories;

class CRMConfigRepository {
    private $wpdb;
    private $table_name;
    private $encryption_key;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'ma_deal_crm_config';

        // Get encryption key from environment or generate one
        $this->encryption_key = defined('CRM_ENCRYPTION_KEY')
            ? CRM_ENCRYPTION_KEY
            : get_option('ma_deal_crm_encryption_key', $this->generateEncryptionKey());
    }

    /**
     * Generate a secure encryption key
     */
    private function generateEncryptionKey(): string {
        $key = base64_encode(random_bytes(32));
        update_option('ma_deal_crm_encryption_key', $key, false);
        return $key;
    }

    /**
     * Encrypt sensitive data
     */
    private function encrypt(string $data): string {
        $key = base64_decode($this->encryption_key);
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt sensitive data
     */
    private function decrypt(string $data): string {
        $key = base64_decode($this->encryption_key);
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }

    /**
     * Create or update CRM configuration
     *
     * @param int $account_id Account ID
     * @param string $provider_type Provider type (salesforce, hubspot)
     * @param array $config Configuration data
     * @return int|false Configuration ID or false on failure
     */
    public function upsert(int $account_id, string $provider_type, array $config) {
        // Check if config already exists
        $existing = $this->getByAccountAndProvider($account_id, $provider_type);

        // Encrypt sensitive fields
        $credentials = isset($config['credentials']) ? $this->encrypt(json_encode($config['credentials'])) : null;
        $refresh_token = isset($config['refresh_token']) ? $this->encrypt($config['refresh_token']) : null;

        $data = [
            'account_id' => $account_id,
            'provider_type' => $provider_type,
            'credentials' => $credentials,
            'instance_url' => $config['instance_url'] ?? null,
            'refresh_token' => $refresh_token,
            'sync_enabled' => $config['sync_enabled'] ?? 1,
            'sync_contacts' => $config['sync_contacts'] ?? 1,
            'sync_deals' => $config['sync_deals'] ?? 1,
            'sync_activities' => $config['sync_activities'] ?? 1,
            'field_mapping' => isset($config['field_mapping']) ? json_encode($config['field_mapping']) : null,
            'stage_mapping' => isset($config['stage_mapping']) ? json_encode($config['stage_mapping']) : null,
            'sync_direction' => $config['sync_direction'] ?? 'bidirectional',
            'conflict_resolution' => $config['conflict_resolution'] ?? 'last_write_wins',
            'is_active' => $config['is_active'] ?? 1,
        ];

        if ($existing) {
            // Update existing
            $result = $this->wpdb->update(
                $this->table_name,
                $data,
                ['id' => $existing['id']],
                ['%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d'],
                ['%d']
            );
            return $result !== false ? $existing['id'] : false;
        } else {
            // Insert new
            $result = $this->wpdb->insert(
                $this->table_name,
                $data,
                ['%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d']
            );
            return $result !== false ? $this->wpdb->insert_id : false;
        }
    }

    /**
     * Get CRM configuration by ID
     *
     * @param int $id Configuration ID
     * @return array|null Configuration or null if not found
     */
    public function getById(int $id): ?array {
        $config = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id),
            ARRAY_A
        );

        return $config ? $this->decryptConfig($config) : null;
    }

    /**
     * Get CRM configuration by account and provider
     *
     * @param int $account_id Account ID
     * @param string $provider_type Provider type
     * @return array|null Configuration or null if not found
     */
    public function getByAccountAndProvider(int $account_id, string $provider_type): ?array {
        $config = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE account_id = %d AND provider_type = %s",
                $account_id,
                $provider_type
            ),
            ARRAY_A
        );

        return $config ? $this->decryptConfig($config) : null;
    }

    /**
     * Get all active CRM configurations for an account
     *
     * @param int $account_id Account ID
     * @return array Array of configurations
     */
    public function getByAccount(int $account_id): array {
        $configs = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE account_id = %d AND is_active = 1 ORDER BY created_at DESC",
                $account_id
            ),
            ARRAY_A
        );

        return array_map([$this, 'decryptConfig'], $configs);
    }

    /**
     * Get all CRM configurations with sync enabled
     *
     * @return array Array of configurations
     */
    public function getActiveSyncConfigs(): array {
        $configs = $this->wpdb->get_results(
            "SELECT * FROM {$this->table_name} WHERE is_active = 1 AND sync_enabled = 1",
            ARRAY_A
        );

        return array_map([$this, 'decryptConfig'], $configs);
    }

    /**
     * Update last sync timestamp
     *
     * @param int $id Configuration ID
     * @return bool True on success
     */
    public function updateLastSync(int $id): bool {
        return $this->wpdb->update(
            $this->table_name,
            ['last_sync_at' => current_time('mysql')],
            ['id' => $id],
            ['%s'],
            ['%d']
        ) !== false;
    }

    /**
     * Update OAuth tokens
     *
     * @param int $id Configuration ID
     * @param string $access_token Access token
     * @param string $refresh_token Refresh token
     * @return bool True on success
     */
    public function updateTokens(int $id, string $access_token, string $refresh_token = null): bool {
        // Get existing credentials
        $config = $this->getById($id);
        if (!$config) {
            return false;
        }

        $credentials = $config['credentials_decrypted'];
        $credentials['access_token'] = $access_token;

        if ($refresh_token) {
            $credentials['refresh_token'] = $refresh_token;
        }

        $data = [
            'credentials' => $this->encrypt(json_encode($credentials)),
        ];

        if ($refresh_token) {
            $data['refresh_token'] = $this->encrypt($refresh_token);
        }

        return $this->wpdb->update(
            $this->table_name,
            $data,
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        ) !== false;
    }

    /**
     * Delete CRM configuration
     *
     * @param int $id Configuration ID
     * @return bool True on success
     */
    public function delete(int $id): bool {
        return $this->wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        ) !== false;
    }

    /**
     * Deactivate CRM configuration
     *
     * @param int $id Configuration ID
     * @return bool True on success
     */
    public function deactivate(int $id): bool {
        return $this->wpdb->update(
            $this->table_name,
            ['is_active' => 0, 'sync_enabled' => 0],
            ['id' => $id],
            ['%d', '%d'],
            ['%d']
        ) !== false;
    }

    /**
     * Decrypt configuration fields
     *
     * @param array $config Configuration array
     * @return array Configuration with decrypted fields
     */
    private function decryptConfig(array $config): array {
        if (!empty($config['credentials'])) {
            try {
                $config['credentials_decrypted'] = json_decode($this->decrypt($config['credentials']), true);
            } catch (\Exception $e) {
                error_log('CRM Config: Failed to decrypt credentials: ' . $e->getMessage());
                $config['credentials_decrypted'] = [];
            }
        }

        if (!empty($config['refresh_token'])) {
            try {
                $config['refresh_token_decrypted'] = $this->decrypt($config['refresh_token']);
            } catch (\Exception $e) {
                error_log('CRM Config: Failed to decrypt refresh token: ' . $e->getMessage());
                $config['refresh_token_decrypted'] = null;
            }
        }

        if (!empty($config['field_mapping'])) {
            $config['field_mapping'] = json_decode($config['field_mapping'], true);
        }

        if (!empty($config['stage_mapping'])) {
            $config['stage_mapping'] = json_decode($config['stage_mapping'], true);
        }

        return $config;
    }

    /**
     * Get sync statistics
     *
     * @param int $account_id Account ID
     * @return array Statistics
     */
    public function getSyncStats(int $account_id): array {
        $total = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE account_id = %d",
                $account_id
            )
        );

        $active = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE account_id = %d AND is_active = 1",
                $account_id
            )
        );

        $sync_enabled = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE account_id = %d AND sync_enabled = 1",
                $account_id
            )
        );

        return [
            'total' => (int)$total,
            'active' => (int)$active,
            'sync_enabled' => (int)$sync_enabled,
        ];
    }
}
