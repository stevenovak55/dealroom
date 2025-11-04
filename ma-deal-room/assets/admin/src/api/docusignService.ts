import { apiClient } from './client';

export interface DocuSignConfig {
  id?: number;
  account_id?: number;
  integration_key: string;
  user_id: string;
  private_key?: string;
  account_id_docusign: string;
  environment: 'sandbox' | 'production';
  is_active: boolean;
  created_at?: string;
  updated_at?: string;
}

export interface DocuSignTemplate {
  templateId: string;
  name: string;
  description?: string;
  created?: string;
  lastModified?: string;
  shared?: boolean;
  owner?: {
    name?: string;
    email?: string;
  };
}

export interface DocuSignRecipient {
  email: string;
  name: string;
  role: 'signer' | 'cc' | 'carbon_copy' | 'certified_delivery';
  routing_order?: number;
}

export interface DocuSignEnvelope {
  id?: number;
  envelope_id: string;
  transaction_id: number;
  status: 'created' | 'sent' | 'delivered' | 'signed' | 'completed' | 'declined' | 'voided';
  subject: string;
  message?: string;
  recipients: DocuSignRecipient[];
  documents: any[];
  sent_at?: string;
  completed_at?: string;
  voided_at?: string;
  voided_reason?: string;
  created_at: string;
  updated_at: string;
}

export interface DocuSignEnvelopeStats {
  stats: Record<string, number>;
  total: number;
}

export interface DocuSignConnectionTestResult {
  success: boolean;
  message: string;
  account_info?: any;
}

class DocuSignService {
  /**
   * Get DocuSign configuration
   */
  async getConfig(): Promise<{ configured: boolean; config?: DocuSignConfig }> {
    const response = await apiClient.get('/docusign/config');
    return response.data;
  }

  /**
   * Save DocuSign configuration
   */
  async saveConfig(config: Partial<DocuSignConfig>): Promise<{ success: boolean; message: string }> {
    const response = await apiClient.post('/docusign/config', config);
    return response.data;
  }

  /**
   * Test DocuSign connection
   */
  async testConnection(): Promise<DocuSignConnectionTestResult> {
    const response = await apiClient.post('/docusign/test-connection');
    return response.data;
  }

  /**
   * List DocuSign templates
   */
  async listTemplates(search?: string): Promise<{ templates: DocuSignTemplate[]; count: number }> {
    const params = search ? { search } : {};
    const response = await apiClient.get('/docusign/templates', { params });
    return response.data;
  }

  /**
   * Get template details
   */
  async getTemplate(templateId: string): Promise<DocuSignTemplate> {
    const response = await apiClient.get(`/docusign/templates/${templateId}`);
    return response.data;
  }

  /**
   * Preview template with transaction data
   */
  async previewTemplate(templateId: string, transactionId: number): Promise<any> {
    const response = await apiClient.post('/docusign/templates/preview', {
      template_id: templateId,
      transaction_id: transactionId,
    });
    return response.data;
  }

  /**
   * Create envelope from template
   */
  async createEnvelope(data: {
    transaction_id: number;
    template_id: string;
    recipients: DocuSignRecipient[];
    subject?: string;
    message?: string;
  }): Promise<any> {
    const response = await apiClient.post('/docusign/envelopes', data);
    return response.data;
  }

  /**
   * Get envelope details
   */
  async getEnvelope(envelopeId: string): Promise<{ envelope: DocuSignEnvelope; docusign_status: any }> {
    const response = await apiClient.get(`/docusign/envelopes/${envelopeId}`);
    return response.data;
  }

  /**
   * Send envelope for signing
   */
  async sendEnvelope(envelopeId: string): Promise<any> {
    const response = await apiClient.post(`/docusign/envelopes/${envelopeId}/send`);
    return response.data;
  }

  /**
   * Void envelope
   */
  async voidEnvelope(envelopeId: string, reason: string): Promise<any> {
    const response = await apiClient.post(`/docusign/envelopes/${envelopeId}/void`, { reason });
    return response.data;
  }

  /**
   * Resend envelope
   */
  async resendEnvelope(envelopeId: string): Promise<any> {
    const response = await apiClient.post(`/docusign/envelopes/${envelopeId}/resend`);
    return response.data;
  }

  /**
   * Get envelope recipients
   */
  async getEnvelopeRecipients(envelopeId: string): Promise<any> {
    const response = await apiClient.get(`/docusign/envelopes/${envelopeId}/recipients`);
    return response.data;
  }

  /**
   * List envelopes
   */
  async listEnvelopes(transactionId?: number): Promise<{ envelopes: DocuSignEnvelope[]; count: number }> {
    const params = transactionId ? { transaction_id: transactionId } : {};
    const response = await apiClient.get('/docusign/envelopes', { params });
    return response.data;
  }

  /**
   * Get envelope statistics
   */
  async getEnvelopeStats(): Promise<DocuSignEnvelopeStats> {
    const response = await apiClient.get('/docusign/envelopes/stats');
    return response.data;
  }
}

export const docusignService = new DocuSignService();
