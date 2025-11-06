import { useNavigate, useSearchParams, Link } from 'react-router-dom';
import { ResetPasswordForm } from '@/components/Auth/ResetPasswordForm';
import { useEffect, useState } from 'react';
import { cn } from '@/utils/cn';

/**
 * ResetPasswordPage Component (Mobile-First Redesign)
 *
 * Responsive password reset page with mobile-first design:
 * - Mobile (<768px): Full-width with touch-friendly spacing
 * - Desktop (>=768px): Centered card with max-width
 *
 * Features:
 * - Token validation from URL params
 * - Invalid token state with clear error message
 * - Touch-friendly password reset form
 * - Responsive text sizing
 * - Auto-redirect to login after success
 */

export const ResetPasswordPage = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const [token, setToken] = useState<string | null>(null);

  useEffect(() => {
    const tokenParam = searchParams.get('token');
    if (!tokenParam) {
      // If no token, redirect to forgot password page
      navigate('/auth/forgot-password', { replace: true });
    } else {
      setToken(tokenParam);
    }
  }, [searchParams, navigate]);

  const handleSuccess = () => {
    // Form already shows success state with "Go to sign in" button
    setTimeout(() => {
      navigate('/auth/login');
    }, 2000);
  };

  // Invalid token state - responsive design
  if (!token) {
    return (
      <div className={cn(
        'flex min-h-screen items-center justify-center',
        'bg-gray-50',
        'px-4 py-8 md:px-6 md:py-12 lg:px-8'
      )}>
        <div className="w-full max-w-md space-y-6 md:space-y-8 text-center">
          <div>
            <h2 className="text-xl md:text-2xl font-bold text-gray-900">
              Invalid Reset Link
            </h2>
            <p className="mt-2 text-sm md:text-base text-gray-600">
              This password reset link is invalid or has expired.
            </p>
          </div>
          <Link
            to="/auth/forgot-password"
            className={cn(
              'inline-block rounded-md bg-primary-600 text-white',
              'hover:bg-primary-700 transition-colors duration-fast',
              // Touch-friendly button
              'px-5 py-3 md:px-4 md:py-2',
              'text-base md:text-sm font-medium',
              'min-h-touch md:min-h-0'
            )}
          >
            Request a new reset link
          </Link>
        </div>
      </div>
    );
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
          <ResetPasswordForm token={token} onSuccess={handleSuccess} />
        </div>

        {/* Sign in link - touch-friendly */}
        <div className="text-center text-sm md:text-base text-gray-600">
          <p>
            Remember your password?{' '}
            <Link
              to="/auth/login"
              className={cn(
                'font-medium text-primary-600 hover:text-primary-500',
                // Increase touch target size
                'inline-block py-1'
              )}
            >
              Sign in
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
};
