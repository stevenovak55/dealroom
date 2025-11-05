# MA Deal Room - Mobile-First Redesign Plan
## Version 2.0 - Complete Platform Redesign

**Document Version:** 1.0.0
**Last Updated:** 2025-11-05
**Status:** Planning Phase
**Lead:** AI Development Team

---

## Executive Summary

This document outlines a comprehensive plan to transform the MA Deal Room platform from a desktop-first web application into a mobile-first progressive web application (PWA) that feels and functions like a native mobile app. The redesign will touch **every single page** and **every menu item** across the entire platform with proper logging, documentation, and progress tracking.

### Goals
- ✅ Mobile-first responsive design across 100% of the platform
- ✅ Native app-like experience with smooth touch interactions
- ✅ Bottom navigation for mobile devices
- ✅ Optimized layouts for small screens (320px+)
- ✅ Progressive enhancement for larger screens
- ✅ Gesture support (swipe, pull-to-refresh, etc.)
- ✅ Offline-first capabilities
- ✅ Fast performance (<3s initial load on 3G)

---

## Current State Analysis

### Tech Stack
- **Framework:** React 18.3.1 with TypeScript
- **Styling:** Tailwind CSS 3.4.13 + Material-UI 7.3.4
- **Routing:** React Router 6.27.0
- **State:** Zustand + TanStack Query
- **Build Tool:** Vite 5.4.8
- **Icons:** Lucide React + MUI Icons

### Architecture
```
ma-deal-room/assets/admin/
├── src/
│   ├── components/
│   │   ├── Layout/          # AppShell, Sidebar, Header
│   │   ├── Auth/            # 7 auth components
│   │   ├── Dashboard/       # Dashboard widgets
│   │   ├── Transactions/    # 4 transaction components
│   │   ├── Documents/       # 4 document components
│   │   ├── Tasks/           # 6 task components
│   │   ├── Timeline/        # 4 timeline components
│   │   ├── Templates/       # Template components
│   │   ├── shared/          # 15+ shared components
│   │   └── ...
│   ├── pages/
│   │   ├── Auth/            # 6 auth pages
│   │   ├── Dashboard/       # 2 dashboard pages
│   │   ├── Transactions/    # 4 transaction pages
│   │   ├── Documents/       # 1 document page
│   │   ├── Templates/       # 3 template pages
│   │   ├── TaskLibrary/     # 5 task library pages
│   │   ├── Timeline/        # 1 timeline page
│   │   ├── Integrations/    # 3 integration pages
│   │   ├── MLS/             # 2 MLS pages
│   │   ├── Reminders/       # 1 reminders page
│   │   ├── Settings/        # 1 settings page
│   │   └── UserProfile/     # 1 profile page
│   └── ...
```

### Navigation Structure
**Main Navigation (10 items):**
1. Dashboard (/)
2. Transactions (/transactions)
3. Documents (/documents)
4. MLS Integration (/mls)
5. CRM Integration (/integrations/crm)
6. DocuSign (/integrations/docusign)
7. Templates (/templates)
8. Task Library (/task-library)
9. Reminders (/reminders)
10. Settings (/settings)

**Total Pages: 30+**
- Auth: 6 pages
- Dashboard: 2 pages
- Transactions: 5 pages (list, detail, create, edit, timeline)
- Documents: 1 page
- Templates: 3 pages
- Task Library: 1 page
- Reminders: 1 page
- MLS: 1 page
- Integrations: 2 pages (CRM, DocuSign)
- Settings: 1 page
- Profile: 1 page

---

## Critical Issues Identified

### 🔴 Layout Issues
1. **Fixed Sidebar** (64px collapsed / 256px expanded)
   - Not responsive on mobile
   - Takes valuable screen space
   - No mobile hamburger menu
   - No bottom navigation

2. **Header Bar**
   - Full-width search bar not mobile-optimized
   - Dropdowns not touch-friendly
   - No responsive breakpoints

3. **Content Area**
   - Fixed padding (`px-6 py-8`) wastes space on mobile
   - No mobile-specific spacing

### 🔴 Component Issues
4. **Tables**
   - Not responsive (overflow-x-auto creates horizontal scroll)
   - Columns don't stack on mobile
   - Fixed widths (px-6 py-4) don't scale

5. **Forms**
   - Multi-column grids don't stack properly
   - Input fields too small for touch
   - No mobile-optimized keyboards

6. **Cards**
   - Grid layouts need mobile breakpoints
   - Content doesn't reflow properly

7. **Modals**
   - Full-screen modals on mobile not implemented
   - Hard to close on small screens

8. **Buttons**
   - Touch targets too small (<44px)
   - Icon-only buttons lack labels on mobile

### 🔴 Interaction Issues
9. **No Touch Gestures**
   - No swipe navigation
   - No pull-to-refresh
   - No touch-optimized scrolling

10. **No Mobile Navigation Patterns**
    - No bottom tab bar
    - No mobile-optimized menu
    - No hamburger menu

11. **Search**
    - Full-width search takes too much space
    - No mobile search overlay
    - Dropdowns not optimized

12. **Notifications**
    - Dropdown not mobile-friendly
    - Should be full-screen on mobile

### 🔴 Performance Issues
13. **No Code Splitting**
    - Large bundle size
    - Slow initial load

14. **No Image Optimization**
    - Full-size images loaded

15. **No Lazy Loading**
    - All components loaded upfront

---

## Mobile-First Design System

### Design Principles

1. **Mobile-First**
   - Design for 320px+ screens first
   - Progressive enhancement for larger screens
   - Content-first approach

2. **Touch-Optimized**
   - Minimum 44×44px touch targets
   - Sufficient spacing between interactive elements
   - Large, easy-to-tap buttons

3. **Performance**
   - <3s initial load on 3G
   - <200ms interaction response
   - Smooth 60fps animations

4. **Native App Feel**
   - Bottom navigation
   - Swipe gestures
   - Pull-to-refresh
   - Haptic feedback
   - Native-like transitions

### Breakpoint System

```css
/* Mobile-first breakpoints */
$breakpoints: (
  'xs': 320px,   /* Small phones */
  'sm': 640px,   /* Large phones */
  'md': 768px,   /* Tablets portrait */
  'lg': 1024px,  /* Tablets landscape / Small desktops */
  'xl': 1280px,  /* Desktops */
  '2xl': 1536px  /* Large desktops */
);
```

### Typography Scale (Mobile-First)

```css
/* Mobile base: 14px */
.text-xs    { font-size: 0.75rem; }    /* 12px */
.text-sm    { font-size: 0.875rem; }   /* 14px */
.text-base  { font-size: 1rem; }       /* 16px */
.text-lg    { font-size: 1.125rem; }   /* 18px */
.text-xl    { font-size: 1.25rem; }    /* 20px */
.text-2xl   { font-size: 1.5rem; }     /* 24px */
.text-3xl   { font-size: 1.875rem; }   /* 30px */
```

### Spacing System (Mobile-First)

```css
/* Mobile-optimized spacing */
.p-2  { padding: 0.5rem; }   /* 8px - tight mobile spacing */
.p-3  { padding: 0.75rem; }  /* 12px - standard mobile */
.p-4  { padding: 1rem; }     /* 16px - comfortable mobile */
.p-6  { padding: 1.5rem; }   /* 24px - desktop standard */
.p-8  { padding: 2rem; }     /* 32px - desktop generous */
```

### Component Standards

#### Touch Targets
```css
/* Minimum touch target: 44×44px (iOS HIG) */
.btn-mobile {
  min-height: 44px;
  min-width: 44px;
  padding: 12px 16px;
}
```

#### Bottom Navigation
```jsx
/* Mobile bottom nav - Always visible on <md screens */
<nav className="fixed bottom-0 inset-x-0 bg-white border-t border-gray-200 z-50 md:hidden">
  <div className="flex justify-around items-center h-16">
    {/* 5 primary nav items */}
  </div>
</nav>
```

#### Mobile Header
```jsx
/* Mobile header - Simplified on <md screens */
<header className="fixed top-0 inset-x-0 bg-white border-b z-40">
  <div className="flex items-center justify-between h-14 px-4">
    <button>Menu</button>
    <h1>Title</h1>
    <button>Actions</button>
  </div>
</header>
```

#### Responsive Tables
```jsx
/* Stack table rows as cards on mobile */
<div className="block md:hidden">
  {/* Card layout for each row */}
</div>
<div className="hidden md:block">
  {/* Table layout for desktop */}
</div>
```

---

## Implementation Plan

### Phase 1: Foundation (Week 1)
**Goal:** Set up mobile-first infrastructure

#### Task 1.1: Design System Setup
- [ ] Create mobile-first Tailwind config
- [ ] Define component variants (mobile/desktop)
- [ ] Set up responsive breakpoints
- [ ] Create spacing/typography scales
- [ ] Document in `DESIGN_SYSTEM.md`

#### Task 1.2: Layout Architecture
- [ ] Create `MobileLayout` component
- [ ] Create `DesktopLayout` component
- [ ] Create `ResponsiveLayout` wrapper
- [ ] Implement bottom navigation
- [ ] Implement mobile header
- [ ] Create hamburger menu
- [ ] Document in `LAYOUT_GUIDE.md`

#### Task 1.3: Shared Components Redesign
- [ ] `Button` - mobile variants
- [ ] `Card` - responsive layouts
- [ ] `Modal` - full-screen mobile
- [ ] `Table` - card view mobile
- [ ] `Input` - touch-optimized
- [ ] `Select` - native mobile picker
- [ ] `Badge` - responsive sizing
- [ ] `Drawer` - bottom sheet mobile
- [ ] Document each in component docs

**Deliverables:**
- ✅ Mobile-first design system
- ✅ Responsive layout system
- ✅ Redesigned shared components
- ✅ Documentation complete

---

### Phase 2: Core Pages (Week 2)
**Goal:** Redesign primary user flows

#### Task 2.1: Authentication Pages (6 pages)
- [ ] Login page - mobile-first
- [ ] Register page - mobile-first
- [ ] Forgot password - mobile-first
- [ ] Reset password - mobile-first
- [ ] Verify email - mobile-first
- [ ] 2FA verify - mobile-first
- [ ] Test on devices 320px-768px

#### Task 2.2: Dashboard (2 pages)
- [ ] Main dashboard - responsive cards
- [ ] Dashboard widgets - stack on mobile
- [ ] Metrics cards - 1 column mobile
- [ ] Charts - mobile-optimized
- [ ] Recent transactions - card view mobile

#### Task 2.3: Transactions (5 pages)
- [ ] Transactions list - card view mobile
- [ ] Transaction detail - responsive layout
- [ ] Create transaction wizard - mobile stepper
- [ ] Edit transaction - mobile form
- [ ] Transaction timeline - vertical mobile

**Deliverables:**
- ✅ 13 pages redesigned
- ✅ Mobile-first workflows
- ✅ Screenshots documented

---

### Phase 3: Content Pages (Week 3)
**Goal:** Redesign content-heavy pages

#### Task 3.1: Documents (1 page)
- [ ] Document manager - grid→list on mobile
- [ ] Upload interface - mobile-optimized
- [ ] Document viewer - full-screen mobile
- [ ] Document actions - bottom sheet

#### Task 3.2: Templates (3 pages)
- [ ] Templates list - card view mobile
- [ ] Template builder - mobile-adapted
- [ ] Template analytics - responsive charts

#### Task 3.3: Task Library (1 page)
- [ ] Task library - mobile grid
- [ ] Task filters - mobile drawer
- [ ] Task detail - bottom sheet
- [ ] Task form - mobile-optimized

#### Task 3.4: Timeline (1 page)
- [ ] Timeline view - vertical mobile
- [ ] Gantt chart - horizontal scroll mobile
- [ ] Timeline editor - touch-optimized

**Deliverables:**
- ✅ 6 pages redesigned
- ✅ Mobile interactions implemented

---

### Phase 4: Integration Pages (Week 4)
**Goal:** Redesign integration and settings

#### Task 4.1: MLS Integration (1 page)
- [ ] MLS dashboard - mobile cards
- [ ] MLS config - mobile form
- [ ] Sync status - mobile layout

#### Task 4.2: CRM Integration (1 page)
- [ ] CRM settings - mobile form
- [ ] Sync monitor - mobile view
- [ ] Field mapping - mobile UI

#### Task 4.3: DocuSign (1 page)
- [ ] DocuSign settings - mobile form
- [ ] Envelope list - card view
- [ ] Envelope detail - mobile layout

#### Task 4.4: Settings & Profile (2 pages)
- [ ] Settings page - mobile sections
- [ ] User profile - mobile form
- [ ] Profile photo - mobile upload

#### Task 4.5: Reminders (1 page)
- [ ] Reminders list - card view mobile
- [ ] Reminder detail - bottom sheet

**Deliverables:**
- ✅ 6 pages redesigned
- ✅ All integrations mobile-optimized

---

### Phase 5: Mobile Enhancements (Week 5)
**Goal:** Add native app features

#### Task 5.1: Touch Gestures
- [ ] Swipe to delete (lists)
- [ ] Swipe between tabs
- [ ] Pull-to-refresh (all lists)
- [ ] Pinch-to-zoom (images/charts)
- [ ] Long-press actions

#### Task 5.2: Mobile Navigation
- [ ] Bottom nav with active states
- [ ] Tab bar animations
- [ ] Swipe-up menu
- [ ] Breadcrumb mobile
- [ ] Back button handling

#### Task 5.3: Mobile Search
- [ ] Full-screen search overlay
- [ ] Voice search integration
- [ ] Recent searches
- [ ] Search suggestions
- [ ] Filter bottom sheet

#### Task 5.4: Notifications Mobile
- [ ] Full-screen notifications
- [ ] Notification badges
- [ ] Push notifications setup
- [ ] In-app notification center

**Deliverables:**
- ✅ Native app-like interactions
- ✅ Gesture support
- ✅ Enhanced mobile UX

---

### Phase 6: Performance & PWA (Week 6)
**Goal:** Optimize and enable PWA

#### Task 6.1: Code Splitting
- [ ] Route-based code splitting
- [ ] Component lazy loading
- [ ] Dynamic imports for heavy components
- [ ] Bundle size analysis

#### Task 6.2: Image Optimization
- [ ] Responsive images
- [ ] Lazy loading images
- [ ] WebP format support
- [ ] Image CDN integration

#### Task 6.3: PWA Setup
- [ ] Service worker
- [ ] Offline support
- [ ] App manifest
- [ ] Install prompt
- [ ] App icons (all sizes)
- [ ] Splash screens

#### Task 6.4: Performance Tuning
- [ ] Lighthouse audit (>90 mobile score)
- [ ] Core Web Vitals optimization
- [ ] Bundle size reduction
- [ ] Initial load optimization
- [ ] Runtime performance

**Deliverables:**
- ✅ PWA-ready application
- ✅ <3s load time on 3G
- ✅ Lighthouse score >90

---

### Phase 7: Testing & Polish (Week 7)
**Goal:** Comprehensive testing

#### Task 7.1: Device Testing
- [ ] iPhone SE (320px)
- [ ] iPhone 12/13/14 (390px)
- [ ] iPhone Pro Max (428px)
- [ ] Samsung Galaxy (360px)
- [ ] iPad Mini (768px)
- [ ] iPad Pro (1024px)

#### Task 7.2: Browser Testing
- [ ] Safari iOS
- [ ] Chrome Android
- [ ] Chrome iOS
- [ ] Firefox Android
- [ ] Samsung Internet

#### Task 7.3: Interaction Testing
- [ ] Touch gestures
- [ ] Keyboard navigation
- [ ] Screen reader support
- [ ] Dark mode (if implemented)
- [ ] RTL support (if needed)

#### Task 7.4: Performance Testing
- [ ] 3G network simulation
- [ ] Lighthouse audits
- [ ] Real device testing
- [ ] Memory profiling

**Deliverables:**
- ✅ Tested on 15+ devices
- ✅ Cross-browser compatible
- ✅ Accessibility compliant

---

## Progress Tracking

### Tracking Document: `MOBILE_REDESIGN_PROGRESS.md`

```markdown
# Mobile Redesign Progress Tracker

## Overall Progress: 0% (0/30 pages complete)

### Phase 1: Foundation [0%]
- [ ] Design System Setup
- [ ] Layout Architecture
- [ ] Shared Components

### Phase 2: Core Pages [0/13]
#### Auth [0/6]
- [ ] Login
- [ ] Register
- [ ] Forgot Password
- [ ] Reset Password
- [ ] Verify Email
- [ ] 2FA Verify

#### Dashboard [0/2]
- [ ] Main Dashboard
- [ ] Dashboard Widgets

#### Transactions [0/5]
- [ ] Transactions List
- [ ] Transaction Detail
- [ ] Create Transaction
- [ ] Edit Transaction
- [ ] Timeline View

### Phase 3: Content Pages [0/6]
- [ ] Documents (1)
- [ ] Templates (3)
- [ ] Task Library (1)
- [ ] Timeline (1)

### Phase 4: Integration Pages [0/6]
- [ ] MLS Integration
- [ ] CRM Integration
- [ ] DocuSign
- [ ] Settings
- [ ] User Profile
- [ ] Reminders

### Phase 5: Mobile Enhancements [0%]
- [ ] Touch Gestures
- [ ] Mobile Navigation
- [ ] Mobile Search
- [ ] Notifications Mobile

### Phase 6: Performance & PWA [0%]
- [ ] Code Splitting
- [ ] Image Optimization
- [ ] PWA Setup
- [ ] Performance Tuning

### Phase 7: Testing & Polish [0%]
- [ ] Device Testing
- [ ] Browser Testing
- [ ] Interaction Testing
- [ ] Performance Testing
```

---

## Logging Strategy

### Git Commit Convention

```
<type>(<scope>): <subject>

Types:
- feat: New mobile feature
- refactor: Mobile-first refactoring
- style: Mobile styling changes
- perf: Performance improvement
- test: Mobile testing
- docs: Documentation

Scopes:
- mobile-layout
- mobile-nav
- mobile-[component]
- mobile-[page]

Examples:
feat(mobile-layout): add bottom navigation component
refactor(mobile-auth): redesign login page for mobile-first
style(mobile-dashboard): optimize cards for small screens
```

### Change Log: `CHANGELOG_MOBILE.md`

```markdown
# Mobile Redesign Change Log

## [Unreleased]

### Phase 1 - Foundation
- [ ] Added mobile-first Tailwind configuration
- [ ] Created responsive layout system
- [ ] Redesigned shared components for mobile

### Phase 2 - Core Pages
- [ ] Redesigned authentication pages
- [ ] Redesigned dashboard
- [ ] Redesigned transactions module

... (continued for each phase)
```

---

## Development Guidelines

### File Naming Conventions

```
src/
├── components/
│   ├── mobile/              # Mobile-specific components
│   │   ├── BottomNav.tsx
│   │   ├── MobileHeader.tsx
│   │   ├── MobileMenu.tsx
│   │   └── ...
│   ├── responsive/          # Responsive variants
│   │   ├── ResponsiveTable.tsx
│   │   ├── ResponsiveCard.tsx
│   │   └── ...
│   └── shared/              # Shared components (mobile-first)
│       └── ...
└── hooks/
    ├── useMediaQuery.ts     # Responsive breakpoint hook
    ├── useTouchGestures.ts  # Touch gesture hooks
    └── useDeviceType.ts     # Device detection
```

### Code Standards

#### 1. Always Mobile-First
```tsx
// ✅ Good: Mobile-first
<div className="flex flex-col gap-2 md:flex-row md:gap-4">

// ❌ Bad: Desktop-first
<div className="flex flex-row gap-4 mobile:flex-col mobile:gap-2">
```

#### 2. Use Responsive Utilities
```tsx
// ✅ Good: Responsive classes
<div className="px-4 py-3 md:px-6 md:py-4">

// ❌ Bad: Fixed classes
<div className="px-6 py-4">
```

#### 3. Touch-Friendly Sizing
```tsx
// ✅ Good: Min 44px touch target
<button className="min-h-[44px] min-w-[44px] p-3">

// ❌ Bad: Too small for touch
<button className="h-8 w-8 p-1">
```

#### 4. Conditional Rendering for Complex Differences
```tsx
// ✅ Good: Separate mobile/desktop components
const isMobile = useMediaQuery('(max-width: 768px)');
return isMobile ? <MobileTable /> : <DesktopTable />;

// ❌ Bad: Cramming everything with classes
<table className="block md:table">
```

#### 5. Performance Considerations
```tsx
// ✅ Good: Lazy load heavy components
const HeavyComponent = lazy(() => import('./HeavyComponent'));

// ✅ Good: Conditional loading
const MobileMenu = lazy(() => import('./mobile/MobileMenu'));
```

---

## Testing Checklist

### Per-Page Testing Checklist

For each page, ensure:

#### Visual Testing
- [ ] Renders correctly at 320px (iPhone SE)
- [ ] Renders correctly at 375px (iPhone 12/13)
- [ ] Renders correctly at 390px (iPhone 14)
- [ ] Renders correctly at 428px (iPhone Pro Max)
- [ ] Renders correctly at 768px (iPad)
- [ ] Renders correctly at 1024px (Desktop)
- [ ] No horizontal scroll at any size
- [ ] All text readable (min 14px)
- [ ] All images load and scale
- [ ] All icons visible and sized correctly

#### Interaction Testing
- [ ] All buttons >44px touch target
- [ ] Forms work with mobile keyboards
- [ ] Dropdowns work on touch devices
- [ ] Modals can be dismissed easily
- [ ] Scrolling is smooth
- [ ] Pull-to-refresh works (if applicable)
- [ ] Swipe gestures work (if applicable)
- [ ] Bottom nav accessible and functional

#### Performance Testing
- [ ] Page loads <3s on 3G
- [ ] Interactions respond <200ms
- [ ] Animations run at 60fps
- [ ] No memory leaks
- [ ] Lighthouse mobile score >90

#### Accessibility Testing
- [ ] Touch targets >44px
- [ ] Color contrast ratio >4.5:1
- [ ] Focus indicators visible
- [ ] Screen reader compatible
- [ ] Keyboard navigable

---

## Documentation Structure

```
docs/
├── MOBILE_FIRST_REDESIGN.md         # This document (master plan)
├── MOBILE_REDESIGN_PROGRESS.md      # Progress tracker (updated daily)
├── CHANGELOG_MOBILE.md              # Change log
├── DESIGN_SYSTEM.md                 # Design system spec
├── LAYOUT_GUIDE.md                  # Layout patterns
├── COMPONENT_GUIDELINES.md          # Component standards
├── TESTING_GUIDE.md                 # Testing procedures
├── PERFORMANCE_GUIDE.md             # Performance optimization
└── mobile/
    ├── components/                  # Component-specific docs
    │   ├── BottomNav.md
    │   ├── MobileHeader.md
    │   └── ...
    └── pages/                       # Page-specific docs
        ├── auth/
        │   ├── Login.md
        │   └── ...
        └── ...
```

---

## Success Criteria

### Must-Have (MVP)
- ✅ All 30+ pages responsive (320px - 2560px)
- ✅ Bottom navigation on mobile
- ✅ Touch-optimized interactions (min 44px targets)
- ✅ Mobile-first layouts (stack on mobile)
- ✅ Lighthouse mobile score >85
- ✅ Load time <5s on 3G
- ✅ No horizontal scroll on any device

### Should-Have (V1.1)
- ✅ Touch gestures (swipe, pull-to-refresh)
- ✅ PWA capabilities (offline support)
- ✅ Lighthouse mobile score >90
- ✅ Load time <3s on 3G
- ✅ Native app-like transitions

### Nice-to-Have (V1.2)
- ✅ Haptic feedback
- ✅ Voice search
- ✅ Dark mode
- ✅ Custom install prompt
- ✅ Advanced gestures (pinch-to-zoom)

---

## Risk Management

### Identified Risks

1. **Scope Creep**
   - *Risk:* Adding features beyond mobile-first
   - *Mitigation:* Strict phase-by-phase approach, MVP focus

2. **Breaking Changes**
   - *Risk:* Redesign breaks existing functionality
   - *Mitigation:* Comprehensive testing, feature flags, gradual rollout

3. **Performance Regression**
   - *Risk:* New code slows down app
   - *Mitigation:* Performance budgets, continuous monitoring

4. **Browser Compatibility**
   - *Risk:* Features don't work on all devices
   - *Mitigation:* Progressive enhancement, polyfills, extensive testing

5. **Timeline Overrun**
   - *Risk:* Project takes longer than 7 weeks
   - *Mitigation:* Buffer time, parallel work streams, clear priorities

---

## Next Steps

### Immediate Actions (This Session)
1. ✅ Review and approve this plan
2. ⏳ Create `MOBILE_REDESIGN_PROGRESS.md`
3. ⏳ Create `CHANGELOG_MOBILE.md`
4. ⏳ Set up development branch: `feature/mobile-first-redesign`

### Week 1 Kickoff
1. Create design system Tailwind config
2. Build `BottomNav` component
3. Build `MobileHeader` component
4. Redesign `Button` component (mobile-first)
5. Redesign `Card` component (mobile-first)

### Questions for Product Team
1. Do we need offline support in MVP?
2. Should we implement dark mode now or later?
3. Any specific devices we must support?
4. Can we use feature flags for gradual rollout?
5. What's the priority order if timeline is tight?

---

## Appendix

### A. Reference Designs
- **Airbnb Mobile:** Card-based layouts, bottom navigation
- **Zillow Mobile:** Property listings, swipe gestures
- **Trello Mobile:** Board view, drag-and-drop on mobile
- **Linear:** Clean mobile-first design, gesture support

### B. Tools & Resources
- **Design:** Figma mobile templates
- **Testing:** BrowserStack, Sauce Labs
- **Performance:** Lighthouse, WebPageTest, Chrome DevTools
- **Analytics:** Google Analytics (mobile metrics)
- **Monitoring:** Sentry (mobile error tracking)

### C. Browser Support Matrix
| Browser | Version | Support Level |
|---------|---------|---------------|
| Safari iOS | 14+ | Full |
| Chrome Android | 90+ | Full |
| Chrome iOS | 90+ | Full |
| Firefox Android | 90+ | Full |
| Samsung Internet | 14+ | Full |
| Edge Mobile | 90+ | Full |

### D. Device Priority List
**Tier 1 (Must Support):**
- iPhone 12/13/14 (390×844)
- iPhone Pro Max (428×926)
- Samsung Galaxy S21/S22 (360×800)
- iPad (768×1024)

**Tier 2 (Should Support):**
- iPhone SE (375×667)
- Samsung Galaxy A series (412×915)
- Google Pixel (412×915)
- iPad Pro (1024×1366)

**Tier 3 (Nice to Support):**
- Small Android phones (320px)
- Large Android tablets (1200px+)
- Foldable devices

---

## Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | 2025-11-05 | AI Team | Initial comprehensive plan |

---

**Last Updated:** 2025-11-05
**Next Review:** Start of Phase 1
