import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';
import { transactionKeys } from './useTransactions';
// Query keys
export const partyKeys = {
    all: ['parties'],
    byTransaction: (transactionId) => [...partyKeys.all, 'transaction', transactionId],
};
// Get parties by transaction
export const useGetParties = (transactionId, enabled = true) => {
    return useQuery({
        queryKey: partyKeys.byTransaction(transactionId),
        queryFn: async () => {
            const response = await apiClient.get(`/transactions/${transactionId}/parties`);
            return response.data.data;
        },
        enabled: enabled && transactionId > 0,
    });
};
// Add party to transaction
export const useCreateParty = (transactionId) => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (data) => {
            const response = await apiClient.post(`/transactions/${transactionId}/parties`, data);
            return response.data.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: partyKeys.byTransaction(transactionId) });
            queryClient.invalidateQueries({ queryKey: transactionKeys.detail(transactionId) });
        },
    });
};
// Update party
export const useUpdateParty = (transactionId, partyId) => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (data) => {
            const response = await apiClient.put(`/transactions/${transactionId}/parties/${partyId}`, data);
            return response.data.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: partyKeys.byTransaction(transactionId) });
            queryClient.invalidateQueries({ queryKey: transactionKeys.detail(transactionId) });
        },
    });
};
// Delete party
export const useDeleteParty = (transactionId) => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (partyId) => {
            const response = await apiClient.delete(`/transactions/${transactionId}/parties/${partyId}`);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: partyKeys.byTransaction(transactionId) });
            queryClient.invalidateQueries({ queryKey: transactionKeys.detail(transactionId) });
        },
    });
};
