import { useQuery } from '@tanstack/react-query';
import { apiClient } from '../client';
// Query keys
export const taskCategoryKeys = {
    all: ['task-categories'],
    list: () => [...taskCategoryKeys.all, 'list'],
};
// Get all task categories
export const useGetTaskCategories = () => {
    return useQuery({
        queryKey: taskCategoryKeys.list(),
        queryFn: async () => {
            const response = await apiClient.get('/task-categories');
            return response.data.data;
        },
    });
};
