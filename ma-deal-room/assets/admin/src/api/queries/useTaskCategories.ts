import { useQuery } from '@tanstack/react-query';
import { apiClient } from '../client';
import type { TaskCategory, ApiResponse } from '../types';

// Query keys
export const taskCategoryKeys = {
  all: ['task-categories'] as const,
  list: () => [...taskCategoryKeys.all, 'list'] as const,
};

// Get all task categories
export const useGetTaskCategories = () => {
  return useQuery({
    queryKey: taskCategoryKeys.list(),
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<TaskCategory[]>>(
        '/task-categories'
      );
      return response.data.data;
    },
  });
};
