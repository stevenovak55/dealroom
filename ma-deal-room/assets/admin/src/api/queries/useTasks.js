import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';
import { transactionKeys } from './useTransactions';
// Query keys
export const taskKeys = {
    all: ['tasks'],
    lists: () => [...taskKeys.all, 'list'],
    list: (filters) => [...taskKeys.lists(), filters],
    details: () => [...taskKeys.all, 'detail'],
    detail: (id) => [...taskKeys.details(), id],
};
// Get all tasks
export const useGetTasks = (params) => {
    return useQuery({
        queryKey: taskKeys.list(params || {}),
        queryFn: async () => {
            const response = await apiClient.get('/tasks', { params });
            return response.data.data;
        },
    });
};
// Get single task
export const useGetTask = (id, enabled = true) => {
    return useQuery({
        queryKey: taskKeys.detail(id),
        queryFn: async () => {
            const response = await apiClient.get(`/tasks/${id}`);
            return response.data.data;
        },
        enabled: enabled && id > 0,
    });
};
// Complete task
export const useCompleteTask = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, data }) => {
            const response = await apiClient.post(`/tasks/${id}/complete`, data);
            return response.data.data;
        },
        onSuccess: (data) => {
            queryClient.invalidateQueries({ queryKey: taskKeys.detail(data.id) });
            queryClient.invalidateQueries({ queryKey: taskKeys.lists() });
            if (data.transaction_id) {
                queryClient.invalidateQueries({
                    queryKey: transactionKeys.detail(data.transaction_id),
                });
            }
        },
    });
};
// Skip task
export const useSkipTask = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, data }) => {
            const response = await apiClient.post(`/tasks/${id}/skip`, data);
            return response.data.data;
        },
        onSuccess: (data) => {
            queryClient.invalidateQueries({ queryKey: taskKeys.detail(data.id) });
            queryClient.invalidateQueries({ queryKey: taskKeys.lists() });
            if (data.transaction_id) {
                queryClient.invalidateQueries({
                    queryKey: transactionKeys.detail(data.transaction_id),
                });
            }
        },
    });
};
// Create task
export const useCreateTask = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (data) => {
            const response = await apiClient.post('/tasks', data);
            return response.data.data;
        },
        onSuccess: (data) => {
            queryClient.invalidateQueries({ queryKey: taskKeys.lists() });
            if (data.transaction_id) {
                queryClient.invalidateQueries({
                    queryKey: transactionKeys.detail(data.transaction_id),
                });
            }
        },
    });
};
// Update task
export const useUpdateTask = (taskId) => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (data) => {
            const response = await apiClient.put(`/tasks/${taskId}`, data);
            return response.data.data;
        },
        onSuccess: (data) => {
            queryClient.invalidateQueries({ queryKey: taskKeys.detail(taskId) });
            queryClient.invalidateQueries({ queryKey: taskKeys.lists() });
            if (data.transaction_id) {
                queryClient.invalidateQueries({
                    queryKey: transactionKeys.detail(data.transaction_id),
                });
            }
        },
    });
};
// Delete task
export const useDeleteTask = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (taskId) => {
            const response = await apiClient.delete(`/tasks/${taskId}`);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: taskKeys.lists() });
            queryClient.invalidateQueries({ queryKey: transactionKeys.all });
        },
    });
};
// Reassign task
export const useReassignTask = (taskId) => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (data) => {
            const response = await apiClient.post(`/tasks/${taskId}/reassign`, data);
            return response.data.data;
        },
        onSuccess: (data) => {
            queryClient.invalidateQueries({ queryKey: taskKeys.detail(taskId) });
            queryClient.invalidateQueries({ queryKey: taskKeys.lists() });
            if (data.transaction_id) {
                queryClient.invalidateQueries({
                    queryKey: transactionKeys.detail(data.transaction_id),
                });
            }
        },
    });
};
