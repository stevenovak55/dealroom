import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';
// Query keys
export const usersKeys = {
    all: ['users'],
    lists: () => [...usersKeys.all, 'list'],
    list: (params) => [...usersKeys.lists(), params],
    details: () => [...usersKeys.all, 'detail'],
    detail: (id, user_type) => [...usersKeys.details(), id, user_type],
    roles: (id, user_type) => [...usersKeys.detail(id, user_type), 'roles'],
};
// Get users list
export const useGetUsers = (params = {}) => {
    return useQuery({
        queryKey: usersKeys.list(params),
        queryFn: async () => {
            const response = await apiClient.get('/users', {
                params,
            });
            return response.data.data;
        },
    });
};
// Get single user
export const useGetUser = (id, userType = 'custom') => {
    return useQuery({
        queryKey: usersKeys.detail(id, userType),
        queryFn: async () => {
            const response = await apiClient.get(`/users/${id}`, {
                params: { user_type: userType },
            });
            return response.data.data;
        },
        enabled: !!id,
    });
};
// Get user roles
export const useGetUserRoles = (id, userType = 'custom') => {
    return useQuery({
        queryKey: usersKeys.roles(id, userType),
        queryFn: async () => {
            const response = await apiClient.get(`/users/${id}/roles`, {
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
        mutationFn: async ({ id, userType, data }) => {
            const response = await apiClient.put(`/users/${id}`, data, {
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
        mutationFn: async ({ id, userType }) => {
            const response = await apiClient.delete(`/users/${id}`, {
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
        mutationFn: async ({ id, userType }) => {
            const response = await apiClient.post(`/users/${id}/lock`, null, {
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
        mutationFn: async ({ id, userType }) => {
            const response = await apiClient.post(`/users/${id}/unlock`, null, {
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
        mutationFn: async ({ userId, userType, roleType, transactionId, isPrimary, }) => {
            const response = await apiClient.post(`/users/${userId}/roles`, {
                role_type: roleType,
                transaction_id: transactionId,
                is_primary: isPrimary,
            }, {
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
// Remove role from user
export const useRemoveRole = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async ({ userId, roleId, userType }) => {
            const response = await apiClient.delete(`/users/${userId}/roles/${roleId}`, {
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
export const useSearchUsers = (query, userType = 'custom') => {
    return useQuery({
        queryKey: [...usersKeys.all, 'search', query, userType],
        queryFn: async () => {
            const response = await apiClient.get('/users/search', {
                params: { q: query, user_type: userType },
            });
            return response.data.data.users;
        },
        enabled: query.length >= 2, // Only search if query is at least 2 characters
    });
};
