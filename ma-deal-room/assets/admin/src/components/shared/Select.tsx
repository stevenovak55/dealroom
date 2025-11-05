import { SelectHTMLAttributes, forwardRef } from 'react';
import { cn } from '@/utils/cn';

/**
 * Select Component (Mobile-First)
 *
 * Touch-optimized native select with:
 * - Minimum 44px height on mobile
 * - Larger text for readability
 * - Native mobile picker on iOS/Android
 * - Responsive sizing
 */

export interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
  label?: string;
  error?: string;
  helperText?: string;
  options: Array<{ value: string; label: string }>;
}

export const Select = forwardRef<HTMLSelectElement, SelectProps>(
  ({ className, label, error, helperText, options, id, ...props }, ref) => {
    const selectId = id || label?.toLowerCase().replace(/\s+/g, '-');

    return (
      <div className="w-full">
        {label && (
          <label
            htmlFor={selectId}
            className={cn(
              'block font-medium text-gray-700 mb-1.5',
              'text-sm',
              'md:text-sm md:mb-1'
            )}
          >
            {label}
            {props.required && <span className="text-danger-500 ml-1">*</span>}
          </label>
        )}
        <select
          id={selectId}
          ref={ref}
          className={cn(
            'flex w-full rounded-lg border border-gray-300 bg-white',
            // Mobile-first sizing (touch-friendly)
            'min-h-touch px-4 py-3 text-base',
            // Desktop sizing
            'md:h-10 md:px-3 md:py-2 md:text-sm',
            // Styling
            'ring-offset-white',
            'transition-colors duration-fast',
            // Focus states
            'focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500',
            // Disabled state
            'disabled:cursor-not-allowed disabled:opacity-50 disabled:bg-gray-50',
            // Error state
            error && 'border-danger-500 focus:ring-danger-500 focus:border-danger-500',
            // Custom arrow (native on mobile)
            'appearance-none bg-no-repeat bg-right',
            'pr-10', // Room for dropdown arrow
            className
          )}
          style={{
            backgroundImage: `url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e")`,
            backgroundPosition: 'right 0.5rem center',
            backgroundSize: '1.5em 1.5em',
          }}
          {...props}
        >
          {options.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>
        {error && (
          <p className="mt-1.5 text-sm text-danger-600">
            {error}
          </p>
        )}
        {helperText && !error && (
          <p className="mt-1.5 text-sm text-gray-500">
            {helperText}
          </p>
        )}
      </div>
    );
  }
);

Select.displayName = 'Select';
