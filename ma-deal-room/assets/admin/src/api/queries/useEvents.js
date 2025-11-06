import { useQuery } from '@tanstack/react-query';
import { apiClient } from '../client';
// Query keys
export const eventKeys = {
    all: ['events'],
    byTransaction: (transactionId, filters) => [...eventKeys.all, 'transaction', transactionId, filters],
};
// Get transaction events
export const useGetTransactionEvents = (transactionId, params, enabled = true) => {
    return useQuery({
        queryKey: eventKeys.byTransaction(transactionId, params || {}),
        queryFn: async () => {
            const response = await apiClient.get(`/transactions/${transactionId}/events`, { params });
            return response.data.data;
        },
        enabled: enabled && transactionId > 0,
    });
};
