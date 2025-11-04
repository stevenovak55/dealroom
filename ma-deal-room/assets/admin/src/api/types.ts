// TypeScript types for MA Deal Room API

// Task phases representing workflow stages
export type TaskPhase =
  | 'pre_listing'           // Before listing contract signed
  | 'post_listing'          // After listing contract signed
  | 'pre_agreement'         // Before P&S signed
  | 'post_agreement'        // After P&S signed
  | 'pre_closing'           // Before closing
  | 'post_closing'          // After closing
  | 'any';                  // Applies to all phases

// Transaction types
export type TransactionType =
  | 'buy_side'              // Buyer representation
  | 'sell_side'             // Seller representation
  | 'rental'                // Rental transactions
  | 'commercial'            // Commercial transactions
  | 'dual_agency'           // Dual agency
  | 'all';                  // Applies to all types

export const TASK_PHASES: Record<TaskPhase, { label: string; description: string }> = {
  pre_listing: { label: 'Pre-Listing', description: 'Before listing contract is signed' },
  post_listing: { label: 'Post-Listing', description: 'After listing contract signed' },
  pre_agreement: { label: 'Pre-Agreement', description: 'Before P&S is signed' },
  post_agreement: { label: 'Post-Agreement', description: 'After P&S signed' },
  pre_closing: { label: 'Pre-Closing', description: 'Before closing date' },
  post_closing: { label: 'Post-Closing', description: 'After closing' },
  any: { label: 'Any Phase', description: 'Applies to all phases' },
};

export const TRANSACTION_TYPES: Record<TransactionType, { label: string; description: string }> = {
  buy_side: { label: 'Buy Side', description: 'Buyer representation' },
  sell_side: { label: 'Sell Side', description: 'Seller representation' },
  rental: { label: 'Rental', description: 'Rental transactions' },
  commercial: { label: 'Commercial', description: 'Commercial transactions' },
  dual_agency: { label: 'Dual Agency', description: 'Dual agency representation' },
  all: { label: 'All Types', description: 'Applies to all transaction types' },
};

export interface Transaction {
  transaction_id: number;
  account_id: number;
  property_address: string;
  property_city: string;
  property_state: string;
  property_zip: string;
  property_type: 'SFH' | 'Condo' | 'Multifamily' | 'Land' | 'Commercial';
  property_year_built?: number;
  property_metadata?: Record<string, any>;
  sale_price?: number;
  list_price?: number;
  accepted_offer_price?: number;
  bedrooms?: number;
  bathrooms?: number;
  square_feet?: number;
  lot_size?: number;
  parking_spaces?: number;
  transaction_side: 'listing' | 'buyer';
  status: 'prospect' | 'listing_active' | 'under_agreement' | 'closed' | 'cancelled';
  listing_date?: string;
  offer_accepted_date?: string;
  ps_agreement_date?: string;
  loan_commitment_date?: string;
  closing_date?: string;
  actual_closing_date?: string;
  assigned_agent_id?: number;
  template_id?: number;
  notes?: string;
  created_at: string;
  updated_at: string;
  task_summary?: {
    total: number;
    completed: number;
    pending: number;
    overdue: number;
  };
  parties?: Party[];
  tasks?: Task[];
}

export interface Task {
  id: number;
  transaction_id: number;
  template_id?: number;
  task_definition_id?: number;
  task_key: string;
  title: string;
  description?: string;
  status: 'pending' | 'in_progress' | 'completed' | 'cancelled' | 'blocked' | 'skipped';
  owner_role: 'agent' | 'seller' | 'buyer' | 'seller_attorney' | 'buyer_attorney' | 'vendor' | 'system';
  assigned_party_id?: number;
  due_at?: string;
  completed_at?: string;
  depends_on_task_ids?: number[];
  applies_if_condition?: string;
  metadata?: Record<string, any>;
  sort_order: number;
  created_at: string;
  updated_at: string;
}

export interface TaskDefinition {
  id: number;
  task_key: string;
  category: string;
  title: string;
  description?: string;
  owner_role: 'agent' | 'seller' | 'buyer' | 'seller_attorney' | 'buyer_attorney' | 'vendor' | 'system';
  priority: 'low' | 'normal' | 'high' | 'urgent';
  estimated_duration?: number;
  due_calculation?: string;
  applies_if?: string;
  depends_on: string[];
  phase?: TaskPhase;
  applicable_transaction_types?: TransactionType[];
  metadata: Record<string, any>;
  is_system: boolean;
  is_milestone: boolean;
  is_required: boolean;
  account_id?: number;
  created_at?: string;
  updated_at?: string;
}

export interface TaskCategory {
  id: number;
  category_key: string;
  name: string;
  description?: string;
  icon?: string;
  color?: string;
  sort_order: number;
}

export interface TemplateTask {
  id: number;
  template_id: number;
  task_definition_id: number;
  sort_order: number;
  override_due_calculation?: string;
  override_owner_role?: string;
  override_applies_if?: string;
  is_optional: boolean;
  task_definition?: TaskDefinition;
}

export interface TaskDefinitionGrouped {
  category_key: string;
  category_name: string;
  description?: string;
  icon?: string;
  color?: string;
  tasks: TaskDefinition[];
}

export interface Template {
  template_id: number;
  account_id?: number;
  name: string;
  description?: string;
  property_type: 'SFH' | 'Condo' | 'Multifamily' | 'Land' | 'Commercial' | 'Any';
  transaction_type?: TransactionType;
  template_yaml: string;
  is_system: boolean;
  is_active: boolean;
  version: number;
  task_count?: number;
  created_by_user_id?: number;
  created_at: string;
  updated_at: string;
  // Enterprise fields
  tags?: string[];
  category?: string;
  usage_count?: number;
  last_used_at?: string;
  parent_template_id?: number; // For template inheritance
  is_draft?: boolean;
  approved_by_user_id?: number;
  approved_at?: string;
}

export interface TemplateVersion {
  version_id: number;
  template_id: number;
  version: number;
  template_yaml: string;
  name: string;
  description?: string;
  created_by_user_id: number;
  created_by_name?: string;
  created_at: string;
  change_notes?: string;
}

export interface TemplateValidationError {
  field: string;
  message: string;
  severity: 'error' | 'warning' | 'info';
  line?: number;
}

export interface TemplateAnalytics {
  template_id: number;
  total_usage: number;
  active_transactions: number;
  avg_completion_time_days: number;
  completion_rate: number;
  most_delayed_tasks: Array<{
    task_key: string;
    title: string;
    avg_delay_days: number;
  }>;
  phase_breakdown: Record<TaskPhase, {
    task_count: number;
    avg_duration_days: number;
    completion_rate: number;
  }>;
}

export interface Party {
  id: number;
  transaction_id: number;
  role: 'buyer' | 'seller' | 'buyer_attorney' | 'seller_attorney' | 'buyer_lender' |
        'buyer_agent' | 'seller_agent' | 'title_company' | 'inspector' | 'appraiser' |
        'hoa_manager' | 'septic_inspector' | 'fire_dept' | 'other';
  company_name?: string;
  contact_name: string;
  email?: string;
  phone?: string;
  address?: string;
  metadata?: Record<string, any>;
  created_at: string;
  updated_at: string;
}

export interface Reminder {
  reminder_id: number;
  task_id: number;
  transaction_id: number;
  recipient_type: 'agent' | 'seller' | 'buyer' | 'attorney' | 'party';
  recipient_email?: string;
  recipient_phone?: string;
  channel: 'email' | 'sms' | 'both';
  scheduled_at: string;
  sent_at?: string;
  status: 'pending' | 'sent' | 'failed' | 'cancelled';
  failure_reason?: string;
  retry_count: number;
  max_retries: number;
  message_template?: string;
  metadata?: Record<string, any>;
  task_title?: string;
  created_at: string;
  updated_at: string;
}

export interface Event {
  event_id: number;
  account_id: number;
  transaction_id?: number;
  entity_type: 'transaction' | 'task' | 'party' | 'template' | 'reminder' | 'vendor_request';
  entity_id: number;
  event_type: 'created' | 'updated' | 'deleted' | 'status_changed' | 'completed' |
               'reminded' | 'escalated' | 'assigned';
  user_id?: number;
  old_data?: Record<string, any>;
  new_data?: Record<string, any>;
  ip_address?: string;
  user_agent?: string;
  created_at: string;
  payload?: Record<string, any>;
}

export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
}

export interface ApiError {
  success: false;
  error: {
    code: string;
    message: string;
    data?: any;
  };
}

export interface PaginatedResponse<T> {
  data: T[];
  pagination: {
    total: number;
    page: number;
    per_page: number;
    total_pages: number;
  };
}

export interface CreateTransactionInput {
  property_address: string;
  property_city: string;
  property_state: string;
  property_zip: string;
  property_type: 'SFH' | 'Condo' | 'Multifamily' | 'Land' | 'Commercial';
  property_metadata?: Record<string, any>;
  mls_number?: string;
  template_id?: string;
  listing_date?: string;
  offer_accepted_date?: string;
  ps_agreement_date?: string;
  loan_commitment_date?: string;
  closing_date?: string;
  sale_price?: number;
  transaction_side?: 'listing' | 'buyer';
}

export interface UpdateTransactionInput {
  property_address?: string;
  property_city?: string;
  property_state?: string;
  property_zip?: string;
  property_type?: 'SFH' | 'Condo' | 'Multifamily' | 'Land' | 'Commercial';
  property_metadata?: Record<string, any>;
  listing_date?: string;
  offer_accepted_date?: string;
  ps_agreement_date?: string;
  loan_commitment_date?: string;
  closing_date?: string;
  sale_price?: number;
  status?: 'prospect' | 'listing_active' | 'under_agreement' | 'closed' | 'cancelled';
  notes?: string;
}

export interface CompleteTaskInput {
  notes?: string;
  completed_by_user_id?: number;
}

export interface SkipTaskInput {
  reason: string;
}

export interface CreatePartyInput {
  role: Party['role'];
  contact_name: string;
  company_name?: string;
  email?: string;
  phone?: string;
  address?: string;
}

export interface UpdatePartyInput {
  role?: Party['role'];
  contact_name?: string;
  company_name?: string;
  email?: string;
  phone?: string;
  address?: string;
}

export interface CreateTaskInput {
  transaction_id: number;
  task_key: string;
  title: string;
  description?: string;
  owner_role: Task['owner_role'];
  status?: Task['status'];
  due_at?: string;
  sort_order?: number;
}

export interface UpdateTaskInput {
  title?: string;
  description?: string;
  status?: Task['status'];
  owner_role?: Task['owner_role'];
  assigned_party_id?: number;
  due_at?: string;
  sort_order?: number;
}

export interface ReassignTaskInput {
  owner_role?: Task['owner_role'];
  assigned_party_id?: number;
}

export interface CreateTaskDefinitionInput {
  task_key: string;
  category: string;
  title: string;
  description?: string;
  owner_role: TaskDefinition['owner_role'];
  priority?: TaskDefinition['priority'];
  estimated_duration?: number;
  due_calculation?: string;
  applies_if?: string;
  depends_on?: string[];
  metadata?: Record<string, any>;
  is_milestone?: boolean;
  is_required?: boolean;
}

export interface UpdateTaskDefinitionInput {
  title?: string;
  description?: string;
  owner_role?: TaskDefinition['owner_role'];
  priority?: TaskDefinition['priority'];
  estimated_duration?: number;
  due_calculation?: string;
  applies_if?: string;
  depends_on?: string[];
  metadata?: Record<string, any>;
  is_milestone?: boolean;
  is_required?: boolean;
}

export interface Document {
  id: number;
  transaction_id: number;
  account_id: number;
  uploaded_by_user_id: number;
  file_name: string;
  file_path: string;
  file_size: number;
  mime_type: string;
  document_type?: string;
  title?: string;
  description?: string;
  metadata?: Record<string, any>;
  is_public: boolean;
  public_token?: string;
  created_at: string;
  updated_at: string;
  download_url?: string;
  formatted_size?: string;
}

export interface UploadDocumentInput {
  transaction_id: number;
  file: File;
  document_type?: string;
  title?: string;
  description?: string;
}

export interface UpdateDocumentInput {
  title?: string;
  description?: string;
  document_type?: string;
  is_public?: boolean;
}

export interface Notification {
  id: number;
  user_id: number;
  type: 'task_assigned' | 'task_updated' | 'document_uploaded' | 'transaction_updated' | 'party_added';
  title: string;
  message: string;
  link?: string;
  entity_type?: string;
  entity_id?: number;
  is_read: boolean;
  read_at?: string;
  created_at: string;
}
