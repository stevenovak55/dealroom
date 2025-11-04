# MA Deal Room WordPress Plugin - Comprehensive Codebase Analysis

**Analysis Date**: October 31, 2025
**Plugin Version**: 1.0.0
**Status**: Production-Ready MVP with Ongoing Enhancement
**Overall Code Size**: ~9,200+ PHP lines, ~100+ React TypeScript files

---

## EXECUTIVE SUMMARY

MA Deal Room is a sophisticated, enterprise-grade WordPress plugin designed specifically for Massachusetts real estate agents and brokerages. It automates complex transaction workflows by encoding state-specific regulatory requirements and best practices into YAML-based task templates. The plugin manages the entire lifecycle of property transactions—from listing through closing—with 200+ property-type-specific tasks, deadline tracking, team coordination, and vendor integration.

**Core Innovation**: Dynamic task instantiation from YAML templates with conditional logic, dependency chains, and milestone-based date calculations tied to transaction events (listing, offer acceptance, purchase agreement, loan commitment, closing).

---

# 1. CORE FEATURES & FUNCTIONALITY

## 1.1 User-Facing Features

### Transaction Management
- **Create Transactions**: Web form to capture property details (address, type, price, dates)
- **Multi-Property Types**: Support for SFH (septic/city water), Condos, Multifamily, Commercial, Land
- **Transaction Sides**: Listing agent vs. buyer agent workflows (different task sets)
- **Status Tracking**: prospect → listing_active → under_agreement → closed/cancelled
- **Key Dates**: Listing date, offer accepted, P&S agreement, loan commitment, closing

### Automated Task Management
- **Task Templates**: 5 pre-built YAML templates (base + 4 property variants)
- **Task Generation**: Auto-create 125-150 tasks per transaction from templates
- **Conditional Tasks**: Tasks that only apply based on property attributes (e.g., "if has_septic")
- **Task Dependencies**: Block task completion until prerequisites done
- **Due Date Calculation**: Relative to transaction milestones (e.g., "21 days before closing")
- **Task Status Flow**: pending → in_progress → completed/skipped/blocked/cancelled
- **Task Assignment**: Assign to agents, buyers, sellers, attorneys, vendors

### Reminder & Notification System
- **Automatic Reminders**: Email notifications for upcoming task deadlines
- **Customizable Schedules**: Set reminder offset (7 days before, 1 day before, etc.)
- **Bulk Processing**: Process 50-1000 reminders in background queue
- **Delivery Tracking**: Log which reminders were sent
- **Multi-Channel Ready**: Email built-in, SMS/Slack ready for future integration

### Vendor Portal
- **Public Portal**: External link for vendors to submit documents
- **Signed URLs**: Time-limited, secure access without WordPress login
- **Document Collection**: Vendors upload inspection reports, title documents, etc.
- **Status Updates**: Vendors mark tasks as complete

### Party Management
- **Transaction Contacts**: Add buyers, sellers, attorneys, lenders, inspectors, title companies
- **Role-Based**: 14 role types (buyer, seller, seller_attorney, appraiser, HOA manager, etc.)
- **Contact Details**: Email, phone, address, company info
- **Metadata**: Bar numbers, license numbers, website URLs for professionals

### Admin Features (React Dashboard)
- **Dashboard**: Overview of active transactions, upcoming deadlines, task summaries
- **Transaction List**: Filterable, sortable, with bulk actions
- **Task Library**: Browse all pre-defined tasks across templates
- **Template Manager**: View system templates, create custom templates
- **Settings Page**: Configure account preferences, notification settings
- **Search**: Global search across transactions, parties, documents
- **Reports**: Transaction analytics, task completion metrics, timeline views

## 1.2 Backend/Admin Features

### Property-Type-Specific Task Templates
Templates encode Massachusetts regulatory requirements:

**Base Template (125+ tasks)**:
- Deal setup (create transaction, notify parties)
- Title/ownership verification (title search, lead paint disclosure)
- Financing tasks (loan application, appraisal, commitment)
- Inspections (general, pest, septic, condo 6(d), homeowners association)
- Compliance tasks (smoke/CO certification, radon disclosure)
- Closing preparation (final walkthrough, closing costs, wire transfer)
- Post-closing (record deed, transfer utilities, update records)

**SFH Septic Template** (26 tasks):
- Title 5 septic inspection (MA-specific requirement)
- Septic tank pumping/records
- Well water testing
- Lead paint disclosure (pre-1978 homes)

**SFH City Water Template** (24 tasks):
- Water quality testing
- Sewer capability verification
- Lead paint compliance

**Condo Template** (28 tasks):
- Condo 6(d) certificate (condominium association review)
- HOA document review
- Assessment review
- House rules compliance

**Multifamily Template** (31 tasks):
- Rent control verification
- Tenant lease review
- Habitability inspection

**Rental Templates** (15 tasks each):
- Landlord vs. tenant workflows
- Security deposit handling (tracked in DB)
- Lease verification

### Modular Task System
- **Task Library**: 200+ reusable task definitions
- **Categories**: 14 task categories (financing, title, inspections, compliance, etc.)
- **Metadata**: Priority levels, estimated duration, citations/references
- **System vs. Custom**: System tasks vs. account-specific custom tasks

---

# 2. ARCHITECTURE & CODE ORGANIZATION

## 2.1 Directory Structure

```
ma-deal-room/
├── ma-deal-room.php                 # Plugin bootstrap (350+ lines)
├── composer.json                     # Dependencies (Symfony YAML)
├── README.md                         # User documentation
├── uninstall.php                     # Cleanup on deactivation
│
├── src/
│   ├── Core/
│   │   ├── Plugin.php               # Singleton plugin class (500+ lines)
│   │   ├── ServiceContainer.php     # Dependency injection container
│   │   └── Hooks.php                # WordPress hook registration
│   │
│   ├── Database/
│   │   └── Migrator.php             # Migration runner (400+ lines)
│   │
│   ├── Models/ (14 entity classes)
│   │   ├── Account.php              # Multi-tenant accounts
│   │   ├── Transaction.php          # Property transactions (110 lines)
│   │   ├── Task.php                 # Task instances (80 lines)
│   │   ├── Template.php             # YAML templates (95 lines)
│   │   ├── Party.php                # Transaction contacts
│   │   ├── TaskDefinition.php       # Parsed tasks with metadata
│   │   ├── TemplateTask.php         # Template-task associations
│   │   ├── Reminder.php             # Reminder queue records
│   │   ├── Document.php             # File attachments
│   │   ├── Notification.php         # Delivery logs
│   │   ├── Event.php                # Audit log entries
│   │   ├── PropertyAttributes.php   # Extended property data
│   │   ├── SecurityDeposit.php      # Rental deposit tracking
│   │   └── VendorRequest.php        # Vendor task records
│   │
│   ├── Repositories/ (12 repositories)
│   │   ├── BaseRepository.php       # Abstract base with CRUD (360 lines)
│   │   ├── TransactionRepository.php
│   │   ├── TaskRepository.php
│   │   ├── TemplateRepository.php
│   │   ├── TaskDefinitionRepository.php
│   │   ├── PartyRepository.php
│   │   ├── ReminderRepository.php
│   │   ├── EventRepository.php
│   │   ├── DocumentRepository.php
│   │   ├── NotificationRepository.php
│   │   ├── TemplateTaskRepository.php
│   │   ├── VendorRequestRepository.php
│   │   └── AccountRepository.php
│   │
│   ├── Services/ (9 business logic services)
│   │   ├── TemplateEngine.php       # YAML parsing + task instantiation (450+ lines)
│   │   ├── TaskScheduler.php        # Due date calculation (150 lines)
│   │   ├── ReminderService.php      # Reminder queue processing (250 lines)
│   │   ├── NotificationService.php  # Email/SMS abstraction (120 lines)
│   │   ├── EmailService.php         # Email template rendering (500+ lines)
│   │   ├── MATimelineCalculator.php # MA-specific date math (300+ lines)
│   │   ├── TaskAssignmentService.php # Task distribution to parties (200+ lines)
│   │   ├── VendorService.php        # Vendor portal logic (150 lines)
│   │   └── RateLimiter.php          # Request throttling (250 lines)
│   │
│   ├── REST/Controllers/ (12 controllers)
│   │   ├── BaseController.php       # Abstract base with auth (400+ lines)
│   │   ├── TransactionController.php # 8 transaction endpoints (500+ lines)
│   │   ├── TaskController.php       # 6 task endpoints (400+ lines)
│   │   ├── TemplateController.php   # 6 template endpoints (300+ lines)
│   │   ├── TaskDefinitionController.php
│   │   ├── ReminderController.php
│   │   ├── DocumentController.php
│   │   ├── NotificationController.php
│   │   ├── VendorPortalController.php
│   │   ├── SettingsController.php
│   │   ├── SearchController.php
│   │   └── UserProfileController.php
│   │
│   ├── Admin/
│   │   └── AdminPages.php           # WordPress admin menu structure
│   │
│   ├── Frontend/
│   │   └── AgentDashboard.php       # Front-end page template
│   │
│   └── CLI/ (7 WP-CLI commands)
│       ├── MigrateCommand.php       # Database migrations
│       ├── RemindersCommand.php     # Send reminder queue
│       ├── TemplatesCommand.php     # Sync system templates
│       ├── QueueCommand.php         # Process background jobs
│       ├── SeedCommand.php          # Load sample data
│       ├── TaskDefinitionsCommand.php
│       └── RateLimitCommand.php
│
├── database/
│   ├── migrations/
│   │   ├── 001_initial_schema.sql       # 14 tables, 35+ indexes (1000+ lines)
│   │   ├── 002_create_documents_table.sql
│   │   ├── 003_create_notifications_table.sql
│   │   ├── 006_create_modular_task_system.sql
│   │   ├── 007_fix_task_due_calculations.sql
│   │   ├── 008_add_loan_commitment_date.sql
│   │   ├── 009_add_template_transaction_side.sql
│   │   ├── 010_add_property_details_fields.sql
│   │   └── rollback_*.sql
│   └── README.md                    # Schema documentation
│
├── assets/
│   ├── admin/                       # React SPA admin UI
│   │   ├── src/
│   │   │   ├── api/
│   │   │   │   ├── client.ts        # Axios HTTP client (95 lines)
│   │   │   │   ├── types.ts         # TypeScript interfaces (250+ lines)
│   │   │   │   └── queries/         # React Query hooks (13 files)
│   │   │   │       ├── useTransactions.ts
│   │   │   │       ├── useTasks.ts
│   │   │   │       ├── useTemplates.ts
│   │   │   │       ├── useParties.ts
│   │   │   │       ├── useReminders.ts
│   │   │   │       ├── useDocuments.ts
│   │   │   │       ├── useNotifications.ts
│   │   │   │       ├── useSearch.ts
│   │   │   │       ├── useSettings.ts
│   │   │   │       ├── useTaskCategories.ts
│   │   │   │       ├── useTaskDefinitions.ts
│   │   │   │       ├── useUserProfile.ts
│   │   │   │       └── useEvents.ts
│   │   │   │
│   │   │   ├── pages/               # Page components (13 pages)
│   │   │   │   ├── Dashboard/
│   │   │   │   ├── Transactions/
│   │   │   │   ├── Tasks/
│   │   │   │   ├── Templates/
│   │   │   │   ├── TaskLibrary/
│   │   │   │   ├── TemplateBuilder/
│   │   │   │   ├── Settings/
│   │   │   │   ├── Documents/
│   │   │   │   ├── Timeline/
│   │   │   │   ├── Reminders/
│   │   │   │   ├── UserProfile/
│   │   │   │   └── Parties/
│   │   │   │
│   │   │   ├── components/          # Reusable components (30+ files)
│   │   │   │   ├── Layout/
│   │   │   │   ├── Tasks/
│   │   │   │   ├── Transactions/
│   │   │   │   ├── Timeline/
│   │   │   │   ├── Documents/
│   │   │   │   ├── Search/
│   │   │   │   ├── Notifications/
│   │   │   │   ├── shared/
│   │   │   │   └── [Enterprise features]
│   │   │   │
│   │   │   ├── hooks/               # Custom React hooks
│   │   │   │   ├── useBulkSelection.ts
│   │   │   │   └── useKeyboardShortcuts.ts
│   │   │   │
│   │   │   ├── routes/
│   │   │   │   └── AppRoutes.tsx     # React Router configuration
│   │   │   │
│   │   │   ├── styles/
│   │   │   │   └── globals.css      # Tailwind CSS
│   │   │   │
│   │   │   └── utils/               # Utilities
│   │   │       ├── formatDate.ts
│   │   │       ├── formatCurrency.ts
│   │   │       ├── timelineCalculator.ts
│   │   │       ├── localStorage.ts
│   │   │       ├── toast.tsx
│   │   │       └── export.ts
│   │   │
│   │   ├── package.json             # Dependencies (React 18, React Query, Tailwind)
│   │   ├── vite.config.ts           # Build configuration
│   │   ├── tailwind.config.js
│   │   ├── tsconfig.json
│   │   └── dist/                    # Built assets
│   │
│   ├── public/
│   │   └── agent-dashboard.php      # Front-end page template
│   │
│   ├── templates/
│   │   └── [YAML template files]
│   │
│   └── emails/                      # Email templates [if present]
│
├── templates/                       # YAML task templates
│   ├── base_transaction.yaml        # 125+ universal tasks (1000+ lines)
│   ├── sfh_septic.yaml             # 26 SFH septic-specific (400+ lines)
│   ├── sfh_city_water.yaml         # 24 SFH city water-specific
│   ├── condo.yaml                  # 28 condo-specific
│   ├── multifamily.yaml            # 31 multifamily-specific
│   ├── rental_landlord.yaml        # 15 rental landlord tasks
│   ├── rental_tenant.yaml          # 15 rental tenant tasks
│   └── [backup files and variants]
│
├── vendor/                          # Composer dependencies
│   └── symfony/yaml/               # YAML parsing library
│
└── [Configuration files]
    ├── .gitignore
    └── README.md
```

## 2.2 Design Patterns Implemented

### Singleton Pattern
```php
// Plugin.php - Controls plugin lifecycle
class Plugin {
    private static $instance = null;
    public static function instance(): Plugin { ... }
}

// Access anywhere: Plugin::instance()->container()->get('service_name')
```

### Service Container / Dependency Injection
```php
// ServiceContainer.php - IoC container
$container->register('transaction_repository', function() {
    return new TransactionRepository();
});

// Controllers receive dependencies via constructor
public function __construct(
    TransactionRepository $repo,
    TemplateEngine $engine
) { ... }
```

### Repository Pattern
```php
// BaseRepository - Abstract data access layer
// Child classes (TransactionRepository, TaskRepository, etc.)
// encapsulate database operations, SQL injection prevention
// 
// Usage: $repository->find($id), query(), create(), update(), delete()
```

### Template Method Pattern
```php
// BaseController - Abstract REST controller
// Child classes override specific methods: get_items(), create_item(), etc.
// Common auth, nonce verification, error handling in base
```

### Factory Pattern
```php
// Services and repositories created in Plugin::register_services()
// ServiceContainer acts as factory, lazy-loads on first access
```

### Observer Pattern
```php
// WordPress hooks throughout
add_action('plugins_loaded', 'ma_deal_room_init');
add_filter('wp_mail_from', $filter_func);
do_action('ma_deal_task_created', $task);
```

### Facade Pattern
```php
// TemplateEngine provides simplified interface to YAML parsing + task instantiation
// Clients don't need to know about Symfony\Yaml or conditional logic details
```

---

# 3. TECHNOLOGY STACK

## 3.1 Backend

**Language**: PHP 8.0+
- Strict type hints on all properties and methods
- Union types, named arguments
- Property initialization shorthand

**WordPress Integration**:
- Custom tables with `wp_` prefix
- WordPress REST API for endpoints
- WP-CLI for background processing
- WordPress hooks (actions/filters) for extensibility
- WordPress authentication/capabilities

**Dependencies**:
- **Symfony YAML**: Professional-grade YAML parsing (composer: symfony/yaml ^6.0)
- No other external PHP dependencies (minimal footprint)

**Database**: MySQL 5.7+ / MariaDB 10.2+
- Custom tables (14 tables, 35+ indexes)
- InnoDB engine (ACID transactions)
- Prepared statements (SQL injection prevention)
- Foreign key constraints (referential integrity)

**Minimum Requirements**:
- PHP 8.0+
- WordPress 6.0+
- MySQL 5.7+ or MariaDB 10.2+

## 3.2 Frontend

**Framework**: React 18.3.1
- Functional components with hooks
- Context API (implied in patterns)

**State Management**: 
- React Query (TanStack Query) 5.56.2 - Server state management
- Zustand 5.0.0 - Client state management
- React Hook Form 7.53.0 - Form state

**Routing**: React Router DOM 6.27.0

**HTTP Client**: Axios 1.7.7
- Interceptors for auth/error handling
- WordPress nonce integration

**UI Framework**: Tailwind CSS 3.4.13
- Utility-first CSS
- Built with PostCSS 8.4.47, Autoprefixer 10.4.20

**Component Library**: Lucide React 0.447.0
- SVG icon library

**Charts**: Recharts 2.12.7 (for dashboard analytics)

**Date Handling**: date-fns 4.1.0

**Validation**: Zod 3.23.8 (TypeScript schema validation)

**Notifications**: react-hot-toast 2.6.0

**Utilities**: 
- clsx 2.1.1 - Conditional CSS classes
- tailwind-merge 2.5.3 - Merge Tailwind classes

**Build Tools**:
- Vite 5.4.8 (fast bundler)
- TypeScript 5.6.2
- ESLint 8.57.0
- Prettier 3.3.3

**Development**:
- 270+ node_modules packages (as of last build)
- npm 10.2.3+

## 3.3 Database Schema

**14 Custom Tables**:

```
wp_ma_deal_migrations          # Migration tracking
wp_ma_deal_accounts            # Multi-tenant accounts
wp_ma_deal_transactions        # Property transactions
wp_ma_deal_parties             # Transaction contacts
wp_ma_deal_property_attributes # Extended property data
wp_ma_deal_templates           # YAML templates
wp_ma_deal_tasks               # Task instances
wp_ma_deal_task_definitions    # Parsed task metadata
wp_ma_deal_template_tasks      # Template-task links
wp_ma_deal_reminders           # Reminder queue
wp_ma_deal_events              # Audit log
wp_ma_deal_documents           # File attachments
wp_ma_deal_notifications       # Delivery tracking
wp_ma_deal_vendor_requests     # Vendor tasks
wp_ma_deal_security_deposits   # Rental deposits
```

**Index Strategy**:
- Composite indexes on high-query columns (account_id, status, created_at)
- Foreign key indexes on relationship columns
- Covering indexes for common queries

**Constraints**:
- Foreign key cascading deletes (account deletion removes transactions, tasks, etc.)
- Unique indexes on business-logic columns (migration tracking)
- NOT NULL constraints on required fields

---

# 4. KEY COMPONENTS ANALYSIS

## 4.1 Models (Entity Classes, 14 Total)

### Transaction Model (110 lines)
**Properties**:
- `id`, `account_id`, `assigned_agent_id` (IDs)
- `property_address`, `property_city`, `property_state`, `property_zip` (Location)
- `property_type` (enum: SFH, Condo, Multifamily, Land, Commercial)
- `property_year_built`, `property_metadata` (JSON)
- `sale_price`
- `transaction_side` (enum: listing, buyer)
- `transaction_type` (enum: buy_side, sell_side, rental_landlord, rental_tenant, commercial_buy, commercial_sell)
- `status` (enum: prospect, listing_active, under_agreement, closed, cancelled)
- `listing_date`, `offer_accepted_date`, `ps_agreement_date`, `loan_commitment_date`, `closing_date`, `actual_closing_date`
- `template_id`, `notes`, `created_at`, `updated_at`

**Key Methods**:
- `toArray()` - Serialize for API response
- `fromArray()` - Deserialize from database
- `getValidTransactionTypes()` - Static helper

**Design Notes**:
- PHP 8 typed properties throughout
- JSON field support for `property_metadata`
- Date fields as strings (MySQL DATE format)
- Business logic helpers for transaction type checking

### Task Model (80 lines)
**Properties**:
- `id`, `transaction_id`, `template_id`
- `task_key` (unique identifier from template)
- `title`, `description`
- `status` (enum: pending, in_progress, completed, cancelled, blocked, skipped)
- `owner_role` (enum: agent, seller, buyer, seller_attorney, buyer_attorney, vendor, system)
- `assigned_party_id` (FK to parties)
- `due_at`, `completed_at` (DateTime)
- `depends_on_task_ids`, `applies_if_condition`, `metadata` (JSON fields)
- `sort_order`, `created_at`, `updated_at`

**Key Feature**:
- JSON array field for dependency chains
- Conditional application logic stored as string for audit trail

### Template Model (95 lines)
**Properties**:
- `id`, `account_id` (NULL for system templates)
- `name`, `description`
- `property_type`, `transaction_side`
- `template_yaml` (LONGTEXT - full YAML content)
- `is_system`, `is_active`, `version`
- `created_by_user_id`, `created_at`, `updated_at`

**Key Feature**:
- `getTaskCount()` - Parses YAML on-the-fly to count tasks (regex fallback for malformed YAML)
- Distinguishes system vs. custom templates

### TaskDefinition Model (200+ lines)
**Properties**:
- `id`, `account_id` (NULL for system)
- `task_key`, `title`, `description`
- `category` (14 categories)
- `owner_role`, `priority` (low/normal/high/urgent)
- `estimated_duration`, `due_calculation`, `applies_if`
- `depends_on` (JSON array of task keys)
- `metadata` (JSON with phase, citation, citation_text, notes, vendor_type, etc.)
- `is_system`, `is_milestone`, `is_required`
- `created_at`, `updated_at`

**Purpose**: Parsed representation of tasks from YAML for database queries/searches

### Party Model (80 lines)
**Properties**:
- `id`, `transaction_id`
- `role` (14 enums: buyer, seller, buyer_attorney, seller_attorney, buyer_lender, buyer_agent, seller_agent, title_company, inspector, appraiser, hoa_manager, septic_inspector, fire_dept, other)
- `company_name`, `contact_name`, `email`, `phone`, `address`
- `metadata` (JSON with website, bar_number, license_number, etc.)
- `created_at`, `updated_at`

### Document Model (100 lines)
**Properties**:
- `id`, `transaction_id`, `party_id`
- `document_type` (14 types: title_report, appraisal, inspection_report, septic_report, etc.)
- `file_path`, `file_name`, `file_size`, `mime_type`
- `upload_method` (api, vendor_portal, email)
- `uploaded_by_user_id`
- `created_at`, `updated_at`

### Event Model (Audit Log, 80 lines)
**Properties**:
- `id`, `account_id`, `transaction_id`
- `entity_type` (transaction, task, party, template, reminder, vendor_request)
- `entity_id`, `event_type` (created, updated, deleted, status_changed, completed, reminded, escalated, assigned)
- `user_id`, `old_data`, `new_data` (JSON)
- `ip_address`, `user_agent`
- `created_at`

### Other Models
- **Account**: Brokerage account with subscription tier
- **Reminder**: Reminder queue entry with delivery status
- **Notification**: Email/SMS delivery log
- **VendorRequest**: Vendor task with signed URL
- **TemplateTask**: Links templates to task definitions
- **PropertyAttributes**: Extended property metadata (septic, pool, basement, garage, etc.)
- **SecurityDeposit**: Rental security deposit tracking

---

## 4.2 Repositories (Data Access Layer, 12 Total)

### BaseRepository (360 lines)
**Purpose**: Abstract base class with common CRUD operations

**Key Methods**:
- `find($id)` - Get single record by ID
- `findAll($limit, $offset)` - Get paginated records
- `create($data)` - Insert new record
- `update($id, $data)` - Update record
- `delete($id)` - Delete record
- `query($conditions, $options)` - Query with WHERE/ORDER/LIMIT
- `count($conditions)` - Count matching records

**Security Features**:
- `validate_column()` - Prevent SQL injection in ORDER BY/WHERE
- `build_where_clause()` - Safe WHERE construction with `$wpdb->prepare()`
- Whitelist of allowed columns per repository

**Key Design**:
```php
protected $allowed_columns = [
    'id', 'account_id', 'status', 'created_at', 
    'updated_at', 'transaction_id', 'template_id', // etc.
];

protected function validate_column($col) {
    if (!in_array($col, $this->allowed_columns)) {
        error_log('Invalid column: ' . $col);
        return null;
    }
    return $col;
}
```

### TransactionRepository
**Specialization**: Query methods for transactions
- Filter by account_id, status, closing_date
- Pagination support
- Hydrate to Transaction model

### TaskRepository
**Specialization**: Query methods for tasks
- Filter by transaction_id, status, due_at
- Support for dependency queries

### TemplateRepository (200+ lines)
**Key Methods**:
- `getSystemTemplates()` - Get all is_system=true
- `getCustomTemplates($account_id)` - Get account-specific
- `findByPropertyType($type)` - Filter by property type
- `findActive()` - Only is_active=true templates

### TaskDefinitionRepository
**Key Methods**:
- `findByCategory($category)` - Query by task category
- `findByKey($task_key)` - Lookup by unique key
- Search and filter methods for task library

### TemplateTaskRepository (200+ lines)
**Key Methods**:
- `findByTemplate($template_id, $with_definitions = false)` - Get all linked tasks
- `findByTaskDefinition($def_id)` - Find templates using a task

### Other Repositories
- **PartyRepository**: Query/manage transaction parties
- **ReminderRepository**: Query reminder queue
- **EventRepository**: Query audit log
- **DocumentRepository**: File attachment queries
- **NotificationRepository**: Delivery log queries
- **AccountRepository**: Multi-tenant account queries
- **VendorRequestRepository**: Vendor task queries

---

## 4.3 Services (Business Logic, 9 Total)

### TemplateEngine Service (450+ lines)
**Purpose**: Parse YAML templates, extract tasks, instantiate for transactions

**Key Methods**:

1. **`parseYaml($yaml_content)`**
   - Uses Symfony\Yaml\Yaml::parse()
   - Returns parsed array or error message

2. **`extractTasksFromParsed($parsed)`**
   - Handles multiple YAML structures:
     - Flat `tasks:` array
     - Nested `workflows: [tasks]`
     - `conditional_tasks: [{condition, tasks}]`
   - Returns merged task array

3. **`instantiateTasks($template, $transaction, $property_data)`**
   - Main entry point for creating tasks from template
   - Intelligent routing:
     - If template has 10+ linked tasks in modular system → use modular approach
     - Otherwise → fall back to YAML parsing
   - Returns array of task data ready for DB insertion

4. **`instantiateTasksFromModular($template, $transaction, $property_data)`**
   - Queries template_task_repository for linked tasks
   - For each task, evaluates:
     - `applies_if` conditions (e.g., "property.has_septic")
     - Dependency chains
   - Calculates due dates

5. **`instantiateTasksFromYaml($template, $transaction, $property_data)`**
   - Parses template YAML directly
   - Extracts task definitions
   - Handles conditionals and dependencies
   - Applies property metadata for condition evaluation

6. **`handleConditionalTasks()`**
   - Evaluates `applies_if` conditions like:
     - `property.type == 'SFH'`
     - `property.has_septic && property.year_built < 1978`
   - Filters task list based on property attributes

7. **`calculateTaskDates()`**
   - Relative date calculation:
     - Input: "Closing-21d" (21 days before closing)
     - Output: Actual date based on transaction.closing_date
   - Supports: Closing, PS, Listing, FirstMeeting anchors
   - Supports: days (d), hours (h), minutes (m) offsets

**Design Note**: Dual approach allows flexibility:
- YAML parsing for templates stored as text (flexible but slower)
- Modular system for pre-defined templates (faster, more queryable)

### TaskScheduler Service (150 lines)
**Purpose**: Calculate task due dates

**Key Method**:
```php
calculateDueDates($tasks, $transaction): array
```
- Processes each task's metadata for `relative_due_date`
- Calls `calculateSingleDueDate()` for each
- Returns updated task array

**Date Formats**:
- "Closing+0d" → closing date
- "Closing-21d" → 21 days before closing
- "PS+7d" → 7 days after purchase agreement
- "Listing+0d" → on listing date

### ReminderService Service (250+ lines)
**Purpose**: Process reminder queue, send notifications

**Key Methods**:

1. **`processQueue($limit = 100)`**
   - Fetches pending reminders from queue
   - For each, calls `sendReminder()`
   - Updates reminder status
   - Returns summary

2. **`sendReminder($reminder)`**
   - Retrieves associated task + transaction
   - Determines recipient (task owner, assigned party, agent)
   - Generates email via NotificationService
   - Updates reminder status
   - Logs event to audit trail

3. **`createReminders($task, $template_offset)`**
   - Creates reminder queue entry
   - Calculates reminder date from task due date + offset
   - Stores in database

4. **`rescheduleReminder($reminder_id, $new_date)`**
   - Updates reminder date (e.g., task moved)

**Integration**: Works with NotificationService and EmailService

### NotificationService Service (120 lines)
**Purpose**: Multi-channel notification abstraction

**Key Methods**:

1. **`sendEmail($to, $subject, $html_body, $attachments = [])`**
   - Wrapper around WordPress `wp_mail()`
   - Supports attachments

2. **`sendSMS($phone, $message)`**
   - Placeholder (TODO: integrate Twilio/Nexmo)

3. **`createNotificationRecord($type, $recipient_id, $subject, $body, $status)`**
   - Logs notification delivery attempt
   - Tracks success/failure

4. **`getDeliveryStatus($notification_id)`**
   - Query delivery status

### EmailService Service (500+ lines)
**Purpose**: Email template rendering and sending

**Email Templates** (multiple templates for different scenarios):
1. Transaction confirmation
2. Task assignment
3. Task deadline reminder
4. Vendor request
5. Document collection
6. Closing checklist
7. Post-closing updates

**Key Features**:
- HTML email templates with embedded styling
- Personalization (transaction, party, agent names)
- Markdown support for task descriptions
- Signature blocks
- Unsubscribe links

**Key Methods**:
- `renderTransactionEmail($transaction)` - Email when transaction created
- `renderTaskAssignmentEmail($task, $recipient)` - Email when task assigned
- `renderReminderEmail($reminder, $task)` - Deadline reminder
- `renderVendorRequestEmail($vendor_request)` - Vendor portal invitation

### MATimelineCalculator Service (300+ lines)
**Purpose**: Massachusetts-specific date calculations

**Key Features**:
- **Holiday Awareness**: US federal holidays, MA court closures
- **Business Day Calculation**: Skip weekends + holidays
- **Milestone Offsets**: Date math relative to transaction milestones
- **Deadline Rules**: MA-specific deadline rules (e.g., Title 5 inspection within 14 days of P&S)

**Key Methods**:
- `getBusinessDaysFromNow($days)` - Add business days to today
- `getBusinessDaysBetween($start, $end)` - Count business days
- `isBusinessDay($date)` - Check if weekday + not holiday
- `getDeadlineFromMilestone($milestone, $offset)` - Milestone-relative date

### TaskAssignmentService Service (200+ lines)
**Purpose**: Distribute tasks to parties, send notifications

**Key Methods**:
1. **`assignTasksToParties($tasks, $transaction, $parties)`**
   - For each task, determine recipient based on `owner_role`
   - Match to party by role
   - Create assignment record
   - Send notification email

2. **`suggestedAssignees($task, $parties)`**
   - Return list of recommended recipients

### VendorService Service (150+ lines)
**Purpose**: Vendor portal logic

**Key Methods**:
1. **`createVendorRequest($task, $vendor_role)`**
   - Generate vendor task record
   - Create signed URL with expiration
   - Send vendor invitation email

2. **`getSignedUrl($request_id, $expiry_hours = 24)`**
   - Generate time-limited public URL
   - No WordPress auth required

3. **`verifySignedUrl($request_id, $signature, $timestamp)`**
   - Validate URL signature (HMAC-SHA256)
   - Check expiration
   - Return request object or null

### RateLimiter Service (250+ lines)
**Purpose**: Request throttling to prevent abuse

**Key Methods**:
- `checkRateLimit($identifier, $limit, $window)` - Check if request allowed
- `incrementCounter($identifier)` - Track request
- `resetCounter($identifier)` - Clear counter

---

## 4.4 REST API Controllers (12 Total, 40+ Endpoints)

### BaseController (400+ lines)
**Abstract Base Class**

**Common Features**:
- `$namespace = 'ma-deal/v1'` - API namespace
- `register_routes()` - Abstract, overridden by children
- `permission_callback()` - Check user is logged in (modifiable for public endpoints)
- `verify_nonce()` - CSRF protection for state-changing operations

**Authentication**:
```php
protected function permission_callback() {
    return current_user_can('edit_posts'); // Can override
}

public function verify_nonce($request) {
    $nonce = $request->get_header('X-WP-Nonce') 
        ?? $request->get_param('_wpnonce');
    
    return wp_verify_nonce($nonce, 'wp_rest');
}
```

**Response Handling**:
- `rest_response($data, $status = 200)` - Success response
- `rest_error($code, $message, $data = null)` - Error response
- `paginated_response($items, $total, $page, $per_page)` - Paginated results

**Utility Methods**:
- `get_account_id()` - Get current account
- `check_account_access($account_id)` - Verify user has account access

### TransactionController (500+ lines)
**Base**: `ma-deal/v1/transactions`

**Endpoints**:

1. **GET /transactions**
   - List transactions with filters
   - Query params: `account_id`, `status`, `property_type`, `assigned_agent_id`, `page`, `per_page`
   - Returns paginated array with task summary

2. **GET /transactions/{id}**
   - Get single transaction with all related data
   - Includes: parties array, tasks array, reminders

3. **POST /transactions**
   - Create new transaction
   - Payload: property details, dates, template_id (optional)
   - Auto-creates initial task if template selected
   - Returns created transaction + generated tasks

4. **PUT /transactions/{id}**
   - Update transaction dates/status
   - Recalculates all dependent task due dates
   - Reschedules reminders

5. **DELETE /transactions/{id}**
   - Delete transaction (cascades to tasks, parties, events)

6. **POST /transactions/{id}/generate-tasks**
   - Apply template to existing transaction
   - Merges new tasks with existing ones

7. **GET /transactions/{id}/timeline**
   - Get transaction milestone timeline
   - Returns: listing, offer, PS, loan commitment, closing with calculated dates

8. **POST /transactions/{id}/duplicate**
   - Clone transaction with new property info
   - Optionally applies same template

### TaskController (400+ lines)
**Base**: `ma-deal/v1/tasks`

**Endpoints**:

1. **GET /tasks**
   - List tasks with filters
   - Query: `transaction_id`, `status`, `due_at`, `owner_role`, `page`, `per_page`
   - Supports sorting by due date

2. **GET /tasks/{id}**
   - Get task with dependencies
   - Includes linked tasks, metadata, reminder info

3. **POST /tasks**
   - Create custom task for transaction
   - Payload: task_key, title, description, due_at, owner_role, etc.

4. **PUT /tasks/{id}**
   - Update task
   - Allows reschedule, reassign, change metadata

5. **PUT /tasks/{id}/complete**
   - Mark task complete
   - Sets `completed_at`, triggers dependent task notifications
   - Logs event

6. **PUT /tasks/{id}/skip**
   - Mark task skipped
   - Records reason in metadata

### TemplateController (300+ lines)
**Base**: `ma-deal/v1/templates`

**Endpoints**:

1. **GET /templates**
   - List templates (system + account-owned)
   - Includes computed task_count
   - Filters: property_type, transaction_side

2. **GET /templates/{id}**
   - Get full template with YAML content

3. **POST /templates**
   - Create custom template
   - Payload: name, description, YAML content, property_type
   - Parses YAML, validates, stores

4. **PUT /templates/{id}**
   - Update custom template
   - Re-validates YAML
   - System templates are read-only

5. **DELETE /templates/{id}**
   - Delete custom template (system templates protected)

6. **POST /templates/{id}/preview**
   - Preview tasks that would be created
   - Returns task list without persisting

### TaskDefinitionController (200+ lines)
**Base**: `ma-deal/v1/task-definitions`

**Endpoints**:

1. **GET /task-definitions**
   - List all task definitions
   - Returns task library with filtering

2. **GET /task-definitions/{id}**
   - Get single definition with metadata

### Other Controllers
- **ReminderController**: GET /reminders, PUT /reminders/{id}/sent
- **DocumentController**: GET/POST documents, DELETE, GET signed URL download
- **NotificationController**: GET notifications, DELETE to dismiss
- **VendorPortalController**: GET /vendor-requests (public), PUT to update
- **SettingsController**: GET/PUT account settings
- **SearchController**: Global search across entities
- **UserProfileController**: GET/PUT user profile

---

# 5. DATA FLOW & PROCESSING

## 5.1 Transaction Creation Flow

```
1. User submits transaction form (React)
   ↓
2. POST /transactions (TransactionController)
   ↓
3. Validate & sanitize input
   ↓
4. Insert into wp_ma_deal_transactions
   ↓
5. Optional: Apply template
   ↓ [if template selected]
6. Call TemplateEngine::instantiateTasks($template, $transaction)
   ↓
7. Parse YAML or load modular tasks
   ↓
8. Evaluate conditional_tasks (applies_if)
   ↓
9. Calculate due dates for each task
   ↓
10. Batch insert into wp_ma_deal_tasks
   ↓
11. Create reminders for tasks with reminder offsets
   ↓
12. Log event to wp_ma_deal_events (audit)
   ↓
13. Send email notification to assigned agent
   ↓
14. Return transaction + created tasks to client
```

## 5.2 Task Instantiation from YAML

```
Example: Single-Family Home with Septic

YAML Template:
  base_transaction.yaml (125 universal tasks)
    ↓
  sfh_septic.yaml extends base (adds 26 septic-specific tasks)

TemplateEngine Processing:
  1. Load sfh_septic.yaml
  2. See "extends: base_transaction" → load base too
  3. Merge both task arrays
  4. Iterate each task:
     - Check "applies_if": "property.has_septic && property.year_built < 1978"
     - Property attributes from transaction.property_metadata
     - If condition true → include task
     - If false → skip task
  5. Resolve dependencies (task A depends_on task B)
  6. Calculate dates:
     - "due: PS" + "due_offset: +14d" → 14 days after PS agreement
  7. Create 150+ task records
  8. Create reminders:
     - Task due in 7 days → reminder email 3 days before
```

## 5.3 Reminder Queue Processing

```
WP-Cron Trigger (or wp ma-deal reminders:send)
   ↓
ReminderService::processQueue()
   ↓
Query: WHERE due_at <= NOW() AND sent_at IS NULL LIMIT 100
   ↓
For each reminder:
  1. Fetch associated task
  2. Fetch associated transaction
  3. Determine recipient (assigned_party, agent, etc.)
  4. Call EmailService to render email
  5. Send via wp_mail()
  6. Create notification record
  7. Update reminder: sent_at = NOW()
  8. Log event to audit trail
   ↓
Return: {processed: 50, failed: 2, skipped: 0}
```

## 5.4 Template Sync (On Plugin Activation)

```
ma_deal_room_activate()
   ↓
ma_deal_room_sync_system_templates()
   ↓
1. Scan /assets/templates/*.yaml files
2. For each file:
   a. Read YAML content
   b. Check if template exists in DB by template_id
   c. If new → INSERT
   d. If exists → UPDATE (new version)
3. Set is_system = true, account_id = NULL
4. Return {synced: 6, errors: 0}
   ↓
Update option: ma_deal_room_templates_synced
```

---

# 6. CODE QUALITY & SECURITY TECHNIQUES

## 6.1 Security Measures

### SQL Injection Prevention
- **$wpdb->prepare()**: All database queries use parameterized statements
  ```php
  $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND status = %s", 
                  $id, $status)
  ```
- **Column Whitelisting**: BaseRepository validates column names against allowed list
  ```php
  protected $allowed_columns = ['id', 'status', 'created_at', ...];
  protected function validate_column($col) { ... }
  ```
- **WHERE clause builder**: Safe construction with placeholders

### XSS Prevention
- **Output Escaping**: WordPress escaping functions (wp_kses_post, esc_html, etc.)
- **React Escaping**: React JSX auto-escapes by default
- **HTML Email**: Sanitized HTML in email templates

### CSRF Protection
- **Nonce Verification**: State-changing operations verify `X-WP-Nonce` header
  ```php
  public function verify_nonce($request) {
      $nonce = $request->get_header('X-WP-Nonce');
      if (!wp_verify_nonce($nonce, 'wp_rest')) {
          return new WP_Error('invalid_nonce', 'Nonce verification failed');
      }
      return true;
  }
  ```

### Authentication & Authorization
- **WordPress Auth**: All endpoints require logged-in user (except vendor portal)
- **Capability Checks**: `current_user_can('edit_posts')` (customizable)
- **Account Access**: Verify user has access to account before returning data
  ```php
  public function check_account_access($account_id) {
      // Ensure user is member of account
  }
  ```

### Vendor Portal Security
- **Signed URLs**: HMAC-SHA256 signature prevents tampering
  ```php
  $signature = hash_hmac('sha256', $request_id . $timestamp, PLUGIN_SECRET);
  ```
- **Time-Limited**: URLs expire after 24 hours (configurable)
- **No Auth Required**: Public endpoint but cryptographically secured

### Rate Limiting
- **RateLimiter Service**: Prevent brute force/DoS
  ```php
  if (!$this->rate_limiter->checkRateLimit($ip, 100, 3600)) {
      return new WP_Error('rate_limited', 'Too many requests');
  }
  ```
- **Per-IP, per-endpoint tracking**

### Input Validation & Sanitization
- **Form Validation**: React Zod schemas on frontend
- **Backend Validation**: PHP property type hints, range checks
  ```php
  if (!in_array($status, ['pending', 'completed', 'skipped'])) {
      return new WP_Error('invalid_status', 'Unknown status');
  }
  ```

## 6.2 Error Handling

### Try-Catch Pattern
- Services wrap operations in try-catch
- Return structured error objects to controllers
- Never expose raw exceptions to API clients

### Database Error Handling
- All queries check for `$wpdb->last_error`
- Return null or empty array on failure
- Log errors with context

### REST API Error Format
```php
// WordPress REST API standard
new WP_Error('code', 'Human-readable message', ['data' => $context]);

// Returns:
{
  "code": "transaction_not_found",
  "message": "Transaction with ID 123 not found",
  "data": {"status": 404}
}
```

## 6.3 Code Reusability & DRY

### Repository Pattern
- BaseRepository provides common CRUD (find, create, update, delete, query)
- All repositories inherit, override only specialized queries
- ~12 repository classes, ~7KB shared code

### Service Abstraction
- TemplateEngine abstracts YAML parsing + task instantiation
- ReminderService abstracts reminder queue processing
- NotificationService abstracts multi-channel delivery
- Clients use high-level services, not low-level details

### WordPress Hooks
- Extensible via filters/actions
- Third-party plugins can hook into task creation, reminder sending, etc.

### Shared Utilities
- BaseController: Common REST patterns (auth, nonce, pagination)
- BaseRepository: Common database operations
- Email templates: Reusable email layouts

## 6.4 Documentation

### Code Comments
- Docblocks on all classes, methods, properties (PHPDoc format)
- Inline comments explaining complex logic (date calculations, conditional evaluation)

### README Files
- Plugin README.md: Installation, features, API overview
- database/README.md: Schema documentation
- Developer Guide (DEVELOPER_GUIDE.md): Template system guide

### API Documentation
- REST endpoints documented in README
- TypeScript interfaces define request/response shapes
- React Query hooks abstract API calls

---

# 7. RECENT DEVELOPMENT & CHANGES

## 7.1 Git History (Recent 20 Commits)

```
9c785a8  Add comprehensive YAML formatting instructions to template form
b1742b2  Fix YAML line breaks being stripped in template creation
020d236  Fix modal z-index to appear above WordPress admin menu
986aaad  Add template CRUD functionality
781cf4b  Fix task complete and skip endpoints to return updated task data
e444293  Fix tasks display and add delete functionality
20d24c9  Remove non-existent columns from task creation
10de3e1  Fix owner_role field name in TemplateEngine
3f5ee28  Fix TemplateEngine property names and database column
f5ff2a9  Add automatic task generation from templates
0370f7e  Fix transaction creation - auto-assign account_id
291b347 Convert Transaction models to arrays in API responses
b174c2a Add field aliases for React TypeScript compatibility
274eaa7 Fix template API to return paginated response with task_count
8dbfd1b Fix template loading and add task_count field
4aaeee7 Fix React routing and add missing API endpoints
b446d93 feat: Complete Phase 9 - Vendor Portal with Signed URLs
9c37273 feat: Complete Phase 8 - Background Jobs & Queue System
74129e5 feat: Complete Phase 7 MVP - Template Engine and React UI integration
b3cf1cf feat: Build complete React admin UI for MA Deal Room
```

## 7.2 Implemented Phases

**Phase 1-4**: ✅ COMPLETE
- Database schema (14 tables, 35+ indexes)
- PSR-4 class structure
- Service container & dependency injection
- REST API endpoints (40+)

**Phase 5**: ✅ COMPLETE
- YAML template engine
- Task scheduling with date calculations
- Modular task system (database-driven)

**Phase 6**: ✅ COMPLETE
- React admin UI (SPA)
- Dashboard with analytics
- Transaction/task/template management

**Phase 7**: ✅ COMPLETE
- Template engine integration with React
- MVP with full transaction workflow

**Phase 8**: ✅ COMPLETE
- Background jobs & queue system
- WP-CLI commands for processing
- Reminder service

**Phase 9**: ✅ COMPLETE
- Vendor portal with signed URLs
- Document collection from vendors
- Public API endpoints

**Phase 10 (Current)**: 🔄 IN PROGRESS
- Template form YAML validation
- UI enhancements (z-index, formatting)
- Property details field expansion
- Search/filtering improvements
- Enterprise features (in development)

## 7.3 Known Issues & Fixes (Recent Session)

### Fixed Issues
1. **YAML Line Breaks** - Template form was stripping line breaks in textarea
   - Solution: Added comprehensive formatting instructions
   
2. **Modal Z-Index** - Template form modal appeared behind WordPress admin menu
   - Solution: Adjusted Tailwind z-index classes
   
3. **Task Ownership** - owner_role field was incorrectly named
   - Solution: Standardized field names across Models, Repositories, API
   
4. **Task Deletion** - Missing DELETE endpoint for tasks
   - Solution: Added delete functionality
   
5. **Account Auto-Assign** - Transactions not auto-assigning to account
   - Solution: Added account_id auto-assignment in TransactionController::create_item()

### Ongoing Work
- Template validation improvements
- Property details field expansion (bedrooms, bathrooms, sqft, etc.)
- Dynamic filtering enhancements
- Search performance optimization

---

# 8. POTENTIAL AREAS FOR IMPROVEMENT

## 8.1 Code Smells & Technical Debt

### 1. Duplicate Date Calculation Logic
- **Issue**: Date calculation logic exists in multiple places
  - TaskScheduler::calculateSingleDueDate()
  - TemplateEngine::calculateTaskDates()
  - MATimelineCalculator
- **Impact**: Risk of divergent behavior
- **Fix**: Centralize in MATimelineCalculator, create shared service

### 2. YAML Parsing Fallbacks
- **Issue**: Template::getTaskCount() uses regex fallback for malformed YAML
- **Impact**: Can over/undercount tasks
- **Fix**: Validate YAML on save, reject invalid templates

### 3. Missing Validation in TemplateEngine
- **Issue**: No validation that `applies_if` conditions reference valid properties
- **Impact**: Tasks silently ignored if condition references non-existent property
- **Fix**: Validate condition syntax, list available properties

### 4. Incomplete Task Definition Parsing
- **Issue**: Task definitions stored in DB but not fully used for instantiation
- **Impact**: Dual system (YAML + modular) creates confusion
- **Fix**: Complete migration to modular system, deprecate pure YAML approach

### 5. Limited Error Messages
- **Issue**: Some API endpoints return generic 400 errors without context
- **Impact**: Frontend devs struggle to debug
- **Fix**: Add detailed error messages with field-specific issues

### 6. No Transaction Locking
- **Issue**: No pessimistic locking for concurrent updates to same transaction
- **Impact**: Race condition if two users edit transaction simultaneously
- **Fix**: Add version column, implement optimistic locking

## 8.2 Performance Considerations

### 1. Task Instantiation
- **Current**: O(tasks) × O(conditions) - evaluates each condition per task
- **At Scale**: 1000 tasks × 100 condition checks = 100K evaluations
- **Fix**: Pre-compile conditions, use property index

### 2. Template Parsing
- **Current**: Templates re-parsed on every instantiation
- **Fix**: Cache parsed templates in Redis/Memcached

### 3. Task Queries
- **Current**: No query batching for task dependencies
- **Impact**: 1 task with 5 dependencies = 6 DB queries
- **Fix**: Use WITH (CTE) or batch load dependencies

### 4. Reminder Queue Processing
- **Current**: Process 100 reminders per run
- **At Scale**: Thousands of reminders could queue up
- **Fix**: Implement priority queue, process higher-priority reminders first

### 5. Search Indexing
- **Current**: Full table scans on search
- **Fix**: Add FULLTEXT indexes on transaction address, task title, etc.

### 6. API Response Size
- **Current**: Paginated endpoints return nested tasks/parties
- **Impact**: Large payloads
- **Fix**: Implement GraphQL, sparse fieldsets, or separate endpoints

## 8.3 Missing Features

### 1. Document Storage
- **Status**: Schema exists, upload API exists
- **Missing**: 
  - Direct file upload handling (currently delegates to vendor portal)
  - Integration with S3/cloud storage (currently uses local filesystem)
  - Document previews in React UI

### 2. SMS Notifications
- **Status**: NotificationService has placeholder
- **Missing**: 
  - Twilio/Nexmo integration
  - SMS template rendering
  - Phone number validation

### 3. Email Scheduling
- **Status**: Reminders send immediately
- **Missing**: 
  - Delay reminders by timezone (currently sends server timezone)
  - Schedule task-specific emails (e.g., "send 1 day before due")

### 4. Task Collaboration
- **Status**: Tasks assigned to individuals
- **Missing**: 
  - Team assignments (multiple people can work on task)
  - Comments/notes on tasks (audit trail)
  - Task status notifications to all assignees

### 5. Reporting & Analytics
- **Status**: Dashboard has basic widgets
- **Missing**: 
  - Customizable reports
  - Export to Excel/PDF
  - Compliance audit reports (for MA regulators)
  - Performance metrics (close rate, average transaction time)

### 6. Integrations
- **Missing**: 
  - MLS/Zillow data import
  - Title company APIs
  - Lender APIs for loan commitment status
  - Slack/Teams notifications

### 7. Mobile App
- **Status**: React UI is web-only
- **Missing**: 
  - Mobile-optimized app
  - Offline support
  - Geolocation for property visits

## 8.4 Testing Coverage

- **Current**: No automated tests visible
- **Missing**:
  - Unit tests for Services, Repositories
  - Integration tests for REST API endpoints
  - E2E tests for critical workflows (create transaction, apply template, mark tasks complete)
  - YAML template validation tests

---

# 9. DEPLOYMENT & OPERATIONS

## 9.1 Installation Process

```bash
# 1. Install dependencies
cd /wp-content/plugins/ma-deal-room
composer install

# 2. Activate plugin via WordPress admin
# OR via WP-CLI
wp plugin activate ma-deal-room

# 3. Migrations run automatically on activation
# OR manual:
wp ma-deal migrate

# 4. Verify
wp ma-deal migrate:status

# 5. Load sample data (optional)
wp ma-deal seed

# 6. Sync system templates
wp ma-deal templates:sync
```

## 9.2 Configuration

### Plugin Constants (ma-deal-room.php)
```php
define('MA_DEAL_VERSION', '1.0.0');
define('MA_DEAL_PATH', plugin_dir_path(__FILE__));
define('MA_DEAL_URL', plugin_dir_url(__FILE__));
define('MA_DEAL_BASENAME', plugin_basename(__FILE__));
define('MA_DEAL_MIN_PHP_VERSION', '8.0');
define('MA_DEAL_MIN_WP_VERSION', '6.0');
```

### Email Configuration (ma-deal-room.php)
- Configured for MailHog (local SMTP server) by default
- Change `avn-mailhog` host for production SMTP

### Cron Jobs
- WP-Cron executes on page load (not reliable for high traffic)
- **Recommended**: Use true cron instead:
  ```bash
  0 */4 * * * wp ma-deal reminders:send --limit=500
  0 0 * * 0 wp ma-deal queue:run
  ```

## 9.3 Database Backup/Recovery

```bash
# Backup
wp db export backup_$(date +%Y%m%d_%H%M%S).sql

# Restore
wp db import backup.sql

# Migration rollback (if needed)
wp ma-deal migrate:rollback
```

---

# 10. FINAL ASSESSMENT

## Strengths

1. **Well-Structured Architecture**: Clean separation of concerns, clear patterns
2. **Security-First**: SQL injection prevention, CSRF protection, nonce verification
3. **Massachusetts Domain Expert**: Encodes specific regulatory requirements (Title 5, condo 6(d), etc.)
4. **Scalable Multi-Tenant Design**: Account isolation, role-based access
5. **Modern Tech Stack**: PHP 8.0+, React 18, TypeScript, REST API
6. **Comprehensive Task System**: 200+ tasks with conditional logic, dependencies, date calculations
7. **Vendor Integration**: Public portal with signed URLs for document collection
8. **Audit Trail**: Complete event logging for compliance

## Weaknesses

1. **Dual Task Systems**: Both YAML and modular task systems create confusion
2. **Limited Test Coverage**: No visible unit/integration tests
3. **Performance Issues**: Full table scans, per-task condition evaluation
4. **Incomplete Features**: SMS, document storage, reporting not fully implemented
5. **Minimal Documentation**: No API documentation beyond README
6. **Duplicate Logic**: Date calculations scattered across services

## Recommendations

### Short Term (1-2 Weeks)
1. Consolidate date calculation logic
2. Add comprehensive input validation
3. Implement basic unit tests for Services
4. Add error message details to API responses

### Medium Term (1-2 Months)
1. Migrate fully to modular task system, deprecate pure YAML
2. Add FULLTEXT indexes for search optimization
3. Implement task caching (Redis)
4. Complete SMS integration
5. Add user documentation with screenshots

### Long Term (3-6 Months)
1. GraphQL API for flexible queries
2. Mobile app
3. MLS/third-party integrations
4. Advanced reporting & analytics
5. Collaboration features (comments, multi-person assignments)

---

## Summary

MA Deal Room is a **production-ready, well-engineered WordPress plugin** with sophisticated transaction management capabilities. It demonstrates strong architectural decisions (service container, repository pattern, REST API), implements security best practices, and encodes complex Massachusetts regulatory requirements into reusable templates.

The codebase is mature enough for production deployment but would benefit from test coverage, performance optimization, and consolidation of dual task systems. With ~9,200 PHP lines and ~100 React components, it represents a significant custom development effort with clear value for Massachusetts real estate professionals.

**Readiness**: Ready for production with recommended operational monitoring and gradual feature enablement.
