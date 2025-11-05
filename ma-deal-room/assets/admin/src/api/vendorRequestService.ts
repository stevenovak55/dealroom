/**
 * Vendor Request Service (Admin Side)
 *
 * API client for managing vendor requests from the admin interface.
 * This is separate from vendorPortalClient which handles public vendor portal.
 *
 * @package MADealRoom
 * @since 2.0.0
 */

import { apiClient as api } from './client';

/**
 * Vendor Request Type
 */
export interface VendorRequest {
  id: number;
  task_id: number | null;
  transaction_id: number;
  party_id: number | null;
  vendor_type: 'fire_dept' | 'septic_inspector' | 'hoa_manager' | 'title_company' | 'appraiser' | 'inspector' | 'attorney' | 'contractor' | 'other';
  vendor_email: string;
  vendor_phone: string | null;
  vendor_name: string | null;
  vendor_company: string | null;
  average_rating: number | null;
  token: string;
  token_expires_at: string;
  status: 'sent' | 'opened' | 'scheduled' | 'completed' | 'expired' | 'cancelled';
  scheduled_date: string | null;
  scheduled_time: string | null;
  completion_date: string | null;
  completion_notes: string | null;
  document_url: string | null;
  confirmation_sent_at: string | null;
  last_opened_at: string | null;
  metadata: any;
  created_at: string;
  updated_at: string;
}

/**
 * Create Vendor Request Payload
 */
export interface CreateVendorRequestPayload {
  transaction_id: number;
  task_id?: number;
  party_id?: number;
  vendor_type: string;
  vendor_email: string;
  vendor_phone?: string;
  vendor_name?: string;
  vendor_company?: string;
  notes?: string;
}

/**
 * Update Vendor Request Payload
 */
export interface UpdateVendorRequestPayload {
  vendor_email?: string;
  vendor_phone?: string;
  vendor_name?: string;
  vendor_company?: string;
  status?: string;
  scheduled_date?: string;
  scheduled_time?: string;
  completion_notes?: string;
}

/**
 * Vendor Rating Payload
 */
export interface VendorRatingPayload {
  rating: number;
  timeliness_rating?: number;
  quality_rating?: number;
  communication_rating?: number;
  review?: string;
  would_recommend?: boolean;
}

/**
 * List Vendor Requests Response
 */
export interface ListVendorRequestsResponse {
  vendor_requests: VendorRequest[];
  total: number;
  page: number;
  per_page: number;
  pages: number;
}

/**
 * Vendor Request Service
 */
export const vendorRequestService = {
  /**
   * List vendor requests with optional filters
   */
  async list(params?: {
    transaction_id?: number;
    task_id?: number;
    status?: string;
    vendor_type?: string;
    page?: number;
    per_page?: number;
  }): Promise<ListVendorRequestsResponse> {
    const response = await api.get('/vendor-requests', { params });
    // WordPress REST API wraps response in { success, data, message }
    return response.data.data || response.data;
  },

  /**
   * Get a single vendor request by ID
   */
  async get(id: number): Promise<{
    vendor_request: VendorRequest;
    portal_url: string;
  }> {
    const response = await api.get(`/vendor-requests/${id}`);
    // WordPress REST API wraps response in { success, data, message }
    return response.data.data || response.data;
  },

  /**
   * Create a new vendor request
   */
  async create(data: CreateVendorRequestPayload): Promise<{
    vendor_request: VendorRequest;
    portal_url: string;
    email_sent: boolean;
  }> {
    const response = await api.post('/vendor-requests', data);
    // WordPress REST API wraps response in { success, data, message }
    return response.data.data || response.data;
  },

  /**
   * Update a vendor request
   */
  async update(id: number, data: UpdateVendorRequestPayload): Promise<{
    vendor_request: VendorRequest;
  }> {
    const response = await api.put(`/vendor-requests/${id}`, data);
    return response.data;
  },

  /**
   * Delete a vendor request
   */
  async delete(id: number): Promise<{ deleted: boolean }> {
    const response = await api.delete(`/vendor-requests/${id}`);
    return response.data;
  },

  /**
   * Resend invitation email
   */
  async resendInvitation(id: number): Promise<{
    sent: boolean;
    portal_url: string;
  }> {
    const response = await api.post(`/vendor-requests/${id}/resend`);
    return response.data;
  },

  /**
   * Submit vendor rating
   */
  async rate(id: number, rating: VendorRatingPayload): Promise<{
    rating_id: number;
  }> {
    const response = await api.post(`/vendor-requests/${id}/rate`, rating);
    return response.data;
  },

  /**
   * Copy portal URL to clipboard
   */
  copyPortalUrl(vendorRequest: VendorRequest): void {
    const portalUrl = `${window.location.origin}/agent-dashboard/#/vendor-portal?token=${vendorRequest.token}`;

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(portalUrl);
    } else {
      // Fallback for older browsers
      const textarea = document.createElement('textarea');
      textarea.value = portalUrl;
      textarea.style.position = 'fixed';
      textarea.style.opacity = '0';
      document.body.appendChild(textarea);
      textarea.select();
      document.execCommand('copy');
      document.body.removeChild(textarea);
    }
  },

  /**
   * Get vendor type label
   */
  getVendorTypeLabel(type: string): string {
    const labels: Record<string, string> = {
      fire_dept: 'Fire Department',
      septic_inspector: 'Septic Inspector',
      hoa_manager: 'HOA Manager',
      title_company: 'Title Company',
      appraiser: 'Appraiser',
      inspector: 'Home Inspector',
      attorney: 'Attorney',
      contractor: 'Contractor',
      other: 'Other',
    };
    return labels[type] || type;
  },

  /**
   * Get status color
   */
  getStatusColor(status: string): string {
    const colors: Record<string, string> = {
      sent: 'gray',
      opened: 'yellow',
      scheduled: 'blue',
      completed: 'green',
      expired: 'red',
      cancelled: 'red',
    };
    return colors[status] || 'gray';
  },

  /**
   * Get status label
   */
  getStatusLabel(status: string): string {
    const labels: Record<string, string> = {
      sent: 'Sent',
      opened: 'Opened',
      scheduled: 'Scheduled',
      completed: 'Completed',
      expired: 'Expired',
      cancelled: 'Cancelled',
    };
    return labels[status] || status;
  },

  /**
   * Check if token is expired
   */
  isTokenExpired(vendorRequest: VendorRequest): boolean {
    if (!vendorRequest.token_expires_at) {
      return false;
    }
    return new Date(vendorRequest.token_expires_at) < new Date();
  },

  /**
   * Check if request can be deleted
   */
  canDelete(vendorRequest: VendorRequest): boolean {
    return vendorRequest.status !== 'completed';
  },

  /**
   * Check if request can be resent
   */
  canResend(vendorRequest: VendorRequest): boolean {
    return vendorRequest.status === 'sent' || vendorRequest.status === 'opened';
  },

  /**
   * Check if request can be rated
   */
  canRate(vendorRequest: VendorRequest): boolean {
    return vendorRequest.status === 'completed';
  },
};

export default vendorRequestService;
