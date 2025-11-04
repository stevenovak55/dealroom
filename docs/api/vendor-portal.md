# Vendor Portal API Documentation

## Overview

The Vendor Portal API provides token-based access for external vendors to view and update their assigned tasks. No authentication is required - access is granted via a secure, time-limited token sent via email.

**Base URL:** `/wp-json/ma-deal/v1/vendor/{token}`

**Security Features:**
- 64-character hexadecimal tokens (cryptographically secure)
- Time-limited tokens (default 30 days)
- Rate limiting to prevent brute force attacks
- Automatic status tracking
- No authentication required (intentional for vendor convenience)

---

## Endpoints

### GET /wp-json/ma-deal/v1/vendor/{token}

Retrieve vendor request details using a secure token.

#### Parameters

| Parameter | Type   | Location | Required | Description                    |
|-----------|--------|----------|----------|--------------------------------|
| token     | string | URL      | Yes      | 64-character hexadecimal token |

#### Response (Success)

```json
{
  "success": true,
  "data": {
    "id": 123,
    "task_id": 456,
    "transaction_id": 789,
    "party_id": 101,
    "vendor_type": "inspector",
    "vendor_email": "inspector@example.com",
    "vendor_phone": "+1234567890",
    "token": "abc123...def456",
    "token_expires_at": "2025-12-02 12:00:00",
    "status": "opened",
    "scheduled_date": null,
    "scheduled_time": null,
    "completion_notes": null,
    "document_url": null,
    "last_opened_at": "2025-11-02 10:30:00",
    "metadata": null,
    "created_at": "2025-11-01 08:00:00",
    "updated_at": "2025-11-02 10:30:00"
  }
}
```

#### Response (Error - Invalid Token)

```json
{
  "success": false,
  "message": "Invalid or expired token",
  "code": 404
}
```

#### Response (Error - Rate Limit)

```json
{
  "success": false,
  "message": "Rate limit exceeded. Please try again later.",
  "code": 429
}
```

#### Status Transitions

- **sent** → **opened**: Automatically updated on first view
- Token expiration checked on each request
- Expired tokens return 404 error

#### Example Request

```bash
curl -X GET "https://example.com/wp-json/ma-deal/v1/vendor/abc123def456...xyz789"
```

---

### POST /wp-json/ma-deal/v1/vendor/{token}

Update vendor request with scheduling, completion, or document information.

#### Parameters

| Parameter         | Type   | Location | Required | Description                                        |
|-------------------|--------|----------|----------|----------------------------------------------------|
| token             | string | URL      | Yes      | 64-character hexadecimal token                     |
| scheduled_date    | string | Body     | No       | Scheduled date (YYYY-MM-DD format, must be future) |
| scheduled_time    | string | Body     | No       | Scheduled time (HH:MM or HH:MM:SS format)          |
| completion_notes  | string | Body     | No       | Notes about completed work (triggers completion)   |
| document_url      | string | Body     | No       | URL to uploaded document                           |
| metadata          | object | Body     | No       | Additional custom data (merged with existing)      |

#### Request Body Examples

**Scheduling an Appointment**

```json
{
  "scheduled_date": "2025-11-15",
  "scheduled_time": "14:30",
  "metadata": {
    "preferred_contact": "phone",
    "special_instructions": "Call 30 minutes before arrival"
  }
}
```

**Completing a Task**

```json
{
  "completion_notes": "Inspection completed. Found minor issues with electrical panel. Report attached.",
  "document_url": "https://example.com/wp-content/uploads/2025/11/inspection-report.pdf"
}
```

**Updating Document Only**

```json
{
  "document_url": "https://example.com/wp-content/uploads/2025/11/updated-report.pdf"
}
```

#### Response (Success)

```json
{
  "success": true,
  "data": {
    "id": 123,
    "status": "scheduled",
    "scheduled_date": "2025-11-15",
    "scheduled_time": "14:30:00",
    "completion_notes": null,
    "document_url": null,
    "metadata": "{\"preferred_contact\":\"phone\",\"special_instructions\":\"Call 30 minutes before arrival\"}",
    "updated_at": "2025-11-02 10:45:00"
  },
  "message": "Vendor request updated successfully"
}
```

#### Response (Error - Invalid Data)

```json
{
  "success": false,
  "message": "Invalid date format. Use YYYY-MM-DD",
  "code": 400
}
```

#### Response (Error - Already Completed)

```json
{
  "success": false,
  "message": "This vendor request cannot be modified (status: completed)",
  "code": 400
}
```

#### Validation Rules

**scheduled_date:**
- Must match format: `YYYY-MM-DD`
- Must be a future date
- Automatically updates status to `scheduled`

**scheduled_time:**
- Must match format: `HH:MM` or `HH:MM:SS`
- Only validated if provided

**completion_notes:**
- Cannot be empty or whitespace only
- Automatically updates status to `completed`
- Use sanitize_textarea_field for safety

**document_url:**
- Must be a valid URL format
- Empty string allowed (clears document)
- Sanitized with esc_url_raw

**metadata:**
- Must be a valid JSON object or array
- Merged with existing metadata (not replaced)
- Existing keys are overwritten by new values

#### Status State Machine

```
sent → opened (automatic on first GET)
     ↓
  opened → scheduled (when scheduled_date provided)
     ↓
 scheduled → completed (when completion_notes provided)
     ↓
 completed (final state)

Any state → expired (automatic when token expires)
Any state → cancelled (admin only, not via API)
```

#### Restrictions

- Cannot update requests with status: `completed`, `cancelled`, or `expired`
- At least one field must be provided for update
- Future-dated scheduling required
- Rate limiting applies (prevents abuse)

#### Example Requests

**cURL - Schedule Appointment**

```bash
curl -X POST "https://example.com/wp-json/ma-deal/v1/vendor/abc123def456...xyz789" \
  -H "Content-Type: application/json" \
  -d '{
    "scheduled_date": "2025-11-15",
    "scheduled_time": "14:30"
  }'
```

**cURL - Complete Task**

```bash
curl -X POST "https://example.com/wp-json/ma-deal/v1/vendor/abc123def456...xyz789" \
  -H "Content-Type: application/json" \
  -d '{
    "completion_notes": "Inspection completed successfully.",
    "document_url": "https://example.com/uploads/report.pdf"
  }'
```

**JavaScript - Fetch API**

```javascript
const updateVendorRequest = async (token, data) => {
  const response = await fetch(
    `https://example.com/wp-json/ma-deal/v1/vendor/${token}`,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    }
  );

  return await response.json();
};

// Usage
const result = await updateVendorRequest('abc123...', {
  scheduled_date: '2025-11-15',
  scheduled_time: '14:30',
});
```

---

## Vendor Types

The system supports the following vendor types:

| Type             | Description                      |
|------------------|----------------------------------|
| fire_dept        | Fire department inspection       |
| septic_inspector | Septic system inspection         |
| hoa_manager      | HOA management services          |
| title_company    | Title company services           |
| appraiser        | Property appraisal               |
| inspector        | General home inspection          |
| other            | Custom vendor types              |

---

## Rate Limiting

**Limits:**
- Public vendor portal: Strict rate limiting applied
- Prevents brute force token guessing attacks
- Configurable per environment

**Headers:**
- Rate limit status may be included in response headers
- Exceeded limits return HTTP 429 status

---

## Security Considerations

1. **Token Generation:**
   - 64 characters = 256 bits of entropy
   - Cryptographically secure random generation
   - One-time use recommended (can be regenerated)

2. **Token Storage:**
   - Stored in database (not hashed - used for lookup)
   - Expires after configurable period (default 30 days)
   - No password or additional authentication required

3. **Rate Limiting:**
   - Prevents brute force token guessing
   - Even 64-char tokens can be attacked with enough time
   - Rate limits make attacks impractical

4. **Public Access:**
   - Intentionally no authentication required
   - Vendors don't need to create accounts
   - Token provides all necessary access control

5. **Audit Trail:**
   - All access logged via events system
   - `last_opened_at` timestamp tracked
   - Status changes logged with metadata

---

## Event Logging

All vendor portal actions are logged to the events table:

```json
{
  "entity_type": "vendor_request",
  "entity_id": 123,
  "event_type": "updated",
  "event_data": {
    "previous_status": "opened",
    "new_status": "scheduled",
    "updates": ["scheduled_date", "scheduled_time"]
  },
  "new_value": {
    "scheduled_date": "2025-11-15",
    "scheduled_time": "14:30:00"
  },
  "transaction_id": 789
}
```

---

## Error Codes

| Code | Meaning              | Common Causes                                          |
|------|----------------------|--------------------------------------------------------|
| 400  | Bad Request          | Invalid date/time format, empty notes, invalid metadata|
| 404  | Not Found            | Invalid token, expired token                           |
| 429  | Too Many Requests    | Rate limit exceeded                                    |
| 500  | Internal Server Error| Database update failure                                |

---

## Related Documentation

- [Authentication API](./authentication.md)
- [Two-Factor Authentication API](./two-factor-auth.md)
- [Email Templates](../templates/email/)
- [Vendor Service](../../src/Services/VendorService.php)

---

## Changelog

### 2025-11-02 - Initial Implementation (T2.2.1)
- Implemented POST endpoint for vendor request updates
- Added validation for scheduling data
- Added completion notes handling
- Added document URL storage
- Added metadata merge functionality
- Added status state machine
- Added comprehensive error handling
- Created API documentation

### Future Enhancements
- Email notifications on status changes (T2.2.5)
- File upload directly to endpoint (currently URL only)
- Token refresh/extension capability
- Webhook notifications for status changes
- Multi-language support for vendor portal
