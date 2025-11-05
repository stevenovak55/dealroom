import { useState, FormEvent } from 'react';
import { Input } from '../shared/Input';
import { Button } from '../shared/Button';
import { useAuth } from '@/hooks/useAuth';
import { cn } from '@/utils/cn';

/**
 * ForgotPasswordForm Component (Mobile-First Redesign)
 *
 * Responsive password recovery form with mobile-first design:
 * - Touch-friendly input (44px minimum from Phase 1)
 * - Mobile keyboard optimization (inputMode="email")
 * - Responsive text sizing
 * - Two states: form and success confirmation
 *
 * Features:
 * - Single email field
 * - Password reset email request
 * - Success confirmation screen
 * - Touch-optimized buttons
 */

export interface ForgotPasswordFormProps {
  onSuccess?: () => void;
  onBackToLogin?: () => void;
}

export const ForgotPasswordForm = ({ onSuccess, onBackToLogin }: ForgotPasswordFormProps) => {
  const { requestPasswordReset, isLoading, error, clearError } = useAuth();
  const [email, setEmail] = useState('');
  const [submitted, setSubmitted] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    clearError();

    try {
      await requestPasswordReset(email);
      setSubmitted(true);
      onSuccess?.();
    } catch (err) {
      // Error is already set in the auth store
    }
  };

  if (submitted) {
    return (
      <div className="space-y-5 md:space-y-6">
        {/* Success state - responsive sizing */}
        <div className="text-center">
          <div className={cn(
            'mx-auto flex items-center justify-center rounded-full bg-success-100',
            // Larger icon on mobile
            'h-14 w-14 md:h-12 md:w-12'
          )}>
            <svg
              className="h-7 w-7 md:h-6 md:w-6 text-success-600"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              aria-hidden="true"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M5 13l4 4L19 7"
              />
            </svg>
          </div>
          <h2 className="mt-4 text-xl md:text-2xl font-bold text-gray-900">
            Check your email
          </h2>
          <p className="mt-2 text-sm md:text-base text-gray-600">
            If an account exists with the email <strong>{email}</strong>, you will receive password
            reset instructions.
          </p>
        </div>

        <div className="space-y-3">
          <p className="text-sm md:text-base text-gray-500">
            Didn't receive an email? Check your spam folder or try again with a different email
            address.
          </p>

          <Button
            type="button"
            variant="secondary"
            size="lg"
            className="w-full"
            onClick={onBackToLogin}
          >
            Back to sign in
          </Button>
        </div>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-5 md:space-y-6">
      {/* Header - responsive text sizing */}
      <div>
        <h2 className="text-xl md:text-2xl font-bold text-gray-900">
          Forgot your password?
        </h2>
        <p className="mt-2 text-sm md:text-base text-gray-600">
          No worries! Enter your email address and we'll send you instructions to reset your
          password.
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

      {/* Email input - Input component already mobile-first from Phase 1 */}
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

      {/* Action buttons - Button component already mobile-first from Phase 1 */}
      <div className="space-y-3">
        <Button
          type="submit"
          size="lg"
          className="w-full"
          isLoading={isLoading}
        >
          Send reset instructions
        </Button>

        <Button
          type="button"
          variant="ghost"
          size="lg"
          className="w-full"
          onClick={onBackToLogin}
          disabled={isLoading}
        >
          Back to sign in
        </Button>
      </div>
    </form>
  );
};
