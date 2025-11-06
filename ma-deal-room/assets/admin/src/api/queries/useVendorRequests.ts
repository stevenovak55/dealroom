import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';
import type { VendorRequest } from '../types';

export const useGetVendorRequests = (transactionId?: number) => {
  return useQuery({
    queryKey: ['vendor-requests', transactionId],
    queryFn: async () => {
      const url = transactionId
        ? `/vendor-requests?transaction_id=${transactionId}`
        : '/vendor-requests';
      const response = await apiClient.get<{ data: VendorRequest[] }>(url);
      return response.data.data;
    },
  });
};

export const useGetVendorRequest = (id: number) => {
  return useQuery({
    queryKey: ['vendor-request', id],
    queryFn: async () => {
      const response = await apiClient.get<{ data: VendorRequest }>(`/vendor-requests/${id}`);
      return response.data.data;
    },
    enabled: !!id,
  });
};

export const useCreateVendorRequest = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: {
      task_id: number;
      transaction_id: number;
      party_id?: number;
      vendor_type: string;
      vendor_email: string;
      vendor_phone?: string;
      metadata?: Record<string, any>;
    }) => {
      const response = await apiClient.post<{ data: VendorRequest }>(
        '/vendor-requests',
        data
      );
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
    mutationFn: async (id: number) => {
      const response = await apiClient.post<{ data: VendorRequest }>(
        `/vendor-requests/${id}/resend`
      );
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
    mutationFn: async (id: number) => {
      const response = await apiClient.post<{ data: VendorRequest }>(
        `/vendor-requests/${id}/cancel`
      );
      return response.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vendor-requests'] });
    },
  });
};
