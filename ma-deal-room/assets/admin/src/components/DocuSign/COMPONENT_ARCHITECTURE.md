# DocuSign Components Architecture

## Component Hierarchy

```
DocuSign Components
│
├── EnvelopeStatus (Standalone)
│   ├── Props: envelopeId, onRefresh?
│   ├── Displays: Status, Recipients, Timeline, Actions
│   └── Used in: Transaction Detail Pages
│
├── DocuSignEnvelopeList (Container + Modal)
│   ├── Props: transactionId?
│   ├── Features: Table, Search, Filter, Pagination
│   ├── Triggers: DocuSignEnvelopeDetail (modal)
│   └── Used in: Envelope Management Page
│
├── DocuSignEnvelopeDetail (Modal)
│   ├── Props: envelopeId, onClose
│   ├── Displays: Full Details, Recipients Table, Documents, Timeline
│   ├── Actions: Resend, Void
│   └── Used by: DocuSignEnvelopeList (click handler)
│
└── DocuSignTemplateSelector (Standalone)
    ├── Props: onSelect, transactionId?
    ├── Features: Search, Grid Cards, Preview, Selection
    └── Used in: Create Envelope Wizards
```

## Data Flow

```
API Layer (docusignService.ts)
    ↓
Component State (useState)
    ↓
UI Rendering (MUI Components)
    ↓
User Actions (onClick, onChange)
    ↓
API Calls (async/await)
    ↓
State Updates (setLoading, setData, setError)
    ↓
Re-render with new data
```

## Component Relationships

### EnvelopeStatus
- **Standalone component**
- No child components
- Can be embedded anywhere
- Self-contained data fetching

### DocuSignEnvelopeList
- **Container component**
- Opens `DocuSignEnvelopeDetail` as modal
- Passes envelopeId to detail modal
- Refreshes on modal close

### DocuSignEnvelopeDetail
- **Modal component**
- Triggered by parent (usually DocuSignEnvelopeList)
- Returns control to parent on close
- Standalone data fetching

### DocuSignTemplateSelector
- **Standalone component**
- Callback pattern (onSelect)
- No child components
- Used in multi-step wizards

## State Management

Each component manages its own state:

```typescript
// Loading state
const [loading, setLoading] = useState(true);

// Error state
const [error, setError] = useState<string | null>(null);

// Data state
const [envelope/templates, setData] = useState<Type[]>([]);

// UI state (filters, search, pagination)
const [searchQuery, setSearchQuery] = useState('');
const [page, setPage] = useState(1);
```

## API Integration Pattern

All components follow the same pattern:

```typescript
const loadData = async () => {
  try {
    setLoading(true);
    setError(null);
    const data = await docusignService.method();
    setData(data);
  } catch (err: any) {
    const message = err.response?.data?.message || err.message;
    setError(message);
  } finally {
    setLoading(false);
  }
};
```

## Typical Integration Scenarios

### Scenario 1: Transaction Detail Page
```
TransactionDetailPage
    └── EnvelopeStatus (shows current envelope status)
```

### Scenario 2: Envelope Management
```
EnvelopeManagementPage
    └── DocuSignEnvelopeList
            └── DocuSignEnvelopeDetail (modal on row click)
```

### Scenario 3: Create Envelope Wizard
```
CreateEnvelopeWizard
    ├── Step 1: DocuSignTemplateSelector
    ├── Step 2: RecipientForm
    ├── Step 3: DocumentSelection
    └── Step 4: Review & Send
```

## Component Dependencies

### External Dependencies
- React (useState, useEffect)
- Material-UI (@mui/material, @mui/icons-material)
- docusignService (../../api/docusignService)
- formatDateTime (../../utils/formatDate)

### No Internal Dependencies
- Components are independent of each other
- Can be used individually or together
- No shared state between components

## File Size Breakdown

```
EnvelopeStatus.tsx          11 KB (303 lines)
DocuSignEnvelopeList.tsx     8.5 KB (264 lines)
DocuSignEnvelopeDetail.tsx   13 KB (350 lines)
DocuSignTemplateSelector.tsx 8.6 KB (257 lines)
index.ts                     392 B (8 lines)
README.md                    6.1 KB
COMPONENT_ARCHITECTURE.md    This file
────────────────────────────────────────
Total                        ~48 KB (1,182 lines)
```

## Performance Considerations

1. **Lazy Loading**: Components only load data when rendered
2. **Pagination**: Large lists are paginated (20 items/page)
3. **Search Debouncing**: Consider adding for better UX
4. **Memoization**: Not needed for current scale
5. **Code Splitting**: Already split into separate files

## Accessibility Features

1. **Keyboard Navigation**: Via MUI built-in support
2. **Screen Readers**: Semantic HTML and ARIA labels
3. **Color Contrast**: Using MUI theme colors
4. **Focus Management**: Modal trap focus
5. **Error Announcements**: Alert components

## Testing Strategy

### Unit Tests (Recommended)
- Test component rendering
- Test error states
- Test loading states
- Mock API calls

### Integration Tests (Recommended)
- Test user workflows
- Test modal interactions
- Test form submissions
- Test API integration

### E2E Tests (Optional)
- Full envelope creation flow
- Search and filter functionality
- Pagination navigation
