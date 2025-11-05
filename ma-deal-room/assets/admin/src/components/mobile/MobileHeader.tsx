import { Menu, Bell, ArrowLeft, MoreVertical } from 'lucide-react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useState } from 'react';
import { cn } from '@/utils/cn';
import { useGetUnreadCount } from '@/api/queries/useNotifications';

/**
 * Mobile Header Component
 *
 * Simplified header for mobile devices (<768px).
 * Features:
 * - Hamburger menu button (left)
 * - Page title (center)
 * - Action buttons (right)
 * - Back button on sub-pages
 * - Touch-friendly targets (min 44px)
 * - Safe area insets for notched devices
 *
 * Hidden on tablet and desktop (>=768px) where full header is used instead.
 */

interface MobileHeaderProps {
  /** Custom title override */
  title?: string;
  /** Show back button instead of menu */
  showBack?: boolean;
  /** Custom back action */
  onBack?: () => void;
  /** Show actions menu */
  showActions?: boolean;
  /** Custom actions menu items */
  actions?: React.ReactNode;
  /** Additional className */
  className?: string;
  /** Callback when menu is opened */
  onMenuOpen?: () => void;
}

/**
 * Get page title from current route
 */
const getPageTitle = (pathname: string): string => {
  const routes: Record<string, string> = {
    '/': 'Dashboard',
    '/transactions': 'Transactions',
    '/transactions/new': 'New Transaction',
    '/documents': 'Documents',
    '/templates': 'Templates',
    '/task-library': 'Task Library',
    '/reminders': 'Reminders',
    '/mls': 'MLS Integration',
    '/integrations/crm': 'CRM Integration',
    '/integrations/docusign': 'DocuSign',
    '/settings': 'Settings',
    '/profile': 'Profile',
  };

  // Check for dynamic routes
  if (pathname.startsWith('/transactions/') && pathname.includes('/edit')) {
    return 'Edit Transaction';
  }
  if (pathname.startsWith('/transactions/') && pathname.includes('/timeline')) {
    return 'Timeline';
  }
  if (pathname.match(/^\/transactions\/\d+$/)) {
    return 'Transaction Details';
  }
  if (pathname.startsWith('/templates/') && pathname.includes('/edit')) {
    return 'Edit Template';
  }
  if (pathname.startsWith('/templates/') && pathname.includes('/analytics')) {
    return 'Template Analytics';
  }

  return routes[pathname] || 'MA Deal Room';
};

export const MobileHeader = ({
  title,
  showBack = false,
  onBack,
  showActions = false,
  actions,
  className,
  onMenuOpen,
}: MobileHeaderProps) => {
  const navigate = useNavigate();
  const location = useLocation();
  const [showActionsMenu, setShowActionsMenu] = useState(false);

  // Get unread notifications count
  const { data: unreadCount = 0 } = useGetUnreadCount();

  // Determine page title
  const pageTitle = title || getPageTitle(location.pathname);

  // Determine if we should show back button
  const shouldShowBack = showBack || location.pathname !== '/';

  // Handle back navigation
  const handleBack = () => {
    if (onBack) {
      onBack();
    } else {
      navigate(-1);
    }
  };

  // Handle menu open
  const handleMenuOpen = () => {
    if (onMenuOpen) {
      onMenuOpen();
    }
  };

  return (
    <header
      className={cn(
        'fixed top-0 left-0 right-0 z-mobile-header',
        'bg-white border-b border-gray-200',
        'md:hidden',
        'pt-safe-top',
        className
      )}
      role="banner"
    >
      <div className="flex items-center justify-between h-14 px-4">
        {/* Left: Menu or Back button */}
        <div className="flex items-center">
          {shouldShowBack ? (
            <button
              type="button"
              onClick={handleBack}
              className={cn(
                'min-w-touch min-h-touch',
                'flex items-center justify-center',
                'p-2 -ml-2 rounded-lg',
                'text-gray-700 hover:bg-gray-100 active:bg-gray-200',
                'transition-colors duration-fast'
              )}
              aria-label="Go back"
            >
              <ArrowLeft className="h-6 w-6" />
            </button>
          ) : (
            <button
              type="button"
              onClick={handleMenuOpen}
              className={cn(
                'min-w-touch min-h-touch',
                'flex items-center justify-center',
                'p-2 -ml-2 rounded-lg',
                'text-gray-700 hover:bg-gray-100 active:bg-gray-200',
                'transition-colors duration-fast'
              )}
              aria-label="Open menu"
            >
              <Menu className="h-6 w-6" />
            </button>
          )}
        </div>

        {/* Center: Page title */}
        <h1 className="text-lg font-semibold text-gray-900 truncate px-4">
          {pageTitle}
        </h1>

        {/* Right: Action buttons */}
        <div className="flex items-center gap-1">
          {/* Notifications */}
          <button
            type="button"
            onClick={() => navigate('/notifications')}
            className={cn(
              'min-w-touch min-h-touch',
              'relative flex items-center justify-center',
              'p-2 rounded-lg',
              'text-gray-700 hover:bg-gray-100 active:bg-gray-200',
              'transition-colors duration-fast'
            )}
            aria-label={`Notifications${unreadCount > 0 ? ` (${unreadCount} unread)` : ''}`}
          >
            <Bell className="h-5 w-5" />
            {unreadCount > 0 && (
              <span
                className="absolute top-1 right-1 h-2 w-2 bg-danger-500 rounded-full"
                aria-hidden="true"
              />
            )}
          </button>

          {/* Actions menu */}
          {showActions && (
            <div className="relative">
              <button
                type="button"
                onClick={() => setShowActionsMenu(!showActionsMenu)}
                className={cn(
                  'min-w-touch min-h-touch',
                  'flex items-center justify-center',
                  'p-2 -mr-2 rounded-lg',
                  'text-gray-700 hover:bg-gray-100 active:bg-gray-200',
                  'transition-colors duration-fast'
                )}
                aria-label="More actions"
                aria-expanded={showActionsMenu}
              >
                <MoreVertical className="h-5 w-5" />
              </button>

              {/* Actions dropdown */}
              {showActionsMenu && actions && (
                <div
                  className={cn(
                    'absolute right-0 top-full mt-2',
                    'min-w-[180px] bg-white rounded-lg shadow-lg',
                    'border border-gray-200',
                    'py-2',
                    'animate-in fade-in slide-in-from-top-2 duration-fast'
                  )}
                  role="menu"
                >
                  {actions}
                </div>
              )}
            </div>
          )}
        </div>
      </div>
    </header>
  );
};

/**
 * Spacer component to prevent content from being hidden behind mobile header
 * Add this at the start of your mobile content
 */
export const MobileHeaderSpacer = () => {
  return <div className="h-14 md:hidden" aria-hidden="true" />;
};
