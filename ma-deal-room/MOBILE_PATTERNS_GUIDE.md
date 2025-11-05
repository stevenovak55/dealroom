# Mobile Patterns Quick Reference Guide

## Common Mobile-First Patterns

### 1. Responsive Container

```tsx
// Mobile-first padding and spacing
<div className="px-4 py-4 md:px-6 md:py-8">
  {/* Content */}
</div>
```

### 2. Responsive Grid Layout

```tsx
// 1 column on mobile, 2 on tablet, 4 on desktop
<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6">
  {items.map(item => <Card key={item.id} />)}
</div>
```

### 3. Responsive Text Sizing

```tsx
<h1 className="text-2xl md:text-3xl lg:text-4xl font-bold">
  Page Title
</h1>
<p className="text-sm md:text-base">
  Description text
</p>
```

### 4. Mobile/Desktop Toggle

```tsx
// Show on mobile only
<div className="md:hidden">
  Mobile content
</div>

// Show on desktop only
<div className="hidden md:block">
  Desktop content
</div>

// Different layouts
<div className="flex flex-col md:flex-row gap-4">
  {/* Stacks on mobile, row on desktop */}
</div>
```

### 5. Touch-Friendly Buttons

```tsx
import { Button } from '@/components/shared/Button';

// Proper touch targets
<Button size="md"> {/* min-h-[44px] */}
  Action
</Button>

// Full width on mobile
<Button className="w-full md:w-auto">
  Submit
</Button>
```

### 6. Responsive Cards

```tsx
import { Card, CardHeader, CardTitle, CardContent } from '@/components/shared/Card';

<Card className="card-hover-effect">
  <CardHeader>
    <CardTitle>Card Title</CardTitle>
  </CardHeader>
  <CardContent>
    {/* Auto-adjusts padding */}
  </CardContent>
</Card>
```

### 7. Mobile-First Table (Use ResponsiveTable)

```tsx
import { ResponsiveTable } from '@/components/shared/ResponsiveTable';

const columns = [
  { 
    header: 'Name', 
    accessor: 'name',
    mobileLabel: 'Name' // Optional: different label for mobile
  },
  { 
    header: 'Status', 
    accessor: (item) => <Badge>{item.status}</Badge> 
  },
];

<ResponsiveTable
  data={items}
  columns={columns}
  keyExtractor={(item) => item.id}
  onRowClick={(item) => navigate(`/detail/${item.id}`)}
  emptyMessage="No items found"
/>
```

### 8. Mobile-Optimized Modal

```tsx
import { Modal, ModalFooter } from '@/components/shared/Modal';

<Modal
  isOpen={isOpen}
  onClose={onClose}
  title="Modal Title"
  size="md" // md:max-w-lg, full width on mobile
>
  <div className="space-y-4">
    {/* Content */}
  </div>
  
  <ModalFooter>
    <Button variant="secondary" onClick={onClose}>
      Cancel
    </Button>
    <Button onClick={onSubmit}>
      Confirm
    </Button>
  </ModalFooter>
</Modal>
```

### 9. Responsive Header Section

```tsx
<div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
  <div>
    <h1 className="text-2xl md:text-3xl font-bold">Title</h1>
    <p className="text-sm md:text-base text-gray-500">Subtitle</p>
  </div>
  <Button className="w-full sm:w-auto">
    Action
  </Button>
</div>
```

### 10. Mobile Card List (Alternative to Table)

```tsx
<div className="space-y-3">
  {items.map(item => (
    <div
      key={item.id}
      onClick={() => handleClick(item)}
      className="bg-white rounded-lg border p-4 active:bg-gray-50 cursor-pointer touch-feedback"
    >
      <div className="flex items-start justify-between gap-2">
        <div className="flex-1 min-w-0">
          <p className="font-semibold text-gray-900 truncate">
            {item.title}
          </p>
          <p className="text-sm text-gray-500 mt-1">
            {item.description}
          </p>
        </div>
        <Badge>{item.status}</Badge>
      </div>
      
      <div className="grid grid-cols-2 gap-3 mt-3 text-sm">
        <div>
          <span className="text-gray-500">Date:</span>
          <span className="ml-1 font-medium">{item.date}</span>
        </div>
        <div>
          <span className="text-gray-500">Value:</span>
          <span className="ml-1 font-medium">{item.value}</span>
        </div>
      </div>
    </div>
  ))}
</div>
```

### 11. Safe Area Padding (for iOS)

```tsx
// Bottom safe area (for bottom nav)
<nav className="fixed bottom-0 left-0 right-0 safe-area-pb">
  {/* Content */}
</nav>

// Top safe area (for headers)
<header className="safe-area-pt">
  {/* Content */}
</header>
```

### 12. Touch Feedback

```tsx
// On interactive elements
<div className="touch-feedback cursor-pointer">
  {/* Scales down and fades on tap */}
</div>

// Ensure touch targets
<button className="touch-target p-2">
  {/* Min 44x44px */}
</button>
```

### 13. Responsive Icon Sizes

```tsx
import { Home } from 'lucide-react';

<Home className="h-5 w-5 md:h-6 md:w-6" />
```

### 14. Mobile-First Form

```tsx
<form className="space-y-4 md:space-y-6">
  {/* Full width inputs on mobile */}
  <div>
    <label className="block text-sm font-medium mb-1">
      Field Label
    </label>
    <input 
      type="text"
      className="w-full px-4 py-2 border rounded-lg text-base focus:ring-2"
    />
  </div>
  
  {/* Buttons */}
  <div className="flex flex-col sm:flex-row gap-2 sm:gap-3">
    <Button variant="secondary" className="w-full sm:w-auto">
      Cancel
    </Button>
    <Button type="submit" className="w-full sm:w-auto">
      Submit
    </Button>
  </div>
</form>
```

### 15. Responsive Image

```tsx
<img 
  src={image}
  alt={alt}
  className="w-full h-48 md:h-64 object-cover rounded-lg"
/>
```

## Animation Classes

```tsx
// Fade in from bottom
<div className="animate-fade-in">...</div>

// Slide in from left (drawer)
<div className="animate-slide-in-left">...</div>

// Slide in from right
<div className="animate-slide-in-right">...</div>

// Slide up (bottom sheet)
<div className="animate-slide-up">...</div>

// Scale up (modal)
<div className="animate-scale-up">...</div>
```

## Tailwind Utility Classes

### Spacing
```
Mobile-first approach:
p-4 md:p-6 lg:p-8     // Padding
gap-3 md:gap-6        // Grid/flex gap
space-y-4 md:space-y-6 // Vertical spacing
```

### Typography
```
text-sm md:text-base lg:text-lg    // Font size
text-2xl md:text-3xl lg:text-4xl  // Headings
```

### Layout
```
flex flex-col md:flex-row          // Stack mobile, row desktop
grid grid-cols-1 md:grid-cols-2    // Responsive grid
w-full md:w-auto                   // Full width mobile
```

### Visibility
```
hidden md:block        // Hide on mobile
md:hidden              // Hide on desktop
```

## Best Practices Checklist

- [ ] Use mobile-first approach (default styles for mobile, add md: for desktop)
- [ ] Ensure all interactive elements have min 44px touch targets
- [ ] Test on actual devices, not just desktop browser
- [ ] Add touch feedback to interactive elements
- [ ] Use appropriate breakpoints (sm: 640px, md: 768px, lg: 1024px)
- [ ] Consider safe areas on iOS devices
- [ ] Use semantic HTML for better accessibility
- [ ] Test both portrait and landscape orientations
- [ ] Ensure text is readable (min 16px for body text)
- [ ] Add loading states for async operations
- [ ] Test keyboard navigation
- [ ] Verify scroll behavior on mobile

## Common Mistakes to Avoid

1. **Don't use hover effects only** - Add touch states
2. **Don't make touch targets too small** - Min 44px
3. **Don't rely on mouse-specific events** - Use touch-friendly alternatives
4. **Don't forget safe areas** - iOS notches and bottom bars
5. **Don't use fixed positioning carelessly** - Can conflict with mobile keyboards
6. **Don't ignore landscape mode** - Test horizontal orientation
7. **Don't make text too small** - Min 16px for readability
8. **Don't forget to test on real devices** - Emulators aren't perfect

## Testing Checklist

- [ ] iPhone SE (375px) - Smallest modern phone
- [ ] iPhone 12/13/14 (390px) - Standard size
- [ ] iPhone 12/13/14 Pro Max (428px) - Large phone
- [ ] iPad (768px) - Tablet portrait
- [ ] iPad Pro (1024px) - Tablet landscape
- [ ] Desktop (1280px+) - Standard desktop

Test these interactions:
- [ ] Bottom navigation works
- [ ] Hamburger menu opens/closes
- [ ] Search expands properly
- [ ] Modals appear correctly
- [ ] Cards are tappable
- [ ] Forms are usable
- [ ] Tables convert to cards
- [ ] Scrolling is smooth
- [ ] Safe areas are respected

---

**Pro Tip**: Always start with mobile design and enhance for larger screens, not the other way around!
