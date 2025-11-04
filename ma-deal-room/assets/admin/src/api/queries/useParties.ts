import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';
import type { Party, CreatePartyInput, UpdatePartyInput, ApiResponse } from '../types';
import { transactionKeys } from './useTransactions';

// Query keys
export const partyKeys = {
  all: ['parties'] as const,
  byTransaction: (transactionId: number) => [...partyKeys.all, 'transaction', transactionId] as const,
};

// Get parties by transaction
export const useGetParties = (transactionId: number, enabled = true) => {
  return useQuery({
    queryKey: partyKeys.byTransaction(transactionId),
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<Party[]>>(
        `/transactions/${transactionId}/parties`
      );
      return response.data.data;
    },
    enabled: enabled && transactionId > 0,
  });
};

// Add party to transaction
export const useCreateParty = (transactionId: number) => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: CreatePartyInput) => {
      const response = await apiClient.post<ApiResponse<Party>>(
        `/transactions/${transactionId}/parties`,
        data
      );
      return response.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: partyKeys.byTransaction(transactionId) });
      queryClient.invalidateQueries({ queryKey: transactionKeys.detail(transactionId) });
    },
  });
};

// Update party
export const useUpdateParty = (transactionId: number, partyId: number) => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: UpdatePartyInput) => {
      const response = await apiClient.put<ApiResponse<Party>>(
        `/transactions/${transactionId}/parties/${partyId}`,
        data
      );
      return response.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: partyKeys.byTransaction(transactionId) });
      queryClient.invalidateQueries({ queryKey: transactionKeys.detail(transactionId) });
    },
  });
};

// Delete party
export const useDeleteParty = (transactionId: number) => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (partyId: number) => {
      const response = await apiClient.delete<ApiResponse<null>>(
        `/transactions/${transactionId}/parties/${partyId}`
      );
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: partyKeys.byTransaction(transactionId) });
      queryClient.invalidateQueries({ queryKey: transactionKeys.detail(transactionId) });
    },
  });
};
