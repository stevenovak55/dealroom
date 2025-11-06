import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import { apiClient } from '../client';
// Query keys
export const taskDefinitionKeys = {
    all: ['task-definitions'],
    lists: () => [...taskDefinitionKeys.all, 'list'],
    list: (filters) => [...taskDefinitionKeys.lists(), filters],
    details: () => [...taskDefinitionKeys.all, 'detail'],
    detail: (id) => [...taskDefinitionKeys.details(), id],
    templates: (id) => [...taskDefinitionKeys.detail(id), 'templates'],
};
// Get all task definitions
export const useGetTaskDefinitions = (params) => {
    return useQuery({
        queryKey: taskDefinitionKeys.list(params || {}),
        queryFn: async () => {
            const response = await apiClient.get('/task-definitions', { params });
            return response.data.data;
        },
        placeholderData: keepPreviousData, // Keep previous data while fetching to prevent UI jumps and focus loss
    });
};
// Get single task definition
export const useGetTaskDefinition = (id, enabled = true) => {
    return useQuery({
        queryKey: taskDefinitionKeys.detail(id),
        queryFn: async () => {
            const response = await apiClient.get(`/task-definitions/${id}`);
            return response.data.data;
        },
        enabled: enabled && id > 0,
    });
};
// Get templates using a task definition
export const useGetTaskTemplates = (id, enabled = true) => {
    return useQuery({
        queryKey: taskDefinitionKeys.templates(id),
        queryFn: async () => {
            const response = await apiClient.get(`/task-definitions/${id}/templates`);
            return response.data.data;
        },
        enabled: enabled && id > 0,
    });
};
// Create custom task definition
export const useCreateTaskDefinition = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (data) => {
            const response = await apiClient.post('/task-definitions', data);
            return response.data.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: taskDefinitionKeys.lists() });
        },
    });
};
// Update task definition
export const useUpdateTaskDefinition = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, data }) => {
            const response = await apiClient.put(`/task-definitions/${id}`, data);
            return response.data.data;
        },
        onSuccess: (data) => {
            queryClient.invalidateQueries({ queryKey: taskDefinitionKeys.detail(data.id) });
            queryClient.invalidateQueries({ queryKey: taskDefinitionKeys.lists() });
        },
    });
};
// Delete custom task definition
export const useDeleteTaskDefinition = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (id) => {
            await apiClient.delete(`/task-definitions/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: taskDefinitionKeys.lists() });
        },
    });
};
