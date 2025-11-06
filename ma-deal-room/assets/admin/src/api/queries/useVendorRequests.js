import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';
export const useGetVendorRequests = (transactionId) => {
    return useQuery({
        queryKey: ['vendor-requests', transactionId],
        queryFn: async () => {
            const url = transactionId
                ? `/vendor-requests?transaction_id=${transactionId}`
                : '/vendor-requests';
            const response = await apiClient.get(url);
            return response.data.data;
        },
    });
};
export const useGetVendorRequest = (id) => {
    return useQuery({
        queryKey: ['vendor-request', id],
        queryFn: async () => {
            const response = await apiClient.get(`/vendor-requests/${id}`);
            return response.data.data;
        },
        enabled: !!id,
    });
};
export const useCreateVendorRequest = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (data) => {
            const response = await apiClient.post('/vendor-requests', data);
            return response.data.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['vendor-requests'] });
        },
    });
};
export const useResendVendorRequest = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (id) => {
            const response = await apiClient.post(`/vendor-requests/${id}/resend`);
            return response.data.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['vendor-requests'] });
        },
    });
};
export const useCancelVendorRequest = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (id) => {
            const response = await apiClient.post(`/vendor-requests/${id}/cancel`);
            return response.data.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['vendor-requests'] });
        },
    });
};
