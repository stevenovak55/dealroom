import { useState, FormEvent } from 'react';
import { Input } from '../shared/Input';
import { Button } from '../shared/Button';
import { useAuth } from '@/hooks/useAuth';
import { cn } from '@/utils/cn';

/**
 * RegisterForm Component (Mobile-First Redesign)
 *
 * Responsive registration form with mobile-first design:
 * - Touch-friendly inputs (44px minimum from Phase 1 Input component)
 * - Mobile keyboard optimization (inputMode from Phase 1)
 * - Responsive grid layout (1 column mobile, 2 columns tablet+)
 * - Touch-friendly links
 *
 * Features:
 * - Full registration with validation
 * - Password strength requirements
 * - Email verification flow
 * - Terms and privacy policy links
 * - Touch-optimized interactive elements
 */

export interface RegisterFormProps {
  onSuccess?: (email: string) => void;
  onLogin?: () => void;
}

export const RegisterForm = ({ onSuccess, onLogin }: RegisterFormProps) => {
  const { register, isLoading, error, clearError } = useAuth();
  const [formData, setFormData] = useState({
    email: '',
    password: '',
    confirmPassword: '',
    firstName: '',
    lastName: '',
    phone: '',
  });
  const [validationErrors, setValidationErrors] = useState<Record<string, string>>({});

  const handleChange = (field: string, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    // Clear validation error for this field
    if (validationErrors[field]) {
      setValidationErrors((prev) => {
        const next = { ...prev };
        delete next[field];
        return next;
      });
    }
  };

  const validate = (): boolean => {
    const errors: Record<string, string> = {};

    // Email validation
    if (!formData.email) {
      errors.email = 'Email is required';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      errors.email = 'Please enter a valid email address';
    }

    // Password validation
    if (!formData.password) {
      errors.password = 'Password is required';
    } else if (formData.password.length < 8) {
      errors.password = 'Password must be at least 8 characters';
    } else if (!/[A-Z]/.test(formData.password)) {
      errors.password = 'Password must contain at least one uppercase letter';
    } else if (!/[a-z]/.test(formData.password)) {
      errors.password = 'Password must contain at least one lowercase letter';
    } else if (!/[0-9]/.test(formData.password)) {
      errors.password = 'Password must contain at least one number';
    }

    // Confirm password validation
    if (!formData.confirmPassword) {
      errors.confirmPassword = 'Please confirm your password';
    } else if (formData.password !== formData.confirmPassword) {
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
      const user = await register({
        email: formData.email,
        password: formData.password,
        first_name: formData.firstName,
        last_name: formData.lastName,
        phone: formData.phone,
      });

      onSuccess?.(user.email);
    } catch (err) {
      // Error is already set in the auth store
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-5 md:space-y-6">
      {/* Header - responsive text sizing */}
      <div>
        <h2 className="text-xl md:text-2xl font-bold text-gray-900">
          Create your account
        </h2>
        <p className="mt-2 text-sm md:text-base text-gray-600">
          Already have an account?{' '}
          <button
            type="button"
            onClick={onLogin}
            className={cn(
              'font-medium text-primary-600 hover:text-primary-500',
              'underline',
              // Touch-friendly padding
              'py-1'
            )}
          >
            Sign in
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
        {/* Name fields - responsive grid: 1 column mobile, 2 columns tablet+ */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Input
            label="First name"
            type="text"
            value={formData.firstName}
            onChange={(e) => handleChange('firstName', e.target.value)}
            autoComplete="given-name"
            placeholder="John"
            disabled={isLoading}
            error={validationErrors.firstName}
          />

          <Input
            label="Last name"
            type="text"
            value={formData.lastName}
            onChange={(e) => handleChange('lastName', e.target.value)}
            autoComplete="family-name"
            placeholder="Doe"
            disabled={isLoading}
            error={validationErrors.lastName}
          />
        </div>

        <Input
          label="Email address"
          type="email"
          value={formData.email}
          onChange={(e) => handleChange('email', e.target.value)}
          required
          autoComplete="email"
          placeholder="you@example.com"
          disabled={isLoading}
          error={validationErrors.email}
          // inputMode="email" is auto-applied in Phase 1 Input component
        />

        <Input
          label="Phone number"
          type="tel"
          value={formData.phone}
          onChange={(e) => handleChange('phone', e.target.value)}
          autoComplete="tel"
          placeholder="(555) 123-4567"
          disabled={isLoading}
          error={validationErrors.phone}
          helperText="Optional - for transaction notifications"
          // inputMode="tel" is auto-applied in Phase 1 Input component
        />

        <Input
          label="Password"
          type="password"
          value={formData.password}
          onChange={(e) => handleChange('password', e.target.value)}
          required
          autoComplete="new-password"
          placeholder="Create a strong password"
          disabled={isLoading}
          error={validationErrors.password}
          helperText="At least 8 characters with uppercase, lowercase, and number"
        />

        <Input
          label="Confirm password"
          type="password"
          value={formData.confirmPassword}
          onChange={(e) => handleChange('confirmPassword', e.target.value)}
          required
          autoComplete="new-password"
          placeholder="Confirm your password"
          disabled={isLoading}
          error={validationErrors.confirmPassword}
        />
      </div>

      {/* Terms and privacy - touch-friendly links */}
      <div className="text-sm md:text-base text-gray-500">
        By creating an account, you agree to our{' '}
        <a
          href="/terms"
          className={cn(
            'font-medium text-primary-600 hover:text-primary-500',
            'underline',
            // Touch-friendly padding
            'inline-block py-1'
          )}
        >
          Terms of Service
        </a>{' '}
        and{' '}
        <a
          href="/privacy"
          className={cn(
            'font-medium text-primary-600 hover:text-primary-500',
            'underline',
            // Touch-friendly padding
            'inline-block py-1'
          )}
        >
          Privacy Policy
        </a>
        .
      </div>

      {/* Submit button - Button component already mobile-first from Phase 1 */}
      <Button
        type="submit"
        size="lg"
        className="w-full"
        isLoading={isLoading}
      >
        Create account
      </Button>
    </form>
  );
};
