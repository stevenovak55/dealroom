import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';
import type {
  Document,
  UploadDocumentInput,
  UpdateDocumentInput,
  ApiResponse,
  PaginatedResponse,
} from '../types';
import { transactionKeys } from './useTransactions';

// Query keys
export const documentKeys = {
  all: ['documents'] as const,
  lists: () => [...documentKeys.all, 'list'] as const,
  list: (filters: Record<string, any>) => [...documentKeys.lists(), filters] as const,
  details: () => [...documentKeys.all, 'detail'] as const,
  detail: (id: number) => [...documentKeys.details(), id] as const,
};

// Get all documents
export const useGetDocuments = (params?: {
  transaction_id?: number;
  document_type?: string;
  page?: number;
  per_page?: number;
}) => {
  return useQuery({
    queryKey: documentKeys.list(params || {}),
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<PaginatedResponse<Document>>>(
        '/documents',
        { params }
      );
      return response.data.data;
    },
  });
};

// Get single document
export const useGetDocument = (id: number, enabled = true) => {
  return useQuery({
    queryKey: documentKeys.detail(id),
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<Document>>(`/documents/${id}`);
      return response.data.data;
    },
    enabled: enabled && id > 0,
  });
};

// Upload document
export const useUploadDocument = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (input: UploadDocumentInput) => {
      const formData = new FormData();
      formData.append('file', input.file);
      formData.append('transaction_id', input.transaction_id.toString());
      if (input.document_type) formData.append('document_type', input.document_type);
      if (input.title) formData.append('title', input.title);
      if (input.description) formData.append('description', input.description);

      console.log('FormData contents:');
      for (let [key, value] of formData.entries()) {
        console.log(key, value);
      }

      // Axios interceptor will handle removing Content-Type for FormData
      const response = await apiClient.post<ApiResponse<Document>>(
        '/documents',
        formData
      );
      return response.data.data;
    },
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: documentKeys.lists() });
      if (data.transaction_id) {
        queryClient.invalidateQueries({
          queryKey: transactionKeys.detail(data.transaction_id),
        });
      }
    },
  });
};

// Update document
export const useUpdateDocument = (documentId: number) => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: UpdateDocumentInput) => {
      const response = await apiClient.put<ApiResponse<Document>>(
        `/documents/${documentId}`,
        data
      );
      return response.data.data;
    },
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: documentKeys.detail(documentId) });
      queryClient.invalidateQueries({ queryKey: documentKeys.lists() });
      if (data.transaction_id) {
        queryClient.invalidateQueries({
          queryKey: transactionKeys.detail(data.transaction_id),
        });
      }
    },
  });
};

// Delete document
export const useDeleteDocument = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (documentId: number) => {
      const response = await apiClient.delete<ApiResponse<null>>(
        `/documents/${documentId}`
      );
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: documentKeys.lists() });
      queryClient.invalidateQueries({ queryKey: transactionKeys.all });
    },
  });
};

// Download document
export const downloadDocument = (documentId: number, fileName: string) => {
  const url = `/wp-json/ma-deal/v1/documents/${documentId}/download`;
  const link = document.createElement('a');
  link.href = url;
  link.download = fileName;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};
