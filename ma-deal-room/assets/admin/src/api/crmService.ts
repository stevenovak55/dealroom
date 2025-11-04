/**
 * CRM Service API Client
 *
 * Handles all CRM-related API calls for Salesforce and HubSpot integration.
 */

import { apiClient } from './client';

export interface CRMProvider {
  name: string;
  auth_type: string;
  supports_api_key: boolean;
}

export interface CRMConfiguration {
  id: number;
  account_id: number;
  provider_type: 'salesforce' | 'hubspot';
  instance_url?: string;
  sync_enabled: boolean;
  sync_contacts: boolean;
  sync_deals: boolean;
  sync_activities: boolean;
  field_mapping?: Record<string, any>;
  stage_mapping?: Record<string, string>;
  sync_direction: 'oneway_to_crm' | 'oneway_from_crm' | 'bidirectional';
  conflict_resolution: 'last_write_wins' | 'crm_wins' | 'dealroom_wins' | 'manual';
  last_sync_at?: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface SyncStatus {
  provider: string;
  sync_enabled: boolean;
  contacts: {
    total: number;
    synced: number;
    pending: number;
    errors: number;
    last_sync: string | null;
  };
  last_sync: string | null;
}

export interface SyncResult {
  success: boolean;
  from_crm?: {
    synced: number;
    created: number;
    updated: number;
    errors: Array<{ crm_id: string; message: string }>;
  };
  to_crm?: {
    synced: number;
    created: number;
    updated: number;
    errors: Array<{ contact_id: number; message: string }>;
  };
}

export interface ConnectionTestResult {
  success: boolean;
  message: string;
  details?: {
    user_id?: string;
    organization_id?: string;
    display_name?: string;
    hub_id?: string;
    hub_domain?: string;
  };
}

/**
 * Get all CRM configurations for the current account
 */
export async function getConfigurations(): Promise<{
  success: boolean;
  configurations: CRMConfiguration[];
  supported_providers: Record<string, CRMProvider>;
}> {
  const response = await apiClient.get('/crm/configurations');
  return response.data;
}

/**
 * Configure CRM connection
 */
export async function configureCRM(config: {
  provider: 'salesforce' | 'hubspot';
  credentials: Record<string, any>;
  instance_url?: string;
  refresh_token?: string;
  sync_enabled?: boolean;
  sync_contacts?: boolean;
  sync_deals?: boolean;
  sync_activities?: boolean;
  field_mapping?: Record<string, any>;
  stage_mapping?: Record<string, string>;
  sync_direction?: string;
  conflict_resolution?: string;
}): Promise<{
  success: boolean;
  message: string;
  config_id: number;
}> {
  const response = await apiClient.post('/crm/configure', config);
  return response.data;
}

/**
 * Test CRM connection
 */
export async function testConnection(provider: 'salesforce' | 'hubspot'): Promise<ConnectionTestResult> {
  const response = await apiClient.post('/crm/test-connection', { provider });
  return response.data;
}

/**
 * Delete CRM configuration
 */
export async function deleteConfiguration(configId: number): Promise<{
  success: boolean;
  message: string;
}> {
  const response = await apiClient.delete(`/crm/configuration/${configId}`);
  return response.data;
}

/**
 * Sync contacts
 */
export async function syncContacts(
  provider: 'salesforce' | 'hubspot',
  direction: 'from_crm' | 'to_crm' | 'bidirectional' = 'bidirectional',
  limit: number = 100
): Promise<SyncResult> {
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
export async function syncDeals(
  provider: 'salesforce' | 'hubspot',
  direction: 'from_crm' | 'to_crm' | 'bidirectional' = 'bidirectional',
  limit: number = 100
): Promise<SyncResult> {
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
export async function getSyncStatus(provider: 'salesforce' | 'hubspot'): Promise<SyncStatus> {
  const response = await apiClient.get(`/crm/sync/status?provider=${provider}`);
  return response.data;
}

/**
 * Sync transaction status change
 */
export async function syncTransactionStatus(
  transactionId: number,
  status: string
): Promise<{
  success: boolean;
  results: Record<string, { success: boolean; message: string }>;
  message: string;
}> {
  const response = await apiClient.post(`/crm/sync/transaction/${transactionId}/status`, {
    status
  });
  return response.data;
}

/**
 * Generate OAuth authorization URL
 */
export function getOAuthUrl(provider: 'salesforce' | 'hubspot', redirectUri: string): string {
  if (provider === 'salesforce') {
    const clientId = process.env.REACT_APP_SALESFORCE_CLIENT_ID || '';
    const params = new URLSearchParams({
      response_type: 'code',
      client_id: clientId,
      redirect_uri: redirectUri,
      state: generateState()
    });
    return `https://login.salesforce.com/services/oauth2/authorize?${params.toString()}`;
  } else if (provider === 'hubspot') {
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
export async function handleOAuthCallback(
  provider: 'salesforce' | 'hubspot',
  code: string,
  redirectUri: string
): Promise<{
  success: boolean;
  message: string;
}> {
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
function generateState(): string {
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
