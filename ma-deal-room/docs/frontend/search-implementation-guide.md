# Search Implementation Guide

## Overview

This guide documents the correct pattern for implementing search functionality with debouncing in React components to prevent common issues like focus loss, excessive API calls, and poor UX.

## The Correct Pattern

### 1. Use an Isolated Debounced Input Component

Always use the `DebouncedSearchInput` component for search inputs. This prevents focus loss during parent re-renders.

**Location:** `assets/admin/src/components/shared/DebouncedSearchInput.tsx`

**Usage:**
```typescript
import { DebouncedSearchInput } from '@/components/shared/DebouncedSearchInput';

const [searchTerm, setSearchTerm] = useState('');

<DebouncedSearchInput
  value={searchTerm}
  onChange={setSearchTerm}
  placeholder="Search..."
  delay={300}
/>
```

### 2. Use `placeholderData: keepPreviousData` in React Query

This prevents data from clearing during refetches, which causes aggressive re-renders and focus loss.

**Example:**
```typescript
import { useQuery, keepPreviousData } from '@tanstack/react-query';

const { data, isLoading } = useQuery({
  queryKey: ['items', searchTerm],
  queryFn: () => fetchItems(searchTerm),
  placeholderData: keepPreviousData, // CRITICAL: Prevents focus loss
});
```

### 3. Backend Must Support Search with Grouping

If your endpoint returns grouped data (`grouped=true`), ensure the backend handles search BEFORE grouping.

**Example (PHP):**
```php
public function get_items(WP_REST_Request $request) {
    $grouped = $request->get_param('grouped');
    $search = $request->get_param('search');

    if ($grouped) {
        // IMPORTANT: Handle search FIRST if present
        if ($search) {
            $results = $this->repository->search($search, $options);
            // Then group the search results
            $grouped_results = $this->groupResults($results);
            return new WP_REST_Response(['data' => $grouped_results]);
        }

        // Normal grouped query without search
        $results = $this->repository->findAllGrouped();
        return new WP_REST_Response(['data' => $results]);
    }

    // Handle non-grouped search...
}
```

## Common Pitfalls to Avoid

### ❌ DON'T: Put Search Input Directly in Parent Component

```typescript
// BAD: Input in parent component
const [search, setSearch] = useState('');

// This causes re-renders that lose focus
<input
  value={search}
  onChange={(e) => setSearch(e.target.value)}
/>
```

**Problem:** Parent re-renders from API responses recreate the input, losing focus.

### ❌ DON'T: Include Both `value` and Local State in useEffect Dependencies

```typescript
// BAD: Both value and searchInput in dependencies
useEffect(() => {
  if (value !== searchInput) {
    setSearchInput(value);
  }
}, [value, searchInput]); // Bug: searchInput causes infinite loops
```

**Problem:** Creates a feedback loop that resets the input on every keystroke.

### ❌ DON'T: Forget `placeholderData` in React Query

```typescript
// BAD: No placeholderData
const { data } = useQuery({
  queryKey: ['items', search],
  queryFn: () => fetchItems(search),
  // Missing: placeholderData: keepPreviousData
});
```

**Problem:** Data becomes undefined during refetch, causing aggressive re-renders and focus loss.

### ❌ DON'T: Ignore Search Parameter When Backend Returns Grouped Data

```php
// BAD: Returns all grouped data, ignoring search
if ($grouped) {
    return $this->repository->findAllGrouped(); // Ignores $search!
}
```

**Problem:** Search appears not to work because backend returns all data regardless of search term.

## Correct Implementation Example

### Frontend Component

```typescript
import { useState } from 'react';
import { useQuery, keepPreviousData } from '@tanstack/react-query';
import { DebouncedSearchInput } from '@/components/shared/DebouncedSearchInput';

export const SearchableList = () => {
  const [searchTerm, setSearchTerm] = useState('');

  const { data, isLoading } = useQuery({
    queryKey: ['items', searchTerm],
    queryFn: async () => {
      const response = await apiClient.get('/items', {
        params: { search: searchTerm, grouped: true }
      });
      return response.data.data;
    },
    placeholderData: keepPreviousData, // CRITICAL
  });

  return (
    <div>
      <DebouncedSearchInput
        value={searchTerm}
        onChange={setSearchTerm}
        placeholder="Search items..."
      />
      {/* Render results */}
    </div>
  );
};
```

### Backend Controller

```php
public function get_items(WP_REST_Request $request) {
    $grouped = $request->get_param('grouped');
    $search = $request->get_param('search');

    if ($grouped) {
        if ($search) {
            // Search first
            $results = $this->repository->search($search, $options);

            // Group search results
            $grouped = [];
            foreach ($results as $item) {
                $category = $item->category;
                if (!isset($grouped[$category])) {
                    $grouped[$category] = [
                        'category_key' => $category,
                        'category_name' => ucwords(str_replace('_', ' ', $category)),
                        'items' => []
                    ];
                }
                $grouped[$category]['items'][] = $item->toArray();
            }

            return new WP_REST_Response(['success' => true, 'data' => $grouped]);
        }

        // No search - return all grouped
        $results = $this->repository->findAllGrouped();
        return new WP_REST_Response(['success' => true, 'data' => $results]);
    }

    // Non-grouped search
    if ($search) {
        $results = $this->repository->search($search, $options);
        return new WP_REST_Response(['success' => true, 'data' => $results]);
    }

    // Default: return all
    $results = $this->repository->findAll();
    return new WP_REST_Response(['success' => true, 'data' => $results]);
}
```

### Backend Repository

```php
public function search(string $search_term, array $options = []): array {
    $table = $this->get_table_name();

    // Escape search term for LIKE query
    $search_term = '%' . $this->wpdb->esc_like($search_term) . '%';

    $where_parts = ['(title LIKE %s OR description LIKE %s)'];
    $params = [$search_term, $search_term];

    // Add optional filters
    if (isset($options['is_system'])) {
        $where_parts[] = 'is_system = %d';
        $params[] = $options['is_system'];
    }

    $where_clause = 'WHERE ' . implode(' AND ', $where_parts);

    $sql = "SELECT * FROM {$table} {$where_clause}";
    $prepared_sql = $this->wpdb->prepare($sql, ...$params);
    $results = $this->wpdb->get_results($prepared_sql, ARRAY_A);

    return $this->hydrate_models($results ?: []);
}
```

## Testing Checklist

When implementing search, verify:

- [ ] Can type continuously without losing focus
- [ ] Input field doesn't reset while typing
- [ ] Cursor stays in the input field
- [ ] Search executes 300ms after typing stops
- [ ] Previous results stay visible while fetching new ones
- [ ] Search works with grouped and non-grouped data
- [ ] Backend properly filters results
- [ ] Empty search returns all results
- [ ] SQL injection protection via `wpdb->esc_like()`

## Real-World Issues Fixed

### Issue 1: Task Library Search (November 2025)

**Problem:** Search field lost focus every few keystrokes, cursor jumped out.

**Root Causes:**
1. Backend ignored search parameter when `grouped=true`
2. React Query cleared data during refetch (no `placeholderData`)
3. Input component re-rendered with parent (not isolated)

**Solutions Applied:**
1. Added search support to grouped endpoint in `TaskDefinitionController`
2. Added `placeholderData: keepPreviousData` to `useTaskDefinitions`
3. Created `DebouncedSearchInput` isolated component

### Issue 2: Transaction Tasks Search (November 2025)

**Problem:** Search only worked on first 100 loaded tasks (client-side only).

**Root Cause:** Backend `TaskController` didn't accept search parameter.

**Solution:** Added backend search support via `TaskRepository->search()`.

## References

- React Query placeholderData: https://tanstack.com/query/latest/docs/react/guides/paginated-queries
- Debouncing in React: https://usehooks.com/usedebounce
- WordPress wpdb Security: https://developer.wordpress.org/reference/classes/wpdb/esc_like/

## Version History

- **2025-11-01**: Initial documentation based on Task Library search fix
