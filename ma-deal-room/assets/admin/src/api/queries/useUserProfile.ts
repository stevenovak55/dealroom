import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';
import type { ApiResponse } from '../types';

// User profile interface
export interface UserProfile {
  id: number;
  username: string;
  email: string;
  display_name: string;
  first_name: string;
  last_name: string;
  avatar_url: string;
  role: string;
  registered_date: string;
  preferences: {
    language: string;
    date_format: string;
    time_format: string;
    items_per_page: number;
    email_notifications: boolean;
  };
}

// Query keys
export const userProfileKeys = {
  all: ['userProfile'] as const,
};

// Get user profile
export const useGetUserProfile = () => {
  return useQuery({
    queryKey: userProfileKeys.all,
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<UserProfile>>('/profile');
      return response.data.data;
    },
  });
};

// Update user profile
export const useUpdateUserProfile = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (profile: Partial<UserProfile>) => {
      const response = await apiClient.put<ApiResponse<UserProfile>>(
        '/profile',
        profile
      );
      return response.data.data;
    },
    onSuccess: () => {
      // Invalidate and refetch
      queryClient.invalidateQueries({ queryKey: userProfileKeys.all });
    },
  });
};
