# Vendor Portal Component Structure

## Component Hierarchy

```
App.tsx
└── HashRouter
    └── AppRoutes.tsx
        └── Route: /vendor/:token (PUBLIC)
            └── VendorPortal.tsx
                ├── Loading State
                │   └── RefreshCw icon + message
                ├── Error State
                │   └── AlertCircle icon + error message + retry button
                └── Success State
                    └── VendorDashboard.tsx
                        ├── Header (status badge)
                        ├── Token Expiration Warning
                        ├── Property Information Card
                        │   ├── Address
                        │   └── Transaction Type
                        ├── Vendor Information Card
                        │   ├── Vendor Type
                        │   ├── Email
                        │   └── Phone
                        ├── Scheduled Appointment Card (conditional)
                        │   └── Date + Time
                        ├── Completion Card (conditional)
                        │   ├── Completion Notes
                        │   └── Document Link
                        └── Action Forms Placeholder
                            └── "Forms will be available here" message
```

## Data Flow

```
URL with Token
    ↓
VendorPortal.tsx (extracts token from useParams)
    ↓
vendorService.getVendorRequest(token)
    ↓
axios.get('/wp-json/ma-deal-room/v1/vendor/{token}')
    ↓
Backend: VendorPortalController.php
    ↓
VendorService.php (validates token, fetches data)
    ↓
VendorRepository.php (database query with transaction join)
    ↓
Response: VendorRequest object
    ↓
VendorPortal state: setVendorRequest(data)
    ↓
VendorDashboard.tsx (displays data)
```

## State Management

### VendorPortal State
- `vendorRequest`: VendorRequest | null
- `loading`: boolean
- `error`: string | null

### VendorDashboard Props
- `vendorRequest`: VendorRequest (required)
- `onUpdate?`: () => void (optional, for future use)

## API Service

### vendorService.ts
```typescript
{
  getVendorRequest(token: string): Promise<VendorRequest>
  updateVendorRequest(token: string, data: VendorUpdateData): Promise<VendorRequest>
}
```

## Type Definitions

### VendorRequest
```typescript
{
  id: number
  task_id: number
  transaction_id: number
  party_id: number
  vendor_type: VendorType
  vendor_email: string
  vendor_phone: string
  token: string
  token_expires_at: string
  status: VendorRequestStatus
  scheduled_date: string | null
  scheduled_time: string | null
  completion_notes: string | null
  document_url: string | null
  last_opened_at: string
  metadata: string | null
  created_at: string
  updated_at: string
  transaction?: {
    property_address: string
    property_city: string
    property_state: string
    property_zip: string
    transaction_side: 'listing' | 'buyer'
  }
}
```

## Visual Design

### Color Scheme
- **Primary:** Blue (#0066CC)
- **Success:** Green (#10B981)
- **Warning:** Yellow/Amber (#F59E0B)
- **Danger:** Red (#EF4444)
- **Neutral:** Gray (#6B7280)

### Status Badge Colors
- `sent` → bg-gray-100 text-gray-800
- `opened` → bg-blue-100 text-blue-800
- `scheduled` → bg-yellow-100 text-yellow-800
- `completed` → bg-green-100 text-green-800
- `expired` → bg-red-100 text-red-800
- `cancelled` → bg-red-100 text-red-800

### Icons (Lucide React)
- **Clock:** Token expiration, expired status
- **Mail:** Sent/opened status
- **Calendar:** Scheduled status
- **CheckCircle:** Completed status
- **MapPin:** Property location
- **FileText:** Documents, vendor type
- **Phone:** Contact phone
- **AlertCircle:** Error states
- **RefreshCw:** Loading, retry

## Responsive Breakpoints

- **Mobile:** < 640px (sm)
- **Tablet:** 640px - 1024px (md, lg)
- **Desktop:** > 1024px (xl, 2xl)

### Layout Adjustments
- Max width: 4xl (56rem / 896px)
- Padding: px-4 (mobile) → px-6 (tablet) → px-8 (desktop)
- Card spacing: space-y-6 (consistent)

## Accessibility Features

1. **Semantic HTML:** Proper heading hierarchy (h1, h2)
2. **ARIA Labels:** Icons have aria-hidden or labels
3. **Color Contrast:** WCAG AA compliant
4. **Keyboard Navigation:** All interactive elements focusable
5. **Screen Reader Support:** Meaningful text alternatives
6. **Focus Indicators:** Visible focus rings

## Error Handling Strategy

### Network Errors
- Show "No response from server" message
- Provide retry button
- Display loading state during retry

### Invalid Token
- Detect "invalid" in error message
- Show specific "Invalid Link" message
- Suggest checking email URL

### Expired Token
- Detect "expired" in error message
- Show specific "Access Expired" message
- Suggest contacting agent for new link

### Generic Errors
- Show error message from server
- Provide retry option
- Log error to console for debugging

## Future Enhancements (T2.2.3, T2.2.4)

### Scheduling Form (T2.2.3)
```
VendorDashboard
└── SchedulingForm (when status = 'opened')
    ├── DatePicker
    ├── TimePicker
    ├── Submit Button
    └── Success/Error Feedback
```

### Completion Form (T2.2.4)
```
VendorDashboard
└── CompletionForm (when status = 'scheduled')
    ├── Textarea (completion notes)
    ├── Document URL Input
    ├── Submit Button
    └── Success/Error Feedback
```

## Testing Checklist

- [ ] Valid token loads vendor data
- [ ] Invalid token shows error
- [ ] Expired token shows expiration message
- [ ] Network error shows retry option
- [ ] Loading state displays during fetch
- [ ] All status types display correctly
- [ ] Token expiration countdown works
- [ ] Property info displays correctly
- [ ] Vendor info displays correctly
- [ ] Scheduled info displays (when scheduled)
- [ ] Completion info displays (when completed)
- [ ] Mobile responsive design works
- [ ] Icons render correctly
- [ ] Colors match design system
- [ ] Accessibility features work

