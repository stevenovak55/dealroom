import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { useEffect } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import { usePermissions } from '@/hooks/usePermissions';
export const ProtectedRoute = ({ children, requiredCapability, requiredCapabilitiesAny, requiredCapabilitiesAll, requiredRole, requireAdmin = false, redirectTo = '/login', showLoading = true, }) => {
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
        return (_jsx("div", { className: "flex h-screen items-center justify-center", children: _jsxs("div", { className: "text-center", children: [_jsx("div", { className: "mx-auto h-12 w-12 animate-spin rounded-full border-4 border-gray-200 border-t-primary-600" }), _jsx("p", { className: "mt-4 text-sm text-gray-600", children: "Loading..." })] }) }));
    }
    // Redirect to login if not authenticated
    if (!isAuthenticated) {
        return _jsx(Navigate, { to: redirectTo, state: { from: location }, replace: true });
    }
    // Check if email verification is required (optional - depends on your requirements)
    // Uncomment if you want to enforce email verification
    // if (user && !user.email_verified) {
    //   return <Navigate to="/verify-email" state={{ from: location }} replace />;
    // }
    // Check admin requirement
    if (requireAdmin && !isAdmin()) {
        return _jsx(AccessDenied, { reason: "This page requires administrator access." });
    }
    // Check role requirement
    if (requiredRole && !hasRole(requiredRole)) {
        return (_jsx(AccessDenied, { reason: `This page requires the "${requiredRole}" role.` }));
    }
    // Check single capability requirement
    if (requiredCapability && !can(requiredCapability)) {
        return (_jsx(AccessDenied, { reason: "You do not have permission to access this page." }));
    }
    // Check "any of" capabilities requirement
    if (requiredCapabilitiesAny && !canAny(requiredCapabilitiesAny)) {
        return (_jsx(AccessDenied, { reason: "You do not have the required permissions to access this page." }));
    }
    // Check "all of" capabilities requirement
    if (requiredCapabilitiesAll && !canAll(requiredCapabilitiesAll)) {
        return (_jsx(AccessDenied, { reason: "You do not have all the required permissions to access this page." }));
    }
    // All checks passed - render the protected content
    return _jsx(_Fragment, { children: children });
};
const AccessDenied = ({ reason }) => {
    return (_jsx("div", { className: "flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8", children: _jsxs("div", { className: "w-full max-w-md space-y-8 text-center", children: [_jsxs("div", { children: [_jsx("div", { className: "mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-danger-100", children: _jsx("svg", { className: "h-10 w-10 text-danger-600", xmlns: "http://www.w3.org/2000/svg", fill: "none", viewBox: "0 0 24 24", stroke: "currentColor", children: _jsx("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 2, d: "M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" }) }) }), _jsx("h2", { className: "mt-6 text-3xl font-bold text-gray-900", children: "Access Denied" }), _jsx("p", { className: "mt-2 text-sm text-gray-600", children: reason || 'You do not have permission to access this page.' })] }), _jsxs("div", { className: "space-y-3", children: [_jsx("button", { onClick: () => window.history.back(), className: "w-full rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2", children: "Go back" }), _jsx("a", { href: "/", className: "block w-full rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2", children: "Go to dashboard" })] }), _jsx("div", { className: "rounded-md bg-gray-50 p-4", children: _jsxs("div", { className: "flex", children: [_jsx("div", { className: "flex-shrink-0", children: _jsx("svg", { className: "h-5 w-5 text-gray-400", xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 20 20", fill: "currentColor", children: _jsx("path", { fillRule: "evenodd", d: "M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z", clipRule: "evenodd" }) }) }), _jsx("div", { className: "ml-3 flex-1 text-left", children: _jsx("p", { className: "text-sm text-gray-700", children: "If you believe you should have access to this page, please contact your administrator." }) })] }) })] }) }));
};
/**
 * Helper component for routes that require authentication only (no specific permissions)
 */
export const RequireAuth = ({ children }) => {
    return _jsx(ProtectedRoute, { children: children });
};
/**
 * Helper component for admin-only routes
 */
export const RequireAdmin = ({ children }) => {
    return _jsx(ProtectedRoute, { requireAdmin: true, children: children });
};
