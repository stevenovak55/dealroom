# Placeholder Features Analysis & Implementation Plan

**Date**: 2025-10-31
**Status**: Ready for Implementation

---

## 🔍 DISCOVERED PLACEHOLDER FEATURES

### 1. **Settings Page** (HIGH PRIORITY)
**Location**: `/pages/Settings/Settings.tsx` (line 20)
**Status**: UI exists, no backend connection
**TODO**: `// TODO: Implement settings save`

**Current State**:
- ✅ UI complete with 3 sections:
  - Account Settings (Company Name, Email, Phone, Timezone)
  - Notification Preferences (Email, SMS, Daily Digest)
  - Branding (Logo URL, Primary Color)
- ❌ No data loading from backend
- ❌ No save functionality
- ❌ All inputs are hardcoded placeholders

**Database Available**:
- `wp_ma_deal_accounts` table exists
- Has `settings` JSON field for storing preferences
- Can store arbitrary key-value pairs

---

### 2. **Global Search** (MEDIUM PRIORITY)
**Location**: `/components/Layout/Header.tsx` (line 40)
**Status**: Search box exists, no functionality
**TODO**: `// TODO: Implement global search`

**Current State**:
- ✅ Search input in header
- ✅ Form submission handler
- ❌ Shows alert: "Global search coming soon!"
- ❌ No backend search endpoint

**Potential Search Targets**:
- Transactions (by address, city, property type)
- Tasks (by title, description)
- Parties (by name, email, phone)
- Documents (by title, type)
- Templates (by name, description)

---

### 3. **User Profile Settings** (LOW PRIORITY)
**Location**: `/components/Layout/Header.tsx` (line 165)
**Status**: Button exists, no page
**Alert**: `'Profile settings coming soon!'`

**Current State**:
- ✅ User menu dropdown with Settings button
- ❌ Clicking shows alert instead of navigation
- ❌ No dedicated user profile page

**Potential Features**:
- User name/email (from WordPress user)
- Avatar upload
- Personal preferences (separate from account settings)
- Password change
- Notification preferences (user-level)

---

### 4. **Other Minor TODOs**
- Some template/task library features may have placeholder search functionality
- Documents/Timeline pages appear to be mostly complete

---

## 📋 RECOMMENDED IMPLEMENTATION PRIORITY

### **Phase 1: Settings Page (Essential)** ⭐⭐⭐
**Why First**: Core functionality that users need immediately
**Effort**: Medium (2-3 hours)
**Components**:
1. Backend: Account settings REST endpoint (GET, PUT)
2. Frontend: useGetSettings and useUpdateSettings hooks
3. Connect Settings page to real data
4. Save to database JSON field
5. Show success/error messages

**Settings to Store**:
```json
{
  "company_name": "Acme Real Estate",
  "email": "admin@example.com",
  "phone": "(555) 123-4567",
  "timezone": "America/New_York",
  "notifications": {
    "email": true,
    "sms": false,
    "daily_digest": true
  },
  "branding": {
    "logo_url": "https://example.com/logo.png",
    "primary_color": "#3b82f6"
  }
}
```

---

### **Phase 2: Global Search (High Value)** ⭐⭐
**Why Second**: Significantly improves UX, high user value
**Effort**: Medium-High (3-4 hours)
**Components**:
1. Backend: Universal search endpoint
2. Search across multiple tables
3. Relevance ranking
4. Frontend: Search results dropdown
5. Navigate to results

**Search Features**:
- Instant search (debounced)
- Recent searches
- Search suggestions
- Keyboard navigation (arrow keys, enter)
- Search filters (by type: transactions, tasks, etc.)

---

### **Phase 3: User Profile (Nice to Have)** ⭐
**Why Last**: Less critical, mostly cosmetic
**Effort**: Low-Medium (1-2 hours)
**Components**:
1. Create UserProfile page component
2. Load WordPress user data
3. Display name, email, role
4. Link avatar with Gravatar
5. Personal preferences

---

## 🚀 IMPLEMENTATION DETAILS

### Phase 1: Settings Page Implementation

#### Backend Changes

**1. Create Settings Controller**
```php
// src/REST/Controllers/SettingsController.php
class SettingsController extends BaseController {
    public function get_settings(WP_REST_Request $request) {
        // Get account_id from current user
        // Fetch account settings from database
        // Return settings JSON
    }

    public function update_settings(WP_REST_Request $request) {
        // Get account_id from current user
        // Validate settings data
        // Update account.settings JSON field
        // Return updated settings
    }
}
```

**2. Register REST Routes**
```php
// Register in Plugin.php or REST init
register_rest_route('ma-deal/v1', '/settings', [
    'methods' => 'GET',
    'callback' => [$settings_controller, 'get_settings'],
    'permission_callback' => 'current_user_can_edit'
]);

register_rest_route('ma-deal/v1', '/settings', [
    'methods' => 'PUT',
    'callback' => [$settings_controller, 'update_settings'],
    'permission_callback' => 'current_user_can_edit'
]);
```

#### Frontend Changes

**3. Create API Queries**
```typescript
// src/api/queries/useSettings.ts
export const useGetSettings = () => {
  return useQuery({
    queryKey: ['settings'],
    queryFn: async () => {
      const response = await apiClient.get('/settings');
      return response.data.data;
    },
  });
};

export const useUpdateSettings = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (settings: Record<string, any>) => {
      const response = await apiClient.put('/settings', settings);
      return response.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['settings']);
    },
  });
};
```

**4. Update Settings Page**
```typescript
// Connect to real data
const { data: settings, isLoading } = useGetSettings();
const updateMutation = useUpdateSettings();

// Use react-hook-form for form management
const { register, handleSubmit } = useForm({
  defaultValues: settings
});

const onSubmit = async (data) => {
  await updateMutation.mutateAsync(data);
  toast.success('Settings saved!');
};
```

---

### Phase 2: Global Search Implementation

#### Backend Changes

**1. Create Search Controller**
```php
// src/REST/Controllers/SearchController.php
class SearchController extends BaseController {
    public function search(WP_REST_Request $request) {
        $query = $request->get_param('q');
        $types = $request->get_param('types'); // transactions,tasks,parties

        $results = [
            'transactions' => $this->search_transactions($query),
            'tasks' => $this->search_tasks($query),
            'parties' => $this->search_parties($query),
            'documents' => $this->search_documents($query),
        ];

        return $results;
    }
}
```

**2. Search Queries**
```sql
-- Transactions search
SELECT * FROM wp_ma_deal_transactions
WHERE property_address LIKE %s
   OR property_city LIKE %s
   OR notes LIKE %s
LIMIT 20;

-- Tasks search
SELECT * FROM wp_ma_deal_tasks
WHERE title LIKE %s
   OR description LIKE %s
LIMIT 20;
```

#### Frontend Changes

**3. Search Component**
```typescript
// components/Search/GlobalSearch.tsx
- Debounced input (300ms)
- Search as you type
- Results dropdown with categories
- Keyboard navigation
- Click result to navigate
```

---

### Phase 3: User Profile Implementation

**1. Create UserProfile Page**
```typescript
// pages/UserProfile/UserProfile.tsx
- Display WordPress user info
- Show Gravatar
- Personal preferences
- Notification settings (user-level)
```

**2. Update Header Navigation**
```typescript
// Change from alert to navigation
onClick={() => navigate('/profile')}
```

---

## 📊 EFFORT ESTIMATION

| Feature | Backend | Frontend | Testing | Total |
|---------|---------|----------|---------|-------|
| **Settings Page** | 1.5h | 1h | 0.5h | 3h |
| **Global Search** | 2h | 1.5h | 0.5h | 4h |
| **User Profile** | 0.5h | 1h | 0.5h | 2h |
| **TOTAL** | 4h | 3.5h | 1.5h | **9h** |

---

## ✅ SUCCESS CRITERIA

### Settings Page
- [ ] Load current settings from database
- [ ] Save settings successfully
- [ ] Show loading states
- [ ] Display success/error messages
- [ ] Settings persist across sessions
- [ ] Validation for required fields
- [ ] Timezone dropdown works
- [ ] Notification toggles work
- [ ] Color picker saves

### Global Search
- [ ] Search works across all entity types
- [ ] Results appear instantly (<300ms)
- [ ] Keyboard navigation works
- [ ] Clicking result navigates correctly
- [ ] Empty state handled
- [ ] Search history (optional)
- [ ] Highlights matching text
- [ ] Mobile responsive

### User Profile
- [ ] Display WordPress user data
- [ ] Show Gravatar
- [ ] Navigate from header menu
- [ ] Edit personal preferences
- [ ] Save changes successfully

---

## 🎯 RECOMMENDED NEXT STEP

**Implement Phase 1: Settings Page**

This is the highest priority because:
1. Most visible incomplete feature
2. Users need to configure their account
3. Foundation for other features
4. Relatively straightforward to implement
5. High impact, medium effort

Would you like me to proceed with implementing the Settings page functionality?

---

## 📝 NOTES

- All implementations should follow existing patterns
- Use existing components (Card, Input, Button, etc.)
- Follow TypeScript strict mode
- Add proper error handling
- Include loading states
- Mobile responsive
- Accessibility (keyboard nav, ARIA labels)

