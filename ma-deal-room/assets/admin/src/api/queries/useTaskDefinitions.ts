import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import { apiClient } from '../client';
import type {
  TaskDefinition,
  TaskDefinitionGrouped,
  ApiResponse,
  PaginatedResponse,
  CreateTaskDefinitionInput,
  UpdateTaskDefinitionInput,
  Template
} from '../types';

// Query keys
export const taskDefinitionKeys = {
  all: ['task-definitions'] as const,
  lists: () => [...taskDefinitionKeys.all, 'list'] as const,
  list: (filters: Record<string, any>) => [...taskDefinitionKeys.lists(), filters] as const,
  details: () => [...taskDefinitionKeys.all, 'detail'] as const,
  detail: (id: number) => [...taskDefinitionKeys.details(), id] as const,
  templates: (id: number) => [...taskDefinitionKeys.detail(id), 'templates'] as const,
};

// Get all task definitions
export const useGetTaskDefinitions = (params?: {
  category?: string;
  search?: string;
  is_system?: boolean;
  grouped?: boolean;
}) => {
  return useQuery({
    queryKey: taskDefinitionKeys.list(params || {}),
    queryFn: async () => {
      const response = await apiClient.get<
        ApiResponse<PaginatedResponse<TaskDefinition> | Record<string, TaskDefinitionGrouped>>
      >(
        '/task-definitions',
        { params }
      );
      return response.data.data;
    },
    placeholderData: keepPreviousData, // Keep previous data while fetching to prevent UI jumps and focus loss
  });
};

// Get single task definition
export const useGetTaskDefinition = (id: number, enabled = true) => {
  return useQuery({
    queryKey: taskDefinitionKeys.detail(id),
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<TaskDefinition>>(
        `/task-definitions/${id}`
      );
      return response.data.data;
    },
    enabled: enabled && id > 0,
  });
};

// Get templates using a task definition
export const useGetTaskTemplates = (id: number, enabled = true) => {
  return useQuery({
    queryKey: taskDefinitionKeys.templates(id),
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<Template[]>>(
        `/task-definitions/${id}/templates`
      );
      return response.data.data;
    },
    enabled: enabled && id > 0,
  });
};

// Create custom task definition
export const useCreateTaskDefinition = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: CreateTaskDefinitionInput) => {
      const response = await apiClient.post<ApiResponse<TaskDefinition>>(
        '/task-definitions',
        data
      );
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
    mutationFn: async ({ id, data }: { id: number; data: UpdateTaskDefinitionInput }) => {
      const response = await apiClient.put<ApiResponse<TaskDefinition>>(
        `/task-definitions/${id}`,
        data
      );
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
    mutationFn: async (id: number) => {
      await apiClient.delete(`/task-definitions/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: taskDefinitionKeys.lists() });
    },
  });
};
