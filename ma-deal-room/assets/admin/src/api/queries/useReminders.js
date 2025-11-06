import { useQuery } from '@tanstack/react-query';
import { apiClient } from '../client';
// Query keys
export const reminderKeys = {
    all: ['reminders'],
    upcoming: (filters) => [...reminderKeys.all, 'upcoming', filters],
};
// Get upcoming reminders
export const useGetUpcomingReminders = (params) => {
    return useQuery({
        queryKey: reminderKeys.upcoming(params || {}),
        queryFn: async () => {
            const response = await apiClient.get('/reminders/upcoming', { params });
            return response.data.data;
        },
    });
};
