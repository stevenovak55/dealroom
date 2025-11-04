import { useNavigate, Link } from 'react-router-dom';
import { RegisterForm } from '@/components/Auth/RegisterForm';
import { useAuth } from '@/hooks/useAuth';
import { useEffect } from 'react';

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
    <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8">
      <div className="w-full max-w-md space-y-8">
        <div className="text-center">
          <h1 className="text-4xl font-bold text-gray-900">MA Deal Room</h1>
          <p className="mt-2 text-sm text-gray-600">
            Real estate transaction management platform
          </p>
        </div>

        <div className="rounded-lg border border-gray-200 bg-white px-8 py-10 shadow-sm">
          <RegisterForm onSuccess={handleRegisterSuccess} onLogin={handleLogin} />
        </div>

        <div className="text-center text-sm text-gray-600">
          <p>
            Already have an account?{' '}
            <Link to="/auth/login" className="font-medium text-primary-600 hover:text-primary-500">
              Sign in
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
};
