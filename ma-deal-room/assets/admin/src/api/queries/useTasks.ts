import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';
import type {
  Task,
  CompleteTaskInput,
  SkipTaskInput,
  CreateTaskInput,
  UpdateTaskInput,
  ReassignTaskInput,
  ApiResponse,
  PaginatedResponse,
} from '../types';
import { transactionKeys } from './useTransactions';

// Query keys
export const taskKeys = {
  all: ['tasks'] as const,
  lists: () => [...taskKeys.all, 'list'] as const,
  list: (filters: Record<string, any>) => [...taskKeys.lists(), filters] as const,
  details: () => [...taskKeys.all, 'detail'] as const,
  detail: (id: number) => [...taskKeys.details(), id] as const,
};

// Get all tasks
export const useGetTasks = (params?: {
  transaction_id?: number;
  status?: string;
  assignee_role?: string;
  overdue?: boolean;
  search?: string;
  page?: number;
  per_page?: number;
  sort?: string;
  order?: 'asc' | 'desc';
}) => {
  return useQuery({
    queryKey: taskKeys.list(params || {}),
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<PaginatedResponse<Task>>>(
        '/tasks',
        { params }
      );
      return response.data.data;
    },
  });
};

// Get single task
export const useGetTask = (id: number, enabled = true) => {
  return useQuery({
    queryKey: taskKeys.detail(id),
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<Task>>(`/tasks/${id}`);
      return response.data.data;
    },
    enabled: enabled && id > 0,
  });
};

// Complete task
export const useCompleteTask = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, data }: { id: number; data?: CompleteTaskInput }) => {
      const response = await apiClient.post<ApiResponse<Task>>(
        `/tasks/${id}/complete`,
        data
      );
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
    mutationFn: async ({ id, data }: { id: number; data: SkipTaskInput }) => {
      const response = await apiClient.post<ApiResponse<Task>>(
        `/tasks/${id}/skip`,
        data
      );
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
    mutationFn: async (data: CreateTaskInput) => {
      const response = await apiClient.post<ApiResponse<Task>>('/tasks', data);
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
export const useUpdateTask = (taskId: number) => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: UpdateTaskInput) => {
      const response = await apiClient.put<ApiResponse<Task>>(
        `/tasks/${taskId}`,
        data
      );
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
    mutationFn: async (taskId: number) => {
      const response = await apiClient.delete<ApiResponse<null>>(
        `/tasks/${taskId}`
      );
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: taskKeys.lists() });
      queryClient.invalidateQueries({ queryKey: transactionKeys.all });
    },
  });
};

// Reassign task
export const useReassignTask = (taskId: number) => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: ReassignTaskInput) => {
      const response = await apiClient.post<ApiResponse<Task>>(
        `/tasks/${taskId}/reassign`,
        data
      );
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
