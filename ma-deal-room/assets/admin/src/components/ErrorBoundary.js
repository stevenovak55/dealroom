import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { Component } from 'react';
import * as Sentry from '@sentry/react';
/**
 * Error Boundary Component
 *
 * Catches React errors and displays a fallback UI while reporting to Sentry.
 */
class ErrorBoundary extends Component {
    constructor(props) {
        super(props);
        Object.defineProperty(this, "handleReload", {
            enumerable: true,
            configurable: true,
            writable: true,
            value: () => {
                // Clear error state and reload
                this.setState({
                    hasError: false,
                    error: null,
                });
                window.location.reload();
            }
        });
        Object.defineProperty(this, "handleGoHome", {
            enumerable: true,
            configurable: true,
            writable: true,
            value: () => {
                // Clear error state and navigate to home
                this.setState({
                    hasError: false,
                    error: null,
                });
                window.location.href = '/wp-admin/admin.php?page=ma-deal-room';
            }
        });
        this.state = {
            hasError: false,
            error: null,
        };
    }
    static getDerivedStateFromError(error) {
        // Update state so the next render will show the fallback UI
        return {
            hasError: true,
            error,
        };
    }
    componentDidCatch(error, errorInfo) {
        // Log error to Sentry
        Sentry.captureException(error, {
            contexts: {
                react: {
                    componentStack: errorInfo.componentStack,
                },
            },
        });
        // Log to console in development
        if (import.meta.env.DEV) {
            console.error('ErrorBoundary caught an error:', error, errorInfo);
        }
    }
    render() {
        if (this.state.hasError) {
            // You can render any custom fallback UI
            if (this.props.fallback) {
                return this.props.fallback;
            }
            return (_jsx("div", { className: "min-h-screen flex items-center justify-center bg-gray-50 px-4", children: _jsxs("div", { className: "max-w-md w-full bg-white shadow-lg rounded-lg p-8", children: [_jsx("div", { className: "flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full", children: _jsx("svg", { className: "w-6 h-6 text-red-600", fill: "none", stroke: "currentColor", viewBox: "0 0 24 24", children: _jsx("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 2, d: "M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" }) }) }), _jsx("h2", { className: "mt-4 text-xl font-semibold text-center text-gray-900", children: "Something went wrong" }), _jsx("p", { className: "mt-2 text-sm text-center text-gray-600", children: "We're sorry, but something unexpected happened. Our team has been notified and we're working on it." }), import.meta.env.DEV && this.state.error && (_jsx("div", { className: "mt-4 p-4 bg-red-50 border border-red-200 rounded", children: _jsx("p", { className: "text-xs font-mono text-red-800 break-words", children: this.state.error.toString() }) })), _jsxs("div", { className: "mt-6 flex gap-3", children: [_jsx("button", { onClick: this.handleReload, className: "flex-1 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors", children: "Reload Page" }), _jsx("button", { onClick: this.handleGoHome, className: "flex-1 bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300 transition-colors", children: "Go Home" })] }), _jsx("p", { className: "mt-4 text-xs text-center text-gray-500", children: "If this problem persists, please contact support." })] }) }));
        }
        return this.props.children;
    }
}
export default ErrorBoundary;
