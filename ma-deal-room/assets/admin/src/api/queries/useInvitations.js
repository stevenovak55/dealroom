import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';
/**
 * Get invitations list
 */
export const useGetInvitations = (params = {}) => {
    return useQuery({
        queryKey: ['invitations', params],
        queryFn: async () => {
            const response = await apiClient.get('/invitations', {
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
    return useMutation({
        mutationFn: async (params) => {
            const response = await apiClient.post('/invitations', params);
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
    return useMutation({
        mutationFn: async (invitationId) => {
            await apiClient.post(`/invitations/${invitationId}/cancel`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['invitations'] });
        },
    });
};
