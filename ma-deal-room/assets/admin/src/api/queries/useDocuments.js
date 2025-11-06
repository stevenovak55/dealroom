import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../client';
import { transactionKeys } from './useTransactions';
// Query keys
export const documentKeys = {
    all: ['documents'],
    lists: () => [...documentKeys.all, 'list'],
    list: (filters) => [...documentKeys.lists(), filters],
    details: () => [...documentKeys.all, 'detail'],
    detail: (id) => [...documentKeys.details(), id],
};
// Get all documents
export const useGetDocuments = (params) => {
    return useQuery({
        queryKey: documentKeys.list(params || {}),
        queryFn: async () => {
            const response = await apiClient.get('/documents', { params });
            return response.data.data;
        },
    });
};
// Get single document
export const useGetDocument = (id, enabled = true) => {
    return useQuery({
        queryKey: documentKeys.detail(id),
        queryFn: async () => {
            const response = await apiClient.get(`/documents/${id}`);
            return response.data.data;
        },
        enabled: enabled && id > 0,
    });
};
// Upload document
export const useUploadDocument = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (input) => {
            const formData = new FormData();
            formData.append('file', input.file);
            formData.append('transaction_id', input.transaction_id.toString());
            if (input.document_type)
                formData.append('document_type', input.document_type);
            if (input.title)
                formData.append('title', input.title);
            if (input.description)
                formData.append('description', input.description);
            console.log('FormData contents:');
            for (let [key, value] of formData.entries()) {
                console.log(key, value);
            }
            // Axios interceptor will handle removing Content-Type for FormData
            const response = await apiClient.post('/documents', formData);
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
export const useUpdateDocument = (documentId) => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (data) => {
            const response = await apiClient.put(`/documents/${documentId}`, data);
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
        mutationFn: async (documentId) => {
            const response = await apiClient.delete(`/documents/${documentId}`);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: documentKeys.lists() });
            queryClient.invalidateQueries({ queryKey: transactionKeys.all });
        },
    });
};
// Download document
export const downloadDocument = (documentId, fileName) => {
    const url = `/wp-json/ma-deal/v1/documents/${documentId}/download`;
    const link = document.createElement('a');
    link.href = url;
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
