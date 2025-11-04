# MA Deal Room REST API Reference

**Version**: 1.0.0
**Base URL**: `/wp-json/ma-deal/v1/`
**Authentication**: WordPress REST API nonce (session-based)
**Format**: JSON

---

## Overview

The MA Deal Room REST API provides programmatic access to all transaction management features. All endpoints follow RESTful conventions with standard HTTP verbs.

### Base URL
```
https://your-site.com/wp-json/ma-deal/v1/
```

### Authentication

Currently uses WordPress REST API nonce authentication (cookie-based). Future versions will support JWT tokens for mobile apps.

**Headers**:
```
X-WP-Nonce: {nonce-from-wp-localize-script}
Content-Type: application/json
```

### Response Format

**Success Response** (200 OK):
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation completed successfully"
}
```

**Error Response** (4xx/5xx):
```json
{
  "success": false,
  "error": {
    "code": "invalid_transaction",
    "message": "Transaction not found",
    "data": { "transaction_id": 123 }
  }
}
```

---

## Transactions

### List Transactions
```http
GET /wp-json/ma-deal/v1/transactions
```

**Query Parameters**:
- `status` (string): Filter by status (active, pending, closed, cancelled)
- `page` (int): Page number (default: 1)
- `per_page` (int): Results per page (default: 20, max: 100)
- `sort` (string): Sort field (closing_date, created_at)
- `order` (string): Sort direction (asc, desc)

**Example Request**:
```bash
curl -X GET 'https://your-site.com/wp-json/ma-deal/v1/transactions?status=active&sort=closing_date&order=asc' \
  -H 'X-WP-Nonce: abc123'
```

**Example Response**:
```json
{
  "success": true,
  "data": {
    "transactions": [
      {
        "transaction_id": 1,
        "account_id": 1,
        "property_address": "123 Main St",
        "property_city": "Boston",
        "property_state": "MA",
        "property_zip": "02101",
        "property_type": "SFH",
        "status": "active",
        "ps_date": "2025-11-15",
        "closing_date": "2025-12-20",
        "created_at": "2025-10-30T10:00:00Z",
        "task_summary": {
          "total": 20,
          "completed": 5,
          "pending": 12,
          "overdue": 3
        }
      }
    ],
    "pagination": {
      "total": 45,
      "page": 1,
      "per_page": 20,
      "total_pages": 3
    }
  }
}
```

---

### Get Transaction
```http
GET /wp-json/ma-deal/v1/transactions/{id}
```

**Parameters**:
- `id` (int, required): Transaction ID

**Example Request**:
```bash
curl -X GET 'https://your-site.com/wp-json/ma-deal/v1/transactions/1' \
  -H 'X-WP-Nonce: abc123'
```

**Example Response**:
```json
{
  "success": true,
  "data": {
    "transaction_id": 1,
    "property_address": "123 Main St",
    "property_type": "SFH",
    "property_metadata": {
      "has_septic": true,
      "year_built": 1965,
      "bedrooms": 3,
      "bathrooms": 2
    },
    "status": "active",
    "listing_date": "2025-10-15",
    "offer_date": "2025-11-01",
    "ps_date": "2025-11-15",
    "closing_date": "2025-12-20",
    "parties": [ ... ],
    "tasks": [ ... ]
  }
}
```

---

### Create Transaction
```http
POST /wp-json/ma-deal/v1/transactions
```

**Request Body**:
```json
{
  "property_address": "123 Main St",
  "property_city": "Boston",
  "property_state": "MA",
  "property_zip": "02101",
  "property_type": "SFH",
  "property_metadata": {
    "has_septic": true,
    "year_built": 1965,
    "bedrooms": 3
  },
  "template_id": "sfh_septic",
  "ps_date": "2025-11-15",
  "closing_date": "2025-12-20"
}
```

**Example Response**:
```json
{
  "success": true,
  "data": {
    "transaction_id": 123,
    "property_address": "123 Main St",
    "status": "active",
    "tasks_created": 27
  },
  "message": "Transaction created successfully with 27 tasks"
}
```

---

### Update Transaction
```http
PUT /wp-json/ma-deal/v1/transactions/{id}
```

**Request Body**: Same fields as Create, all optional

---

### Delete Transaction
```http
DELETE /wp-json/ma-deal/v1/transactions/{id}
```

**Response**:
```json
{
  "success": true,
  "message": "Transaction deleted successfully"
}
```

---

### Apply Template to Transaction
```http
POST /wp-json/ma-deal/v1/transactions/{id}/apply-template
```

Apply an additional template to an existing transaction, generating new tasks based on the template configuration.

**Request Body**:
```json
{
  "template_id": 2
}
```

**Request Parameters**:
- `template_id` (integer, required): The ID of the template to apply

**Security**:
- Validates user has access to the transaction's account
- Validates template ownership (must be system template or owned by same account)

**Example Request**:
```bash
curl -X POST 'https://your-site.com/wp-json/ma-deal/v1/transactions/123/apply-template' \
  -H 'X-WP-Nonce: abc123' \
  -H 'Content-Type: application/json' \
  -d '{"template_id": 2}'
```

**Example Response**:
```json
{
  "success": true,
  "data": {
    "message": "Successfully applied template \"Condo Unit\" and created 23 tasks",
    "tasks_created": 23,
    "task_ids": [201, 202, 203, ...]
  }
}
```

**Features**:
- Evaluates conditional logic (applies_if expressions)
- Calculates relative due dates based on transaction milestones
- Automatically assigns tasks to parties when available
- Wrapped in database transaction for atomicity
- Logs audit event for compliance

**Error Responses**:
- 400: Template ID not provided
- 403: No permission to access transaction or template
- 404: Transaction or template not found
- 500: Failed to create tasks

---

## Tasks

### List Tasks
```http
GET /wp-json/ma-deal/v1/tasks
```

**Query Parameters**:
- `transaction_id` (int): Filter by transaction
- `status` (string): pending, in_progress, completed, blocked, skipped, cancelled
- `assignee_role` (string): Seller, ListingAgent, BuyerAgent, etc.
- `overdue` (boolean): true to show only overdue tasks
- `page`, `per_page`, `sort`, `order` (same as transactions)

**Example Request**:
```bash
curl -X GET 'https://your-site.com/wp-json/ma-deal/v1/tasks?transaction_id=1&status=pending&sort=due_at' \
  -H 'X-WP-Nonce: abc123'
```

---

### Get Task
```http
GET /wp-json/ma-deal/v1/tasks/{id}
```

---

### Complete Task
```http
POST /wp-json/ma-deal/v1/tasks/{id}/complete
```

**Request Body** (optional):
```json
{
  "notes": "Certificate obtained from fire department",
  "completed_by_user_id": 5
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "task_id": 42,
    "status": "completed",
    "completed_at": "2025-11-20T14:30:00Z",
    "next_tasks_unblocked": 2
  },
  "message": "Task completed successfully. 2 dependent tasks are now available."
}
```

---

### Skip Task
```http
POST /wp-json/ma-deal/v1/tasks/{id}/skip
```

**Request Body**:
```json
{
  "reason": "Not applicable - buyer waived inspection"
}
```

---

## Templates

### List Templates
```http
GET /wp-json/ma-deal/v1/templates
```

**Query Parameters**:
- `property_type` (string): Filter by property type (SFH, Condo, Multifamily)
- `is_active` (boolean): Filter by active status

**Example Response**:
```json
{
  "success": true,
  "data": {
    "templates": [
      {
        "template_id": 1,
        "template_key": "sfh_septic",
        "title": "Single-Family Home (Septic System)",
        "description": "Complete checklist for SFH with septic...",
        "property_types": ["SFH"],
        "task_count": 27,
        "version": "1.0.0"
      }
    ]
  }
}
```

---

### Get Template
```http
GET /wp-json/ma-deal/v1/templates/{id}
```

**Response** includes full YAML template content and parsed task definitions.

---

## Reminders

### Upcoming Reminders
```http
GET /wp-json/ma-deal/v1/reminders/upcoming
```

**Query Parameters**:
- `days` (int): Number of days ahead (default: 7)
- `transaction_id` (int): Filter by transaction

**Example Response**:
```json
{
  "success": true,
  "data": {
    "reminders": [
      {
        "reminder_id": 101,
        "task_id": 42,
        "transaction_id": 1,
        "scheduled_at": "2025-11-25T09:00:00Z",
        "channel": "email",
        "recipient_email": "agent@example.com",
        "status": "pending",
        "task_title": "Schedule Smoke & CO Inspection"
      }
    ]
  }
}
```

---

## Vendor Portal (Public)

### Get Vendor Request
```http
GET /wp-json/ma-deal/v1/vendor/{token}
```

**Authentication**: None (public endpoint, uses signed URL token)

**Example Response**:
```json
{
  "success": true,
  "data": {
    "request_id": 15,
    "vendor_type": "fire_dept_smoke_cert",
    "property_address": "123 Main St, Boston, MA 02101",
    "preferred_dates": ["2025-11-20", "2025-11-21", "2025-11-22"],
    "notes": "Please call agent 24 hours before inspection",
    "contact": {
      "name": "John Agent",
      "phone": "(617) 555-1234",
      "email": "john@realty.com"
    },
    "status": "pending",
    "token_expires_at": "2025-11-27T23:59:59Z"
  }
}
```

---

### Respond to Vendor Request
```http
POST /wp-json/ma-deal/v1/vendor/{token}
```

**Request Body**:
```json
{
  "response": "accepted",
  "selected_date": "2025-11-20",
  "selected_time": "10:00 AM",
  "notes": "Inspection confirmed for November 20 at 10am",
  "vendor_contact": {
    "name": "Fire Inspector Jane Doe",
    "phone": "(617) 555-9999"
  }
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "request_id": 15,
    "status": "accepted",
    "calendar_invite_url": "https://your-site.com/vendor-calendar/abc123.ics"
  },
  "message": "Response recorded. Confirmation email sent to agent."
}
```

---

## Parties

### Add Party to Transaction
```http
POST /wp-json/ma-deal/v1/transactions/{transaction_id}/parties
```

**Request Body**:
```json
{
  "role": "SellerAttorney",
  "name": "Jane Smith, Esq.",
  "email": "jane@lawfirm.com",
  "phone": "(617) 555-7777",
  "company": "Smith & Associates Law"
}
```

---

### List Parties
```http
GET /wp-json/ma-deal/v1/transactions/{transaction_id}/parties
```

---

## Events (Audit Log)

### Get Transaction Events
```http
GET /wp-json/ma-deal/v1/transactions/{transaction_id}/events
```

**Query Parameters**:
- `event_type` (string): Filter by event type
- `page`, `per_page` (pagination)

**Example Response**:
```json
{
  "success": true,
  "data": {
    "events": [
      {
        "event_id": 501,
        "event_type": "task_completed",
        "actor_type": "user",
        "actor_id": 5,
        "entity_type": "task",
        "entity_id": 42,
        "created_at": "2025-11-20T14:30:00Z",
        "payload": {
          "task_title": "Schedule Smoke & CO Inspection",
          "completed_by": "John Agent"
        }
      }
    ]
  }
}
```

---

## Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `unauthorized` | 401 | Authentication required |
| `forbidden` | 403 | Insufficient permissions |
| `not_found` | 404 | Resource not found |
| `invalid_transaction` | 400 | Transaction ID invalid or not accessible |
| `invalid_task` | 400 | Task ID invalid or not accessible |
| `invalid_template` | 400 | Template not found or invalid |
| `validation_error` | 400 | Request validation failed |
| `duplicate_transaction` | 409 | Transaction already exists |
| `task_blocked` | 409 | Task has unmet dependencies |
| `internal_error` | 500 | Server error |

---

## Rate Limiting

Currently no rate limits enforced. Future versions will implement:
- 1000 requests per hour per account
- 100 requests per minute per IP
- Burst allowance: 20 requests per second

---

## Pagination

All list endpoints support pagination:

**Request**:
```
?page=2&per_page=50
```

**Response Headers**:
```
X-WP-Total: 127
X-WP-TotalPages: 3
```

**Response Body**:
```json
{
  "pagination": {
    "total": 127,
    "page": 2,
    "per_page": 50,
    "total_pages": 3
  }
}
```

---

## Webhooks (Future)

Planned for v0.3.0:
- `transaction.created`
- `transaction.updated`
- `transaction.closed`
- `task.completed`
- `task.overdue`
- `reminder.sent`

---

## SDKs & Client Libraries

**Official SDKs** (Planned):
- JavaScript/TypeScript (npm package)
- PHP (Composer package)
- Python (PyPI package)

**Community**:
- Coming soon

---

## Versioning

API follows semantic versioning:
- **Breaking changes**: New major version (/v2/)
- **New features**: Minor version (backward compatible)
- **Bug fixes**: Patch version

Current version will be maintained for 12 months after new major version release.

---

## Support

- **Documentation**: https://docs.madealroom.com (coming soon)
- **API Status**: https://status.madealroom.com (coming soon)
- **Issues**: GitHub repository
- **Email**: api-support@madealroom.com

---

**Last Updated**: 2025-10-30
**Changelog**: See [CHANGELOG.md](CHANGELOG.md)
