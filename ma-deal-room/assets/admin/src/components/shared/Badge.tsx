import { cn } from '@/utils/cn';

/**
 * Badge Component (Mobile-First)
 *
 * Responsive badge with:
 * - Slightly larger on mobile for readability
 * - Responsive padding and text size
 * - Multiple variants for different states
 */

interface BadgeProps {
  children: React.ReactNode;
  variant?: 'default' | 'success' | 'warning' | 'danger' | 'info';
  size?: 'sm' | 'md' | 'lg';
  className?: string;
}

export const Badge = ({ children, variant = 'default', size = 'md', className }: BadgeProps) => {
  const variants = {
    default: 'bg-gray-100 text-gray-800',
    success: 'bg-success-100 text-success-800',
    warning: 'bg-warning-100 text-warning-800',
    danger: 'bg-danger-100 text-danger-800',
    info: 'bg-primary-100 text-primary-800',
  };

  const sizes = {
    // Mobile-first sizing
    sm: cn(
      'px-2 py-0.5 text-xs',
      'md:px-2 md:py-0.5'
    ),
    md: cn(
      'px-2.5 py-1 text-xs',
      'md:px-2.5 md:py-0.5 md:text-xs'
    ),
    lg: cn(
      'px-3 py-1.5 text-sm',
      'md:px-3 md:py-1 md:text-sm'
    ),
  };

  return (
    <span
      className={cn(
        'inline-flex items-center rounded-full font-medium',
        'transition-colors duration-fast',
        variants[variant],
        sizes[size],
        className
      )}
    >
      {children}
    </span>
  );
};
