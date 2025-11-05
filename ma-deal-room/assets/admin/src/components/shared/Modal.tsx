import { useEffect } from 'react';
import { X } from 'lucide-react';
import { cn } from '@/utils/cn';

interface ModalProps {
  isOpen: boolean;
  onClose: () => void;
  title?: string;
  children: React.ReactNode;
  size?: 'sm' | 'md' | 'lg' | 'xl';
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
  useEffect(() => {
    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && isOpen) {
        onClose();
      }
    };

    document.addEventListener('keydown', handleEscape);
    return () => document.removeEventListener('keydown', handleEscape);
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  const sizes = {
    sm: 'md:max-w-md',
    md: 'md:max-w-lg',
    lg: 'md:max-w-2xl',
    xl: 'md:max-w-4xl',
  };

  return (
    <div className="fixed inset-0 overflow-y-auto" style={{ zIndex: 100000 }}>
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-black bg-opacity-50 transition-opacity animate-fade-in"
        onClick={onClose}
      />

      {/* Modal */}
      <div className="flex min-h-full items-end md:items-center justify-center md:p-4">
        <div
          className={cn(
            'relative w-full bg-white shadow-xl',
            // Mobile: full screen bottom sheet
            'max-h-[90vh] md:max-h-[85vh] rounded-t-2xl md:rounded-lg animate-slide-up md:animate-scale-up',
            // Desktop: centered modal
            sizes[size]
          )}
          onClick={(e) => e.stopPropagation()}
        >
          {/* Header */}
          {(title || showCloseButton) && (
            <div className="flex items-center justify-between px-4 py-3 md:px-6 md:py-4 border-b border-gray-200">
              {title && <h2 className="text-lg md:text-xl font-semibold text-gray-900">{title}</h2>}
              {showCloseButton && (
                <button
                  onClick={onClose}
                  className="text-gray-400 hover:text-gray-500 transition-colors touch-target p-2 -mr-2"
                >
                  <X className="h-5 w-5" />
                </button>
              )}
            </div>
          )}

          {/* Content */}
          <div className="px-4 py-3 md:px-6 md:py-4 overflow-y-auto max-h-[calc(90vh-120px)]">{children}</div>
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
    <div className={cn('flex items-center justify-end gap-2 md:gap-3 px-4 py-3 md:px-6 md:py-4 border-t border-gray-200 bg-gray-50', className)}>
      {children}
    </div>
  );
};
