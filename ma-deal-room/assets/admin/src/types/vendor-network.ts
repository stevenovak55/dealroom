/**
 * Vendor Network Types
 * @since 2.1.0
 */

export interface VendorProfile {
  id: number;
  email: string;
  name: string;
  company?: string;
  phone?: string;
  website?: string;
  address?: string;
  city?: string;
  state?: string;
  zip?: string;
  license_number?: string;
  license_state?: string;
  insurance_expires?: string;
  bio?: string;
  profile_photo?: string;
  years_experience?: number;
  service_areas?: string[];
  certifications?: Array<{
    name: string;
    issuer?: string;
    expires?: string;
  }>;
  languages?: string[];
  average_rating: number;
  total_reviews: number;
  total_completed: number;
  response_time_hours?: number;
  completion_rate?: number;
  is_verified: boolean;
  verified_at?: string;
  is_active: boolean;
  metadata?: Record<string, any>;
  created_at: string;
  updated_at: string;
  // Computed fields
  display_name?: string;
  location?: string;
  has_insurance?: boolean;
  has_license?: boolean;
  is_preferred?: boolean;
  categories?: VendorCategory[];
  recent_reviews?: VendorReview[];
  stats?: VendorStats;
}

export interface VendorCategory {
  id: number;
  vendor_profile_id: number;
  vendor_type: string;
  is_primary: boolean;
  specialties?: string[];
  typical_turnaround_days?: number;
  price_range_min?: number;
  price_range_max?: number;
  created_at: string;
}

export interface VendorReview {
  id: number;
  vendor_profile_id: number;
  vendor_request_id?: number;
  reviewer_user_id?: number;
  reviewer_name: string;
  rating: number;
  review_text?: string;
  service_type?: string;
  professionalism?: number;
  communication?: number;
  timeliness?: number;
  value?: number;
  would_recommend?: boolean;
  is_verified_transaction: boolean;
  response_from_vendor?: string;
  response_date?: string;
  is_published: boolean;
  created_at: string;
  updated_at: string;
}

export interface VendorStats {
  total_requests: number;
  completed_requests: number;
  completion_rate: number;
  avg_completion_days: number;
  avg_response_hours: number;
  rating_distribution?: Record<string, number>;
  review_breakdown?: {
    professionalism: number;
    communication: number;
    timeliness: number;
    value: number;
    recommendation_rate: number;
  };
}

export interface PreferredVendor extends VendorProfile {
  preference_order: number;
  preferred_notes?: string;
  tags?: string[];
  last_used_at?: string;
  times_used: number;
}

export interface VendorSearchFilters {
  search?: string;
  vendor_type?: string;
  state?: string;
  city?: string;
  zip?: string;
  min_rating?: number;
  verified_only?: boolean;
}

export interface VendorSearchOptions {
  order?: 'rating' | 'reviews' | 'name' | 'recent';
  limit?: number;
  page?: number;
}

export interface CreateReviewPayload {
  vendor_request_id?: number;
  rating: number;
  review_text?: string;
  service_type?: string;
  professionalism?: number;
  communication?: number;
  timeliness?: number;
  value?: number;
  would_recommend?: boolean;
}

export interface ReinviteVendorPayload {
  transaction_id: number;
  task_id?: number;
  vendor_type: string;
  notes?: string;
}

export interface VendorInvitationResult {
  vendor_request: any; // VendorRequest type from existing system
  vendor_profile: VendorProfile;
  portal_url: string;
  email_sent: boolean;
}

export type VendorType =
  | 'inspector'
  | 'appraiser'
  | 'title_company'
  | 'attorney'
  | 'contractor'
  | 'fire_dept'
  | 'septic_inspector'
  | 'hoa_manager'
  | 'photographer'
  | 'stager'
  | 'cleaning_service'
  | 'moving_company'
  | 'surveyor'
  | 'other';

export const VENDOR_TYPE_LABELS: Record<VendorType, string> = {
  inspector: 'Home Inspector',
  appraiser: 'Appraiser',
  title_company: 'Title Company',
  attorney: 'Attorney',
  contractor: 'Contractor',
  fire_dept: 'Fire Department',
  septic_inspector: 'Septic Inspector',
  hoa_manager: 'HOA Manager',
  photographer: 'Photographer',
  stager: 'Stager',
  cleaning_service: 'Cleaning Service',
  moving_company: 'Moving Company',
  surveyor: 'Surveyor',
  other: 'Other',
};