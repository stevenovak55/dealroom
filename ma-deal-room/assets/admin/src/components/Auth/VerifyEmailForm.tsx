import { useState, useEffect } from 'react';
import { Button } from '../shared/Button';
import { useAuth } from '@/hooks/useAuth';

export interface VerifyEmailFormProps {
  token?: string;
  email?: string;
  onSuccess?: () => void;
  onBackToLogin?: () => void;
}

export const VerifyEmailForm = ({
  token,
  email,
  onSuccess,
  onBackToLogin
}: VerifyEmailFormProps) => {
  const { verifyEmail, resendVerification, isLoading, error, clearError } = useAuth();
  const [verified, setVerified] = useState(false);
  const [resending, setResending] = useState(false);
  const [resent, setResent] = useState(false);

  useEffect(() => {
    if (token) {
      // Automatically verify if token is provided
      handleVerify();
    }
  }, [token]);

  const handleVerify = async () => {
    if (!token) return;

    clearError();
    try {
      await verifyEmail(token);
      setVerified(true);
      onSuccess?.();
    } catch (err) {
      // Error is already set in the auth store
    }
  };

  const handleResend = async () => {
    clearError();
    setResending(true);

    try {
      await resendVerification();
      setResent(true);
      setTimeout(() => setResent(false), 3000);
    } catch (err) {
      // Error is already set in the auth store
    } finally {
      setResending(false);
    }
  };

  if (verified) {
    return (
      <div className="space-y-6">
        <div className="text-center">
          <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-success-100">
            <svg
              className="h-6 w-6 text-success-600"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M5 13l4 4L19 7"
              />
            </svg>
          </div>
          <h2 className="mt-4 text-2xl font-bold text-gray-900">Email verified!</h2>
          <p className="mt-2 text-sm text-gray-600">
            Your email has been successfully verified. You can now access all features of your
            account.
          </p>
        </div>

        <Button type="button" className="w-full" onClick={onBackToLogin}>
          Continue to sign in
        </Button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="text-center">
        <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-100">
          <svg
            className="h-6 w-6 text-primary-600"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
            />
          </svg>
        </div>
        <h2 className="mt-4 text-2xl font-bold text-gray-900">Verify your email</h2>
        <p className="mt-2 text-sm text-gray-600">
          {email ? (
            <>
              We've sent a verification email to <strong>{email}</strong>. Please check your inbox
              and click the verification link.
            </>
          ) : (
            <>Please verify your email address to continue.</>
          )}
        </p>
      </div>

      {error && (
        <div className="rounded-md bg-danger-50 p-4">
          <div className="flex">
            <div className="flex-shrink-0">
              <svg
                className="h-5 w-5 text-danger-400"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fillRule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                  clipRule="evenodd"
                />
              </svg>
            </div>
            <div className="ml-3">
              <h3 className="text-sm font-medium text-danger-800">{error}</h3>
            </div>
          </div>
        </div>
      )}

      {resent && (
        <div className="rounded-md bg-success-50 p-4">
          <div className="flex">
            <div className="flex-shrink-0">
              <svg
                className="h-5 w-5 text-success-400"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fillRule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                  clipRule="evenodd"
                />
              </svg>
            </div>
            <div className="ml-3">
              <h3 className="text-sm font-medium text-success-800">
                Verification email sent! Check your inbox.
              </h3>
            </div>
          </div>
        </div>
      )}

      <div className="space-y-3">
        <p className="text-sm text-gray-500">
          Didn't receive the email? Check your spam folder or request a new verification email.
        </p>

        <div className="space-y-3">
          {token && (
            <Button
              type="button"
              className="w-full"
              onClick={handleVerify}
              isLoading={isLoading}
            >
              Verify now
            </Button>
          )}

          <Button
            type="button"
            variant="secondary"
            className="w-full"
            onClick={handleResend}
            isLoading={resending}
            disabled={isLoading}
          >
            Resend verification email
          </Button>

          <Button
            type="button"
            variant="ghost"
            className="w-full"
            onClick={onBackToLogin}
            disabled={isLoading || resending}
          >
            Back to sign in
          </Button>
        </div>
      </div>
    </div>
  );
};
