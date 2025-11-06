import { Fragment, ReactNode, useEffect } from 'react';
import { X } from 'lucide-react';
import { cn } from '@/utils/cn';
import { useIsMobile } from '@/hooks/useMediaQuery';

/**
 * Drawer Component (Mobile-First Redesign)
 *
 * Responsive drawer/sheet component:
 * - Mobile (<768px): Bottom sheet that slides up from bottom
 * - Desktop (>=768px): Side drawer that slides in from right
 *
 * Features:
 * - Touch-friendly close button (44px minimum)
 * - Drag handle on mobile
 * - Body scroll lock when open
 * - Safe area insets for notched devices
 * - Smooth animations
 * - Backdrop overlay
 * - Keyboard accessibility (Escape key)
 */

interface DrawerProps {
  /** Whether the drawer is open */
  isOpen: boolean;
  /** Callback when drawer should close */
  onClose: () => void;
  /** Optional title shown in header */
  title?: string;
  /** Drawer content */
  children: ReactNode;
  /** Size (desktop only) */
  size?: 'sm' | 'md' | 'lg' | 'xl';
}

// Desktop drawer sizes (right-side)
const desktopSizeClasses = {
  sm: 'max-w-md',
  md: 'max-w-lg',
  lg: 'max-w-2xl',
  xl: 'max-w-4xl',
};

export const Drawer = ({
  isOpen,
  onClose,
  title,
  children,
  size = 'lg',
}: DrawerProps) => {
  const isMobile = useIsMobile();

  // Close on escape key
  useEffect(() => {
    if (!isOpen) return;

    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        onClose();
      }
    };

    document.addEventListener('keydown', handleEscape);
    return () => document.removeEventListener('keydown', handleEscape);
  }, [isOpen, onClose]);

  // Prevent body scroll when drawer is open
  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }

    return () => {
      document.body.style.overflow = '';
    };
  }, [isOpen]);

  if (!isOpen) return null;

  return (
    <Fragment>
      {/* Backdrop overlay */}
      <div
        className={cn(
          'fixed inset-0 bg-black/50 z-40',
          'animate-in fade-in duration-normal'
        )}
        onClick={onClose}
        aria-hidden="true"
      />

      {isMobile ? (
        // Mobile: Bottom sheet
        <div
          className={cn(
            'fixed bottom-0 left-0 right-0 z-50',
            'bg-white rounded-t-2xl shadow-2xl',
            'max-h-[90vh] flex flex-col',
            'animate-in slide-in-from-bottom duration-normal',
            'pb-safe-bottom'
          )}
          role="dialog"
          aria-modal="true"
          aria-labelledby={title ? 'drawer-title' : undefined}
        >
          {/* Drag handle */}
          <div className="flex justify-center pt-3 pb-2">
            <div className="w-12 h-1 bg-gray-300 rounded-full" aria-hidden="true" />
          </div>

          {/* Header */}
          {title && (
            <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200">
              <h2 id="drawer-title" className="text-lg font-semibold text-gray-900">
                {title}
              </h2>
              <button
                type="button"
                onClick={onClose}
                className={cn(
                  'min-w-touch min-h-touch',
                  'flex items-center justify-center',
                  'p-2 -mr-2 rounded-lg',
                  'text-gray-500 hover:text-gray-700 hover:bg-gray-100',
                  'active:bg-gray-200',
                  'transition-colors duration-fast'
                )}
                aria-label="Close drawer"
              >
                <X className="h-6 w-6" />
              </button>
            </div>
          )}

          {/* Content - scrollable */}
          <div className="flex-1 overflow-y-auto overscroll-contain">
            {children}
          </div>
        </div>
      ) : (
        // Desktop: Right-side drawer
        <div
          className={cn(
            'fixed top-0 right-0 h-full w-full z-50',
            'bg-white shadow-2xl',
            'flex flex-col',
            'animate-in slide-in-from-right duration-normal',
            desktopSizeClasses[size]
          )}
          role="dialog"
          aria-modal="true"
          aria-labelledby={title ? 'drawer-title' : undefined}
        >
          {/* Header */}
          {title && (
            <div className="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
              <h2 id="drawer-title" className="text-xl font-semibold text-gray-900">
                {title}
              </h2>
              <button
                type="button"
                onClick={onClose}
                className={cn(
                  'p-2 rounded-lg',
                  'text-gray-500 hover:text-gray-700 hover:bg-gray-100',
                  'active:bg-gray-200',
                  'transition-colors duration-fast'
                )}
                aria-label="Close drawer"
              >
                <X className="h-5 w-5" />
              </button>
            </div>
          )}

          {/* Content - scrollable */}
          <div className="flex-1 overflow-y-auto">
            {children}
          </div>
        </div>
      )}
    </Fragment>
  );
};
