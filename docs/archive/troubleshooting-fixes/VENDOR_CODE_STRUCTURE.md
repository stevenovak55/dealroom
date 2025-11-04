# Vendor Code Structure & Dependencies

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                      VENDOR SYSTEM FLOW                         │
└─────────────────────────────────────────────────────────────────┘

1. AGENT/ADMIN CREATES VENDOR TASK
   └─> VendorRequestRepository.create()
   └─> VendorService.generateSignedUrl()
   └─> Store token in wp_ma_deal_vendor_requests
   └─> NotificationService.sendVendorRequest()
   └─> Send email with link: /vendor/{token}

2. EXTERNAL VENDOR RECEIVES EMAIL
   └─> Clicks link with token
   └─> Browser requests /vendor/{token}

3. VENDOR PORTAL (NOT YET BUILT)
   └─> Frontend: Calls GET /wp-json/ma-deal/v1/vendor/{token}
   └─> VendorPortalController.get_vendor_portal()
   └─> VendorService.validateToken()
   └─> Returns VendorRequest data
   └─> Render vendor dashboard
   └─> Show scheduling/document upload UI

4. VENDOR SUBMITS RESPONSE
   └─> Frontend: Calls POST /wp-json/ma-deal/v1/vendor/{token}
   └─> VendorPortalController.update_vendor_request() [STUB]
   └─> Validate and persist:
       ├─> scheduled_date / scheduled_time
       ├─> completion_notes
       ├─> document_url
       └─> status update
   └─> Log event in wp_ma_deal_events

5. AGENT SEES UPDATE
   └─> Dashboard shows vendor progress
   └─> Can download documents
   └─> Updates task status
```

## Class Dependencies

### VendorService
```
VendorService
├── VendorRequestRepository
│   ├── BaseRepository (parent)
│   ├── WP Database API
│   └── VendorRequest (model)
└── Uses:
    ├── date() / strtotime() for expiration
    ├── random_bytes() for tokens
    └── current_time() for timestamps
```

### VendorPortalController
```
VendorPortalController (extends BaseController)
├── VendorService
│   └── VendorRequestRepository
├── EventRepository
│   └── Event (model)
├── RateLimiter
│   └── Rate limit checks
└── Uses:
    ├── WP_REST_Request
    ├── WP_Error
    └── Rate limiting
```

### NotificationService (Partial)
```
NotificationService
├── sendVendorRequest()  [METHOD STUB]
├── EmailService
├── SMSService
└── NotificationPreferencesService
```

## Data Models & Tables

### WordPress Database Structure
```
wp_ma_deal_vendor_requests (Primary)
├── Links to wp_ma_deal_tasks (task_id FK)
├── Links to wp_ma_deal_transactions (transaction_id FK)
├── Links to wp_ma_deal_parties (party_id FK - optional)
└── Tracked in wp_ma_deal_events (entity_type = 'vendor_request')

wp_ma_deal_tasks
├── owner_role: can be 'vendor'
├── assigned_party_id: references vendor party
└── Linked tasks depend on vendor completion

wp_ma_deal_parties
├── role: can be vendor types (inspector, appraiser, etc.)
├── email: vendor contact
└── metadata: additional vendor info

wp_ma_deal_events
├── entity_type: 'vendor_request'
├── entity_id: vendor_request.id
├── event_type: created, updated, status_changed
└── Audit trail for compliance
```

## Service Registration (Dependency Injection)

### Plugin.php Container Setup
```php
// Line 184
$container->register('vendor_request_repository', function($container) {
    return new VendorRequestRepository($container->get('cache_service'));
});

// Lines 185-187
$container->register('vendor_service', function($container) {
    return new VendorService(
        $container->get('vendor_request_repository')
    );
});

// Lines 426-431
$container->register('vendor_portal_controller', function($container) {
    return new VendorPortalController(
        $container->get('vendor_service'),
        $container->get('event_repository')
    );
});

// Registered routes via register_rest_route()
// Namespace: 'ma-deal/v1'
// Base: 'vendor'
// Pattern: /wp-json/ma-deal/v1/vendor/{token}
```

## File Relationships

```
PHP Backend:
├── Core/Plugin.php
│   └─> Registers vendor services & controller
├── Services/
│   ├─> VendorService.php
│   │   └─> Uses VendorRequestRepository
│   ├─> NotificationService.php
│   │   └─> Uses vendor-request.php template
│   └─> RateLimiter.php
│       └─> Rate limits 'vendor-portal' bucket
├── REST/Controllers/
│   └─> VendorPortalController.php
│       ├─> Uses VendorService
│       ├─> Uses EventRepository
│       └─> Route: /vendor/{token}
├── Repositories/
│   └─> VendorRequestRepository.php
│       └─> Queries wp_ma_deal_vendor_requests
├── Models/
│   └─> VendorRequest.php
│       └─> Data model for vendor requests
└── Templates/emails/
    └─> vendor-request.php
        └─> HTML email template

Frontend TypeScript:
├── api/types.ts
│   ├─> Defines Event type with 'vendor_request'
│   └─> Defines Task type with vendor owner_role
├── routes/ (NOT YET BUILT)
│   └─> Should have /vendor/{token} route
├── pages/ (NOT YET BUILT)
│   └─> VendorPortal.tsx
├── components/ (NOT YET BUILT)
│   ├─> VendorDashboard.tsx
│   ├─> ScheduleForm.tsx
│   ├─> DocumentUpload.tsx
│   └─> CompletionForm.tsx
└── api/queries/ (NOT YET BUILT)
    └─> useVendor.ts (API service)

Database:
└─> migrations/001_initial_schema.sql
    └─> Creates wp_ma_deal_vendor_requests table
```

## State Transition Diagram

```
VENDOR REQUEST LIFECYCLE

Created by Agent
│
└─> Status: 'sent'
    └─> Email sent with token URL
    └─> Record: created_at, token, token_expires_at
    └─> Next: Vendor receives email

Vendor Opens Link
│
└─> Status: 'opened' (auto-update on first GET)
    └─> Record: last_opened_at
    └─> Behavior: validateToken() updates status
    └─> Next: Vendor fills out details

Vendor Schedules Service
│
└─> Status: 'scheduled' (on POST with scheduled_date)
    └─> Record: scheduled_date, scheduled_time
    └─> Behavior: POST endpoint should update
    └─> Next: Service occurs, vendor returns

Vendor Completes Service
│
└─> Status: 'completed' (on final POST)
    └─> Record: completion_notes, document_url
    └─> Behavior: POST endpoint should update
    └─> Next: Agent downloads documents, closes task

TERMINAL STATES:
├─> 'expired' (time limit exceeded)
├─> 'cancelled' (agent cancelled request)
└─> 'completed' (vendor finished)
```

## Token Security Implementation

```
SECURE TOKEN GENERATION & VALIDATION

generateSignedUrl($request_id, $expiration_days = 30)
├─> $token = bin2hex(random_bytes(32))
├─> Store in database with expiry
├─> Returns 64-char hex string
└─> Example: "a1b2c3d4e5f6...xyz" (64 chars)

validateToken($token)
├─> Look up token in database
├─> Check expiration (now < token_expires_at)
├─> If valid:
│   ├─> Update status: 'sent' -> 'opened'
│   ├─> Update last_opened_at: now
│   └─> Return VendorRequest object
└─> If invalid or expired:
    ├─> Update status: 'sent' -> 'expired'
    └─> Return null

SECURITY MEASURES:
├─> Cryptographically random (random_bytes)
├─> Long enough to prevent brute force (64 chars)
├─> Unique constraint in database
├─> Time-based expiration
├─> Rate limiting on lookups (vendor-portal bucket)
└─> No sensitive data in token itself
```

## API Flow Example

```
CLIENT REQUEST:
GET /wp-json/ma-deal/v1/vendor/a1b2c3d4...xyz

ROUTING:
WordPress REST API Router
  └─> Matches: /wp-json/ma-deal/v1/vendor/{token}
  └─> Calls: VendorPortalController->get_vendor_portal($request)

CONTROLLER EXECUTION:
get_vendor_portal($request)
  ├─> Get 'token' from URL parameter
  ├─> Check rate limit (vendor-portal bucket)
  ├─> Call $vendor_service->validateToken($token)
  │   └─> Query database
  │   └─> Check expiration
  │   └─> Update status & timestamps
  │   └─> Return VendorRequest or null
  ├─> If null: return error(404)
  └─> If valid: return success($vendor_request)

SUCCESS RESPONSE:
{
  "success": true,
  "data": {
    "id": 123,
    "task_id": 456,
    "transaction_id": 789,
    "party_id": 999,
    "vendor_type": "inspector",
    "vendor_email": "john@inspections.com",
    "vendor_phone": "617-555-1234",
    "token": "a1b2c3d4...xyz",
    "token_expires_at": "2025-12-02T12:00:00",
    "status": "opened",
    "scheduled_date": null,
    "scheduled_time": null,
    "completion_notes": null,
    "document_url": null,
    "last_opened_at": "2025-11-02T10:30:00",
    "metadata": null,
    "created_at": "2025-11-01T09:00:00",
    "updated_at": "2025-11-01T09:00:00"
  }
}

ERROR RESPONSE:
{
  "success": false,
  "data": null,
  "message": "Invalid or expired token"
}
```

## TODO Implementation Points

### VendorPortalController.update_vendor_request() (Currently Stub)

```php
// REQUIRED IMPLEMENTATION:
public function update_vendor_request(WP_REST_Request $request) {
    // 1. Get & validate token
    $token = $request->get_param('token');
    $vendor_request = $this->vendor_service->validateToken($token);
    if (!$vendor_request) {
        return $this->error('Invalid or expired token', 404);
    }

    // 2. Get request body
    $body = $request->get_json_params();

    // 3. Validate input
    // - Check scheduled_date format if provided
    // - Check scheduled_time format if provided
    // - Validate completion_notes length if provided
    // - Validate document_url/upload if provided

    // 4. Update vendor request
    // - Update: scheduled_date, scheduled_time, completion_notes, document_url
    // - Update status: 'sent' -> 'scheduled' -> 'completed'
    // - Update updated_at timestamp

    // 5. Log event
    // $this->logEvent('vendor_request', $vendor_request->id, 'updated', ...)

    // 6. Send confirmation email (optional)
    // $this->notification_service->sendVendorConfirmation(...)

    // 7. Return success
    return $this->success(null, 'Vendor request updated successfully');
}
```

### Frontend Vendor Portal Component (Not Built)

```typescript
// REQUIRED IMPLEMENTATION:
import { useParams } from 'react-router-dom';
import { VendorService } from '@/api/services/vendor';
import { VendorRequest } from '@/api/types';

export function VendorPortal() {
    const { token } = useParams<{ token: string }>();
    const [request, setRequest] = useState<VendorRequest | null>(null);
    const [status, setStatus] = useState('loading');

    useEffect(() => {
        // 1. Fetch vendor request
        VendorService.getRequest(token).then(data => {
            setRequest(data);
            setStatus('loaded');
        }).catch(() => {
            setStatus('error');
        });
    }, [token]);

    return (
        <div className="vendor-portal">
            <VendorDashboard request={request} />
            <ScheduleForm request={request} token={token} />
            <DocumentUpload request={request} token={token} />
            <CompletionForm request={request} token={token} />
        </div>
    );
}
```

---

**Last Updated:** November 2, 2025
**Scope:** Vendor functionality exploration
