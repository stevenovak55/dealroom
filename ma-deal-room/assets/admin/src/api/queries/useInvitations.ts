import { useMutation, useQueryClient } from '@tanstack/react-query';
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
      // Invalidate users query to refresh list
      queryClient.invalidateQueries({ queryKey: ['users'] });
    },
  });
};
