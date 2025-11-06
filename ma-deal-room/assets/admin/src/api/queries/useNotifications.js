import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';
// Query keys
export const notificationKeys = {
    all: ['notifications'],
    lists: () => [...notificationKeys.all, 'list'],
    list: (filters) => [...notificationKeys.lists(), filters],
    unreadCount: () => [...notificationKeys.all, 'unread-count'],
};
// Get notifications
export const useGetNotifications = (params) => {
    return useQuery({
        queryKey: notificationKeys.list(params || {}),
        queryFn: async () => {
            const response = await apiClient.get('/notifications', { params });
            return response.data.data;
        },
        refetchInterval: 30000, // Refetch every 30 seconds
    });
};
// Get unread count
export const useGetUnreadCount = () => {
    return useQuery({
        queryKey: notificationKeys.unreadCount(),
        queryFn: async () => {
            const response = await apiClient.get('/notifications/unread-count');
            return response.data.data.count;
        },
        refetchInterval: 30000, // Refetch every 30 seconds
    });
};
// Mark notification as read
export const useMarkAsRead = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (id) => {
            const response = await apiClient.put(`/notifications/${id}/read`);
            return response.data;
        },
        onSuccess: () => {
            // Invalidate and refetch
            queryClient.invalidateQueries({ queryKey: notificationKeys.all });
        },
    });
};
// Mark all notifications as read
export const useMarkAllAsRead = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async () => {
            const response = await apiClient.put('/notifications/mark-all-read');
            return response.data;
        },
        onSuccess: () => {
            // Invalidate and refetch
            queryClient.invalidateQueries({ queryKey: notificationKeys.all });
        },
    });
};
