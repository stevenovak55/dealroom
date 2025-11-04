import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';
import type { Template, ApiResponse, PaginatedResponse, TemplateVersion, TemplateValidationError, TemplateAnalytics } from '../types';

// Query keys
export const templateKeys = {
  all: ['templates'] as const,
  lists: () => [...templateKeys.all, 'list'] as const,
  list: (filters: Record<string, any>) => [...templateKeys.lists(), filters] as const,
  details: () => [...templateKeys.all, 'detail'] as const,
  detail: (id: number) => [...templateKeys.details(), id] as const,
  versions: (id: number) => [...templateKeys.all, 'versions', id] as const,
  analytics: (id: number) => [...templateKeys.all, 'analytics', id] as const,
};

// Get all templates
export const useGetTemplates = (params?: {
  property_type?: string;
  transaction_side?: 'listing' | 'buyer';
  is_active?: boolean;
}) => {
  return useQuery({
    queryKey: templateKeys.list(params || {}),
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<PaginatedResponse<Template>>>(
        '/templates',
        { params }
      );
      return response.data.data;
    },
  });
};

// Get single template
export const useGetTemplate = (id: number, enabled = true) => {
  return useQuery({
    queryKey: templateKeys.detail(id),
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<Template>>(
        `/templates/${id}`
      );
      return response.data.data;
    },
    enabled: enabled && id > 0,
  });
};

// Create template
export const useCreateTemplate = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: Partial<Template>) => {
      const response = await apiClient.post<ApiResponse<Template>>(
        '/templates',
        data
      );
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
    mutationFn: async ({ id, data }: { id: number; data: Partial<Template> }) => {
      const response = await apiClient.put<ApiResponse<Template>>(
        `/templates/${id}`,
        data
      );
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
    mutationFn: async (id: number) => {
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
    mutationFn: async ({ id, newName }: { id: number; newName?: string }) => {
      const response = await apiClient.post<ApiResponse<Template>>(
        `/templates/${id}/clone`,
        { name: newName }
      );
      return response.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: templateKeys.lists() });
    },
  });
};

// Get template version history
export const useGetTemplateVersions = (templateId: number, enabled = true) => {
  return useQuery({
    queryKey: templateKeys.versions(templateId),
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<TemplateVersion[]>>(
        `/templates/${templateId}/versions`
      );
      return response.data.data;
    },
    enabled: enabled && templateId > 0,
  });
};

// Restore template version
export const useRestoreTemplateVersion = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ templateId, versionId }: { templateId: number; versionId: number }) => {
      const response = await apiClient.post<ApiResponse<Template>>(
        `/templates/${templateId}/versions/${versionId}/restore`
      );
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
    mutationFn: async (templateData: Partial<Template>) => {
      const response = await apiClient.post<ApiResponse<{
        valid: boolean;
        errors: TemplateValidationError[];
      }>>(
        '/templates/validate',
        templateData
      );
      return response.data.data;
    },
  });
};

// Get template analytics
export const useGetTemplateAnalytics = (templateId: number, enabled = true) => {
  return useQuery({
    queryKey: templateKeys.analytics(templateId),
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<TemplateAnalytics>>(
        `/templates/${templateId}/analytics`
      );
      return response.data.data;
    },
    enabled: enabled && templateId > 0,
  });
};

// Bulk operations
export const useBulkUpdateTemplates = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ ids, data }: {
      ids: number[];
      data: Partial<Template>
    }) => {
      const response = await apiClient.post<ApiResponse<Template[]>>(
        '/templates/bulk-update',
        { template_ids: ids, updates: data }
      );
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
    mutationFn: async (id: number) => {
      const response = await apiClient.get(
        `/templates/${id}/export`,
        { responseType: 'blob' }
      );
      return response.data;
    },
  });
};

// Import template
export const useImportTemplate = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (file: File) => {
      const formData = new FormData();
      formData.append('file', file);
      const response = await apiClient.post<ApiResponse<Template>>(
        '/templates/import',
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        }
      );
      return response.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: templateKeys.lists() });
    },
  });
};
