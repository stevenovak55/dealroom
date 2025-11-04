import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';
import type { Notification, ApiResponse } from '../types';

// Query keys
export const notificationKeys = {
  all: ['notifications'] as const,
  lists: () => [...notificationKeys.all, 'list'] as const,
  list: (filters: Record<string, any>) => [...notificationKeys.lists(), filters] as const,
  unreadCount: () => [...notificationKeys.all, 'unread-count'] as const,
};

// Get notifications
export const useGetNotifications = (params?: {
  unread_only?: boolean;
  limit?: number;
}) => {
  return useQuery({
    queryKey: notificationKeys.list(params || {}),
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<{
        data: Notification[];
        total: number;
      }>>(
        '/notifications',
        { params }
      );
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
      const response = await apiClient.get<ApiResponse<{ count: number }>>(
        '/notifications/unread-count'
      );
      return response.data.data.count;
    },
    refetchInterval: 30000, // Refetch every 30 seconds
  });
};

// Mark notification as read
export const useMarkAsRead = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: number) => {
      const response = await apiClient.put<ApiResponse<null>>(
        `/notifications/${id}/read`
      );
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
      const response = await apiClient.put<ApiResponse<null>>(
        '/notifications/mark-all-read'
      );
      return response.data;
    },
    onSuccess: () => {
      // Invalidate and refetch
      queryClient.invalidateQueries({ queryKey: notificationKeys.all });
    },
  });
};
