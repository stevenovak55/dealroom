import { jsx as _jsx } from "react/jsx-runtime";
import React from 'react';
import ReactDOM from 'react-dom/client';
import * as Sentry from '@sentry/react';
import App from './App';
import ErrorBoundary from './components/ErrorBoundary';
import './styles/globals.css';
// Initialize Sentry
const sentryDsn = import.meta.env.VITE_SENTRY_DSN;
const environment = import.meta.env.VITE_ENVIRONMENT || import.meta.env.MODE;
if (sentryDsn && environment !== 'development') {
    Sentry.init({
        dsn: sentryDsn,
        environment,
        integrations: [
            Sentry.browserTracingIntegration(),
            Sentry.replayIntegration({
                maskAllText: true,
                blockAllMedia: true,
            }),
        ],
        // Performance Monitoring
        tracesSampleRate: environment === 'production' ? 0.2 : 1.0, // 20% in prod, 100% in staging
        // Session Replay
        replaysSessionSampleRate: 0.1, // 10% of sessions
        replaysOnErrorSampleRate: 1.0, // 100% of sessions with errors
        // Release tracking
        release: `ma-deal-room-admin@${import.meta.env.VITE_APP_VERSION || 'unknown'}`,
    });
}
// Get the root element
const rootElement = document.getElementById('ma-deal-room-app');
if (!rootElement) {
    console.error('Root element #ma-deal-room-app not found');
}
else {
    ReactDOM.createRoot(rootElement).render(_jsx(React.StrictMode, { children: _jsx(ErrorBoundary, { children: _jsx(App, {}) }) }));
}
