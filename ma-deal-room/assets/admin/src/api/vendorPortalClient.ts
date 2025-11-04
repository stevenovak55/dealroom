import axios, { AxiosInstance } from 'axios';

/**
 * Vendor Portal API Client
 *
 * Public API client for vendor portal (token-based authentication)
 */

export interface VendorRequest {
  id: number;
  task_id: number;
  transaction_id: number;
  vendor_type: string;
  vendor_name?: string;
  vendor_company?: string;
  vendor_email: string;
  vendor_phone?: string;
  status: 'sent' | 'opened' | 'scheduled' | 'completed' | 'expired' | 'cancelled';
  scheduled_date?: string;
  scheduled_time?: string;
  completion_date?: string;
  completion_notes?: string;
  document_url?: string;
  average_rating?: number;
  token_expires_at: string;
}

export interface Transaction {
  id: number;
  property_address: string;
  property_city: string;
  property_state: string;
  property_zip: string;
  closing_date?: string;
}

export interface VendorMessage {
  id: number;
  vendor_request_id: number;
  sender_type: 'vendor' | 'agent';
  sender_name: string;
  sender_email: string;
  message: string;
  is_read: boolean;
  read_at?: string;
  created_at: string;
}

export interface VendorAvailability {
  id: number;
  vendor_request_id: number;
  available_date: string;
  start_time: string;
  end_time: string;
  timezone: string;
  notes?: string;
}

export interface VendorRating {
  id: number;
  rating: number;
  timeliness_rating?: number;
  quality_rating?: number;
  communication_rating?: number;
  review?: string;
  would_recommend: boolean;
  created_at: string;
}

export interface VendorPortalData {
  vendor_request: VendorRequest;
  transaction: Transaction;
  messages: {
    messages: VendorMessage[];
    total_count: number;
    unread_vendor: number;
    unread_agent: number;
  };
  availability: VendorAvailability[];
  rating?: VendorRating;
}

export interface ScheduleData {
  scheduled_date: string;
  scheduled_time?: string;
  vendor_name?: string;
  vendor_company?: string;
}

export interface AvailabilityWindow {
  available_date: string;
  start_time: string;
  end_time: string;
  timezone?: string;
  notes?: string;
}

export interface CompletionData {
  completion_date?: string;
  completion_notes?: string;
}

export interface MessageData {
  sender_name: string;
  message: string;
}

class VendorPortalAPI {
  private client: AxiosInstance;
  private token: string;

  constructor(token: string, baseURL: string = '/wp-json/ma-deal/v1') {
    this.token = token;
    this.client = axios.create({
      baseURL,
      headers: {
        'Content-Type': 'application/json',
      },
    });
  }

  /**
   * Get complete vendor portal data
   */
  async getPortalData(): Promise<VendorPortalData> {
    const response = await this.client.get<{ data: VendorPortalData }>(`/vendor/${this.token}`);
    return response.data.data;
  }

  /**
   * Schedule an appointment
   */
  async scheduleAppointment(data: ScheduleData): Promise<void> {
    await this.client.post(`/vendor/${this.token}/schedule`, data);
  }

  /**
   * Submit availability windows
   */
  async submitAvailability(windows: AvailabilityWindow[]): Promise<void> {
    await this.client.post(`/vendor/${this.token}/availability`, {
      availability: windows,
    });
  }

  /**
   * Upload a document
   */
  async uploadDocument(file: File): Promise<{ document_url: string }> {
    const formData = new FormData();
    formData.append('file', file);

    const response = await this.client.post<{ data: { document_url: string } }>(
      `/vendor/${this.token}/upload`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    );

    return response.data.data;
  }

  /**
   * Complete the request
   */
  async completeRequest(data: CompletionData): Promise<void> {
    await this.client.post(`/vendor/${this.token}/complete`, data);
  }

  /**
   * Send a message to the agent
   */
  async sendMessage(data: MessageData): Promise<{ message_id: number }> {
    const response = await this.client.post<{ data: { message_id: number } }>(
      `/vendor/${this.token}/message`,
      data
    );
    return response.data.data;
  }

  /**
   * Get all messages
   */
  async getMessages(): Promise<{
    messages: VendorMessage[];
    total_count: number;
    unread_vendor: number;
    unread_agent: number;
  }> {
    const response = await this.client.get<{
      data: {
        messages: VendorMessage[];
        total_count: number;
        unread_vendor: number;
        unread_agent: number;
      };
    }>(`/vendor/${this.token}/messages`);
    return response.data.data;
  }

  /**
   * Mark all messages as read
   */
  async markMessagesAsRead(): Promise<void> {
    await this.client.post(`/vendor/${this.token}/messages/read`);
  }

  /**
   * Download calendar file (ICS)
   */
  getCalendarDownloadUrl(): string {
    return `${this.client.defaults.baseURL}/vendor/${this.token}/calendar`;
  }
}

/**
 * Create a vendor portal API client instance
 */
export function createVendorPortalAPI(token: string): VendorPortalAPI {
  return new VendorPortalAPI(token);
}

export default VendorPortalAPI;
