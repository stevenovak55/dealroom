import { useQuery } from '@tanstack/react-query';
import { apiClient } from '../client';
// Query keys
export const searchKeys = {
    all: ['search'],
    query: (q) => [...searchKeys.all, 'query', q],
};
// Search hook
export const useSearch = (query, enabled = true) => {
    return useQuery({
        queryKey: searchKeys.query(query),
        queryFn: async () => {
            if (!query || query.length < 2) {
                return {
                    transactions: [],
                    tasks: [],
                    parties: [],
                    documents: [],
                    templates: [],
                    total: 0,
                };
            }
            const response = await apiClient.get('/search', { params: { q: query, limit: 5 } });
            return response.data.data;
        },
        enabled: enabled && query.length >= 2,
        staleTime: 1000 * 60 * 5, // Cache for 5 minutes
    });
};
