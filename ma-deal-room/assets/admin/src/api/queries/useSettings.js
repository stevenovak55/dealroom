import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';
// Query keys
export const settingsKeys = {
    all: ['settings'],
};
// Get settings
export const useGetSettings = () => {
    return useQuery({
        queryKey: settingsKeys.all,
        queryFn: async () => {
            const response = await apiClient.get('/settings');
            return response.data.data;
        },
    });
};
// Update settings
export const useUpdateSettings = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (settings) => {
            const response = await apiClient.put('/settings', settings);
            return response.data.data;
        },
        onSuccess: () => {
            // Invalidate and refetch
            queryClient.invalidateQueries({ queryKey: settingsKeys.all });
        },
    });
};
