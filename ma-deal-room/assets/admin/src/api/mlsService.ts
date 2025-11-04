import { apiClient } from './client';

export interface MLSSearchCriteria {
  mls_number?: string;
  address?: string;
  city?: string;
  zip_code?: string;
  min_price?: number;
  max_price?: number;
  status?: string;
}

export interface MLSListing {
  mls_number: string;
  address: string;
  city?: string;
  state?: string;
  zip_code?: string;
  price?: number;
  bedrooms?: number;
  bathrooms?: number;
  square_feet?: number;
  lot_size?: number;
  year_built?: number;
  property_type?: string;
  status?: string;
  listing_date?: string;
  photos?: string[];
  is_imported?: boolean;
  existing_transaction_id?: number;
}

export interface MLSConfig {
  id: number;
  account_id: number;
  provider_type: string;
  name: string;
  credentials: Record<string, any>;
  settings?: Record<string, any>;
  is_active: boolean;
  last_sync_at?: string | null;
  created_at: string;
  updated_at: string;
}

export interface MLSStats {
  total_imported: number;
  by_status: Record<string, number>;
  by_type: Record<string, number>;
  last_import: string | null;
}

export const mlsService = {
  /**
   * Search MLS listings
   */
  async search(criteria: MLSSearchCriteria, configId?: number) {
    const response = await apiClient.post('/mls/search', {
      criteria,
      config_id: configId,
    });
    return response.data;
  },

  /**
   * Get property details by MLS number
   */
  async getPropertyDetails(mlsNumber: string, configId?: number) {
    const response = await apiClient.get(`/mls/property/${mlsNumber}`, {
      params: { config_id: configId },
    });
    return response.data;
  },

  /**
   * Import a single property
   */
  async importProperty(mlsNumber: string, queuePhotos: boolean = true, configId?: number) {
    const response = await apiClient.post('/mls/import', {
      mls_number: mlsNumber,
      queue_photos: queuePhotos,
      config_id: configId,
    });
    return response.data;
  },

  /**
   * Batch import multiple properties
   */
  async importBatch(mlsNumbers: string[], queuePhotos: boolean = true, configId?: number) {
    const response = await apiClient.post('/mls/import-batch', {
      mls_numbers: mlsNumbers,
      queue_photos: queuePhotos,
      config_id: configId,
    });
    return response.data;
  },

  /**
   * Get import statistics
   */
  async getStats() {
    const response = await apiClient.get<{ data: MLSStats }>('/mls/stats');
    return response.data;
  },

  /**
   * Test MLS connection
   */
  async testConnection(providerType: string, config: Record<string, any>) {
    const response = await apiClient.post('/mls/test-connection', {
      provider_type: providerType,
      config,
    });
    return response.data;
  },

  /**
   * Get supported MLS providers
   */
  async getProviders() {
    const response = await apiClient.get('/mls/providers');
    return response.data;
  },

  /**
   * Get required fields for a provider
   */
  async getProviderFields(providerType: string) {
    const response = await apiClient.get(`/mls/providers/${providerType}/fields`);
    return response.data;
  },

  /**
   * List MLS configurations
   */
  async listConfigs() {
    const response = await apiClient.get<{ data: MLSConfig[] }>('/mls/config');
    return response.data;
  },

  /**
   * Create MLS configuration
   */
  async createConfig(config: Partial<MLSConfig>) {
    const response = await apiClient.post('/mls/config', config);
    return response.data;
  },

  /**
   * Get specific configuration
   */
  async getConfig(configId: number) {
    const response = await apiClient.get<{ data: MLSConfig }>(`/mls/config/${configId}`);
    return response.data;
  },

  /**
   * Update configuration
   */
  async updateConfig(configId: number, config: Partial<MLSConfig>) {
    const response = await apiClient.put(`/mls/config/${configId}`, config);
    return response.data;
  },

  /**
   * Delete configuration
   */
  async deleteConfig(configId: number) {
    const response = await apiClient.delete(`/mls/config/${configId}`);
    return response.data;
  },

  /**
   * Submit transaction to MLS
   */
  async submitListing(transactionId: number, queueSubmission: boolean = false, configId?: number) {
    const response = await apiClient.post('/mls/submit', {
      transaction_id: transactionId,
      queue_submission: queueSubmission,
      config_id: configId,
    });
    return response.data;
  },

  /**
   * Get submission status
   */
  async getSubmissionStatus(transactionId: number) {
    const response = await apiClient.get(`/mls/submission/${transactionId}/status`);
    return response.data;
  },

  /**
   * Sync transaction with MLS
   */
  async syncTransaction(transactionId: number, configId?: number) {
    const response = await apiClient.post('/mls/sync', {
      transaction_id: transactionId,
      config_id: configId,
    });
    return response.data;
  },

  /**
   * Sync all transactions
   */
  async syncAllTransactions(queueSync: boolean = false, configId?: number) {
    const response = await apiClient.post('/mls/sync-all', {
      queue_sync: queueSync,
      config_id: configId,
    });
    return response.data;
  },

  /**
   * Get sync history for a transaction
   */
  async getSyncHistory(transactionId: number) {
    const response = await apiClient.get(`/mls/sync-history/${transactionId}`);
    return response.data;
  },

  /**
   * Schedule automatic sync
   */
  async scheduleSync(schedule: string, configId?: number) {
    const response = await apiClient.post('/mls/schedule-sync', {
      schedule,
      config_id: configId,
    });
    return response.data;
  },

  /**
   * Unschedule automatic sync
   */
  async unscheduleSync(configId?: number) {
    const response = await apiClient.post('/mls/unschedule-sync', {
      config_id: configId,
    });
    return response.data;
  },
};

export default mlsService;
