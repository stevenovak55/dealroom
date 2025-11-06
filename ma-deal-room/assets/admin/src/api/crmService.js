/**
 * CRM Service API Client
 *
 * Handles all CRM-related API calls for Salesforce and HubSpot integration.
 */
import { apiClient } from './client';
/**
 * Get all CRM configurations for the current account
 */
export async function getConfigurations() {
    const response = await apiClient.get('/crm/configurations');
    return response.data;
}
/**
 * Configure CRM connection
 */
export async function configureCRM(config) {
    const response = await apiClient.post('/crm/configure', config);
    return response.data;
}
/**
 * Test CRM connection
 */
export async function testConnection(provider) {
    const response = await apiClient.post('/crm/test-connection', { provider });
    return response.data;
}
/**
 * Delete CRM configuration
 */
export async function deleteConfiguration(configId) {
    const response = await apiClient.delete(`/crm/configuration/${configId}`);
    return response.data;
}
/**
 * Sync contacts
 */
export async function syncContacts(provider, direction = 'bidirectional', limit = 100) {
    const response = await apiClient.post('/crm/sync/contacts', {
        provider,
        direction,
        limit
    });
    return response.data;
}
/**
 * Sync deals
 */
export async function syncDeals(provider, direction = 'bidirectional', limit = 100) {
    const response = await apiClient.post('/crm/sync/deals', {
        provider,
        direction,
        limit
    });
    return response.data;
}
/**
 * Get sync status
 */
export async function getSyncStatus(provider) {
    const response = await apiClient.get(`/crm/sync/status?provider=${provider}`);
    return response.data;
}
/**
 * Sync transaction status change
 */
export async function syncTransactionStatus(transactionId, status) {
    const response = await apiClient.post(`/crm/sync/transaction/${transactionId}/status`, {
        status
    });
    return response.data;
}
/**
 * Generate OAuth authorization URL
 */
export function getOAuthUrl(provider, redirectUri) {
    if (provider === 'salesforce') {
        const clientId = process.env.REACT_APP_SALESFORCE_CLIENT_ID || '';
        const params = new URLSearchParams({
            response_type: 'code',
            client_id: clientId,
            redirect_uri: redirectUri,
            state: generateState()
        });
        return `https://login.salesforce.com/services/oauth2/authorize?${params.toString()}`;
    }
    else if (provider === 'hubspot') {
        const clientId = process.env.REACT_APP_HUBSPOT_CLIENT_ID || '';
        const params = new URLSearchParams({
            client_id: clientId,
            redirect_uri: redirectUri,
            scope: 'crm.objects.contacts.read crm.objects.contacts.write crm.objects.deals.read crm.objects.deals.write timeline'
        });
        return `https://app.hubspot.com/oauth/authorize?${params.toString()}`;
    }
    return '';
}
/**
 * Handle OAuth callback
 */
export async function handleOAuthCallback(provider, code, redirectUri) {
    // Exchange code for tokens by configuring CRM
    const response = await apiClient.post('/crm/configure', {
        provider,
        credentials: {
            code,
            redirect_uri: redirectUri
        }
    });
    return response.data;
}
/**
 * Generate random state for OAuth CSRF protection
 */
function generateState() {
    return Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
}
export const crmService = {
    getConfigurations,
    configureCRM,
    testConnection,
    deleteConfiguration,
    syncContacts,
    syncDeals,
    getSyncStatus,
    syncTransactionStatus,
    getOAuthUrl,
    handleOAuthCallback
};
