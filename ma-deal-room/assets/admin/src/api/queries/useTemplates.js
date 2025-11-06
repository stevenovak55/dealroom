import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';
// Query keys
export const templateKeys = {
    all: ['templates'],
    lists: () => [...templateKeys.all, 'list'],
    list: (filters) => [...templateKeys.lists(), filters],
    details: () => [...templateKeys.all, 'detail'],
    detail: (id) => [...templateKeys.details(), id],
    versions: (id) => [...templateKeys.all, 'versions', id],
    analytics: (id) => [...templateKeys.all, 'analytics', id],
};
// Get all templates
export const useGetTemplates = (params) => {
    return useQuery({
        queryKey: templateKeys.list(params || {}),
        queryFn: async () => {
            const response = await apiClient.get('/templates', { params });
            return response.data.data;
        },
    });
};
// Get single template
export const useGetTemplate = (id, enabled = true) => {
    return useQuery({
        queryKey: templateKeys.detail(id),
        queryFn: async () => {
            const response = await apiClient.get(`/templates/${id}`);
            return response.data.data;
        },
        enabled: enabled && id > 0,
    });
};
// Create template
export const useCreateTemplate = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (data) => {
            const response = await apiClient.post('/templates', data);
            return response.data.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: templateKeys.lists() });
        },
    });
};
// Update template
export const useUpdateTemplate = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, data }) => {
            const response = await apiClient.put(`/templates/${id}`, data);
            return response.data.data;
        },
        onSuccess: (data) => {
            queryClient.invalidateQueries({ queryKey: templateKeys.detail(data.template_id) });
            queryClient.invalidateQueries({ queryKey: templateKeys.lists() });
        },
    });
};
// Delete template
export const useDeleteTemplate = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (id) => {
            await apiClient.delete(`/templates/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: templateKeys.lists() });
        },
    });
};
// ============= ENTERPRISE FEATURES =============
// Clone/Duplicate template
export const useCloneTemplate = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, newName }) => {
            const response = await apiClient.post(`/templates/${id}/clone`, { name: newName });
            return response.data.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: templateKeys.lists() });
        },
    });
};
// Get template version history
export const useGetTemplateVersions = (templateId, enabled = true) => {
    return useQuery({
        queryKey: templateKeys.versions(templateId),
        queryFn: async () => {
            const response = await apiClient.get(`/templates/${templateId}/versions`);
            return response.data.data;
        },
        enabled: enabled && templateId > 0,
    });
};
// Restore template version
export const useRestoreTemplateVersion = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async ({ templateId, versionId }) => {
            const response = await apiClient.post(`/templates/${templateId}/versions/${versionId}/restore`);
            return response.data.data;
        },
        onSuccess: (data) => {
            queryClient.invalidateQueries({ queryKey: templateKeys.detail(data.template_id) });
            queryClient.invalidateQueries({ queryKey: templateKeys.versions(data.template_id) });
            queryClient.invalidateQueries({ queryKey: templateKeys.lists() });
        },
    });
};
// Validate template
export const useValidateTemplate = () => {
    return useMutation({
        mutationFn: async (templateData) => {
            const response = await apiClient.post('/templates/validate', templateData);
            return response.data.data;
        },
    });
};
// Get template analytics
export const useGetTemplateAnalytics = (templateId, enabled = true) => {
    return useQuery({
        queryKey: templateKeys.analytics(templateId),
        queryFn: async () => {
            const response = await apiClient.get(`/templates/${templateId}/analytics`);
            return response.data.data;
        },
        enabled: enabled && templateId > 0,
    });
};
// Bulk operations
export const useBulkUpdateTemplates = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async ({ ids, data }) => {
            const response = await apiClient.post('/templates/bulk-update', { template_ids: ids, updates: data });
            return response.data.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: templateKeys.lists() });
        },
    });
};
// Export template
export const useExportTemplate = () => {
    return useMutation({
        mutationFn: async (id) => {
            const response = await apiClient.get(`/templates/${id}/export`, { responseType: 'blob' });
            return response.data;
        },
    });
};
// Import template
export const useImportTemplate = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (file) => {
            const formData = new FormData();
            formData.append('file', file);
            const response = await apiClient.post('/templates/import', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            return response.data.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: templateKeys.lists() });
        },
    });
};
