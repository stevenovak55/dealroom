import { useQuery } from '@tanstack/react-query';
import { apiClient } from '../client';
import type { Event, ApiResponse, PaginatedResponse } from '../types';

// Query keys
export const eventKeys = {
  all: ['events'] as const,
  byTransaction: (transactionId: number, filters: Record<string, any>) =>
    [...eventKeys.all, 'transaction', transactionId, filters] as const,
};

// Get transaction events
export const useGetTransactionEvents = (
  transactionId: number,
  params?: {
    event_type?: string;
    page?: number;
    per_page?: number;
  },
  enabled = true
) => {
  return useQuery({
    queryKey: eventKeys.byTransaction(transactionId, params || {}),
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<PaginatedResponse<Event>>>(
        `/transactions/${transactionId}/events`,
        { params }
      );
      return response.data.data;
    },
    enabled: enabled && transactionId > 0,
  });
};
