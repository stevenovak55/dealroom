import { useQuery } from '@tanstack/react-query';
import { apiClient } from '../client';
import type { ApiResponse } from '../types';

// Search result interface
export interface SearchResult {
  id: number;
  type: 'transaction' | 'task' | 'party' | 'document' | 'template';
  title: string;
  subtitle: string;
  meta: string;
  url: string;
}

export interface SearchResults {
  transactions: SearchResult[];
  tasks: SearchResult[];
  parties: SearchResult[];
  documents: SearchResult[];
  templates: SearchResult[];
  total: number;
}

// Query keys
export const searchKeys = {
  all: ['search'] as const,
  query: (q: string) => [...searchKeys.all, 'query', q] as const,
};

// Search hook
export const useSearch = (query: string, enabled: boolean = true) => {
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

      const response = await apiClient.get<ApiResponse<SearchResults>>(
        '/search',
        { params: { q: query, limit: 5 } }
      );
      return response.data.data;
    },
    enabled: enabled && query.length >= 2,
    staleTime: 1000 * 60 * 5, // Cache for 5 minutes
  });
};
