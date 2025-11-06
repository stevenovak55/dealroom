import { useState, FormEvent } from 'react';
import { Input } from '../shared/Input';
import { Button } from '../shared/Button';
import { useAuth } from '@/hooks/useAuth';
import { cn } from '@/utils/cn';

/**
 * ResetPasswordForm Component (Mobile-First Redesign)
 *
 * Responsive password reset form with mobile-first design:
 * - Touch-friendly inputs (44px minimum from Phase 1)
 * - Responsive text sizing
 * - Two password fields with validation
 * - Success confirmation state
 *
 * Features:
 * - Password strength validation
 * - Password confirmation matching
 * - Clear error messages
 * - Success state with auto-redirect
 * - Touch-optimized buttons
 */

export interface ResetPasswordFormProps {
  token: string;
  onSuccess?: () => void;
}

export const ResetPasswordForm = ({ token, onSuccess }: ResetPasswordFormProps) => {
  const { resetPassword, isLoading, error, clearError } = useAuth();
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [validationErrors, setValidationErrors] = useState<Record<string, string>>({});
  const [submitted, setSubmitted] = useState(false);

  const validate = (): boolean => {
    const errors: Record<string, string> = {};

    // Password validation
    if (!newPassword) {
      errors.newPassword = 'Password is required';
    } else if (newPassword.length < 8) {
      errors.newPassword = 'Password must be at least 8 characters';
    } else if (!/[A-Z]/.test(newPassword)) {
      errors.newPassword = 'Password must contain at least one uppercase letter';
    } else if (!/[a-z]/.test(newPassword)) {
      errors.newPassword = 'Password must contain at least one lowercase letter';
    } else if (!/[0-9]/.test(newPassword)) {
      errors.newPassword = 'Password must contain at least one number';
    }

    // Confirm password validation
    if (!confirmPassword) {
      errors.confirmPassword = 'Please confirm your password';
    } else if (newPassword !== confirmPassword) {
      errors.confirmPassword = 'Passwords do not match';
    }

    setValidationErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    clearError();

    if (!validate()) {
      return;
    }

    try {
      await resetPassword({ token, new_password: newPassword });
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
            Password reset successful
          </h2>
          <p className="mt-2 text-sm md:text-base text-gray-600">
            Your password has been successfully reset. You can now sign in with your new password.
          </p>
        </div>

        <Button
          type="button"
          size="lg"
          className="w-full"
          onClick={onSuccess}
        >
          Go to sign in
        </Button>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-5 md:space-y-6">
      {/* Header - responsive text sizing */}
      <div>
        <h2 className="text-xl md:text-2xl font-bold text-gray-900">
          Reset your password
        </h2>
        <p className="mt-2 text-sm md:text-base text-gray-600">
          Please enter your new password below.
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

      {/* Password fields - Input component already mobile-first from Phase 1 */}
      <div className="space-y-4 md:space-y-5">
        <Input
          label="New password"
          type="password"
          value={newPassword}
          onChange={(e) => {
            setNewPassword(e.target.value);
            if (validationErrors.newPassword) {
              setValidationErrors((prev) => {
                const next = { ...prev };
                delete next.newPassword;
                return next;
              });
            }
          }}
          required
          autoComplete="new-password"
          placeholder="Create a strong password"
          disabled={isLoading}
          error={validationErrors.newPassword}
          helperText="At least 8 characters with uppercase, lowercase, and number"
        />

        <Input
          label="Confirm new password"
          type="password"
          value={confirmPassword}
          onChange={(e) => {
            setConfirmPassword(e.target.value);
            if (validationErrors.confirmPassword) {
              setValidationErrors((prev) => {
                const next = { ...prev };
                delete next.confirmPassword;
                return next;
              });
            }
          }}
          required
          autoComplete="new-password"
          placeholder="Confirm your password"
          disabled={isLoading}
          error={validationErrors.confirmPassword}
        />
      </div>

      {/* Submit button - Button component already mobile-first from Phase 1 */}
      <Button
        type="submit"
        size="lg"
        className="w-full"
        isLoading={isLoading}
      >
        Reset password
      </Button>
    </form>
  );
};
