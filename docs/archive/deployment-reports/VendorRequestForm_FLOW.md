# VendorRequestForm Component Flow Diagram

## User Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    User Interaction                          │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
            ┌──────────────────────────────┐
            │   Click "Request Vendor"     │
            │         Button               │
            └──────────────────────────────┘
                            │
                            ▼
            ┌──────────────────────────────┐
            │   VendorRequestForm Opens    │
            │   isOpen = true              │
            └──────────────────────────────┘
                            │
                ┌───────────┴──────────┐
                │                      │
                ▼                      ▼
    ┌─────────────────────┐   ┌────────────────┐
    │   taskId provided   │   │ tasks provided │
    │   (hide dropdown)   │   │ (show dropdown)│
    └─────────────────────┘   └────────────────┘
                │                      │
                └──────────┬───────────┘
                           ▼
            ┌──────────────────────────────┐
            │   User fills form:           │
            │   - Task (if needed)         │
            │   - Vendor Type              │
            │   - Vendor Email             │
            │   - Vendor Phone (optional)  │
            └──────────────────────────────┘
                           │
                           ▼
            ┌──────────────────────────────┐
            │   User clicks "Create &      │
            │   Send Invitation"           │
            └──────────────────────────────┘
                           │
                           ▼
            ┌──────────────────────────────┐
            │   Client-side Validation     │
            └──────────────────────────────┘
                           │
                ┌──────────┴──────────┐
                │                     │
          Errors│                     │Valid
                ▼                     ▼
    ┌─────────────────────┐   ┌────────────────────┐
    │ Show inline errors  │   │ Submit to API      │
    │ - Red borders       │   │ - Loading state    │
    │ - Error messages    │   │ - Disable inputs   │
    └─────────────────────┘   └────────────────────┘
                                       │
                            ┌──────────┴──────────┐
                            │                     │
                      Error │                     │ Success
                            ▼                     ▼
                ┌─────────────────────┐   ┌────────────────────┐
                │ Show error toast    │   │ Show success state │
                │ Keep form open      │   │ - Checkmark        │
                └─────────────────────┘   │ - Portal URL       │
                                          │ - Copy button      │
                                          └────────────────────┘
                                                   │
                                        ┌──────────┴──────────┐
                                        │                     │
                                        ▼                     ▼
                            ┌────────────────────┐   ┌───────────────┐
                            │ "Send to Another"  │   │    "Done"     │
                            │ - Reset form       │   │ - Close modal │
                            │ - Keep modal open  │   └───────────────┘
                            └────────────────────┘
```

## State Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    Component State                           │
└─────────────────────────────────────────────────────────────┘

Initial State (Form)
├── formData: { task_id, vendor_type, vendor_email, vendor_phone }
├── errors: {}
├── createdVendorRequest: null
└── copySuccess: false

User Input
├── handleChange() → Updates formData
└── Clear corresponding error

Submit
├── validate() → Sets errors or returns true
└── createMutation.mutate()

Loading
├── createMutation.isPending = true
└── Disable all inputs and buttons

Success
├── createMutation.onSuccess()
├── Set createdVendorRequest
├── Show success UI
├── Call onSuccess() callback
└── Invalidate queries

Error
├── createMutation.onError()
└── Show error toast

Copy URL
├── handleCopyUrl()
├── setCopySuccess(true)
├── Show "Copied!" feedback
└── Reset after 2 seconds

Send Another
├── handleSendAnother()
├── Reset formData (keep task_id if provided)
├── Clear createdVendorRequest
└── Show form again

Close
├── handleClose()
└── Call onClose() callback
```

## API Integration Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    API Communication                         │
└─────────────────────────────────────────────────────────────┘

Form Submit
    │
    ▼
vendorRequestService.create(data)
    │
    ├─── POST /vendor-requests
    │    {
    │      transaction_id: number,
    │      task_id: number,
    │      vendor_type: string,
    │      vendor_email: string,
    │      vendor_phone?: string
    │    }
    │
    ▼
Response
    │
    ├─── Success (201)
    │    {
    │      id: number,
    │      token: string,
    │      portal_url: string,
    │      ...vendorRequest fields
    │    }
    │
    └─── Error (4xx/5xx)
         {
           error: {
             code: string,
             message: string
           }
         }

Query Invalidation
    │
    ▼
queryClient.invalidateQueries(['vendor-requests'])
    │
    └─── Refetch all vendor request lists
```

## Component Tree

```
<VendorRequestForm>
  │
  ├── <Modal> (shared component)
  │   ├── Backdrop (click to close)
  │   ├── Modal Container
  │   │   ├── Header
  │   │   │   ├── Title
  │   │   │   └── Close Button (X)
  │   │   │
  │   │   └── Content
  │   │       │
  │   │       ├── Success State (if createdVendorRequest)
  │   │       │   ├── Success Icon
  │   │       │   ├── Confirmation Message
  │   │       │   ├── Portal URL Input (readonly)
  │   │       │   ├── Copy Button
  │   │       │   └── <ModalFooter>
  │   │       │       ├── "Send to Another Vendor"
  │   │       │       └── "Done"
  │   │       │
  │   │       └── Form State (else)
  │   │           ├── <form>
  │   │           │   ├── Task Select (conditional)
  │   │           │   │   ├── <label>
  │   │           │   │   ├── <select>
  │   │           │   │   └── Error Message
  │   │           │   │
  │   │           │   ├── Vendor Type Select
  │   │           │   │   ├── <label> + Icon
  │   │           │   │   ├── <select>
  │   │           │   │   └── Error Message
  │   │           │   │
  │   │           │   ├── Vendor Email Input
  │   │           │   │   ├── <label> + Icon
  │   │           │   │   ├── <input type="email">
  │   │           │   │   ├── Error Message
  │   │           │   │   └── Help Text
  │   │           │   │
  │   │           │   ├── Vendor Phone Input
  │   │           │   │   ├── <label> + Icon
  │   │           │   │   ├── <input type="tel">
  │   │           │   │   └── Error Message
  │   │           │   │
  │   │           │   └── <ModalFooter>
  │   │           │       ├── Cancel Button
  │   │           │       └── Submit Button (with loading)
  │   │           │
  │   │           └── Toast Notifications (global)
  │   │
  │   └── [Keyboard Listeners - Escape to close]
  │
  └── [React Query - useMutation hook]
```

## Validation Flow

```
validate()
  │
  ├── Check task_id (if applicable)
  │   ├── Missing? → errors.task_id = "Please select a task"
  │   └── Valid → Continue
  │
  ├── Check vendor_type
  │   ├── Empty? → errors.vendor_type = "Please select a vendor type"
  │   └── Valid → Continue
  │
  ├── Check vendor_email
  │   ├── Empty? → errors.vendor_email = "Email is required"
  │   ├── Invalid format? → errors.vendor_email = "Invalid email format"
  │   └── Valid → Continue
  │
  └── Check vendor_phone (if provided)
      ├── Invalid format? → errors.vendor_phone = "Invalid phone format"
      └── Valid → Continue

Return: Object.keys(errors).length === 0
```

## Icons Used

```
Form Icons (lucide-react)
├── Briefcase     → Vendor Type field
├── Mail          → Vendor Email field
├── Phone         → Vendor Phone field
├── Loader2       → Loading state (spinning)
├── CheckCircle   → Success state & Copy success
└── Copy          → Copy URL button
```

## CSS Classes (Tailwind)

```
Colors
├── Primary:   bg-blue-600, hover:bg-blue-700
├── Secondary: bg-gray-600, hover:bg-gray-700
├── Success:   bg-green-600, text-green-600
├── Error:     border-red-300, text-red-600
└── Info:      text-gray-500

States
├── Disabled:  bg-gray-100, cursor-not-allowed, opacity-50
├── Focus:     focus:ring-2, focus:ring-blue-500
└── Hover:     hover:bg-gray-50

Spacing
├── Padding:   p-4, px-3, py-2
├── Margin:    mt-1, mb-2, gap-2
└── Rounded:   rounded-md, rounded-full
```
