# MA Deal Room - Vendor Functionality Exploration Report

**Date:** November 2, 2025  
**Thoroughness Level:** Very Thorough  
**Status:** Partial implementation (Phase 2.2 incomplete)

---

## Executive Summary

The MA Deal Room codebase has a **foundational vendor infrastructure** that supports external vendor coordination for tasks like inspections, appraisals, and other property-related services. However, most of the **vendor portal UI and interactive features are not yet implemented**.

### What Exists:
- Complete database schema for vendor requests
- Backend service for token generation and validation
- REST API endpoints (partial implementation)
- Email template for vendor requests
- Basic security (rate limiting, token expiration)

### What's Missing:
- Public vendor portal UI (React components)
- Vendor dashboard/portal pages
- Vendor request management interface
- Document upload handling in vendor portal
- Vendor scheduling/calendar integration
- Completion status and notes submission

---

## 1. DATABASE SCHEMA - VENDOR TABLES

### Table: `wp_ma_deal_vendor_requests`
**Location:** `/home/snova/projects/dealroom/ma-deal-room/database/migrations/001_initial_schema.sql`

```sql
CREATE TABLE IF NOT EXISTS `wp_ma_deal_vendor_requests` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_id` BIGINT(20) UNSIGNED NOT NULL,
  `transaction_id` BIGINT(20) UNSIGNED NOT NULL,
  `party_id` BIGINT(20) UNSIGNED DEFAULT NULL,
  `vendor_type` ENUM('fire_dept', 'septic_inspector', 'hoa_manager', 'title_company',
                     'appraiser', 'inspector', 'other') NOT NULL,
  `vendor_email` VARCHAR(255) NOT NULL,
  `vendor_phone` VARCHAR(20) DEFAULT NULL,
  `token` VARCHAR(64) NOT NULL (UNIQUE),
  `token_expires_at` DATETIME NOT NULL,
  `status` ENUM('sent', 'opened', 'scheduled', 'completed', 'expired', 'cancelled'),
  `scheduled_date` DATE DEFAULT NULL,
  `scheduled_time` TIME DEFAULT NULL,
  `completion_notes` TEXT DEFAULT NULL,
  `document_url` VARCHAR(500) DEFAULT NULL,
  `last_opened_at` DATETIME DEFAULT NULL,
  `metadata` JSON DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_token` (`token`),
  KEY `idx_status_created` (`status`, `created_at`),
  KEY `idx_task` (`task_id`),
  KEY `idx_transaction` (`transaction_id`),
  KEY `idx_party` (`party_id`),
  KEY `idx_token_expires` (`token_expires_at`)
);
```

**Status Lifecycle:** `sent` → `opened` → `scheduled` → `completed` (or `expired`, `cancelled`)

**Key Features:**
- Secure token-based access (64-char hex)
- Token expiration tracking (typically 30-60 days)
- Supports 7 vendor types + "other"
- Tracks vendor interactions (`last_opened_at`)
- Document upload field (`document_url`)
- Flexible metadata for vendor-specific fields

---

## 2. BACKEND CODE - PHP SERVICES & CONTROLLERS

### 2.1 VendorService
**Location:** `/home/snova/projects/dealroom/ma-deal-room/src/Services/VendorService.php`

**Methods:**
- `generateSignedUrl(int $request_id, int $expiration_days = 30): string`
  - Generates secure 64-char random token
  - Stores token and expiration in database
  - Returns token for URL construction

- `validateToken(string $token): ?object`
  - Validates token existence and expiration
  - Updates status from `sent` to `opened` on first access
  - Updates `last_opened_at` timestamp
  - Returns VendorRequest object or null

- `generateICS(array $event_data): string`
  - Generates iCalendar (.ics) file for event import
  - Supports summary, start/end times, description, location, organizer

### 2.2 VendorPortalController
**Location:** `/home/snova/projects/dealroom/ma-deal-room/src/REST/Controllers/VendorPortalController.php`

**Routes:**
- `GET /wp-json/ma-deal/v1/vendor/{token}` - Retrieve vendor request details
- `POST /wp-json/ma-deal/v1/vendor/{token}` - Update vendor request (TODO)

**Security:**
- Rate limiting enabled (`vendor-portal` limiter)
- No authentication required (public endpoint)
- Token validation on all requests
- CORS-safe for public access

**Implementation Status:**
- ✅ GET endpoint fully implemented
- ⚠️ POST endpoint stub only (TODO comment on line 66)

### 2.3 VendorRequestRepository
**Location:** `/home/snova/projects/dealroom/ma-deal-room/src/Repositories/VendorRequestRepository.php`

**Methods:**
- `findByToken(string $token): ?object` - Look up vendor request by token
- `findByTask(int $task_id): array` - Get all vendor requests for a task
- `updateStatus(int $id, string $status): bool` - Update request status

---

## 3. MODELS & DATA STRUCTURES

### VendorRequest Model
**Location:** `/home/snova/projects/dealroom/ma-deal-room/src/Models/VendorRequest.php`

**Properties:**
```php
class VendorRequest {
    public int $id;
    public int $task_id;
    public int $transaction_id;
    public ?int $party_id;
    public string $vendor_type; // fire_dept, septic_inspector, hoa_manager, ...
    public string $vendor_email;
    public ?string $vendor_phone;
    public string $token;
    public string $token_expires_at;
    public string $status; // sent, opened, scheduled, completed, expired, cancelled
    public ?string $scheduled_date;
    public ?string $scheduled_time;
    public ?string $completion_notes;
    public ?string $document_url;
    public ?string $last_opened_at;
    public ?array $metadata;
    public string $created_at;
    public string $updated_at;
}
```

**Methods:**
- `toArray(): array` - Convert model to database-friendly array
- `fromArray(array $data): self` - Create model from array

### TypeScript Type Definitions
**Location:** `/home/snova/projects/dealroom/ma-deal-room/assets/admin/src/api/types.ts`

**Event Type Enum includes:**
- `vendor_request` (line 262) - Supports audit logging for vendor requests

**Task Model includes:**
- `owner_role: 'agent' | 'seller' | 'buyer' | 'seller_attorney' | 'buyer_attorney' | 'vendor' | 'system'`
- `vendor` is a recognized task owner type (line 91)

---

## 4. EMAIL TEMPLATES

### Vendor Request Email Template
**Location:** `/home/snova/projects/dealroom/ma-deal-room/src/Templates/emails/vendor-request.php`

**Template Variables:**
- `$vendor_name` - Recipient's name
- `$vendor_email` - Recipient's email
- `$transaction` - Transaction object with property details
- `$service_type` - Type of service requested
- `$requester_name`, `$requester_email`, `$requester_phone` - Contact info
- `$message` - Custom message from requester

**Email Content:**
- Professional HTML email with gradient styling
- Service type highlighted in yellow card
- Transaction details: property address, city/state/zip, property type, closing date
- Optional custom message section
- Contact information card
- Next steps checklist

**Status:** ✅ Fully implemented and ready to use

---

## 5. API ENDPOINTS

### Current Endpoints:

1. **GET /wp-json/ma-deal/v1/vendor/{token}**
   - **Status:** ✅ Fully Implemented
   - **Access:** Public (no authentication)
   - **Rate Limiting:** Enabled (`vendor-portal`)
   - **Response:** VendorRequest object with all details
   - **Security:** Token validation + expiration check

2. **POST /wp-json/ma-deal/v1/vendor/{token}**
   - **Status:** ⚠️ Stub Only (TODO in code)
   - **Intended Purpose:** Update vendor request with:
     - Scheduling information
     - Document uploads
     - Completion status
     - Notes
   - **Current Behavior:** Logs generic event, returns success message
   - **Missing Implementation:**
     - Request validation
     - Scheduled date/time handling
     - Document upload processing
     - Completion notes persistence
     - Status transitions

---

## 6. FRONTEND CODE - REACT/TYPESCRIPT

### Current Status: ❌ NO VENDOR PORTAL UI COMPONENTS

**Search Results:**
- No vendor portal React components found
- No vendor dashboard pages found
- No vendor routes configured
- No API service for vendor operations
- Only type definitions mention `vendor_request` in event logging

**Files Checked:**
- `/home/snova/projects/dealroom/ma-deal-room/assets/admin/src/routes/` - No vendor routes
- `/home/snova/projects/dealroom/ma-deal-room/assets/admin/src/pages/` - No vendor pages
- `/home/snova/projects/dealroom/ma-deal-room/assets/admin/src/components/` - No vendor components
- `/home/snova/projects/dealroom/ma-deal-room/assets/admin/src/api/queries/` - No vendor queries

### Type Definition References:
- **vendors in Task interface** (line 91): `owner_role` includes `'vendor'`
- **vendor_request in Event enum** (line 262): Event tracking for vendor requests
- Task model supports vendor assignment via `assigned_party_id`

---

## 7. INTEGRATION POINTS

### How Vendors are Used in the System:

1. **Task Assignment**
   - Tasks can have `owner_role: 'vendor'`
   - Tasks linked to `wp_ma_deal_parties` via `assigned_party_id`
   - Parties table supports vendor types: inspector, appraiser, title_company, etc.

2. **Email Notification**
   - NotificationService has `sendVendorRequest()` method stub
   - Uses `vendor-request.php` email template
   - Triggered when vendor task is created

3. **Event Tracking**
   - Vendor request events logged in `wp_ma_deal_events` table
   - Entity type: `vendor_request`
   - Supports audit trail and compliance tracking

4. **Rate Limiting**
   - Special rate limit bucket: `vendor-portal`
   - Prevents token brute-force attacks

---

## 8. MISSING/INCOMPLETE FEATURES

### Phase 2.2 Vendor Portal (Planned but not implemented)

From `/home/snova/projects/dealroom/DEVELOPMENT_ROADMAP.md` (line 2741):
- T2.2.1: Vendor Portal UI (React) - **NOT STARTED**
- T2.2.2: Vendor Dashboard - **NOT STARTED**
- T2.2.3: Document Upload - **NOT STARTED**
- T2.2.4: Scheduling/Calendar - **NOT STARTED**
- T2.2.5: Vendor Notifications - **NOT STARTED**

### Backend TODO (in code):
**File:** `/home/snova/projects/dealroom/ma-deal-room/src/REST/Controllers/VendorPortalController.php` (line 66)

```php
// TODO: Implement vendor request update logic in Phase 6
// This will handle:
// - Scheduling appointments
// - Uploading documents
// - Updating completion status
```

### Required Implementation:

#### Backend (PHP):
- [ ] Complete POST endpoint in VendorPortalController
- [ ] Implement scheduling validation
- [ ] Handle file uploads with security checks
- [ ] Validate completion notes
- [ ] Status transition logic (open → scheduled → completed)
- [ ] Finish sendVendorRequest() in NotificationService
- [ ] Add cron job for token expiration cleanup
- [ ] Add vendor signature/authentication options

#### Frontend (React/TypeScript):
- [ ] Create `/vendor/{token}` public route (no WordPress auth)
- [ ] VendorPortal page component
  - Display transaction details
  - Vendor request summary
  - Status indicator
- [ ] ScheduleForm component
  - Date/time picker
  - Availability confirmation
  - Messaging to requester
- [ ] DocumentUpload component
  - Drag-and-drop upload
  - File validation
  - Progress tracking
- [ ] CompletionForm component
  - Notes textarea
  - Service confirmation
  - Final submission
- [ ] VendorNotifications (email follow-ups)
- [ ] API service: `vendorPortalService`
  - getVendorRequest(token)
  - updateVendorRequest(token, data)

#### Database:
- [x] Schema ready
- [ ] Indexes optimized for public access (token lookup)
- [ ] Archive strategy for expired tokens

---

## 9. FILE DIRECTORY STRUCTURE

```
ma-deal-room/
├── src/
│   ├── REST/Controllers/
│   │   └── VendorPortalController.php ✅
│   ├── Services/
│   │   ├── VendorService.php ✅
│   │   └── NotificationService.php (partial)
│   ├── Models/
│   │   └── VendorRequest.php ✅
│   ├── Repositories/
│   │   └── VendorRequestRepository.php ✅
│   ├── Templates/emails/
│   │   └── vendor-request.php ✅
│   └── Core/
│       └── Plugin.php (registers VendorPortalController)
├── database/migrations/
│   └── 001_initial_schema.sql ✅
└── assets/admin/src/
    ├── api/
    │   ├── types.ts (has Event.vendor_request)
    │   └── queries/
    │       └── (NO vendor queries)
    ├── routes/ (NO vendor routes)
    ├── pages/ (NO vendor pages)
    └── components/ (NO vendor components)
```

---

## 10. CONFIGURATION & DEPENDENCIES

### Service Registration
**Location:** `/home/snova/projects/dealroom/ma-deal-room/src/Core/Plugin.php` (lines 21, 52, 184, 426-431)

```php
$container->register('vendor_request_repository', function($container) {
    return new VendorRequestRepository($container->get('cache_service'));
});

$container->register('vendor_service', function($container) {
    return new VendorService(
        $container->get('vendor_request_repository')
    );
});

$container->register('vendor_portal_controller', function($container) {
    return new VendorPortalController(
        $container->get('vendor_service'),
        $container->get('event_repository')
    );
});
```

### Supported Vendor Types
```php
'fire_dept'
'septic_inspector'
'hoa_manager'
'title_company'
'appraiser'
'inspector'
'other'
```

### Token Security
- Algorithm: Random bytes (32 bytes = 64 hex chars)
- Method: `bin2hex(random_bytes(32))`
- Uniqueness: Database unique constraint
- Expiration: Configurable (default 30 days)
- Validation: Strict expiration check with status update to 'expired'

---

## 11. SECURITY CONSIDERATIONS

### Already Implemented:
- ✅ Secure random token generation
- ✅ Token uniqueness via database constraint
- ✅ Token expiration tracking
- ✅ Rate limiting on public endpoint
- ✅ No authentication required (by design for external vendors)
- ✅ CORS-safe design

### Still Needed:
- [ ] HTTPS enforcement
- [ ] CSRF tokens for form submissions (if needed)
- [ ] File upload validation (MIME type, size, virus scan)
- [ ] Document access control (token-based)
- [ ] Vendor identity verification options
- [ ] Logging/audit trail for all vendor actions
- [ ] Email verification before accepting responses
- [ ] Rate limiting on file uploads

---

## 12. TESTING & VALIDATION

### Database Migrations:
- **Migration 001**: Creates vendor_requests table with all constraints
- **Rollback Available**: Yes (rollback_001.sql)
- **Status**: Applied

### Unit Tests:
- No vendor-specific tests found

### Integration Tests:
- No vendor portal integration tests

### E2E Tests:
- No vendor portal E2E tests

### Test Files to Create:
- `tests/Unit/Services/VendorServiceTest.php`
- `tests/Integration/REST/VendorPortalControllerTest.php`
- `tests/Feature/VendorWorkflowTest.php` (token generation → email → update)
- Frontend: `__tests__/pages/VendorPortal.test.tsx`

---

## 13. RECOMMENDATIONS & NEXT STEPS

### Immediate Priority (Phase 2.2):
1. **Complete VendorPortalController POST endpoint**
   - Input validation
   - Status state machine
   - Error handling

2. **Create Public Vendor Portal Page**
   - Public route (no auth)
   - Token-based access
   - Responsive design

3. **Implement Document Upload**
   - Backend file handling
   - Frontend drag-and-drop UI
   - Security scanning

4. **Add Vendor Dashboard**
   - Summary of vendor's open requests
   - Status updates
   - Document management

### Medium Priority:
5. Scheduling/calendar integration
6. Automated reminder emails
7. Vendor directory/ratings
8. Signature capture
9. Payment processing (if needed)

### Long-term:
10. Vendor marketplace/directory
11. Advanced scheduling (Calendly integration)
12. Automated compliance checking
13. Multi-language support
14. Mobile app for vendors

---

## 14. SUMMARY TABLE

| Component | Location | Status | Notes |
|-----------|----------|--------|-------|
| **Database Schema** | `001_initial_schema.sql` | ✅ Complete | `wp_ma_deal_vendor_requests` table with all fields |
| **VendorService** | `src/Services/VendorService.php` | ✅ Complete | Token generation, validation, ICS calendar |
| **VendorRequest Model** | `src/Models/VendorRequest.php` | ✅ Complete | Data model with JSON metadata support |
| **VendorRequestRepository** | `src/Repositories/VendorRequestRepository.php` | ✅ Complete | Query methods for token lookup, status updates |
| **VendorPortalController** | `src/REST/Controllers/VendorPortalController.php` | ⚠️ Partial | GET works, POST is stub |
| **Email Template** | `src/Templates/emails/vendor-request.php` | ✅ Complete | Professional HTML template ready |
| **Type Definitions** | `api/types.ts` | ✅ Partial | Vendor types defined, no API client |
| **Frontend Routes** | `assets/admin/src/routes/` | ❌ Missing | Need `/vendor/{token}` route |
| **Vendor Portal UI** | `assets/admin/src/pages/` | ❌ Missing | Need VendorPortal component |
| **API Service** | `api/queries/` | ❌ Missing | Need vendorPortal service |
| **Testing** | `tests/` | ❌ Missing | No vendor tests |
| **Documentation** | Roadmap | ✅ Documented | T2.2 tasks listed |

---

## APPENDIX A: VENDOR TYPE ENUM

Supported vendor types in the system:
1. `fire_dept` - Fire department for inspections
2. `septic_inspector` - Septic system inspection
3. `hoa_manager` - HOA management/approval
4. `title_company` - Title insurance/search
5. `appraiser` - Property appraiser
6. `inspector` - General home inspector
7. `other` - Custom/other vendor types

---

## APPENDIX B: API REQUEST/RESPONSE EXAMPLES

### GET /wp-json/ma-deal/v1/vendor/{token}
**Response (Success):**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "task_id": 456,
    "transaction_id": 789,
    "vendor_type": "inspector",
    "vendor_email": "inspector@example.com",
    "status": "opened",
    "scheduled_date": null,
    "completion_notes": null,
    "document_url": null,
    "last_opened_at": "2025-11-02T10:30:00",
    "token_expires_at": "2025-12-02T12:00:00",
    "metadata": null,
    "created_at": "2025-11-01T09:00:00"
  }
}
```

**Response (Invalid Token):**
```json
{
  "success": false,
  "data": null,
  "message": "Invalid or expired token"
}
```

### POST /wp-json/ma-deal/v1/vendor/{token} (TODO)
**Request Body (Expected):**
```json
{
  "scheduled_date": "2025-11-15",
  "scheduled_time": "10:00:00",
  "completion_notes": "Inspection completed, found minor issues",
  "document_url": "/uploads/inspection-report-2025-11-15.pdf"
}
```

**Response (Expected):**
```json
{
  "success": true,
  "data": null,
  "message": "Vendor request updated successfully"
}
```

---

**Report Generated:** November 2, 2025
**Codebase Status:** Main branch @ commit 9f0b048
