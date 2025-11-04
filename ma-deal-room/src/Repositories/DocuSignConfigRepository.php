<?php
/**
 * DocuSign Configuration Repository
 *
 * @package    MADealRoom
 * @subpackage Repositories
 * @since      1.0.0
 */

namespace MADealRoom\Repositories;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

/**
 * Class DocuSignConfigRepository
 *
 * Handles CRUD operations for DocuSign configuration with encrypted credential storage
 */
class DocuSignConfigRepository {
    /**
     * Database instance
     *
     * @var \wpdb
     */
    private \wpdb $wpdb;

    /**
     * Table name
     *
     * @var string
     */
    private string $table;

    /**
     * Encryption key
     *
     * @var Key|null
     */
    private ?Key $encryption_key = null;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'ma_deal_docusign_config';

        // Initialize encryption key
        $this->initializeEncryptionKey();
    }

    /**
     * Get configuration by account ID
     *
     * @param int $account_id Account ID
     * @return array|null Configuration or null if not found
     */
    public function getByAccountId(int $account_id): ?array {
        $config = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE account_id = %d",
                $account_id
            ),
            ARRAY_A
        );

        if (!$config) {
            return null;
        }

        // Decrypt sensitive fields
        if (!empty($config['private_key'])) {
            $config['private_key'] = $this->decrypt($config['private_key']);
        }

        return $config;
    }

    /**
     * Get active configuration by account ID
     *
     * @param int $account_id Account ID
     * @return array|null Configuration or null if not found
     */
    public function getActiveByAccountId(int $account_id): ?array {
        $config = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE account_id = %d AND is_active = 1",
                $account_id
            ),
            ARRAY_A
        );

        if (!$config) {
            return null;
        }

        // Decrypt sensitive fields
        if (!empty($config['private_key'])) {
            $config['private_key'] = $this->decrypt($config['private_key']);
        }

        return $config;
    }

    /**
     * Create new configuration
     *
     * @param array $data Configuration data
     * @return int|false Configuration ID or false on failure
     */
    public function create(array $data) {
        // Encrypt sensitive fields
        if (isset($data['private_key'])) {
            $data['private_key'] = $this->encrypt($data['private_key']);
        }

        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');

        $result = $this->wpdb->insert($this->table, $data);

        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Update configuration
     *
     * @param int   $id   Configuration ID
     * @param array $data Configuration data
     * @return bool Success
     */
    public function update(int $id, array $data): bool {
        // Encrypt sensitive fields if present
        if (isset($data['private_key'])) {
            $data['private_key'] = $this->encrypt($data['private_key']);
        }

        $data['updated_at'] = current_time('mysql');

        return $this->wpdb->update(
            $this->table,
            $data,
            ['id' => $id],
            null,
            ['%d']
        ) !== false;
    }

    /**
     * Update by account ID
     *
     * @param int   $account_id Account ID
     * @param array $data       Configuration data
     * @return bool Success
     */
    public function updateByAccountId(int $account_id, array $data): bool {
        // Encrypt sensitive fields if present
        if (isset($data['private_key'])) {
            $data['private_key'] = $this->encrypt($data['private_key']);
        }

        $data['updated_at'] = current_time('mysql');

        return $this->wpdb->update(
            $this->table,
            $data,
            ['account_id' => $account_id],
            null,
            ['%d']
        ) !== false;
    }

    /**
     * Delete configuration
     *
     * @param int $id Configuration ID
     * @return bool Success
     */
    public function delete(int $id): bool {
        return $this->wpdb->delete(
            $this->table,
            ['id' => $id],
            ['%d']
        ) !== false;
    }

    /**
     * Delete by account ID
     *
     * @param int $account_id Account ID
     * @return bool Success
     */
    public function deleteByAccountId(int $account_id): bool {
        return $this->wpdb->delete(
            $this->table,
            ['account_id' => $account_id],
            ['%d']
        ) !== false;
    }

    /**
     * Check if configuration exists for account
     *
     * @param int $account_id Account ID
     * @return bool
     */
    public function existsForAccount(int $account_id): bool {
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE account_id = %d",
                $account_id
            )
        );

        return (int) $count > 0;
    }

    /**
     * Deactivate all configurations for account
     *
     * @param int $account_id Account ID
     * @return bool Success
     */
    public function deactivateAll(int $account_id): bool {
        return $this->wpdb->update(
            $this->table,
            [
                'is_active' => 0,
                'updated_at' => current_time('mysql'),
            ],
            ['account_id' => $account_id],
            ['%d', '%s'],
            ['%d']
        ) !== false;
    }

    /**
     * Initialize encryption key
     *
     * @return void
     */
    private function initializeEncryptionKey(): void {
        $key_string = get_option('ma_deal_encryption_key');

        if (!$key_string) {
            // Generate new key
            $key = Key::createNewRandomKey();
            $key_string = $key->saveToAsciiSafeString();
            update_option('ma_deal_encryption_key', $key_string, false);
            $this->encryption_key = $key;
        } else {
            $this->encryption_key = Key::loadFromAsciiSafeString($key_string);
        }
    }

    /**
     * Encrypt data
     *
     * @param string $data Data to encrypt
     * @return string Encrypted data
     */
    private function encrypt(string $data): string {
        if (!$this->encryption_key) {
            throw new \Exception('Encryption key not initialized');
        }

        return Crypto::encrypt($data, $this->encryption_key);
    }

    /**
     * Decrypt data
     *
     * @param string $encrypted_data Encrypted data
     * @return string Decrypted data
     */
    private function decrypt(string $encrypted_data): string {
        if (!$this->encryption_key) {
            throw new \Exception('Encryption key not initialized');
        }

        try {
            return Crypto::decrypt($encrypted_data, $this->encryption_key);
        } catch (\Exception $e) {
            throw new \Exception('Failed to decrypt data: ' . $e->getMessage(), 0, $e);
        }
    }
}
