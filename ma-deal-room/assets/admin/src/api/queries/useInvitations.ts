import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';

interface SendInvitationParams {
  email: string;
  role_type: string;
  account_id: number;
  transaction_id?: number;
  message?: string;
}

interface SendInvitationResponse {
  success: boolean;
  invitation_id: number;
  message: string;
}

interface Invitation {
  id: number;
  email: string;
  role_type: string;
  status: 'pending' | 'accepted' | 'declined' | 'cancelled' | 'expired';
  account_id: number;
  transaction_id?: number;
  invited_by_user_id: number;
  invited_by_user_type: 'custom' | 'wordpress';
  message?: string;
  expires_at: string;
  created_at: string;
}

interface GetInvitationsParams {
  status?: string;
  account_id?: number;
  transaction_id?: number;
  page?: number;
  per_page?: number;
}

interface GetInvitationsResponse {
  invitations: Invitation[];
  pagination: {
    total: number;
    page: number;
    per_page: number;
    total_pages: number;
  };
}

/**
 * Get invitations list
 */
export const useGetInvitations = (params: GetInvitationsParams = {}) => {
  return useQuery<GetInvitationsResponse, Error>({
    queryKey: ['invitations', params],
    queryFn: async () => {
      const response = await apiClient.get<GetInvitationsResponse>('/invitations', {
        params,
      });
      return response.data;
    },
  });
};

/**
 * Send user invitation
 */
export const useSendInvitation = () => {
  const queryClient = useQueryClient();

  return useMutation<SendInvitationResponse, Error, SendInvitationParams>({
    mutationFn: async (params) => {
      const response = await apiClient.post<SendInvitationResponse>(
        '/invitations',
        params
      );
      return response.data;
    },
    onSuccess: () => {
      // Invalidate queries to refresh lists
      queryClient.invalidateQueries({ queryKey: ['users'] });
      queryClient.invalidateQueries({ queryKey: ['invitations'] });
    },
  });
};

/**
 * Cancel invitation
 */
export const useCancelInvitation = () => {
  const queryClient = useQueryClient();

  return useMutation<void, Error, number>({
    mutationFn: async (invitationId) => {
      await apiClient.post(`/invitations/${invitationId}/cancel`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['invitations'] });
    },
  });
};
