# DocuSign Components

This directory contains React TypeScript components for managing DocuSign envelopes in the MA Deal Room application.

## Components

### 1. EnvelopeStatus

Display envelope status on transaction detail pages.

**Props:**
- `envelopeId: string` - The DocuSign envelope ID
- `onRefresh?: () => void` - Optional callback when data refreshes

**Features:**
- Color-coded status chip (completed=green, sent=blue, voided=red, etc.)
- Recipients list with completion status
- Signature timestamps
- View in DocuSign link
- Resend/void action buttons (admin only)

**Usage:**
```tsx
import { EnvelopeStatus } from '@/components/DocuSign';

<EnvelopeStatus 
  envelopeId="abc123-envelope-id" 
  onRefresh={() => console.log('Data refreshed')}
/>
```

---

### 2. DocuSignEnvelopeList

List all envelopes with pagination, filtering, and search.

**Props:**
- `transactionId?: number` - Optional filter by transaction ID

**Features:**
- Paginated table (20 items per page)
- Filter by status dropdown
- Search by subject/message
- Click row to view details in modal
- Displays: Subject, Status, Transaction, Sent Date, Completed Date, Actions

**Usage:**
```tsx
import { DocuSignEnvelopeList } from '@/components/DocuSign';

// All envelopes
<DocuSignEnvelopeList />

// Filtered by transaction
<DocuSignEnvelopeList transactionId={123} />
```

---

### 3. DocuSignEnvelopeDetail

View envelope details in a modal dialog.

**Props:**
- `envelopeId: string` - The envelope ID to display
- `onClose: () => void` - Callback when modal closes

**Features:**
- Modal dialog with full envelope details
- Recipients table with name, email, role, routing order, status
- Document list
- Timeline of events (sent, delivered, signed, completed)
- Action buttons: Resend, Void (with confirmation)

**Usage:**
```tsx
import { DocuSignEnvelopeDetail } from '@/components/DocuSign';

const [selectedId, setSelectedId] = useState<string | null>(null);

{selectedId && (
  <DocuSignEnvelopeDetail
    envelopeId={selectedId}
    onClose={() => setSelectedId(null)}
  />
)}
```

---

### 4. DocuSignTemplateSelector

Select DocuSign template for envelope creation.

**Props:**
- `onSelect: (templateId: string) => void` - Callback when template selected
- `transactionId?: number` - Optional for preview functionality

**Features:**
- Search input for template name
- Grid of template cards showing: name, description, last modified, owner
- Preview button (requires transactionId)
- Select button on each card
- Visual indication of selected template

**Usage:**
```tsx
import { DocuSignTemplateSelector } from '@/components/DocuSign';

<DocuSignTemplateSelector
  onSelect={(templateId) => {
    console.log('Selected template:', templateId);
    // Create envelope with this template...
  }}
  transactionId={123}
/>
```

---

## API Service

All components use the `docusignService` from `/api/docusignService.ts`:

```tsx
import { docusignService } from '@/api/docusignService';

// Available methods:
docusignService.getEnvelope(envelopeId)
docusignService.listEnvelopes(transactionId?)
docusignService.resendEnvelope(envelopeId)
docusignService.voidEnvelope(envelopeId, reason)
docusignService.listTemplates(search?)
docusignService.previewTemplate(templateId, transactionId)
```

---

## Styling

All components use Material-UI (MUI) v7 components for consistency with the existing DocuSign settings pages:

- `@mui/material` - UI components
- `@mui/icons-material` - Icons
- Follows MUI theming and styling patterns

---

## Example Integration

### Transaction Detail Page

```tsx
import { EnvelopeStatus } from '@/components/DocuSign';

function TransactionDetail({ transactionId }) {
  const [envelopeId, setEnvelopeId] = useState<string | null>(null);

  // Load envelope ID for this transaction...

  return (
    <Box>
      <Typography variant="h5">Transaction Details</Typography>
      
      {envelopeId && (
        <Box sx={{ mt: 3 }}>
          <Typography variant="h6" gutterBottom>DocuSign Status</Typography>
          <EnvelopeStatus envelopeId={envelopeId} />
        </Box>
      )}
    </Box>
  );
}
```

### Envelopes Management Page

```tsx
import { DocuSignEnvelopeList } from '@/components/DocuSign';

function EnvelopesPage() {
  return (
    <Box>
      <Typography variant="h4" gutterBottom>DocuSign Envelopes</Typography>
      <DocuSignEnvelopeList />
    </Box>
  );
}
```

### Create Envelope Wizard

```tsx
import { DocuSignTemplateSelector } from '@/components/DocuSign';

function CreateEnvelopeWizard({ transactionId }) {
  const [selectedTemplateId, setSelectedTemplateId] = useState<string | null>(null);

  return (
    <Box>
      <Typography variant="h5" gutterBottom>Select Template</Typography>
      <DocuSignTemplateSelector
        transactionId={transactionId}
        onSelect={setSelectedTemplateId}
      />
      
      {selectedTemplateId && (
        <Button onClick={() => createEnvelope(selectedTemplateId)}>
          Create Envelope
        </Button>
      )}
    </Box>
  );
}
```

---

## Accessibility

All components follow accessibility best practices:

- Semantic HTML structure
- ARIA labels where needed
- Keyboard navigation support (via MUI)
- Color contrast compliance
- Loading states with CircularProgress
- Error handling with Alert components

---

## TypeScript Types

Components use types from `docusignService.ts`:

```typescript
interface DocuSignEnvelope {
  id?: number;
  envelope_id: string;
  transaction_id: number;
  status: 'created' | 'sent' | 'delivered' | 'signed' | 'completed' | 'declined' | 'voided';
  subject: string;
  message?: string;
  recipients: DocuSignRecipient[];
  documents: any[];
  sent_at?: string;
  completed_at?: string;
  voided_at?: string;
  voided_reason?: string;
  created_at: string;
  updated_at: string;
}

interface DocuSignRecipient {
  email: string;
  name: string;
  role: 'signer' | 'cc' | 'carbon_copy' | 'certified_delivery';
  routing_order?: number;
}

interface DocuSignTemplate {
  templateId: string;
  name: string;
  description?: string;
  created?: string;
  lastModified?: string;
  shared?: boolean;
  owner?: {
    name?: string;
    email?: string;
  };
}
```
