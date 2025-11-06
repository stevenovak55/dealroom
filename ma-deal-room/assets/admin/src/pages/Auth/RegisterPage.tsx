import { useNavigate, Link } from 'react-router-dom';
import { RegisterForm } from '@/components/Auth/RegisterForm';
import { useAuth } from '@/hooks/useAuth';
import { useEffect } from 'react';
import { cn } from '@/utils/cn';

/**
 * RegisterPage Component (Mobile-First Redesign)
 *
 * Responsive registration page with mobile-first design:
 * - Mobile (<768px): Full-width with touch-friendly spacing
 * - Desktop (>=768px): Centered card with max-width
 *
 * Features:
 * - Touch-friendly form fields
 * - Responsive text sizing
 * - Safe padding on mobile
 * - Email verification redirect after registration
 */

export const RegisterPage = () => {
  const navigate = useNavigate();
  const { isAuthenticated } = useAuth();

  useEffect(() => {
    // If already authenticated, redirect to dashboard
    if (isAuthenticated) {
      navigate('/', { replace: true });
    }
  }, [isAuthenticated, navigate]);

  const handleRegisterSuccess = (email: string) => {
    // Redirect to verify email page with the registered email
    navigate('/auth/verify-email', { state: { email } });
  };

  const handleLogin = () => {
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

        {/* Register card - responsive padding */}
        <div className={cn(
          'rounded-lg border border-gray-200 bg-white shadow-sm',
          // Mobile-first padding: larger on mobile for easier touch
          'px-5 py-8 md:px-8 md:py-10'
        )}>
          <RegisterForm onSuccess={handleRegisterSuccess} onLogin={handleLogin} />
        </div>

        {/* Sign in link - touch-friendly */}
        <div className="text-center text-sm md:text-base text-gray-600">
          <p>
            Already have an account?{' '}
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
