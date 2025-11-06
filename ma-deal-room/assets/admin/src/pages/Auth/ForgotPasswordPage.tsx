import { useNavigate, Link } from 'react-router-dom';
import { ForgotPasswordForm } from '@/components/Auth/ForgotPasswordForm';
import { cn } from '@/utils/cn';

/**
 * ForgotPasswordPage Component (Mobile-First Redesign)
 *
 * Responsive password recovery page with mobile-first design:
 * - Mobile (<768px): Full-width with touch-friendly spacing
 * - Desktop (>=768px): Centered card with max-width
 *
 * Features:
 * - Single-field email form
 * - Touch-friendly buttons and links
 * - Responsive text sizing
 * - Success confirmation screen
 */

export const ForgotPasswordPage = () => {
  const navigate = useNavigate();

  const handleSuccess = () => {
    // Form already shows success state, user can click "Back to sign in"
  };

  const handleBackToLogin = () => {
    navigate('/auth/login');
  };

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
          <ForgotPasswordForm onSuccess={handleSuccess} onBackToLogin={handleBackToLogin} />
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
