<?php
/**
 * Contact Sync Service
 *
 * Handles bi-directional synchronization of contacts between Deal Room and CRM systems.
 * Supports field mapping, duplicate detection, and conflict resolution.
 *
 * @package MA_Deal_Room\Services\Integration\CRM
 * @since 1.0.0
 */

namespace MADealRoom\Services\Integration\CRM;

use MADealRoom\Repositories\CRMConfigRepository;

class ContactSyncService {
    private $wpdb;
    private $contacts_table;
    private $config_repository;
    private $factory;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->contacts_table = $wpdb->prefix . 'ma_deal_contacts';
        $this->config_repository = new CRMConfigRepository();
        $this->factory = new CRMClientFactory();
    }

    /**
     * Sync contacts from CRM to Deal Room
     *
     * @param int $account_id Account ID
     * @param string $provider_type CRM provider type
     * @param array $options Sync options (limit, filters, etc.)
     * @return array Sync result ['synced' => int, 'created' => int, 'updated' => int, 'errors' => array]
     */
    public function syncFromCRM(int $account_id, string $provider_type, array $options = []): array {
        $client = $this->factory->createFromAccount($account_id, $provider_type);
        $config = $this->config_repository->getByAccountAndProvider($account_id, $provider_type);

        if (!$config['sync_contacts'] || !$config['sync_enabled']) {
            throw new \Exception('Contact sync is disabled for this CRM configuration');
        }

        $limit = $options['limit'] ?? 100;
        $offset = $options['offset'] ?? 0;
        $filters = $options['filters'] ?? [];

        // Get contacts from CRM
        $crm_contacts = $client->getContacts($filters, $limit, $offset);

        $result = [
            'synced' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        foreach ($crm_contacts as $crm_contact) {
            try {
                $contact_id = $this->mapCRMField($crm_contact, 'id', $provider_type);

                // Check if contact already exists in Deal Room
                $existing = $this->findContactByCrmId($account_id, $provider_type, $contact_id);

                if (!$existing) {
                    // Check for duplicate by email
                    $email = $this->mapCRMField($crm_contact, 'email', $provider_type);
                    if ($email) {
                        $existing = $this->findContactByEmail($account_id, $email);
                    }
                }

                $dealroom_data = $this->mapCRMContactToDealRoom($crm_contact, $provider_type, $config);

                if ($existing) {
                    // Update existing contact
                    if ($this->shouldUpdate($existing, $crm_contact, $config)) {
                        $this->updateContact($existing['id'], $dealroom_data, $provider_type, $contact_id);
                        $result['updated']++;
                    }
                } else {
                    // Create new contact
                    $this->createContact($account_id, $dealroom_data, $provider_type, $contact_id);
                    $result['created']++;
                }

                $result['synced']++;
            } catch (\Exception $e) {
                $result['errors'][] = [
                    'crm_id' => $contact_id ?? 'unknown',
                    'message' => $e->getMessage(),
                ];
                error_log('Contact sync error (CRM -> Deal Room): ' . $e->getMessage());
            }
        }

        // Update last sync timestamp
        $this->config_repository->updateLastSync($config['id']);

        return $result;
    }

    /**
     * Sync contacts from Deal Room to CRM
     *
     * @param int $account_id Account ID
     * @param string $provider_type CRM provider type
     * @param array $options Sync options
     * @return array Sync result
     */
    public function syncToCRM(int $account_id, string $provider_type, array $options = []): array {
        $client = $this->factory->createFromAccount($account_id, $provider_type);
        $config = $this->config_repository->getByAccountAndProvider($account_id, $provider_type);

        if (!$config['sync_contacts'] || !$config['sync_enabled']) {
            throw new \Exception('Contact sync is disabled for this CRM configuration');
        }

        // Get contacts from Deal Room that need syncing
        $contacts = $this->getContactsForSync($account_id, $provider_type, $options);

        $result = [
            'synced' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        foreach ($contacts as $contact) {
            try {
                $crm_data = $this->mapDealRoomContactToCRM($contact, $config);

                if ($contact['crm_id']) {
                    // Update existing CRM contact
                    $client->updateContact($contact['crm_id'], $crm_data);
                    $this->markContactSynced($contact['id'], $provider_type);
                    $result['updated']++;
                } else {
                    // Create new CRM contact
                    $response = $client->createContact($crm_data);
                    $this->updateContactCrmId($contact['id'], $provider_type, $response['id']);
                    $result['created']++;
                }

                $result['synced']++;
            } catch (\Exception $e) {
                $result['errors'][] = [
                    'contact_id' => $contact['id'],
                    'message' => $e->getMessage(),
                ];
                $this->markContactSyncError($contact['id'], $provider_type, $e->getMessage());
                error_log('Contact sync error (Deal Room -> CRM): ' . $e->getMessage());
            }
        }

        // Update last sync timestamp
        $this->config_repository->updateLastSync($config['id']);

        return $result;
    }

    /**
     * Map CRM contact data to Deal Room format
     */
    private function mapCRMContactToDealRoom(array $crm_contact, string $provider_type, array $config): array {
        $field_mapping = $config['field_mapping'] ?? [];

        $data = [];

        // Standard field mappings
        if ($provider_type === 'salesforce') {
            $data['first_name'] = $crm_contact['FirstName'] ?? '';
            $data['last_name'] = $crm_contact['LastName'] ?? '';
            $data['email'] = $crm_contact['Email'] ?? '';
            $data['phone'] = $crm_contact['Phone'] ?? '';
            $data['address'] = $crm_contact['MailingStreet'] ?? '';
            $data['city'] = $crm_contact['MailingCity'] ?? '';
            $data['state'] = $crm_contact['MailingState'] ?? '';
            $data['zip'] = $crm_contact['MailingPostalCode'] ?? '';
        } elseif ($provider_type === 'hubspot') {
            $props = $crm_contact['properties'] ?? $crm_contact;
            $data['first_name'] = $props['firstname'] ?? '';
            $data['last_name'] = $props['lastname'] ?? '';
            $data['email'] = $props['email'] ?? '';
            $data['phone'] = $props['phone'] ?? '';
            $data['address'] = $props['address'] ?? '';
            $data['city'] = $props['city'] ?? '';
            $data['state'] = $props['state'] ?? '';
            $data['zip'] = $props['zip'] ?? '';
        }

        // Apply custom field mapping if configured
        if (!empty($field_mapping['crm_to_dealroom'])) {
            foreach ($field_mapping['crm_to_dealroom'] as $crm_field => $dealroom_field) {
                if (isset($crm_contact[$crm_field])) {
                    $data[$dealroom_field] = $crm_contact[$crm_field];
                }
            }
        }

        return array_filter($data); // Remove empty values
    }

    /**
     * Map Deal Room contact data to CRM format
     */
    private function mapDealRoomContactToCRM(array $contact, array $config): array {
        $field_mapping = $config['field_mapping'] ?? [];

        $data = [
            'first_name' => $contact['first_name'] ?? '',
            'last_name' => $contact['last_name'] ?? '',
            'email' => $contact['email'] ?? '',
            'phone' => $contact['phone'] ?? '',
            'address' => [
                'street' => $contact['address'] ?? '',
                'city' => $contact['city'] ?? '',
                'state' => $contact['state'] ?? '',
                'zip' => $contact['zip'] ?? '',
            ],
        ];

        // Apply custom field mapping if configured
        if (!empty($field_mapping['dealroom_to_crm'])) {
            foreach ($field_mapping['dealroom_to_crm'] as $dealroom_field => $crm_field) {
                if (isset($contact[$dealroom_field])) {
                    $data[$crm_field] = $contact[$dealroom_field];
                }
            }
        }

        return $data;
    }

    /**
     * Find contact by CRM ID
     */
    private function findContactByCrmId(int $account_id, string $provider_type, string $crm_id): ?array {
        $contact = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->contacts_table} WHERE account_id = %d AND crm_provider = %s AND crm_id = %s",
                $account_id,
                $provider_type,
                $crm_id
            ),
            ARRAY_A
        );

        return $contact ?: null;
    }

    /**
     * Find contact by email
     */
    private function findContactByEmail(int $account_id, string $email): ?array {
        $contact = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->contacts_table} WHERE account_id = %d AND email = %s",
                $account_id,
                $email
            ),
            ARRAY_A
        );

        return $contact ?: null;
    }

    /**
     * Create contact in Deal Room
     */
    private function createContact(int $account_id, array $data, string $provider_type, string $crm_id): int {
        $data['account_id'] = $account_id;
        $data['crm_provider'] = $provider_type;
        $data['crm_id'] = $crm_id;
        $data['crm_sync_status'] = 'synced';
        $data['crm_last_sync'] = current_time('mysql');
        $data['created_at'] = current_time('mysql');

        $this->wpdb->insert($this->contacts_table, $data);

        return $this->wpdb->insert_id;
    }

    /**
     * Update contact in Deal Room
     */
    private function updateContact(int $id, array $data, string $provider_type, string $crm_id): bool {
        $data['crm_provider'] = $provider_type;
        $data['crm_id'] = $crm_id;
        $data['crm_sync_status'] = 'synced';
        $data['crm_last_sync'] = current_time('mysql');

        return $this->wpdb->update(
            $this->contacts_table,
            $data,
            ['id' => $id]
        ) !== false;
    }

    /**
     * Get contacts that need syncing to CRM
     */
    private function getContactsForSync(int $account_id, string $provider_type, array $options): array {
        $limit = $options['limit'] ?? 100;

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->contacts_table}
             WHERE account_id = %d
             AND (crm_provider IS NULL OR crm_provider = %s OR crm_sync_status = 'pending' OR crm_sync_status = 'error')
             LIMIT %d",
            $account_id,
            $provider_type,
            $limit
        );

        return $this->wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Mark contact as synced
     */
    private function markContactSynced(int $id, string $provider_type): bool {
        return $this->wpdb->update(
            $this->contacts_table,
            [
                'crm_sync_status' => 'synced',
                'crm_last_sync' => current_time('mysql'),
            ],
            ['id' => $id]
        ) !== false;
    }

    /**
     * Update contact CRM ID
     */
    private function updateContactCrmId(int $id, string $provider_type, string $crm_id): bool {
        return $this->wpdb->update(
            $this->contacts_table,
            [
                'crm_provider' => $provider_type,
                'crm_id' => $crm_id,
                'crm_sync_status' => 'synced',
                'crm_last_sync' => current_time('mysql'),
            ],
            ['id' => $id]
        ) !== false;
    }

    /**
     * Mark contact sync error
     */
    private function markContactSyncError(int $id, string $provider_type, string $error): bool {
        return $this->wpdb->update(
            $this->contacts_table,
            [
                'crm_sync_status' => 'error',
                'crm_sync_error' => $error,
                'crm_last_sync' => current_time('mysql'),
            ],
            ['id' => $id]
        ) !== false;
    }

    /**
     * Determine if contact should be updated based on conflict resolution strategy
     */
    private function shouldUpdate(array $existing, array $crm_contact, array $config): bool {
        $conflict_resolution = $config['conflict_resolution'] ?? 'last_write_wins';

        if ($conflict_resolution === 'crm_wins') {
            return true; // Always update from CRM
        }

        if ($conflict_resolution === 'dealroom_wins') {
            return false; // Never update from CRM
        }

        if ($conflict_resolution === 'last_write_wins') {
            // Compare timestamps if available
            $existing_updated = strtotime($existing['updated_at'] ?? $existing['created_at']);
            $crm_updated = $this->getCRMUpdatedTimestamp($crm_contact);

            return $crm_updated > $existing_updated;
        }

        // Default: update
        return true;
    }

    /**
     * Get CRM updated timestamp
     */
    private function getCRMUpdatedTimestamp(array $crm_contact): int {
        if (isset($crm_contact['LastModifiedDate'])) {
            // Salesforce format
            return strtotime($crm_contact['LastModifiedDate']);
        }

        if (isset($crm_contact['properties']['hs_lastmodifieddate'])) {
            // HubSpot format (milliseconds)
            return intval($crm_contact['properties']['hs_lastmodifieddate']) / 1000;
        }

        return time();
    }

    /**
     * Map CRM field based on provider type
     */
    private function mapCRMField(array $crm_contact, string $field, string $provider_type) {
        $field_map = [
            'salesforce' => [
                'id' => 'Id',
                'email' => 'Email',
                'first_name' => 'FirstName',
                'last_name' => 'LastName',
            ],
            'hubspot' => [
                'id' => 'id',
                'email' => 'properties.email',
                'first_name' => 'properties.firstname',
                'last_name' => 'properties.lastname',
            ],
        ];

        $crm_field = $field_map[$provider_type][$field] ?? $field;

        // Handle nested properties (e.g., 'properties.email')
        if (strpos($crm_field, '.') !== false) {
            $parts = explode('.', $crm_field);
            $value = $crm_contact;
            foreach ($parts as $part) {
                $value = $value[$part] ?? null;
                if ($value === null) break;
            }
            return $value;
        }

        return $crm_contact[$crm_field] ?? null;
    }

    /**
     * Get sync statistics
     *
     * @param int $account_id Account ID
     * @param string $provider_type CRM provider type
     * @return array Statistics
     */
    public function getSyncStats(int $account_id, string $provider_type): array {
        $total = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->contacts_table} WHERE account_id = %d AND crm_provider = %s",
                $account_id,
                $provider_type
            )
        );

        $synced = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->contacts_table} WHERE account_id = %d AND crm_provider = %s AND crm_sync_status = 'synced'",
                $account_id,
                $provider_type
            )
        );

        $pending = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->contacts_table} WHERE account_id = %d AND crm_provider = %s AND crm_sync_status = 'pending'",
                $account_id,
                $provider_type
            )
        );

        $errors = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->contacts_table} WHERE account_id = %d AND crm_provider = %s AND crm_sync_status = 'error'",
                $account_id,
                $provider_type
            )
        );

        $config = $this->config_repository->getByAccountAndProvider($account_id, $provider_type);

        return [
            'total' => (int)$total,
            'synced' => (int)$synced,
            'pending' => (int)$pending,
            'errors' => (int)$errors,
            'last_sync' => $config['last_sync_at'] ?? null,
        ];
    }
}
