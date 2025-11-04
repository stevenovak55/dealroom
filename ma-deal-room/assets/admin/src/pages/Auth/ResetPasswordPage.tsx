import { useNavigate, useSearchParams, Link } from 'react-router-dom';
import { ResetPasswordForm } from '@/components/Auth/ResetPasswordForm';
import { useEffect, useState } from 'react';

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

  if (!token) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8">
        <div className="w-full max-w-md space-y-8 text-center">
          <div>
            <h2 className="text-2xl font-bold text-gray-900">Invalid Reset Link</h2>
            <p className="mt-2 text-sm text-gray-600">
              This password reset link is invalid or has expired.
            </p>
          </div>
          <Link
            to="/auth/forgot-password"
            className="inline-block rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700"
          >
            Request a new reset link
          </Link>
        </div>
      </div>
    );
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
          <ResetPasswordForm token={token} onSuccess={handleSuccess} />
        </div>

        <div className="text-center text-sm text-gray-600">
          <p>
            Remember your password?{' '}
            <Link to="/auth/login" className="font-medium text-primary-600 hover:text-primary-500">
              Sign in
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
};
