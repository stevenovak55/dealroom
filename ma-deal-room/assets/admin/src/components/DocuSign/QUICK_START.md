# DocuSign Components - Quick Start Guide

## Installation

Components are already created. No installation needed.

## Import

```typescript
// Named imports (recommended)
import { 
  EnvelopeStatus, 
  DocuSignEnvelopeList,
  DocuSignEnvelopeDetail,
  DocuSignTemplateSelector 
} from '@/components/DocuSign';

// Or individual imports
import EnvelopeStatus from '@/components/DocuSign/EnvelopeStatus';
```

## Usage Examples

### 1. Show Envelope Status (Simplest)

```tsx
import { EnvelopeStatus } from '@/components/DocuSign';

function MyComponent() {
  return <EnvelopeStatus envelopeId="abc123" />;
}
```

### 2. List All Envelopes

```tsx
import { DocuSignEnvelopeList } from '@/components/DocuSign';

function EnvelopesPage() {
  return (
    <Box>
      <Typography variant="h4">All Envelopes</Typography>
      <DocuSignEnvelopeList />
    </Box>
  );
}
```

### 3. List Envelopes for a Transaction

```tsx
import { DocuSignEnvelopeList } from '@/components/DocuSign';

function TransactionEnvelopes({ transactionId }) {
  return <DocuSignEnvelopeList transactionId={transactionId} />;
}
```

### 4. Select Template (Wizard Step)

```tsx
import { DocuSignTemplateSelector } from '@/components/DocuSign';
import { useState } from 'react';

function CreateEnvelopeWizard({ transactionId }) {
  const [templateId, setTemplateId] = useState<string | null>(null);

  return (
    <Box>
      <DocuSignTemplateSelector
        transactionId={transactionId}
        onSelect={(id) => {
          setTemplateId(id);
          console.log('Selected template:', id);
        }}
      />
      
      {templateId && (
        <Button onClick={() => createEnvelope(templateId)}>
          Continue
        </Button>
      )}
    </Box>
  );
}
```

### 5. Manual Envelope Detail Modal

```tsx
import { DocuSignEnvelopeDetail } from '@/components/DocuSign';
import { useState } from 'react';

function MyComponent() {
  const [selectedId, setSelectedId] = useState<string | null>(null);

  return (
    <>
      <Button onClick={() => setSelectedId('abc123')}>
        View Envelope
      </Button>

      {selectedId && (
        <DocuSignEnvelopeDetail
          envelopeId={selectedId}
          onClose={() => setSelectedId(null)}
        />
      )}
    </>
  );
}
```

## Common Patterns

### Pattern 1: Transaction Detail Page

Show envelope status on transaction detail page:

```tsx
function TransactionDetail({ transaction }) {
  return (
    <Box>
      <Typography variant="h4">Transaction #{transaction.id}</Typography>
      
      {/* Other transaction details... */}
      
      {transaction.docusign_envelope_id && (
        <Box sx={{ mt: 4 }}>
          <Typography variant="h5" gutterBottom>
            DocuSign Status
          </Typography>
          <EnvelopeStatus 
            envelopeId={transaction.docusign_envelope_id}
            onRefresh={() => refetchTransaction()}
          />
        </Box>
      )}
    </Box>
  );
}
```

### Pattern 2: Dedicated Envelopes Page

Full page for managing all envelopes:

```tsx
function EnvelopesPage() {
  return (
    <Container maxWidth="xl">
      <Stack spacing={3} sx={{ py: 3 }}>
        <Typography variant="h3">DocuSign Envelopes</Typography>
        <DocuSignEnvelopeList />
      </Stack>
    </Container>
  );
}
```

### Pattern 3: Multi-Step Envelope Creation

Use template selector in a wizard:

```tsx
function CreateEnvelopeWizard({ transactionId }) {
  const [step, setStep] = useState(1);
  const [templateId, setTemplateId] = useState<string | null>(null);
  const [recipients, setRecipients] = useState([]);

  if (step === 1) {
    return (
      <Box>
        <Typography variant="h5">Step 1: Select Template</Typography>
        <DocuSignTemplateSelector
          transactionId={transactionId}
          onSelect={(id) => {
            setTemplateId(id);
            setStep(2);
          }}
        />
      </Box>
    );
  }

  if (step === 2) {
    return (
      <Box>
        <Typography variant="h5">Step 2: Add Recipients</Typography>
        {/* Your recipient form here */}
        <Button onClick={() => setStep(3)}>Continue</Button>
      </Box>
    );
  }

  // Step 3: Review and send...
}
```

## Props Reference

### EnvelopeStatus

| Prop | Type | Required | Description |
|------|------|----------|-------------|
| envelopeId | string | Yes | DocuSign envelope ID |
| onRefresh | () => void | No | Callback after actions |

### DocuSignEnvelopeList

| Prop | Type | Required | Description |
|------|------|----------|-------------|
| transactionId | number | No | Filter by transaction |

### DocuSignEnvelopeDetail

| Prop | Type | Required | Description |
|------|------|----------|-------------|
| envelopeId | string | Yes | Envelope to display |
| onClose | () => void | Yes | Close modal handler |

### DocuSignTemplateSelector

| Prop | Type | Required | Description |
|------|------|----------|-------------|
| onSelect | (id: string) => void | Yes | Selection callback |
| transactionId | number | No | For preview feature |

## Styling Tips

All components use MUI and respect your theme:

```tsx
// Override theme for DocuSign section
<ThemeProvider theme={docusignTheme}>
  <DocuSignEnvelopeList />
</ThemeProvider>

// Add custom spacing
<Box sx={{ mt: 3, mb: 5 }}>
  <EnvelopeStatus envelopeId="abc123" />
</Box>

// Full-width containers
<Box sx={{ width: '100%', maxWidth: 1200, mx: 'auto' }}>
  <DocuSignEnvelopeList />
</Box>
```

## Error Handling

All components handle errors automatically:

```tsx
// Component shows error Alert automatically
<EnvelopeStatus envelopeId="invalid-id" />
// Displays: "Failed to load envelope" Alert

// No try-catch needed in your code
// Errors are caught and displayed by the component
```

## Loading States

All components show loading indicators:

```tsx
// Automatically shows CircularProgress while loading
<DocuSignEnvelopeList />

// No need for your own loading state
```

## TypeScript Support

Full type safety:

```typescript
import type { DocuSignEnvelope } from '@/api/docusignService';

// Types are automatically inferred
const handleSelect = (templateId: string) => {
  console.log(templateId); // string type
};

<DocuSignTemplateSelector onSelect={handleSelect} />
```

## Troubleshooting

### Component doesn't render
- Check that envelope ID exists
- Verify DocuSign is configured
- Check browser console for errors

### Modal doesn't open
- Ensure envelopeId is set (not null)
- Check that Dialog parent is rendered
- Verify z-index isn't conflicting

### Data not loading
- Verify API endpoint is accessible
- Check network tab in DevTools
- Ensure DocuSign config is active

### TypeScript errors
- Update imports to use correct paths
- Ensure types are exported from docusignService
- Check tsconfig.json paths

## Best Practices

1. **Always provide keys** for lists
2. **Handle null states** gracefully
3. **Use onRefresh callback** to update parent data
4. **Don't nest modals** (z-index issues)
5. **Test with real data** before production

## Performance Tips

1. **Memoize callbacks** if passed as props
2. **Lazy load** envelope list if page has many components
3. **Debounce search** in template selector (future enhancement)
4. **Paginate** large lists (already done in EnvelopeList)

## Next Steps

1. Add components to your pages
2. Test with real DocuSign data
3. Customize styling if needed
4. Add unit tests
5. Deploy and monitor

## Need Help?

- Check README.md for detailed documentation
- Review COMPONENT_ARCHITECTURE.md for design details
- See component source code for implementation
- Check docusignService.ts for API methods

## Example Project Structure

```
src/
├── components/
│   └── DocuSign/           ← Your new components
├── pages/
│   ├── Integrations/
│   │   └── DocuSign/
│   │       └── Envelopes.tsx  ← Use DocuSignEnvelopeList here
│   └── Transactions/
│       └── Detail.tsx      ← Use EnvelopeStatus here
└── api/
    └── docusignService.ts  ← Already exists
```

Happy coding!
