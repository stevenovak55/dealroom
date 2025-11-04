import { ReactNode, useEffect } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import { usePermissions } from '@/hooks/usePermissions';

export interface ProtectedRouteProps {
  /**
   * The component/content to render if access is granted
   */
  children: ReactNode;

  /**
   * Required capability/permission to access this route
   */
  requiredCapability?: string;

  /**
   * Array of capabilities - user must have at least one
   */
  requiredCapabilitiesAny?: string[];

  /**
   * Array of capabilities - user must have all of them
   */
  requiredCapabilitiesAll?: string[];

  /**
   * Required role to access this route
   */
  requiredRole?: string;

  /**
   * Require admin role
   */
  requireAdmin?: boolean;

  /**
   * Path to redirect to if not authenticated (default: /login)
   */
  redirectTo?: string;

  /**
   * Show loading state while checking authentication
   */
  showLoading?: boolean;
}

export const ProtectedRoute = ({
  children,
  requiredCapability,
  requiredCapabilitiesAny,
  requiredCapabilitiesAll,
  requiredRole,
  requireAdmin = false,
  redirectTo = '/login',
  showLoading = true,
}: ProtectedRouteProps) => {
  const location = useLocation();
  const { isAuthenticated, isLoading, initialize } = useAuth();
  const { can, canAny, canAll, hasRole, isAdmin } = usePermissions();

  // Initialize auth on mount if not already done
  useEffect(() => {
    if (!isAuthenticated && !isLoading) {
      initialize();
    }
  }, []);

  // Show loading state
  if (isLoading && showLoading) {
    return (
      <div className="flex h-screen items-center justify-center">
        <div className="text-center">
          <div className="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-gray-200 border-t-primary-600"></div>
          <p className="mt-4 text-sm text-gray-600">Loading...</p>
        </div>
      </div>
    );
  }

  // Redirect to login if not authenticated
  if (!isAuthenticated) {
    return <Navigate to={redirectTo} state={{ from: location }} replace />;
  }

  // Check if email verification is required (optional - depends on your requirements)
  // Uncomment if you want to enforce email verification
  // if (user && !user.email_verified) {
  //   return <Navigate to="/verify-email" state={{ from: location }} replace />;
  // }

  // Check admin requirement
  if (requireAdmin && !isAdmin()) {
    return <AccessDenied reason="This page requires administrator access." />;
  }

  // Check role requirement
  if (requiredRole && !hasRole(requiredRole)) {
    return (
      <AccessDenied
        reason={`This page requires the "${requiredRole}" role.`}
      />
    );
  }

  // Check single capability requirement
  if (requiredCapability && !can(requiredCapability)) {
    return (
      <AccessDenied
        reason="You do not have permission to access this page."
      />
    );
  }

  // Check "any of" capabilities requirement
  if (requiredCapabilitiesAny && !canAny(requiredCapabilitiesAny)) {
    return (
      <AccessDenied
        reason="You do not have the required permissions to access this page."
      />
    );
  }

  // Check "all of" capabilities requirement
  if (requiredCapabilitiesAll && !canAll(requiredCapabilitiesAll)) {
    return (
      <AccessDenied
        reason="You do not have all the required permissions to access this page."
      />
    );
  }

  // All checks passed - render the protected content
  return <>{children}</>;
};

/**
 * Access Denied component shown when user doesn't have required permissions
 */
interface AccessDeniedProps {
  reason?: string;
}

const AccessDenied = ({ reason }: AccessDeniedProps) => {
  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8">
      <div className="w-full max-w-md space-y-8 text-center">
        <div>
          <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-danger-100">
            <svg
              className="h-10 w-10 text-danger-600"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
              />
            </svg>
          </div>
          <h2 className="mt-6 text-3xl font-bold text-gray-900">Access Denied</h2>
          <p className="mt-2 text-sm text-gray-600">
            {reason || 'You do not have permission to access this page.'}
          </p>
        </div>

        <div className="space-y-3">
          <button
            onClick={() => window.history.back()}
            className="w-full rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
          >
            Go back
          </button>

          <a
            href="/"
            className="block w-full rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
          >
            Go to dashboard
          </a>
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
                If you believe you should have access to this page, please contact your
                administrator.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

/**
 * Helper component for routes that require authentication only (no specific permissions)
 */
export const RequireAuth = ({ children }: { children: ReactNode }) => {
  return <ProtectedRoute>{children}</ProtectedRoute>;
};

/**
 * Helper component for admin-only routes
 */
export const RequireAdmin = ({ children }: { children: ReactNode }) => {
  return <ProtectedRoute requireAdmin>{children}</ProtectedRoute>;
};
