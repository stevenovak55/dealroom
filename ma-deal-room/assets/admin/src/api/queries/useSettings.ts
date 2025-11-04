import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';
import type { ApiResponse } from '../types';

// Settings interface
export interface Settings {
  account_name: string;
  company_name: string;
  email: string;
  phone: string;
  timezone: string;
  notifications: {
    email: boolean;
    sms: boolean;
    daily_digest: boolean;
  };
  branding: {
    logo_url: string;
    primary_color: string;
  };
}

// Query keys
export const settingsKeys = {
  all: ['settings'] as const,
};

// Get settings
export const useGetSettings = () => {
  return useQuery({
    queryKey: settingsKeys.all,
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<Settings>>('/settings');
      return response.data.data;
    },
  });
};

// Update settings
export const useUpdateSettings = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (settings: Partial<Settings>) => {
      const response = await apiClient.put<ApiResponse<Settings>>(
        '/settings',
        settings
      );
      return response.data.data;
    },
    onSuccess: () => {
      // Invalidate and refetch
      queryClient.invalidateQueries({ queryKey: settingsKeys.all });
    },
  });
};
