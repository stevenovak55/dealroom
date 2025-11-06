import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';
import type { ApiResponse } from '../types';

// User interface
export interface User {
  id: number;
  email: string;
  first_name?: string;
  last_name?: string;
  phone?: string;
  status: 'active' | 'inactive' | 'suspended' | 'pending_verification';
  email_verified: boolean;
  email_verified_at?: string;
  last_login_at?: string;
  last_login_ip?: string;
  failed_login_attempts: number;
  locked_until?: string;
  created_at: string;
  updated_at: string;
  roles?: UserRole[];
}

export interface UserRole {
  id: number;
  role_type: string;
  transaction_id?: number;
  is_primary: boolean;
  assigned_at: string;
}

export interface UsersListParams {
  page?: number;
  per_page?: number;
  user_type?: 'custom' | 'wordpress';
  role_type?: string;
  status?: string;
  search?: string;
}

export interface UsersListResponse {
  users: User[];
  pagination: {
    total: number;
    page: number;
    per_page: number;
    total_pages: number;
  };
}

// Query keys
export const usersKeys = {
  all: ['users'] as const,
  lists: () => [...usersKeys.all, 'list'] as const,
  list: (params: UsersListParams) => [...usersKeys.lists(), params] as const,
  details: () => [...usersKeys.all, 'detail'] as const,
  detail: (id: number, user_type: 'custom' | 'wordpress') => [...usersKeys.details(), id, user_type] as const,
  roles: (id: number, user_type: 'custom' | 'wordpress') => [...usersKeys.detail(id, user_type), 'roles'] as const,
};

// Get users list
export const useGetUsers = (params: UsersListParams = {}) => {
  return useQuery({
    queryKey: usersKeys.list(params),
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<UsersListResponse>>('/users', {
        params,
      });
      return response.data.data;
    },
  });
};

// Get single user
export const useGetUser = (id: number, userType: 'custom' | 'wordpress' = 'custom') => {
  return useQuery({
    queryKey: usersKeys.detail(id, userType),
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<User>>(`/users/${id}`, {
        params: { user_type: userType },
      });
      return response.data.data;
    },
    enabled: !!id,
  });
};

// Get user roles
export const useGetUserRoles = (id: number, userType: 'custom' | 'wordpress' = 'custom') => {
  return useQuery({
    queryKey: usersKeys.roles(id, userType),
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<{ roles: UserRole[] }>>(`/users/${id}/roles`, {
        params: { user_type: userType },
      });
      return response.data.data.roles;
    },
    enabled: !!id,
  });
};

// Update user
export const useUpdateUser = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, userType, data }: { id: number; userType: 'custom' | 'wordpress'; data: Partial<User> }) => {
      const response = await apiClient.put<ApiResponse<User>>(`/users/${id}`, data, {
        params: { user_type: userType },
      });
      return response.data.data;
    },
    onSuccess: (data, variables) => {
      // Invalidate all user queries
      queryClient.invalidateQueries({ queryKey: usersKeys.lists() });
      queryClient.invalidateQueries({ queryKey: usersKeys.detail(variables.id, variables.userType) });
    },
  });
};

// Delete user
export const useDeleteUser = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, userType }: { id: number; userType: 'custom' | 'wordpress' }) => {
      const response = await apiClient.delete<ApiResponse<null>>(`/users/${id}`, {
        params: { user_type: userType },
      });
      return response.data;
    },
    onSuccess: () => {
      // Invalidate all user lists
      queryClient.invalidateQueries({ queryKey: usersKeys.lists() });
    },
  });
};

// Lock user
export const useLockUser = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, userType }: { id: number; userType: 'custom' | 'wordpress' }) => {
      const response = await apiClient.post<ApiResponse<null>>(`/users/${id}/lock`, null, {
        params: { user_type: userType },
      });
      return response.data;
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: usersKeys.detail(variables.id, variables.userType) });
      queryClient.invalidateQueries({ queryKey: usersKeys.lists() });
    },
  });
};

// Unlock user
export const useUnlockUser = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, userType }: { id: number; userType: 'custom' | 'wordpress' }) => {
      const response = await apiClient.post<ApiResponse<null>>(`/users/${id}/unlock`, null, {
        params: { user_type: userType },
      });
      return response.data;
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: usersKeys.detail(variables.id, variables.userType) });
      queryClient.invalidateQueries({ queryKey: usersKeys.lists() });
    },
  });
};

// Assign role to user
export const useAssignRole = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({
      userId,
      userType,
      roleType,
      transactionId,
      isPrimary,
    }: {
      userId: number;
      userType: 'custom' | 'wordpress';
      roleType: string;
      transactionId?: number;
      isPrimary?: boolean;
    }) => {
      const response = await apiClient.post<ApiResponse<{ role_id: number }>>(
        `/users/${userId}/roles`,
        {
          role_type: roleType,
          transaction_id: transactionId,
          is_primary: isPrimary,
        },
        {
          params: { user_type: userType },
        }
      );
      return response.data;
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: usersKeys.roles(variables.userId, variables.userType) });
      queryClient.invalidateQueries({ queryKey: usersKeys.detail(variables.userId, variables.userType) });
      queryClient.invalidateQueries({ queryKey: usersKeys.lists() });
    },
  });
};

// Remove role from user
export const useRemoveRole = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ userId, roleId, userType }: { userId: number; roleId: number; userType: 'custom' | 'wordpress' }) => {
      const response = await apiClient.delete<ApiResponse<null>>(`/users/${userId}/roles/${roleId}`, {
        params: { user_type: userType },
      });
      return response.data;
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: usersKeys.roles(variables.userId, variables.userType) });
      queryClient.invalidateQueries({ queryKey: usersKeys.detail(variables.userId, variables.userType) });
      queryClient.invalidateQueries({ queryKey: usersKeys.lists() });
    },
  });
};

// Search users
export const useSearchUsers = (query: string, userType: 'custom' | 'wordpress' = 'custom') => {
  return useQuery({
    queryKey: [...usersKeys.all, 'search', query, userType],
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<{ users: User[] }>>('/users/search', {
        params: { q: query, user_type: userType },
      });
      return response.data.data.users;
    },
    enabled: query.length >= 2, // Only search if query is at least 2 characters
  });
};
