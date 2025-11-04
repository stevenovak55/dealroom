import { useQuery } from '@tanstack/react-query';
import { apiClient } from '../client';
import type { Reminder, ApiResponse, PaginatedResponse } from '../types';

// Query keys
export const reminderKeys = {
  all: ['reminders'] as const,
  upcoming: (filters: Record<string, any>) => [...reminderKeys.all, 'upcoming', filters] as const,
};

// Get upcoming reminders
export const useGetUpcomingReminders = (params?: {
  days?: number;
  transaction_id?: number;
}) => {
  return useQuery({
    queryKey: reminderKeys.upcoming(params || {}),
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<PaginatedResponse<Reminder>>>(
        '/reminders/upcoming',
        { params }
      );
      return response.data.data;
    },
  });
};
