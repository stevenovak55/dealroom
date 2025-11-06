import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useEffect } from 'react';
import { HashRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { AppRoutes } from './routes/AppRoutes';
import { useAuthStore } from './store/useAuthStore';
// Create a client
const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            refetchOnWindowFocus: false,
            retry: 1,
            staleTime: 0, // Always consider data stale, refetch immediately after mutations
            gcTime: 5 * 60 * 1000, // Keep unused data in cache for 5 minutes (formerly cacheTime)
        },
    },
});
function App() {
    const { initialize } = useAuthStore();
    useEffect(() => {
        // Initialize auth state
        initialize();
    }, [initialize]);
    return (_jsxs(QueryClientProvider, { client: queryClient, children: [_jsx(HashRouter, { children: _jsx(AppRoutes, {}) }), _jsx(ReactQueryDevtools, { initialIsOpen: false })] }));
}
export default App;
