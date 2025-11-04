import { useNavigate, useLocation, Link } from 'react-router-dom';
import { LoginForm } from '@/components/Auth/LoginForm';
import { useAuth } from '@/hooks/useAuth';
import { useEffect } from 'react';

export const LoginPage = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const { isAuthenticated, requires2FA } = useAuth();

  // Get the path user was trying to access before being redirected to login
  const from = (location.state as any)?.from?.pathname || '/';

  useEffect(() => {
    // If already authenticated, redirect to the original destination
    if (isAuthenticated && !requires2FA) {
      navigate(from, { replace: true });
    }
  }, [isAuthenticated, requires2FA, from, navigate]);

  const handleLoginSuccess = () => {
    if (requires2FA) {
      navigate('/auth/2fa-verify');
    } else {
      navigate(from, { replace: true });
    }
  };

  const handleForgotPassword = () => {
    navigate('/auth/forgot-password');
  };

  const handleRegister = () => {
    navigate('/auth/register');
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8">
      <div className="w-full max-w-md space-y-8">
        <div className="text-center">
          <h1 className="text-4xl font-bold text-gray-900">MA Deal Room</h1>
          <p className="mt-2 text-sm text-gray-600">
            Real estate transaction management platform
          </p>
        </div>

        <div className="rounded-lg border border-gray-200 bg-white px-8 py-10 shadow-sm">
          <LoginForm
            onSuccess={handleLoginSuccess}
            onForgotPassword={handleForgotPassword}
            onRegister={handleRegister}
          />
        </div>

        <div className="text-center text-sm text-gray-600">
          <p>
            Don't have an account?{' '}
            <Link to="/auth/register" className="font-medium text-primary-600 hover:text-primary-500">
              Sign up for free
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
};
