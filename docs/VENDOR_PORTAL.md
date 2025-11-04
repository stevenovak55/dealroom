# MA Deal Room - Vendor Portal

**Last Updated**: 2025-10-30

The Vendor Portal provides a public-facing interface for external vendors (fire departments, inspectors, HOAs, attorneys) to respond to service requests without requiring WordPress credentials.

---

## Overview

The vendor portal allows:
- **Public access** via signed URLs with expiration
- **Vendors** to view request details (property address, preferred dates, notes)
- **Response submission** (accept, decline, request more info)
- **Scheduling** with date/time selection
- **Secure communication** without email back-and-forth

---

## Architecture

### Signed URL System

**URL Format**:
```
https://your-site.com/vendor-portal/?token=ABC123XYZ789
```

**Token Properties**:
- **Length**: 64 characters (random alphanumeric)
- **Expiration**: 7 days by default
- **Single-use**: Optional (can be configured)
- **Database storage**: `wp_ma_deal_vendor_requests` table

**Token Generation** (PHP):
```php
$token = bin2hex(random_bytes(32)); // 64-character hex string
$expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));

// Store in database with request details
$wpdb->insert($table, [
    'token' => $token,
    'task_id' => $task_id,
    'vendor_email' => $vendor_email,
    'token_expires_at' => $expires_at,
    'status' => 'pending'
]);
```

---

## REST API Endpoints

### Get Vendor Request

**Endpoint**: `GET /wp-json/ma-deal/v1/vendor/{token}`

**Authentication**: None (public endpoint with signed URL)

**Response**:
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

### Submit Vendor Response

**Endpoint**: `POST /wp-json/ma-deal/v1/vendor/{token}`

**Request Body**:
```json
{
  "response": "accepted",
  "selectedDate": "2025-11-20",
  "selectedTime": "10:00",
  "notes": "Inspection confirmed for November 20 at 10am",
  "vendorName": "Fire Inspector Jane Doe",
  "vendorPhone": "(617) 555-9999"
}
```

**Response Types**:
- `accepted` - Vendor accepts request (requires date/time)
- `declined` - Vendor declines request
- `need_info` - Vendor needs more information

**Response**:
```json
{
  "success": true,
  "data": {
    "request_id": 15,
    "status": "accepted",
    "scheduled_date": "2025-11-20 10:00:00"
  },
  "message": "Response recorded. Confirmation email sent to agent."
}
```

---

## Frontend (HTML Page)

**File**: `ma-deal-room/assets/public/vendor-portal.html`

**Features**:
- Responsive design (mobile-friendly)
- Loads request details via REST API
- Dynamic form based on response type
- Client-side validation
- Success/error alerts
- Token expiration handling

**JavaScript API Client**:
```javascript
// Load request
const response = await fetch(`${apiUrl}/vendor/${token}`);
const data = await response.json();

// Submit response
await fetch(`${apiUrl}/vendor/${token}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(formData)
});
```

---

## PHP Template Integration

**File**: `ma-deal-room/templates/vendor-portal.php`

**Purpose**: Serves the HTML page within WordPress context

**Usage**: Add to theme or register as custom page template

**WordPress Integration**:
- Verifies token parameter exists
- Injects WordPress REST API URL
- Handles error states (invalid token, template not found)

---

## Workflow

1. **Agent creates task** that requires vendor action (e.g., Title 5 Septic Inspection)
2. **System generates signed URL** with 7-day expiration token
3. **Email sent to vendor** with link to portal
4. **Vendor clicks link** → Portal page loads
5. **Portal fetches request details** via REST API (using token)
6. **Vendor fills out form** (accept/decline + date/time)
7. **Portal submits response** to REST API
8. **System records response** and emails agent confirmation
9. **Task automatically updated** with vendor response

---

## Email Template Example

**Subject**: MA Deal Room: Inspection Request

**Body**:
```
Hello,

You have a new service request from MA Deal Room:

Property: 123 Main St, Boston, MA 02101
Type: Smoke & CO Detector Inspection
Preferred Dates: Nov 20-22, 2025

Please respond to this request:
https://your-site.com/vendor-portal/?token=ABC123XYZ789

This link expires on November 27, 2025.

Contact: John Agent
Phone: (617) 555-1234
Email: john@realty.com

Thank you,
MA Deal Room
```

---

## Security Considerations

### Token Security
- ✅ **Cryptographically random** (bin2hex + random_bytes)
- ✅ **Long tokens** (64 characters)
- ✅ **Expiration enforced** (default 7 days)
- ✅ **Stored hashed** (future enhancement)
- ✅ **Rate limiting** (future enhancement)

### Public Endpoint Risks
- ⚠️ **No authentication** - Anyone with token can access
- ⚠️ **Token in URL** - Can leak via referrer headers
- ⚠️ **Email interception** - If email compromised, token compromised

### Mitigation Strategies
- Use HTTPS to prevent man-in-the-middle
- Short expiration window (7 days)
- One-time use tokens (future enhancement)
- IP restriction (future enhancement)
- Vendor email verification (future enhancement)

---

## Future Enhancements

**v0.3.0 (Q2 2026)**:
- SMS notifications with portal link
- Calendar invite (.ics file) generation
- Document upload for certificates
- E-signature integration
- One-time use tokens
- Email verification before response submission

**v0.4.0 (Q3 2026)**:
- Vendor accounts (persistent login)
- Vendor dashboard (view all requests)
- Recurring vendor preferences
- Automated pricing/quoting
- Payment integration

---

## Troubleshooting

### "Invalid Link" Error
- Token expired (> 7 days old)
- Token not found in database
- Missing token parameter in URL

**Solution**: Agent must generate and send new portal link

### Portal Not Loading
- Check WordPress permalinks are enabled
- Verify REST API is accessible (`/wp-json/` responds)
- Check for plugin conflicts
- Clear browser cache

### Response Not Submitting
- Check browser console for JavaScript errors
- Verify REST API endpoint is accessible
- Check CORS settings if on subdomain
- Ensure VendorPortalController is registered

---

## Development

### Local Testing

**1. Generate test token**:
```bash
wp ma-deal vendor:generate-token --task-id=1 --vendor-email=test@example.com
```

**2. Visit portal**:
```
http://localhost:8080/vendor-portal/?token=YOUR_TOKEN
```

**3. Test API directly**:
```bash
curl http://localhost:8080/wp-json/ma-deal/v1/vendor/YOUR_TOKEN
```

---

**Status**: Phase 9 stub implementation complete. Basic vendor portal functional with signed URLs, REST API integration, and responsive HTML interface.

