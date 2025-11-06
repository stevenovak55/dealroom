import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';
// Query keys
export const transactionKeys = {
    all: ['transactions'],
    lists: () => [...transactionKeys.all, 'list'],
    list: (filters) => [...transactionKeys.lists(), filters],
    details: () => [...transactionKeys.all, 'detail'],
    detail: (id) => [...transactionKeys.details(), id],
};
// Get all transactions
export const useGetTransactions = (params) => {
    return useQuery({
        queryKey: transactionKeys.list(params || {}),
        queryFn: async () => {
            const response = await apiClient.get('/transactions', { params });
            return response.data.data;
        },
    });
};
// Get single transaction
export const useGetTransaction = (id, enabled = true) => {
    return useQuery({
        queryKey: transactionKeys.detail(id),
        queryFn: async () => {
            const response = await apiClient.get(`/transactions/${id}`);
            return response.data.data;
        },
        enabled: enabled && id > 0,
    });
};
// Create transaction
export const useCreateTransaction = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (data) => {
            const response = await apiClient.post('/transactions', data);
            return response.data.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: transactionKeys.lists() });
        },
    });
};
// Update transaction
export const useUpdateTransaction = (id) => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (data) => {
            const response = await apiClient.put(`/transactions/${id}`, data);
            return response.data.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: transactionKeys.detail(id) });
            queryClient.invalidateQueries({ queryKey: transactionKeys.lists() });
        },
    });
};
// Delete transaction
export const useDeleteTransaction = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (id) => {
            const response = await apiClient.delete(`/transactions/${id}`);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: transactionKeys.lists() });
        },
    });
};
// Apply template to transaction
export const useApplyTemplate = (transactionId) => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (templateId) => {
            const response = await apiClient.post(`/transactions/${transactionId}/apply-template`, { template_id: templateId });
            return response.data.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: transactionKeys.detail(transactionId) });
            queryClient.invalidateQueries({ queryKey: transactionKeys.lists() });
            // Invalidate tasks list since new tasks were created
            queryClient.invalidateQueries({ queryKey: ['tasks'] });
        },
    });
};
