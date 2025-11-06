import { Menu, Bell, ArrowLeft, MoreVertical } from 'lucide-react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useState, useRef, useEffect } from 'react';
import { cn } from '@/utils/cn';
import { useGetUnreadCount, useGetNotifications, useMarkAsRead } from '@/api/queries/useNotifications';
import { formatDistanceToNow } from 'date-fns';

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
  const [showNotificationsPanel, setShowNotificationsPanel] = useState(false);
  const notificationsRef = useRef<HTMLDivElement>(null);

  // Get notifications
  const { data: unreadCount = 0 } = useGetUnreadCount();
  const { data: notificationsData } = useGetNotifications({ limit: 10 });
  const markAsRead = useMarkAsRead();

  const notifications = notificationsData?.data || [];

  // Close notifications panel when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (notificationsRef.current && !notificationsRef.current.contains(event.target as Node)) {
        setShowNotificationsPanel(false);
      }
    };

    if (showNotificationsPanel) {
      document.addEventListener('mousedown', handleClickOutside);
      return () => document.removeEventListener('mousedown', handleClickOutside);
    }
  }, [showNotificationsPanel]);

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
          <div ref={notificationsRef} className="relative">
            <button
              type="button"
              onClick={() => setShowNotificationsPanel(!showNotificationsPanel)}
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

            {/* Notifications Panel */}
            {showNotificationsPanel && (
              <div
                className={cn(
                  'absolute right-0 top-full mt-2',
                  'w-80 max-w-[calc(100vw-2rem)] bg-white rounded-lg shadow-xl',
                  'border border-gray-200',
                  'max-h-96 overflow-y-auto',
                  'animate-in fade-in slide-in-from-top-2 duration-fast'
                )}
                role="menu"
              >
                <div className="p-3 border-b border-gray-200 flex items-center justify-between">
                  <h3 className="font-semibold text-gray-900">Notifications</h3>
                  {unreadCount > 0 && (
                    <span className="text-xs text-gray-500">{unreadCount} unread</span>
                  )}
                </div>

                {notifications.length === 0 ? (
                  <div className="p-8 text-center text-gray-500 text-sm">
                    No notifications
                  </div>
                ) : (
                  <div>
                    {notifications.map((notification) => (
                      <button
                        key={notification.id}
                        onClick={() => {
                          if (!notification.is_read) {
                            markAsRead.mutate(notification.id);
                          }
                          if (notification.link) {
                            navigate(notification.link);
                          }
                          setShowNotificationsPanel(false);
                        }}
                        className={cn(
                          'w-full text-left p-3 border-b border-gray-100 last:border-0',
                          'hover:bg-gray-50 active:bg-gray-100 transition-colors',
                          !notification.is_read && 'bg-blue-50'
                        )}
                      >
                        <div className="flex items-start gap-2">
                          {!notification.is_read && (
                            <div className="flex-shrink-0 w-2 h-2 rounded-full bg-blue-600 mt-1.5" />
                          )}
                          <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium text-gray-900 line-clamp-1">
                              {notification.title}
                            </p>
                            <p className="text-xs text-gray-600 mt-0.5 line-clamp-2">
                              {notification.message}
                            </p>
                            <p className="text-xs text-gray-500 mt-1">
                              {formatDistanceToNow(new Date(notification.created_at), { addSuffix: true })}
                            </p>
                          </div>
                        </div>
                      </button>
                    ))}
                  </div>
                )}
              </div>
            )}
          </div>

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
