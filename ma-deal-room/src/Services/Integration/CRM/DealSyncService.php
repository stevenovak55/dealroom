<?php
/**
 * Deal Sync Service
 *
 * Handles bi-directional synchronization of deals/transactions between Deal Room and CRM systems.
 * Syncs transaction data as Opportunities (Salesforce) or Deals (HubSpot).
 *
 * @package MA_Deal_Room\Services\Integration\CRM
 * @since 1.0.0
 */

namespace MADealRoom\Services\Integration\CRM;

use MADealRoom\Repositories\CRMConfigRepository;

class DealSyncService {
    private $wpdb;
    private $transactions_table;
    private $config_repository;
    private $factory;

    // Default stage mappings
    private $default_stage_mapping = [
        'salesforce' => [
            'pending' => 'Prospecting',
            'active' => 'Qualification',
            'under_contract' => 'Proposal/Price Quote',
            'closing' => 'Negotiation/Review',
            'closed' => 'Closed Won',
            'cancelled' => 'Closed Lost',
        ],
        'hubspot' => [
            'pending' => 'appointmentscheduled',
            'active' => 'qualifiedtobuy',
            'under_contract' => 'presentationscheduled',
            'closing' => 'decisionmakerboughtin',
            'closed' => 'closedwon',
            'cancelled' => 'closedlost',
        ],
    ];

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->transactions_table = $wpdb->prefix . 'ma_deal_transactions';
        $this->config_repository = new CRMConfigRepository();
        $this->factory = new CRMClientFactory();
    }

    /**
     * Sync deals from CRM to Deal Room
     *
     * @param int $account_id Account ID
     * @param string $provider_type CRM provider type
     * @param array $options Sync options
     * @return array Sync result
     */
    public function syncFromCRM(int $account_id, string $provider_type, array $options = []): array {
        $client = $this->factory->createFromAccount($account_id, $provider_type);
        $config = $this->config_repository->getByAccountAndProvider($account_id, $provider_type);

        if (!$config['sync_deals'] || !$config['sync_enabled']) {
            throw new \Exception('Deal sync is disabled for this CRM configuration');
        }

        $limit = $options['limit'] ?? 100;
        $offset = $options['offset'] ?? 0;
        $filters = $options['filters'] ?? [];

        // Get deals from CRM
        $crm_deals = $client->getDeals($filters, $limit, $offset);

        $result = [
            'synced' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        foreach ($crm_deals as $crm_deal) {
            try {
                $deal_id = $this->mapCRMField($crm_deal, 'id', $provider_type);

                // Check if transaction already exists
                $existing = $this->findTransactionByCrmId($account_id, $provider_type, $deal_id);

                $dealroom_data = $this->mapCRMDealToTransaction($crm_deal, $provider_type, $config);

                if ($existing) {
                    // Update existing transaction
                    if ($this->shouldUpdate($existing, $crm_deal, $config)) {
                        $this->updateTransaction($existing['id'], $dealroom_data, $provider_type, $deal_id);
                        $result['updated']++;
                    }
                } else {
                    // Create new transaction
                    $this->createTransaction($account_id, $dealroom_data, $provider_type, $deal_id);
                    $result['created']++;
                }

                $result['synced']++;
            } catch (\Exception $e) {
                $result['errors'][] = [
                    'crm_id' => $deal_id ?? 'unknown',
                    'message' => $e->getMessage(),
                ];
                error_log('Deal sync error (CRM -> Deal Room): ' . $e->getMessage());
            }
        }

        // Update last sync timestamp
        $this->config_repository->updateLastSync($config['id']);

        return $result;
    }

    /**
     * Sync deals from Deal Room to CRM
     *
     * @param int $account_id Account ID
     * @param string $provider_type CRM provider type
     * @param array $options Sync options
     * @return array Sync result
     */
    public function syncToCRM(int $account_id, string $provider_type, array $options = []): array {
        $client = $this->factory->createFromAccount($account_id, $provider_type);
        $config = $this->config_repository->getByAccountAndProvider($account_id, $provider_type);

        if (!$config['sync_deals'] || !$config['sync_enabled']) {
            throw new \Exception('Deal sync is disabled for this CRM configuration');
        }

        // Get transactions that need syncing
        $transactions = $this->getTransactionsForSync($account_id, $provider_type, $options);

        $result = [
            'synced' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        foreach ($transactions as $transaction) {
            try {
                $crm_data = $this->mapTransactionToCRMDeal($transaction, $provider_type, $config);

                $crm_id = $this->getCrmId($transaction, $provider_type);

                if ($crm_id) {
                    // Update existing CRM deal
                    $client->updateDeal($crm_id, $crm_data);
                    $this->markTransactionSynced($transaction['id'], $provider_type);
                    $result['updated']++;
                } else {
                    // Create new CRM deal
                    $response = $client->createDeal($crm_data);
                    $this->updateTransactionCrmId($transaction['id'], $provider_type, $response['id']);
                    $result['created']++;
                }

                $result['synced']++;
            } catch (\Exception $e) {
                $result['errors'][] = [
                    'transaction_id' => $transaction['id'],
                    'message' => $e->getMessage(),
                ];
                $this->markTransactionSyncError($transaction['id'], $provider_type, $e->getMessage());
                error_log('Deal sync error (Deal Room -> CRM): ' . $e->getMessage());
            }
        }

        // Update last sync timestamp
        $this->config_repository->updateLastSync($config['id']);

        return $result;
    }

    /**
     * Sync transaction status change to CRM
     *
     * @param int $transaction_id Transaction ID
     * @param string $new_status New status
     * @return array Result for each configured CRM
     */
    public function syncStatusChange(int $transaction_id, string $new_status): array {
        $transaction = $this->getTransaction($transaction_id);
        if (!$transaction) {
            throw new \Exception('Transaction not found');
        }

        $account_id = $transaction['account_id'];
        $configs = $this->config_repository->getByAccount($account_id);

        $results = [];

        foreach ($configs as $config) {
            if (!$config['sync_deals'] || !$config['sync_enabled']) {
                continue;
            }

            try {
                $provider_type = $config['provider_type'];
                $crm_id = $this->getCrmId($transaction, $provider_type);

                if (!$crm_id) {
                    // Transaction not synced to this CRM yet, skip
                    continue;
                }

                $client = $this->factory->createFromAccount($account_id, $provider_type);
                $stage_mapping = $config['stage_mapping'] ?? $this->default_stage_mapping[$provider_type];
                $crm_stage = $stage_mapping[$new_status] ?? null;

                if (!$crm_stage) {
                    throw new \Exception("No stage mapping found for status: {$new_status}");
                }

                // Update CRM deal stage
                $update_data = ['stage' => $crm_stage];

                // Add close date if closing or closed
                if (in_array($new_status, ['closing', 'closed'])) {
                    $update_data['close_date'] = $transaction['close_date'] ?? date('Y-m-d');
                }

                $client->updateDeal($crm_id, $update_data);

                $results[$provider_type] = [
                    'success' => true,
                    'message' => 'Status synced successfully',
                ];
            } catch (\Exception $e) {
                $results[$provider_type] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
                error_log("CRM status sync error ({$provider_type}): " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Map CRM deal data to Deal Room transaction format
     */
    private function mapCRMDealToTransaction(array $crm_deal, string $provider_type, array $config): array {
        $field_mapping = $config['field_mapping'] ?? [];
        $data = [];

        if ($provider_type === 'salesforce') {
            $data['address'] = $crm_deal['Name'] ?? '';
            $data['property_value'] = $crm_deal['Amount'] ?? 0;
            $data['status'] = $this->mapCRMStageToStatus($crm_deal['StageName'] ?? '', $provider_type, $config);
            $data['close_date'] = $crm_deal['CloseDate'] ?? null;
            $data['description'] = $crm_deal['Description'] ?? '';
        } elseif ($provider_type === 'hubspot') {
            $props = $crm_deal['properties'] ?? $crm_deal;
            $data['address'] = $props['dealname'] ?? '';
            $data['property_value'] = $props['amount'] ?? 0;
            $data['status'] = $this->mapCRMStageToStatus($props['dealstage'] ?? '', $provider_type, $config);

            // HubSpot closedate is in milliseconds
            if (isset($props['closedate'])) {
                $data['close_date'] = date('Y-m-d', intval($props['closedate']) / 1000);
            }
        }

        // Apply custom field mapping if configured
        if (!empty($field_mapping['crm_to_dealroom'])) {
            foreach ($field_mapping['crm_to_dealroom'] as $crm_field => $dealroom_field) {
                if (isset($crm_deal[$crm_field])) {
                    $data[$dealroom_field] = $crm_deal[$crm_field];
                }
            }
        }

        return array_filter($data);
    }

    /**
     * Map Deal Room transaction to CRM deal format
     */
    private function mapTransactionToCRMDeal(array $transaction, string $provider_type, array $config): array {
        $field_mapping = $config['field_mapping'] ?? [];
        $stage_mapping = $config['stage_mapping'] ?? $this->default_stage_mapping[$provider_type];

        $data = [
            'name' => $transaction['address'] ?? "Deal #{$transaction['id']}",
            'amount' => (float)($transaction['property_value'] ?? 0),
            'stage' => $stage_mapping[$transaction['status']] ?? $stage_mapping['pending'],
            'close_date' => $transaction['close_date'] ?? date('Y-m-d', strtotime('+30 days')),
            'description' => $transaction['notes'] ?? '',
        ];

        // Apply custom field mapping if configured
        if (!empty($field_mapping['dealroom_to_crm'])) {
            foreach ($field_mapping['dealroom_to_crm'] as $dealroom_field => $crm_field) {
                if (isset($transaction[$dealroom_field])) {
                    $data[$crm_field] = $transaction[$dealroom_field];
                }
            }
        }

        return $data;
    }

    /**
     * Map CRM stage to Deal Room status
     */
    private function mapCRMStageToStatus(string $crm_stage, string $provider_type, array $config): string {
        $stage_mapping = $config['stage_mapping'] ?? $this->default_stage_mapping[$provider_type];

        // Reverse the mapping
        $reverse_mapping = array_flip($stage_mapping);

        return $reverse_mapping[$crm_stage] ?? 'pending';
    }

    /**
     * Find transaction by CRM ID
     */
    private function findTransactionByCrmId(int $account_id, string $provider_type, string $crm_id): ?array {
        $field = $provider_type === 'salesforce' ? 'crm_opportunity_id' : 'crm_deal_id';

        $transaction = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->transactions_table} WHERE account_id = %d AND crm_provider = %s AND {$field} = %s",
                $account_id,
                $provider_type,
                $crm_id
            ),
            ARRAY_A
        );

        return $transaction ?: null;
    }

    /**
     * Get transaction by ID
     */
    private function getTransaction(int $id): ?array {
        $transaction = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->transactions_table} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        return $transaction ?: null;
    }

    /**
     * Create transaction in Deal Room
     */
    private function createTransaction(int $account_id, array $data, string $provider_type, string $crm_id): int {
        $data['account_id'] = $account_id;
        $data['crm_provider'] = $provider_type;

        if ($provider_type === 'salesforce') {
            $data['crm_opportunity_id'] = $crm_id;
        } else {
            $data['crm_deal_id'] = $crm_id;
        }

        $data['crm_sync_status'] = 'synced';
        $data['crm_last_sync'] = current_time('mysql');
        $data['created_at'] = current_time('mysql');

        $this->wpdb->insert($this->transactions_table, $data);

        return $this->wpdb->insert_id;
    }

    /**
     * Update transaction in Deal Room
     */
    private function updateTransaction(int $id, array $data, string $provider_type, string $crm_id): bool {
        $data['crm_provider'] = $provider_type;

        if ($provider_type === 'salesforce') {
            $data['crm_opportunity_id'] = $crm_id;
        } else {
            $data['crm_deal_id'] = $crm_id;
        }

        $data['crm_sync_status'] = 'synced';
        $data['crm_last_sync'] = current_time('mysql');

        return $this->wpdb->update(
            $this->transactions_table,
            $data,
            ['id' => $id]
        ) !== false;
    }

    /**
     * Get transactions that need syncing to CRM
     */
    private function getTransactionsForSync(int $account_id, string $provider_type, array $options): array {
        $limit = $options['limit'] ?? 100;

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->transactions_table}
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
     * Mark transaction as synced
     */
    private function markTransactionSynced(int $id, string $provider_type): bool {
        return $this->wpdb->update(
            $this->transactions_table,
            [
                'crm_sync_status' => 'synced',
                'crm_last_sync' => current_time('mysql'),
            ],
            ['id' => $id]
        ) !== false;
    }

    /**
     * Update transaction CRM ID
     */
    private function updateTransactionCrmId(int $id, string $provider_type, string $crm_id): bool {
        $data = [
            'crm_provider' => $provider_type,
            'crm_sync_status' => 'synced',
            'crm_last_sync' => current_time('mysql'),
        ];

        if ($provider_type === 'salesforce') {
            $data['crm_opportunity_id'] = $crm_id;
        } else {
            $data['crm_deal_id'] = $crm_id;
        }

        return $this->wpdb->update(
            $this->transactions_table,
            $data,
            ['id' => $id]
        ) !== false;
    }

    /**
     * Mark transaction sync error
     */
    private function markTransactionSyncError(int $id, string $provider_type, string $error): bool {
        return $this->wpdb->update(
            $this->transactions_table,
            ['crm_sync_status' => 'error'],
            ['id' => $id]
        ) !== false;
    }

    /**
     * Get CRM ID from transaction
     */
    private function getCrmId(array $transaction, string $provider_type): ?string {
        if ($provider_type === 'salesforce') {
            return $transaction['crm_opportunity_id'] ?? null;
        } else {
            return $transaction['crm_deal_id'] ?? null;
        }
    }

    /**
     * Determine if transaction should be updated
     */
    private function shouldUpdate(array $existing, array $crm_deal, array $config): bool {
        $conflict_resolution = $config['conflict_resolution'] ?? 'last_write_wins';

        if ($conflict_resolution === 'crm_wins') {
            return true;
        }

        if ($conflict_resolution === 'dealroom_wins') {
            return false;
        }

        if ($conflict_resolution === 'last_write_wins') {
            $existing_updated = strtotime($existing['updated_at'] ?? $existing['created_at']);
            $crm_updated = $this->getCRMUpdatedTimestamp($crm_deal);
            return $crm_updated > $existing_updated;
        }

        return true;
    }

    /**
     * Get CRM updated timestamp
     */
    private function getCRMUpdatedTimestamp(array $crm_deal): int {
        if (isset($crm_deal['LastModifiedDate'])) {
            return strtotime($crm_deal['LastModifiedDate']);
        }

        if (isset($crm_deal['properties']['hs_lastmodifieddate'])) {
            return intval($crm_deal['properties']['hs_lastmodifieddate']) / 1000;
        }

        return time();
    }

    /**
     * Map CRM field based on provider type
     */
    private function mapCRMField(array $crm_deal, string $field, string $provider_type) {
        $field_map = [
            'salesforce' => [
                'id' => 'Id',
                'name' => 'Name',
                'amount' => 'Amount',
                'stage' => 'StageName',
            ],
            'hubspot' => [
                'id' => 'id',
                'name' => 'properties.dealname',
                'amount' => 'properties.amount',
                'stage' => 'properties.dealstage',
            ],
        ];

        $crm_field = $field_map[$provider_type][$field] ?? $field;

        // Handle nested properties
        if (strpos($crm_field, '.') !== false) {
            $parts = explode('.', $crm_field);
            $value = $crm_deal;
            foreach ($parts as $part) {
                $value = $value[$part] ?? null;
                if ($value === null) break;
            }
            return $value;
        }

        return $crm_deal[$crm_field] ?? null;
    }

    /**
     * Get sync statistics
     */
    public function getSyncStats(int $account_id, string $provider_type): array {
        $total = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->transactions_table} WHERE account_id = %d AND crm_provider = %s",
                $account_id,
                $provider_type
            )
        );

        $synced = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->transactions_table} WHERE account_id = %d AND crm_provider = %s AND crm_sync_status = 'synced'",
                $account_id,
                $provider_type
            )
        );

        $config = $this->config_repository->getByAccountAndProvider($account_id, $provider_type);

        return [
            'total' => (int)$total,
            'synced' => (int)$synced,
            'last_sync' => $config['last_sync_at'] ?? null,
        ];
    }
}
