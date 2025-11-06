import { NavLink, useLocation } from 'react-router-dom';
import { Home, FileText, FolderOpen, Bell, Settings } from 'lucide-react';
import { cn } from '@/utils/cn';

/**
 * Bottom Navigation Component (Mobile-First)
 *
 * Displays at the bottom of the screen on mobile devices (<768px).
 * Features:
 * - 5 primary navigation items
 * - Active state indicators
 * - Touch-friendly targets (min 44px)
 * - Smooth transitions
 * - Safe area insets for notched devices
 *
 * Hidden on tablet and desktop (>=768px) where sidebar is used instead.
 */

interface NavItem {
  name: string;
  href: string;
  icon: React.ComponentType<{ className?: string }>;
  badge?: number;
}

const navigationItems: NavItem[] = [
  {
    name: 'Home',
    href: '/',
    icon: Home,
  },
  {
    name: 'Transactions',
    href: '/transactions',
    icon: FileText,
  },
  {
    name: 'Documents',
    href: '/documents',
    icon: FolderOpen,
  },
  {
    name: 'Reminders',
    href: '/reminders',
    icon: Bell,
  },
  {
    name: 'Settings',
    href: '/settings',
    icon: Settings,
  },
];

export const BottomNav = () => {
  const location = useLocation();

  /**
   * Check if nav item is active
   * Matches exact path or path prefix
   */
  const isActive = (href: string): boolean => {
    if (href === '/') {
      return location.pathname === '/';
    }
    return location.pathname.startsWith(href);
  };

  return (
    <nav
      className="fixed bottom-0 left-0 right-0 z-mobile-nav bg-white border-t border-gray-200 md:hidden pb-safe-bottom"
      role="navigation"
      aria-label="Mobile navigation"
    >
      <div className="flex justify-around items-center h-16 px-2">
        {navigationItems.map((item) => {
          const Icon = item.icon;
          const active = isActive(item.href);

          return (
            <NavLink
              key={item.href}
              to={item.href}
              className={cn(
                'flex flex-col items-center justify-center',
                'min-w-touch min-h-touch',
                'px-3 py-2 rounded-lg',
                'transition-all duration-fast',
                'relative',
                active
                  ? 'text-primary-600'
                  : 'text-gray-500 hover:text-gray-700 active:bg-gray-100'
              )}
              aria-current={active ? 'page' : undefined}
            >
              {/* Icon */}
              <div className="relative">
                <Icon
                  className={cn(
                    'h-6 w-6 transition-transform duration-fast',
                    active && 'scale-110'
                  )}
                />

                {/* Badge (if any) */}
                {item.badge !== undefined && item.badge > 0 && (
                  <span
                    className="absolute -top-1 -right-1 h-4 min-w-[16px] px-1 flex items-center justify-center text-[10px] font-bold text-white bg-danger-500 rounded-full"
                    aria-label={`${item.badge} notifications`}
                  >
                    {item.badge > 99 ? '99+' : item.badge}
                  </span>
                )}
              </div>

              {/* Label */}
              <span
                className={cn(
                  'text-[11px] font-medium mt-1',
                  'transition-colors duration-fast'
                )}
              >
                {item.name}
              </span>

              {/* Active indicator */}
              {active && (
                <span
                  className="absolute bottom-0 left-1/2 -translate-x-1/2 w-12 h-1 bg-primary-600 rounded-full"
                  aria-hidden="true"
                />
              )}
            </NavLink>
          );
        })}
      </div>
    </nav>
  );
};

/**
 * Spacer component to prevent content from being hidden behind bottom nav
 * Add this at the end of your mobile content to ensure proper spacing
 */
export const BottomNavSpacer = () => {
  return <div className="h-16 md:hidden" aria-hidden="true" />;
};
