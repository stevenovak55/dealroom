import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';
// Query keys
export const userProfileKeys = {
    all: ['userProfile'],
};
// Get user profile
export const useGetUserProfile = () => {
    return useQuery({
        queryKey: userProfileKeys.all,
        queryFn: async () => {
            const response = await apiClient.get('/profile');
            return response.data.data;
        },
    });
};
// Update user profile
export const useUpdateUserProfile = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (profile) => {
            const response = await apiClient.put('/profile', profile);
            return response.data.data;
        },
        onSuccess: () => {
            // Invalidate and refetch
            queryClient.invalidateQueries({ queryKey: userProfileKeys.all });
        },
    });
};
