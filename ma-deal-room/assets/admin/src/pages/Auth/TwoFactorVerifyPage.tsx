import { useNavigate, useLocation } from 'react-router-dom';
import { TwoFactorVerifyForm } from '@/components/Auth/TwoFactorVerifyForm';
import { useAuth } from '@/hooks/useAuth';
import { useEffect } from 'react';
import { cn } from '@/utils/cn';

/**
 * TwoFactorVerifyPage Component (Mobile-First Redesign)
 *
 * Responsive 2FA verification page with mobile-first design:
 * - Mobile (<768px): Full-width with touch-friendly spacing
 * - Desktop (>=768px): Centered card with max-width
 *
 * Features:
 * - TOTP code or backup code entry
 * - Touch-friendly code input
 * - Responsive text sizing
 * - Security info banner
 * - Auto-redirect after successful verification
 */

export const TwoFactorVerifyPage = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const { requires2FA, isAuthenticated } = useAuth();

  // Get the path user was trying to access
  const from = (location.state as any)?.from?.pathname || '/';

  useEffect(() => {
    // If already fully authenticated, redirect
    if (isAuthenticated && !requires2FA) {
      navigate(from, { replace: true });
    }
    // If 2FA not required (user came directly here), redirect to login
    if (!requires2FA) {
      navigate('/auth/login', { replace: true });
    }
  }, [isAuthenticated, requires2FA, from, navigate]);

  const handleSuccess = () => {
    // After successful 2FA verification, redirect to original destination
    navigate(from, { replace: true });
  };

  const handleCancel = () => {
    // Cancel 2FA and go back to login
    navigate('/auth/login');
  };

  if (!requires2FA) {
    return null; // Will redirect via useEffect
  }

  return (
    <div className={cn(
      'flex min-h-screen items-center justify-center',
      'bg-gray-50',
      // Mobile-first padding
      'px-4 py-8 md:px-6 md:py-12 lg:px-8'
    )}>
      <div className="w-full max-w-md space-y-6 md:space-y-8">
        {/* Header - responsive text sizing */}
        <div className="text-center">
          <h1 className="text-3xl md:text-4xl font-bold text-gray-900">
            MA Deal Room
          </h1>
          <p className="mt-2 text-sm md:text-base text-gray-600">
            Real estate transaction management platform
          </p>
        </div>

        {/* Form card - responsive padding */}
        <div className={cn(
          'rounded-lg border border-gray-200 bg-white shadow-sm',
          // Mobile-first padding: larger on mobile for easier touch
          'px-5 py-8 md:px-8 md:py-10'
        )}>
          <TwoFactorVerifyForm onSuccess={handleSuccess} onCancel={handleCancel} />
        </div>

        {/* Security info banner - responsive sizing */}
        <div className={cn(
          'rounded-md bg-gray-50',
          'p-3 md:p-4'
        )}>
          <div className="flex">
            <div className="flex-shrink-0">
              <svg
                className="h-5 w-5 md:h-6 md:w-6 text-gray-400"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
              >
                <path
                  fillRule="evenodd"
                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
                  clipRule="evenodd"
                />
              </svg>
            </div>
            <div className="ml-3 flex-1 text-left">
              <p className="text-sm md:text-base text-gray-700">
                Two-factor authentication adds an extra layer of security to your account. Keep
                your backup codes in a safe place.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
