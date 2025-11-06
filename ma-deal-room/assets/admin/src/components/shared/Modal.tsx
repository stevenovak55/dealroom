import { useEffect } from 'react';
import { X } from 'lucide-react';
import { cn } from '@/utils/cn';
import { useIsMobile } from '@/hooks/useMediaQuery';

/**
 * Modal Component (Mobile-First)
 *
 * Responsive modal that adapts to screen size:
 * - Mobile (<768px): Full-screen with slide-up animation
 * - Desktop (>=768px): Centered dialog with max-width
 *
 * Features:
 * - Body scroll lock when open
 * - Escape key to close
 * - Click backdrop to close
 * - Touch-friendly close button
 * - Safe area insets on mobile
 */

interface ModalProps {
  isOpen: boolean;
  onClose: () => void;
  title?: string;
  children: React.ReactNode;
  size?: 'sm' | 'md' | 'lg' | 'xl' | 'full';
  showCloseButton?: boolean;
}

export const Modal = ({
  isOpen,
  onClose,
  title,
  children,
  size = 'md',
  showCloseButton = true,
}: ModalProps) => {
  const isMobile = useIsMobile();

  // Handle escape key
  useEffect(() => {
    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && isOpen) {
        onClose();
      }
    };

    document.addEventListener('keydown', handleEscape);
    return () => document.removeEventListener('keydown', handleEscape);
  }, [isOpen, onClose]);

  // Body scroll lock
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

  // Desktop sizes (ignored on mobile where it's full-screen)
  const sizes = {
    sm: 'md:max-w-md',
    md: 'md:max-w-lg',
    lg: 'md:max-w-2xl',
    xl: 'md:max-w-4xl',
    full: 'md:max-w-7xl',
  };

  return (
    <div
      className="fixed inset-0 z-mobile-modal overflow-y-auto"
      role="dialog"
      aria-modal="true"
      aria-labelledby={title ? 'modal-title' : undefined}
    >
      {/* Backdrop */}
      <div
        className={cn(
          'fixed inset-0 bg-black/50',
          'transition-opacity duration-normal',
          'animate-in fade-in'
        )}
        onClick={onClose}
        aria-hidden="true"
      />

      {/* Modal Container */}
      <div
        className={cn(
          'flex',
          // Mobile: Full-screen, align to bottom
          'min-h-full items-end',
          // Desktop: Centered
          'md:min-h-full md:items-center md:justify-center md:p-4'
        )}
      >
        <div
          className={cn(
            'relative w-full bg-white shadow-xl',
            // Mobile: Full-screen with rounded top corners, slide up animation
            'max-h-[90vh] rounded-t-2xl',
            'animate-in slide-in-from-bottom duration-normal',
            'pt-safe-top pb-safe-bottom',
            // Desktop: Centered dialog with all rounded corners
            'md:max-h-[85vh] md:rounded-lg',
            'md:animate-in md:fade-in md:zoom-in-95',
            sizes[size]
          )}
          onClick={(e) => e.stopPropagation()}
        >
          {/* Drag handle (mobile only) */}
          {isMobile && (
            <div className="flex justify-center pt-3 pb-2 md:hidden">
              <div className="w-12 h-1 bg-gray-300 rounded-full" aria-hidden="true" />
            </div>
          )}

          {/* Header */}
          {(title || showCloseButton) && (
            <div
              className={cn(
                'flex items-center justify-between',
                'px-4 py-3',
                'md:px-6 md:py-4',
                'border-b border-gray-200'
              )}
            >
              {title && (
                <h2
                  id="modal-title"
                  className={cn(
                    'font-semibold text-gray-900',
                    'text-lg',
                    'md:text-xl'
                  )}
                >
                  {title}
                </h2>
              )}
              <div className="flex-1" />
              {showCloseButton && (
                <button
                  type="button"
                  onClick={onClose}
                  className={cn(
                    'min-h-touch min-w-touch',
                    'flex items-center justify-center',
                    'p-2 -mr-2 rounded-lg',
                    'text-gray-400 hover:text-gray-600 hover:bg-gray-100',
                    'transition-colors duration-fast'
                  )}
                  aria-label="Close"
                >
                  <X className="h-5 w-5" />
                </button>
              )}
            </div>
          )}

          {/* Content */}
          <div
            className={cn(
              'overflow-y-auto',
              'px-4 py-3',
              'md:px-6 md:py-4',
              // Calculate max height (full height - header - footer - safe areas)
              'max-h-[calc(90vh-8rem)]',
              'md:max-h-[calc(85vh-8rem)]'
            )}
          >
            {children}
          </div>
        </div>
      </div>
    </div>
  );
};

interface ModalFooterProps {
  children: React.ReactNode;
  className?: string;
}

export const ModalFooter = ({ children, className }: ModalFooterProps) => {
  return (
    <div
      className={cn(
        'flex items-center gap-3',
        // Mobile: Stack buttons vertically, full width
        'flex-col-reverse',
        'px-4 py-3',
        // Desktop: Horizontal layout
        'md:flex-row md:justify-end',
        'md:px-6 md:py-4',
        'border-t border-gray-200 bg-gray-50',
        className
      )}
    >
      {children}
    </div>
  );
};
