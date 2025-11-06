import { useState, FormEvent } from 'react';
import { Input } from '../shared/Input';
import { Button } from '../shared/Button';
import { useAuth } from '@/hooks/useAuth';
import { cn } from '@/utils/cn';

/**
 * LoginForm Component (Mobile-First Redesign)
 *
 * Responsive login form with mobile-first design:
 * - Touch-friendly inputs (44px minimum from Phase 1 Input component)
 * - Mobile keyboard optimization (inputMode="email" from Phase 1)
 * - Responsive text sizing
 * - Touch-friendly checkbox and links
 *
 * Features:
 * - Email/password authentication
 * - Remember me option
 * - Error message display
 * - Loading states
 * - Touch-optimized interactive elements
 */

export interface LoginFormProps {
  onSuccess?: () => void;
  onForgotPassword?: () => void;
  onRegister?: () => void;
}

export const LoginForm = ({ onSuccess, onForgotPassword, onRegister }: LoginFormProps) => {
  const { login, isLoading, error, clearError } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [remember, setRemember] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    clearError();

    try {
      await login({
        email,
        password,
        remember,
        device_name: navigator.userAgent,
        device_type: 'web',
      });

      onSuccess?.();
    } catch (err) {
      // Error is already set in the auth store
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-5 md:space-y-6">
      {/* Header - responsive text sizing */}
      <div>
        <h2 className="text-xl md:text-2xl font-bold text-gray-900">
          Sign in to your account
        </h2>
        <p className="mt-2 text-sm md:text-base text-gray-600">
          Or{' '}
          <button
            type="button"
            onClick={onRegister}
            className={cn(
              'font-medium text-primary-600 hover:text-primary-500',
              'underline',
              // Touch-friendly padding
              'py-1'
            )}
          >
            create a new account
          </button>
        </p>
      </div>

      {/* Error message - responsive sizing */}
      {error && (
        <div className={cn(
          'rounded-md bg-danger-50',
          'p-3 md:p-4'
        )}>
          <div className="flex">
            <div className="flex-shrink-0">
              <svg
                className="h-5 w-5 md:h-6 md:w-6 text-danger-400"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
              >
                <path
                  fillRule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                  clipRule="evenodd"
                />
              </svg>
            </div>
            <div className="ml-3">
              <h3 className="text-sm md:text-base font-medium text-danger-800">
                {error}
              </h3>
            </div>
          </div>
        </div>
      )}

      {/* Form fields - Input component already mobile-first from Phase 1 */}
      <div className="space-y-4 md:space-y-5">
        <Input
          label="Email address"
          type="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
          autoComplete="email"
          placeholder="you@example.com"
          disabled={isLoading}
          // inputMode="email" is auto-applied in Phase 1 Input component
        />

        <Input
          label="Password"
          type="password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
          autoComplete="current-password"
          placeholder="Enter your password"
          disabled={isLoading}
        />
      </div>

      {/* Remember me and forgot password - mobile-first layout */}
      <div className={cn(
        'flex flex-col space-y-3',
        'md:flex-row md:items-center md:justify-between md:space-y-0'
      )}>
        {/* Remember me checkbox - touch-friendly */}
        <div className="flex items-center">
          <input
            id="remember-me"
            name="remember-me"
            type="checkbox"
            checked={remember}
            onChange={(e) => setRemember(e.target.checked)}
            className={cn(
              // Touch-friendly size on mobile
              'h-5 w-5 md:h-4 md:w-4',
              'rounded border-gray-300 text-primary-600',
              'focus:ring-primary-600 focus:ring-2',
              'cursor-pointer'
            )}
            disabled={isLoading}
          />
          <label
            htmlFor="remember-me"
            className={cn(
              'ml-2 block text-sm md:text-base text-gray-900',
              'cursor-pointer',
              // Larger touch target
              'py-1'
            )}
          >
            Remember me
          </label>
        </div>

        {/* Forgot password link - touch-friendly */}
        <div className="text-sm md:text-base">
          <button
            type="button"
            onClick={onForgotPassword}
            className={cn(
              'font-medium text-primary-600 hover:text-primary-500',
              'underline',
              // Touch-friendly padding
              'py-1 px-1',
              'transition-colors duration-fast'
            )}
            disabled={isLoading}
          >
            Forgot your password?
          </button>
        </div>
      </div>

      {/* Submit button - Button component already mobile-first from Phase 1 */}
      <Button
        type="submit"
        size="lg"
        className="w-full"
        isLoading={isLoading}
      >
        Sign in
      </Button>
    </form>
  );
};
