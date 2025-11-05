# Mobile-First Component Architecture Map

## Component Hierarchy

```
AppShell (Root Container)
├── Sidebar (Desktop: Sidebar | Mobile: Drawer)
│   ├── Mobile Backdrop (when open)
│   └── Navigation Items
│
├── Main Content Area
│   ├── Header
│   │   ├── Mobile: Hamburger + Expandable Search + Icons
│   │   └── Desktop: Full Search + Notifications + User Menu
│   │
│   ├── Page Content (Dashboard, etc.)
│   │   ├── Responsive Container (px-4 md:px-6)
│   │   └── Component-specific layouts
│   │
│   └── BottomNavigation (Mobile Only)
│       └── 5 Primary Tabs
```

## Mobile Layout Flow

### Small Screens (< 768px)

```
┌─────────────────────────┐
│   Header (Compact)      │
│ [☰] [Search] [🔔] [👤]  │
├─────────────────────────┤
│                         │
│   Main Content          │
│   (scrollable)          │
│   - Dashboard           │
│   - Stats (2x2)         │
│   - Card Lists          │
│                         │
│                         │
├─────────────────────────┤
│  Bottom Navigation      │
│ [🏠] [💼] [📁] [✓] [•••] │
└─────────────────────────┘
```

### With Drawer Open (Mobile)

```
┌────────────┐┌──────────┐
│            ││ Drawer   │
│  Backdrop  ││          │
│  (Dark)    ││ • Home   │
│            ││ • Trans  │
│  Click to  ││ • Docs   │
│  Close     ││ • MLS    │
│            ││ • CRM    │
│            ││ • etc    │
│            ││          │
│            ││ [v2.0.0] │
└────────────┘└──────────┘
```

### Desktop Screens (≥ 768px)

```
┌────────┬────────────────────────────────┐
│        │   Header (Full)                │
│ Side   │ [Search Bar      ] [🔔] [User] │
│ bar    ├────────────────────────────────┤
│        │                                │
│ • Home │   Main Content                 │
│ • Txns │   (max-width container)        │
│ • Docs │   - Dashboard                  │
│ • MLS  │   - Stats (1x4)                │
│ • CRM  │   - Table View                 │
│ • Sign │                                │
│ • Tmpl │                                │
│ • Task │                                │
│ • Notf │                                │
│ • Set  │                                │
│        │                                │
│ [1.0]  │                                │
└────────┴────────────────────────────────┘
```

## Component Responsiveness Matrix

| Component          | Mobile (<768px)                | Desktop (≥768px)           |
|--------------------|--------------------------------|----------------------------|
| **AppShell**       | pb-16 (bottom nav space)       | pb-0                       |
| **Sidebar**        | Fixed drawer (z-50)            | Collapsible sidebar        |
| **Header**         | Compact (icons only)           | Full layout                |
| **Search**         | Expandable icon                | Always visible input       |
| **BottomNav**      | Visible (fixed bottom)         | Hidden                     |
| **Stats Cards**    | 2 columns                      | 4 columns                  |
| **Tables**         | Card-based list                | Traditional table          |
| **Modals**         | Bottom sheet (slide-up)        | Centered (scale-up)        |
| **Buttons**        | w-full on many                 | w-auto                     |
| **Text**           | text-sm to text-2xl            | text-base to text-3xl      |
| **Spacing**        | px-4 py-3, gap-3               | px-6 py-4, gap-6           |

## State Management Flow

```
User Actions → UIStore → Component Re-render

Mobile Menu Toggle:
[Hamburger Click] → toggleMobileMenu() → mobileMenuOpen: true
                                       → Sidebar slides in
                                       → Backdrop appears
                                       → Body scroll locked

[Backdrop Click] → setMobileMenuOpen(false) → mobileMenuOpen: false
                                             → Sidebar slides out
                                             → Body scroll restored

[Nav Link Click] → handleLinkClick() → setMobileMenuOpen(false)
                                      → Navigate to route
```

## Animation Timeline

### Mobile Drawer Open (300ms)
```
0ms:    Click hamburger
0ms:    Set mobileMenuOpen = true
0ms:    Add backdrop (fade-in)
0ms:    Show sidebar (slide-in-left)
        translateX: -100% → 0
        opacity: 0 → 1
300ms:  Animation complete
```

### Bottom Sheet Modal (300ms)
```
0ms:    Modal opens
0ms:    Show backdrop (fade-in)
0ms:    Modal slides up
        translateY: 100% → 0
        opacity: 0 → 1
300ms:  Animation complete
```

### Touch Feedback (<100ms)
```
Touch:  Scale: 1 → 0.98
        Opacity: 1 → 0.9
Release: Return to normal
```

## Responsive Breakpoint Behavior

### 375px (iPhone SE)
- Minimum supported width
- 2-column stats cards
- Full-width buttons
- Compact spacing

### 390px (iPhone 12/13/14)
- Standard mobile layout
- Optimal touch targets
- Card-based lists

### 428px (iPhone Pro Max)
- Larger cards
- More content visible
- Same layout as standard

### 768px (iPad Portrait)
- Transition point
- Bottom nav disappears
- Sidebar appears
- Tables become visible
- 4-column stats grid

### 1024px (iPad Landscape / Desktop)
- Full desktop layout
- Collapsible sidebar
- Maximum efficiency
- Hover effects active

## Touch Target Map

```
Minimum Touch Sizes (44x44px iOS HIG):

Header:
├── Hamburger: 44x44 ✓
├── Search Icon: 44x44 ✓
├── Notification: 44x44 ✓
└── User Menu: 44x44 ✓

Bottom Nav:
├── Each Tab: ~75x64 ✓✓
└── Icon + Label

Buttons:
├── Small: 36x36 (non-primary)
├── Medium: 44x44 ✓ (default)
└── Large: 48x48 ✓✓

Cards:
└── Entire card tappable
```

## Safe Area Handling (iOS)

```
┌─────────────────────────┐ ← safe-area-inset-top
│   Status Bar / Notch    │
├─────────────────────────┤
│   Header (16px height)  │
├─────────────────────────┤
│                         │
│   Content Area          │
│   (scrollable)          │
│                         │
├─────────────────────────┤
│ Bottom Nav (16px + env) │
└─────────────────────────┘ ← safe-area-inset-bottom
    Home Indicator
```

## CSS Utility Classes Quick Reference

```css
/* Touch & Mobile */
.touch-target       → min-height: 44px, min-width: 44px
.touch-feedback     → active: scale(0.98) + opacity(0.9)
.safe-area-pb       → padding-bottom: env(safe-area-inset-bottom)
.safe-area-pt       → padding-top: env(safe-area-inset-top)

/* Animations */
.animate-fade-in         → Fade + translate Y
.animate-slide-in-left   → Slide from left
.animate-slide-in-right  → Slide from right
.animate-slide-up        → Slide from bottom
.animate-scale-up        → Scale + opacity

/* Hover (Desktop Only) */
.card-hover-effect  → @media (min-width: 768px) hover effects
```

## Data Flow Example: Dashboard

```
Dashboard.tsx
├── Fetch: useGetTransactions()
│
├── Desktop View (≥768px)
│   └── <table>
│       ├── <thead>
│       └── <tbody>
│           └── transactions.map()
│
└── Mobile View (<768px)
    └── <div> (card container)
        └── transactions.map()
            └── <Link> (card)
                ├── Property Info
                ├── Status Badge
                └── Details Grid
```

## File Structure

```
src/
├── components/
│   ├── Layout/
│   │   ├── AppShell.tsx          [Modified]
│   │   ├── Sidebar.tsx           [Modified] - Drawer on mobile
│   │   ├── Header.tsx            [Modified] - Compact on mobile
│   │   └── BottomNavigation.tsx  [NEW] - Mobile only
│   │
│   └── shared/
│       ├── Button.tsx            [Modified] - Touch targets
│       ├── Card.tsx              [Modified] - Responsive padding
│       ├── Modal.tsx             [Modified] - Bottom sheet mobile
│       ├── Table.tsx             [Existing] - Desktop only
│       └── ResponsiveTable.tsx   [NEW] - Auto mobile/desktop
│
├── pages/
│   └── Dashboard/
│       └── Dashboard.tsx         [Modified] - Fully responsive
│
├── store/
│   └── useUIStore.ts             [Modified] - Mobile state
│
└── styles/
    └── globals.css               [Modified] - Mobile utilities
```

## Import Patterns

```tsx
// Layout components
import { AppShell } from '@/components/Layout/AppShell';
import { Sidebar } from '@/components/Layout/Sidebar';
import { Header } from '@/components/Layout/Header';
import { BottomNavigation } from '@/components/Layout/BottomNavigation';

// Shared components
import { Button } from '@/components/shared/Button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/shared/Card';
import { Modal, ModalFooter } from '@/components/shared/Modal';
import { ResponsiveTable } from '@/components/shared/ResponsiveTable';

// State
import { useUIStore } from '@/store/useUIStore';

// Icons
import { Home, Menu, Search, Bell, User } from 'lucide-react';

// Utilities
import { cn } from '@/utils/cn';
```

---

This map provides a visual and structural reference for understanding the mobile-first architecture of MA Deal Room v2.0.0.
