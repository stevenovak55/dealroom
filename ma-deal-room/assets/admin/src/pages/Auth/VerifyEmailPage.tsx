import { useNavigate, useSearchParams, useLocation, Link } from 'react-router-dom';
import { VerifyEmailForm } from '@/components/Auth/VerifyEmailForm';

export const VerifyEmailPage = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const location = useLocation();

  // Get token from URL query parameter
  const token = searchParams.get('token') || undefined;

  // Get email from location state (passed from registration)
  const email = (location.state as any)?.email;

  const handleSuccess = () => {
    // Redirect to login after successful verification
    setTimeout(() => {
      navigate('/auth/login');
    }, 2000);
  };

  const handleBackToLogin = () => {
    navigate('/auth/login');
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
          <VerifyEmailForm
            token={token}
            email={email}
            onSuccess={handleSuccess}
            onBackToLogin={handleBackToLogin}
          />
        </div>

        <div className="text-center text-sm text-gray-600">
          <p>
            Already verified?{' '}
            <Link to="/auth/login" className="font-medium text-primary-600 hover:text-primary-500">
              Sign in
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
};
