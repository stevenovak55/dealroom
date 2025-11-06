import { cn } from '@/utils/cn';

/**
 * Card Component (Mobile-First)
 *
 * Responsive card container with mobile-optimized spacing.
 * - Mobile: Compact padding (p-4)
 * - Desktop: Generous padding (px-6 py-4)
 */

interface CardProps {
  children: React.ReactNode;
  className?: string;
  /** Interactive card with hover effect */
  interactive?: boolean;
}

export const Card = ({ children, className, interactive = false }: CardProps) => {
  return (
    <div
      className={cn(
        'rounded-lg border border-gray-200 bg-white shadow-sm',
        'transition-shadow duration-fast',
        interactive && 'hover:shadow-md cursor-pointer active:scale-[0.99]',
        className
      )}
    >
      {children}
    </div>
  );
};

interface CardHeaderProps {
  children: React.ReactNode;
  className?: string;
}

export const CardHeader = ({ children, className }: CardHeaderProps) => {
  return (
    <div
      className={cn(
        // Mobile-first padding
        'px-4 py-3',
        'md:px-6 md:py-4',
        'border-b border-gray-200',
        className
      )}
    >
      {children}
    </div>
  );
};

interface CardTitleProps {
  children: React.ReactNode;
  className?: string;
}

export const CardTitle = ({ children, className }: CardTitleProps) => {
  return (
    <h3
      className={cn(
        // Mobile-first text size
        'text-base font-semibold text-gray-900',
        'md:text-lg',
        className
      )}
    >
      {children}
    </h3>
  );
};

interface CardContentProps {
  children: React.ReactNode;
  className?: string;
}

export const CardContent = ({ children, className }: CardContentProps) => {
  return (
    <div
      className={cn(
        // Mobile-first padding
        'px-4 py-3',
        'md:px-6 md:py-4',
        className
      )}
    >
      {children}
    </div>
  );
};

interface CardFooterProps {
  children: React.ReactNode;
  className?: string;
}

export const CardFooter = ({ children, className }: CardFooterProps) => {
  return (
    <div
      className={cn(
        // Mobile-first padding
        'px-4 py-3',
        'md:px-6 md:py-4',
        'border-t border-gray-200 bg-gray-50',
        className
      )}
    >
      {children}
    </div>
  );
};
