import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Layout, FileText, Plus, Edit, Trash2, Sparkles, Copy, History, BarChart3, Download, Upload, MoreVertical } from 'lucide-react';
import { useGetTemplates, useCreateTemplate, useUpdateTemplate, useDeleteTemplate, useCloneTemplate, useExportTemplate, useImportTemplate } from '@/api/queries/useTemplates';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Badge } from '@/components/shared/Badge';
import { Button } from '@/components/shared/Button';
import { PageLoader } from '@/components/shared/Loader';
import { EmptyState } from '@/components/shared/EmptyState';
import { TemplateForm } from './TemplateForm';
import { TemplateVersionHistory } from '@/components/Templates/TemplateVersionHistory';
import type { Template } from '@/api/types';
import toast from 'react-hot-toast';

export const TemplatesList = () => {
  const navigate = useNavigate();
  const { data, isLoading } = useGetTemplates({ is_active: true });
  const createMutation = useCreateTemplate();
  const updateMutation = useUpdateTemplate();
  const deleteMutation = useDeleteTemplate();
  const cloneMutation = useCloneTemplate();
  const exportMutation = useExportTemplate();
  const importMutation = useImportTemplate();

  const [isFormOpen, setIsFormOpen] = useState(false);
  const [selectedTemplate, setSelectedTemplate] = useState<Template | null>(null);
  const [versionHistoryTemplate, setVersionHistoryTemplate] = useState<Template | null>(null);
  const [showDropdown, setShowDropdown] = useState<number | null>(null);

  const templates = data?.data || [];

  if (isLoading) {
    return <PageLoader />;
  }

  const handleOpenCreate = () => {
    setSelectedTemplate(null);
    setIsFormOpen(true);
  };

  const handleOpenEdit = (template: Template) => {
    setSelectedTemplate(template);
    setIsFormOpen(true);
  };

  const handleCloseForm = () => {
    setIsFormOpen(false);
    setSelectedTemplate(null);
  };

  const handleSubmit = async (data: Partial<Template>) => {
    try {
      if (selectedTemplate) {
        await updateMutation.mutateAsync({ id: selectedTemplate.template_id, data });
      } else {
        await createMutation.mutateAsync(data);
      }
      handleCloseForm();
    } catch (error) {
      console.error('Failed to save template:', error);
      alert('Failed to save template. Please try again.');
    }
  };

  const handleDelete = async (template: Template) => {
    if (template.is_system) {
      toast.error('System templates cannot be deleted.');
      return;
    }

    if (!confirm(`Are you sure you want to delete "${template.name}"? This cannot be undone.`)) {
      return;
    }

    try {
      await deleteMutation.mutateAsync(template.template_id);
      toast.success('Template deleted successfully');
    } catch (error: any) {
      console.error('Failed to delete template:', error);
      const message = error.response?.data?.message || 'Failed to delete template';
      toast.error(message);
    }
  };

  const handleClone = async (template: Template) => {
    const newName = prompt(`Enter name for cloned template:`, `${template.name} (Copy)`);
    if (!newName) return;

    try {
      await cloneMutation.mutateAsync({ id: template.template_id, newName });
      toast.success('Template cloned successfully');
    } catch (error: any) {
      console.error('Failed to clone template:', error);
      toast.error(error?.message || 'Failed to clone template');
    }
  };

  const handleExport = async (template: Template) => {
    try {
      const blob = await exportMutation.mutateAsync(template.template_id);
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `template-${template.name.toLowerCase().replace(/\s+/g, '-')}.yaml`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
      toast.success('Template exported successfully');
    } catch (error: any) {
      console.error('Failed to export template:', error);
      toast.error('Failed to export template');
    }
  };

  const handleImport = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    try {
      await importMutation.mutateAsync(file);
      toast.success('Template imported successfully');
      event.target.value = ''; // Reset input
    } catch (error: any) {
      console.error('Failed to import template:', error);
      toast.error(error?.message || 'Failed to import template');
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Templates</h1>
          <p className="text-sm text-gray-500 mt-1">
            Pre-configured checklists for different property types
          </p>
        </div>
        <div className="flex items-center gap-2">
          <div>
            <input
              id="import-template"
              type="file"
              accept=".yaml,.yml"
              className="hidden"
              onChange={handleImport}
            />
            <label htmlFor="import-template">
              <Button variant="secondary">
                <Upload className="h-4 w-4 mr-2" />
                Import
              </Button>
            </label>
          </div>
          <Button variant="secondary" onClick={() => navigate('/templates/new')}>
            <Sparkles className="h-4 w-4 mr-2" />
            Visual Builder
          </Button>
          <Button variant="primary" onClick={handleOpenCreate}>
            <Plus className="h-4 w-4 mr-2" />
            Quick Create
          </Button>
        </div>
      </div>

      {/* Templates grid */}
      {templates.length === 0 ? (
        <Card>
          <EmptyState
            icon={Layout}
            title="No templates available"
            description="Contact your administrator to set up templates"
          />
        </Card>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {templates.map((template) => (
            <Card key={template.template_id} className="hover:shadow-lg transition-shadow">
              <CardHeader>
                <CardTitle className="flex items-start justify-between">
                  <div className="flex items-center gap-2">
                    <FileText className="h-5 w-5 text-primary-600" />
                    <span className="text-base">{template.name}</span>
                  </div>
                  {template.is_system && <Badge variant="info">System</Badge>}
                </CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-gray-600 mb-4 line-clamp-3">
                  {template.description || 'No description available'}
                </p>
                <div className="flex items-center justify-between text-sm">
                  <span className="text-gray-500">Property Type</span>
                  <Badge variant="default">{template.property_type}</Badge>
                </div>
                <div className="flex items-center justify-between text-sm mt-2">
                  <span className="text-gray-500">Tasks</span>
                  <span className="font-medium text-gray-900">{template.task_count || 0}</span>
                </div>
                <div className="flex items-center justify-between text-sm mt-2">
                  <span className="text-gray-500">Version</span>
                  <button
                    onClick={() => setVersionHistoryTemplate(template)}
                    className="font-medium text-blue-600 hover:text-blue-700 flex items-center gap-1"
                  >
                    v{template.version}
                    <History className="h-3 w-3" />
                  </button>
                </div>

                {template.usage_count !== undefined && (
                  <div className="flex items-center justify-between text-sm mt-2">
                    <span className="text-gray-500">Usage</span>
                    <span className="font-medium text-gray-900">{template.usage_count}</span>
                  </div>
                )}

                {/* Action buttons */}
                <div className="flex items-center gap-2 mt-4 pt-4 border-t border-gray-200">
                  <Button
                    size="sm"
                    variant="secondary"
                    onClick={() => navigate(`/templates/${template.template_id}/analytics`)}
                    className="flex-1"
                  >
                    <BarChart3 className="h-3 w-3 mr-1" />
                    Analytics
                  </Button>

                  {!template.is_system && (
                    <>
                      <Button
                        size="sm"
                        variant="secondary"
                        onClick={() => handleOpenEdit(template)}
                      >
                        <Edit className="h-3 w-3" />
                      </Button>

                      <div className="relative">
                        <Button
                          size="sm"
                          variant="secondary"
                          onClick={() => setShowDropdown(showDropdown === template.template_id ? null : template.template_id)}
                        >
                          <MoreVertical className="h-3 w-3" />
                        </Button>

                        {showDropdown === template.template_id && (
                          <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-10">
                            <button
                              onClick={() => {
                                handleClone(template);
                                setShowDropdown(null);
                              }}
                              className="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2"
                            >
                              <Copy className="h-4 w-4" />
                              Clone Template
                            </button>
                            <button
                              onClick={() => {
                                handleExport(template);
                                setShowDropdown(null);
                              }}
                              className="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2"
                            >
                              <Download className="h-4 w-4" />
                              Export YAML
                            </button>
                            <button
                              onClick={() => {
                                setVersionHistoryTemplate(template);
                                setShowDropdown(null);
                              }}
                              className="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2"
                            >
                              <History className="h-4 w-4" />
                              Version History
                            </button>
                            <div className="border-t border-gray-200 my-1" />
                            <button
                              onClick={() => {
                                handleDelete(template);
                                setShowDropdown(null);
                              }}
                              className="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
                            >
                              <Trash2 className="h-4 w-4" />
                              Delete
                            </button>
                          </div>
                        )}
                      </div>
                    </>
                  )}
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {/* Template form modal */}
      <TemplateForm
        template={selectedTemplate}
        isOpen={isFormOpen}
        onClose={handleCloseForm}
        onSubmit={handleSubmit}
        isLoading={createMutation.isPending || updateMutation.isPending}
      />

      {/* Version History Modal */}
      {versionHistoryTemplate && (
        <TemplateVersionHistory
          templateId={versionHistoryTemplate.template_id}
          templateName={versionHistoryTemplate.name}
          isOpen={true}
          onClose={() => setVersionHistoryTemplate(null)}
        />
      )}
    </div>
  );
};
