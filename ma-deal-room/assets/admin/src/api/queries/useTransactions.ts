import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';
import type {
  Transaction,
  CreateTransactionInput,
  UpdateTransactionInput,
  ApiResponse,
  PaginatedResponse,
} from '../types';

// Query keys
export const transactionKeys = {
  all: ['transactions'] as const,
  lists: () => [...transactionKeys.all, 'list'] as const,
  list: (filters: Record<string, any>) => [...transactionKeys.lists(), filters] as const,
  details: () => [...transactionKeys.all, 'detail'] as const,
  detail: (id: number) => [...transactionKeys.details(), id] as const,
};

// Get all transactions
export const useGetTransactions = (params?: {
  status?: string;
  page?: number;
  per_page?: number;
  sort?: string;
  order?: 'asc' | 'desc';
}) => {
  return useQuery({
    queryKey: transactionKeys.list(params || {}),
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<PaginatedResponse<Transaction>>>(
        '/transactions',
        { params }
      );
      return response.data.data;
    },
  });
};

// Get single transaction
export const useGetTransaction = (id: number, enabled = true) => {
  return useQuery({
    queryKey: transactionKeys.detail(id),
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<Transaction>>(
        `/transactions/${id}`
      );
      return response.data.data;
    },
    enabled: enabled && id > 0,
  });
};

// Create transaction
export const useCreateTransaction = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: CreateTransactionInput) => {
      const response = await apiClient.post<ApiResponse<Transaction>>(
        '/transactions',
        data
      );
      return response.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: transactionKeys.lists() });
    },
  });
};

// Update transaction
export const useUpdateTransaction = (id: number) => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: UpdateTransactionInput) => {
      const response = await apiClient.put<ApiResponse<Transaction>>(
        `/transactions/${id}`,
        data
      );
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
    mutationFn: async (id: number) => {
      const response = await apiClient.delete<ApiResponse<void>>(
        `/transactions/${id}`
      );
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: transactionKeys.lists() });
    },
  });
};

// Apply template to transaction
export const useApplyTemplate = (transactionId: number) => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (templateId: number) => {
      const response = await apiClient.post<ApiResponse<{
        message: string;
        tasks_created: number;
        task_ids: number[];
      }>>(
        `/transactions/${transactionId}/apply-template`,
        { template_id: templateId }
      );
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
