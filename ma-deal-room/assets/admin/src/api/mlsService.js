import { apiClient } from './client';
export const mlsService = {
    /**
     * Search MLS listings
     */
    async search(criteria, configId) {
        const response = await apiClient.post('/mls/search', {
            criteria,
            config_id: configId,
        });
        return response.data;
    },
    /**
     * Get property details by MLS number
     */
    async getPropertyDetails(mlsNumber, configId) {
        const response = await apiClient.get(`/mls/property/${mlsNumber}`, {
            params: { config_id: configId },
        });
        return response.data;
    },
    /**
     * Import a single property
     */
    async importProperty(mlsNumber, queuePhotos = true, configId) {
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
    async importBatch(mlsNumbers, queuePhotos = true, configId) {
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
        const response = await apiClient.get('/mls/stats');
        return response.data;
    },
    /**
     * Test MLS connection
     */
    async testConnection(providerType, config) {
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
    async getProviderFields(providerType) {
        const response = await apiClient.get(`/mls/providers/${providerType}/fields`);
        return response.data;
    },
    /**
     * List MLS configurations
     */
    async listConfigs() {
        const response = await apiClient.get('/mls/config');
        return response.data;
    },
    /**
     * Create MLS configuration
     */
    async createConfig(config) {
        const response = await apiClient.post('/mls/config', config);
        return response.data;
    },
    /**
     * Get specific configuration
     */
    async getConfig(configId) {
        const response = await apiClient.get(`/mls/config/${configId}`);
        return response.data;
    },
    /**
     * Update configuration
     */
    async updateConfig(configId, config) {
        const response = await apiClient.put(`/mls/config/${configId}`, config);
        return response.data;
    },
    /**
     * Delete configuration
     */
    async deleteConfig(configId) {
        const response = await apiClient.delete(`/mls/config/${configId}`);
        return response.data;
    },
    /**
     * Submit transaction to MLS
     */
    async submitListing(transactionId, queueSubmission = false, configId) {
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
    async getSubmissionStatus(transactionId) {
        const response = await apiClient.get(`/mls/submission/${transactionId}/status`);
        return response.data;
    },
    /**
     * Sync transaction with MLS
     */
    async syncTransaction(transactionId, configId) {
        const response = await apiClient.post('/mls/sync', {
            transaction_id: transactionId,
            config_id: configId,
        });
        return response.data;
    },
    /**
     * Sync all transactions
     */
    async syncAllTransactions(queueSync = false, configId) {
        const response = await apiClient.post('/mls/sync-all', {
            queue_sync: queueSync,
            config_id: configId,
        });
        return response.data;
    },
    /**
     * Get sync history for a transaction
     */
    async getSyncHistory(transactionId) {
        const response = await apiClient.get(`/mls/sync-history/${transactionId}`);
        return response.data;
    },
    /**
     * Schedule automatic sync
     */
    async scheduleSync(schedule, configId) {
        const response = await apiClient.post('/mls/schedule-sync', {
            schedule,
            config_id: configId,
        });
        return response.data;
    },
    /**
     * Unschedule automatic sync
     */
    async unscheduleSync(configId) {
        const response = await apiClient.post('/mls/unschedule-sync', {
            config_id: configId,
        });
        return response.data;
    },
};
export default mlsService;
