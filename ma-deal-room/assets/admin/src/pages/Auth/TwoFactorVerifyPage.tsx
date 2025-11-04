import { useNavigate, useLocation } from 'react-router-dom';
import { TwoFactorVerifyForm } from '@/components/Auth/TwoFactorVerifyForm';
import { useAuth } from '@/hooks/useAuth';
import { useEffect } from 'react';

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
    <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8">
      <div className="w-full max-w-md space-y-8">
        <div className="text-center">
          <h1 className="text-4xl font-bold text-gray-900">MA Deal Room</h1>
          <p className="mt-2 text-sm text-gray-600">
            Real estate transaction management platform
          </p>
        </div>

        <div className="rounded-lg border border-gray-200 bg-white px-8 py-10 shadow-sm">
          <TwoFactorVerifyForm onSuccess={handleSuccess} onCancel={handleCancel} />
        </div>

        <div className="rounded-md bg-gray-50 p-4">
          <div className="flex">
            <div className="flex-shrink-0">
              <svg
                className="h-5 w-5 text-gray-400"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fillRule="evenodd"
                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
                  clipRule="evenodd"
                />
              </svg>
            </div>
            <div className="ml-3 flex-1 text-left">
              <p className="text-sm text-gray-700">
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
