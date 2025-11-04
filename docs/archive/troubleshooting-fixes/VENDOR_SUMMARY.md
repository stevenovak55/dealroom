# Vendor Functionality - Quick Reference

## Current Implementation Status

### What's Built (Ready to Use)
- ✅ Database schema with vendor_requests table (7 vendor types)
- ✅ VendorService (token generation, validation, ICS calendar)
- ✅ VendorRequest model and repository
- ✅ VendorPortalController (GET endpoint working)
- ✅ Professional vendor request email template
- ✅ Rate limiting for public portal
- ✅ Type definitions in TypeScript

### What's Missing (Phase 2.2)
- ❌ Vendor portal UI (React components)
- ❌ Vendor dashboard page
- ❌ Document upload handling
- ❌ Scheduling/calendar features
- ❌ Completion form
- ❌ API service client

## File Locations

| Component | Path |
|-----------|------|
| Database Schema | `/ma-deal-room/database/migrations/001_initial_schema.sql` |
| VendorService | `/ma-deal-room/src/Services/VendorService.php` |
| VendorRequest Model | `/ma-deal-room/src/Models/VendorRequest.php` |
| VendorPortalController | `/ma-deal-room/src/REST/Controllers/VendorPortalController.php` |
| Email Template | `/ma-deal-room/src/Templates/emails/vendor-request.php` |
| Type Definitions | `/ma-deal-room/assets/admin/src/api/types.ts` |

## Key Endpoints

- `GET /wp-json/ma-deal/v1/vendor/{token}` - Retrieve vendor request ✅
- `POST /wp-json/ma-deal/v1/vendor/{token}` - Update vendor request ⚠️ (stub)

## Database Table

```
wp_ma_deal_vendor_requests
├── id (primary key)
├── task_id (FK to wp_ma_deal_tasks)
├── transaction_id (FK to wp_ma_deal_transactions)
├── party_id (FK to wp_ma_deal_parties)
├── vendor_type (enum: fire_dept, septic_inspector, hoa_manager, title_company, appraiser, inspector, other)
├── vendor_email
├── vendor_phone
├── token (64-char hex, unique, secure)
├── token_expires_at
├── status (sent, opened, scheduled, completed, expired, cancelled)
├── scheduled_date
├── scheduled_time
├── completion_notes
├── document_url
├── last_opened_at
├── metadata (JSON)
└── timestamps
```

## Vendor Type Support

1. `fire_dept` - Fire department
2. `septic_inspector` - Septic system inspection
3. `hoa_manager` - HOA management
4. `title_company` - Title company
5. `appraiser` - Property appraiser
6. `inspector` - Home inspector
7. `other` - Custom vendors

## API Example

### GET Request
```bash
curl https://example.com/wp-json/ma-deal/v1/vendor/abc123def456...
```

### Response
```json
{
  "success": true,
  "data": {
    "id": 123,
    "task_id": 456,
    "transaction_id": 789,
    "vendor_type": "inspector",
    "vendor_email": "vendor@example.com",
    "status": "opened",
    "token_expires_at": "2025-12-02T12:00:00",
    "last_opened_at": "2025-11-02T10:30:00"
  }
}
```

## Integration Points

1. **Task Assignment** - Tasks can have `owner_role: 'vendor'`
2. **Email Notifications** - Vendor request email template ready
3. **Event Tracking** - Vendor actions logged in events table
4. **Rate Limiting** - Protected against brute force attacks

## Next Steps

To complete the vendor portal (Phase 2.2):

1. Create vendor portal React page with token-based routing
2. Implement POST endpoint for scheduling and document upload
3. Add vendor dashboard showing open requests
4. Build scheduling/calendar UI
5. Add completion form for notes and document submission
6. Create API service client for frontend
7. Add comprehensive tests

## Security Notes

- Tokens are 64-char random hex strings (cryptographically secure)
- Tokens expire (default 30 days, configurable)
- No authentication required for vendors (intentional)
- Rate limiting prevents brute force attacks
- Status tracking provides audit trail
- CORS-safe for external access

## See Also

- Full report: `VENDOR_FUNCTIONALITY_EXPLORATION.md`
- Roadmap: `DEVELOPMENT_ROADMAP.md` (search for "T2.2")
