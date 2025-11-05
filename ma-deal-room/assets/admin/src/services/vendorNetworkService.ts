import { apiClient as api } from '../api/client';
import {
  VendorProfile,
  VendorSearchFilters,
  VendorSearchOptions,
  CreateReviewPayload,
  ReinviteVendorPayload,
  VendorInvitationResult,
  VendorReview,
  VendorStats,
  PreferredVendor,
} from '@/types/vendor-network';

/**
 * Vendor Network API Service
 * @since 2.1.0
 */
class VendorNetworkService {
  private baseUrl = '/vendor-network';

  /**
   * Search vendors with filters
   */
  async searchVendors(
    filters: VendorSearchFilters = {},
    options: VendorSearchOptions = {}
  ): Promise<{ vendors: VendorProfile[]; total: number }> {
    const params = new URLSearchParams();

    // Add filters
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null) {
        params.append(key, String(value));
      }
    });

    // Add options
    if (options.order) params.append('order', options.order);
    if (options.limit) params.append('per_page', String(options.limit));
    if (options.page) params.append('page', String(options.page));

    const response = await api.get(`${this.baseUrl}/search?${params.toString()}`);
    return response.data.data || response.data;
  }

  /**
   * Get single vendor profile
   */
  async getVendorProfile(vendorId: number): Promise<VendorProfile> {
    const response = await api.get(`${this.baseUrl}/${vendorId}`);
    const data = response.data.data || response.data;
    return data.vendor;
  }

  /**
   * Get preferred vendors for current user
   */
  async getPreferredVendors(vendorType?: string): Promise<PreferredVendor[]> {
    const params = vendorType ? `?vendor_type=${vendorType}` : '';
    const response = await api.get(`${this.baseUrl}/preferred${params}`);
    const data = response.data.data || response.data;
    return data.vendors;
  }

  /**
   * Add vendor to preferred list
   */
  async addToPreferred(
    vendorId: number,
    data: {
      vendor_type?: string;
      notes?: string;
      tags?: string[];
    } = {}
  ): Promise<void> {
    await api.post(`${this.baseUrl}/${vendorId}/prefer`, data);
  }

  /**
   * Remove vendor from preferred list
   */
  async removeFromPreferred(vendorId: number): Promise<void> {
    await api.delete(`${this.baseUrl}/${vendorId}/prefer`);
  }

  /**
   * Quick re-invite vendor for new request
   */
  async reinviteVendor(
    vendorId: number,
    data: ReinviteVendorPayload
  ): Promise<VendorInvitationResult> {
    const response = await api.post(`${this.baseUrl}/${vendorId}/reinvite`, data);
    return response.data.data || response.data;
  }

  /**
   * Get vendor reviews
   */
  async getVendorReviews(
    vendorId: number,
    options: { page?: number; per_page?: number } = {}
  ): Promise<{ reviews: VendorReview[]; total: number }> {
    const params = new URLSearchParams();
    if (options.page) params.append('page', String(options.page));
    if (options.per_page) params.append('per_page', String(options.per_page));

    const response = await api.get(`${this.baseUrl}/${vendorId}/reviews?${params.toString()}`);
    return response.data.data || response.data;
  }

  /**
   * Create vendor review
   */
  async createReview(vendorId: number, data: CreateReviewPayload): Promise<void> {
    await api.post(`${this.baseUrl}/${vendorId}/reviews`, data);
  }

  /**
   * Get vendor statistics
   */
  async getVendorStats(vendorId: number): Promise<VendorStats> {
    const response = await api.get(`${this.baseUrl}/${vendorId}/stats`);
    const data = response.data.data || response.data;
    return data.stats;
  }

  /**
   * Import existing vendors (admin only)
   */
  async importVendors(): Promise<{
    imported: number;
    skipped: number;
    total: number;
  }> {
    const response = await api.post(`${this.baseUrl}/import`);
    return response.data.data || response.data;
  }

  /**
   * Create a new vendor
   */
  async createVendor(data: any): Promise<{ vendor_id: number; message: string }> {
    const response = await api.post(`${this.baseUrl}/create`, data);
    return response.data.data || response.data;
  }
}

export const vendorNetworkService = new VendorNetworkService();