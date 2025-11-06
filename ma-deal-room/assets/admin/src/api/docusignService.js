import { apiClient } from './client';
class DocuSignService {
    /**
     * Get DocuSign configuration
     */
    async getConfig() {
        const response = await apiClient.get('/docusign/config');
        return response.data;
    }
    /**
     * Save DocuSign configuration
     */
    async saveConfig(config) {
        const response = await apiClient.post('/docusign/config', config);
        return response.data;
    }
    /**
     * Test DocuSign connection
     */
    async testConnection() {
        const response = await apiClient.post('/docusign/test-connection');
        return response.data;
    }
    /**
     * List DocuSign templates
     */
    async listTemplates(search) {
        const params = search ? { search } : {};
        const response = await apiClient.get('/docusign/templates', { params });
        return response.data;
    }
    /**
     * Get template details
     */
    async getTemplate(templateId) {
        const response = await apiClient.get(`/docusign/templates/${templateId}`);
        return response.data;
    }
    /**
     * Preview template with transaction data
     */
    async previewTemplate(templateId, transactionId) {
        const response = await apiClient.post('/docusign/templates/preview', {
            template_id: templateId,
            transaction_id: transactionId,
        });
        return response.data;
    }
    /**
     * Create envelope from template
     */
    async createEnvelope(data) {
        const response = await apiClient.post('/docusign/envelopes', data);
        return response.data;
    }
    /**
     * Get envelope details
     */
    async getEnvelope(envelopeId) {
        const response = await apiClient.get(`/docusign/envelopes/${envelopeId}`);
        return response.data;
    }
    /**
     * Send envelope for signing
     */
    async sendEnvelope(envelopeId) {
        const response = await apiClient.post(`/docusign/envelopes/${envelopeId}/send`);
        return response.data;
    }
    /**
     * Void envelope
     */
    async voidEnvelope(envelopeId, reason) {
        const response = await apiClient.post(`/docusign/envelopes/${envelopeId}/void`, { reason });
        return response.data;
    }
    /**
     * Resend envelope
     */
    async resendEnvelope(envelopeId) {
        const response = await apiClient.post(`/docusign/envelopes/${envelopeId}/resend`);
        return response.data;
    }
    /**
     * Get envelope recipients
     */
    async getEnvelopeRecipients(envelopeId) {
        const response = await apiClient.get(`/docusign/envelopes/${envelopeId}/recipients`);
        return response.data;
    }
    /**
     * List envelopes
     */
    async listEnvelopes(transactionId) {
        const params = transactionId ? { transaction_id: transactionId } : {};
        const response = await apiClient.get('/docusign/envelopes', { params });
        return response.data;
    }
    /**
     * Get envelope statistics
     */
    async getEnvelopeStats() {
        const response = await apiClient.get('/docusign/envelopes/stats');
        return response.data;
    }
}
export const docusignService = new DocuSignService();
