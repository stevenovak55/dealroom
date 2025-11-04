import { useState, useEffect } from 'react';
import { Modal, ModalFooter } from '@/components/shared/Modal';
import { Button } from '@/components/shared/Button';
import { Input } from '@/components/shared/Input';
import { Select } from '@/components/shared/Select';
import type { Template } from '@/api/types';

interface TemplateFormProps {
  template?: Template | null;
  isOpen: boolean;
  onClose: () => void;
  onSubmit: (data: Partial<Template>) => void;
  isLoading?: boolean;
}

export const TemplateForm = ({
  template,
  isOpen,
  onClose,
  onSubmit,
  isLoading = false,
}: TemplateFormProps) => {
  const [formData, setFormData] = useState<{
    name: string;
    description: string;
    property_type: 'SFH' | 'Condo' | 'Multifamily' | 'Land' | 'Commercial' | 'Any';
    template_yaml: string;
    version: string;
    is_active: boolean;
  }>({
    name: '',
    description: '',
    property_type: 'Any',
    template_yaml: '',
    version: '1.0',
    is_active: true,
  });

  const [errors, setErrors] = useState<Record<string, string>>({});

  useEffect(() => {
    if (template) {
      setFormData({
        name: template.name || '',
        description: template.description || '',
        property_type: template.property_type || 'Any',
        template_yaml: template.template_yaml || '',
        version: String(template.version || 1.0),
        is_active: template.is_active ?? true,
      });
    } else {
      setFormData({
        name: '',
        description: '',
        property_type: 'Any',
        template_yaml: '',
        version: '1.0',
        is_active: true,
      });
    }
    setErrors({});
  }, [template, isOpen]);

  const handleChange = (field: string, value: any) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    // Clear error for this field
    if (errors[field]) {
      setErrors((prev) => {
        const newErrors = { ...prev };
        delete newErrors[field];
        return newErrors;
      });
    }
  };

  const validate = () => {
    const newErrors: Record<string, string> = {};

    if (!formData.name.trim()) {
      newErrors.name = 'Name is required';
    }

    if (!formData.property_type) {
      newErrors.property_type = 'Property type is required';
    }

    if (!formData.template_yaml.trim()) {
      newErrors.template_yaml = 'Template YAML is required';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (!validate()) {
      return;
    }

    // Convert version to number for API
    const submitData = {
      ...formData,
      version: parseFloat(formData.version) || 1.0,
    };

    onSubmit(submitData);
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={template ? 'Edit Template' : 'New Template'}
      size="xl"
    >
      <form onSubmit={handleSubmit}>
        <div className="space-y-4">
          {/* Name */}
          <div>
            <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
              Name *
            </label>
            <Input
              id="name"
              type="text"
              value={formData.name}
              onChange={(e) => handleChange('name', e.target.value)}
              placeholder="e.g., Massachusetts Single Family Home"
              error={errors.name}
            />
          </div>

          {/* Description */}
          <div>
            <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-1">
              Description
            </label>
            <textarea
              id="description"
              rows={3}
              value={formData.description}
              onChange={(e) => handleChange('description', e.target.value)}
              placeholder="Brief description of this template"
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            />
          </div>

          {/* Property Type */}
          <div>
            <label htmlFor="property_type" className="block text-sm font-medium text-gray-700 mb-1">
              Property Type *
            </label>
            <Select
              id="property_type"
              value={formData.property_type}
              onChange={(e) => handleChange('property_type', e.target.value)}
              error={errors.property_type}
              options={[
                { value: 'Any', label: 'Any' },
                { value: 'SFH', label: 'Single Family Home' },
                { value: 'Condo', label: 'Condo' },
                { value: 'Multifamily', label: 'Multi-Family' },
                { value: 'Land', label: 'Land' },
                { value: 'Commercial', label: 'Commercial' },
              ]}
            />
          </div>

          {/* Version */}
          <div>
            <label htmlFor="version" className="block text-sm font-medium text-gray-700 mb-1">
              Version
            </label>
            <Input
              id="version"
              type="text"
              value={formData.version}
              onChange={(e) => handleChange('version', e.target.value)}
              placeholder="e.g., 1.0"
            />
          </div>

          {/* Template YAML */}
          <div>
            <label htmlFor="template_yaml" className="block text-sm font-medium text-gray-700 mb-1">
              Template YAML *
            </label>

            {/* Help text */}
            <div className="mb-2 p-3 bg-blue-50 border border-blue-200 rounded-md text-sm text-blue-800">
              <p className="font-medium mb-1">YAML Format Instructions:</p>
              <ul className="list-disc list-inside space-y-1 text-xs">
                <li>Start with <code className="bg-blue-100 px-1 rounded">tasks:</code> on the first line</li>
                <li>Each task starts with <code className="bg-blue-100 px-1 rounded">- key:</code> (note the dash and space)</li>
                <li>Indent task properties with 2 spaces (not tabs)</li>
                <li>Required: <strong>key</strong>, <strong>title</strong>, <strong>owner</strong></li>
                <li>Optional: description, due (e.g., Closing-21d), mandatory (true/false)</li>
                <li>Due date formats: Closing-21d, PS+7d, Listing+0d</li>
              </ul>
            </div>

            <textarea
              id="template_yaml"
              rows={14}
              value={formData.template_yaml}
              onChange={(e) => handleChange('template_yaml', e.target.value)}
              placeholder={`tasks:
  - key: task_1
    title: Task name
    description: Task description
    owner: agent
    due: Closing-21d
    mandatory: true
  - key: task_2
    title: Another task
    owner: seller
    due: PS+7d`}
              className={`w-full px-3 py-2 border rounded-md font-mono text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 ${
                errors.template_yaml ? 'border-red-300' : 'border-gray-300'
              }`}
            />
            {errors.template_yaml && (
              <p className="text-sm text-red-600 mt-1">{errors.template_yaml}</p>
            )}

            {/* Additional examples */}
            <details className="mt-2">
              <summary className="text-xs text-gray-600 cursor-pointer hover:text-gray-800">
                Click to view complete example
              </summary>
              <pre className="mt-2 p-3 bg-gray-50 border border-gray-200 rounded text-xs overflow-x-auto">
{`tasks:
  - key: initial_listing
    title: Initial listing setup
    description: Set up listing in MLS
    owner: agent
    due: Listing+0d
    mandatory: true

  - key: arrange_photography
    title: Arrange professional photography
    owner: agent
    due: Listing+2d

  - key: title_search
    title: Order title search
    owner: seller_attorney
    due: PS+3d
    mandatory: true

  - key: schedule_inspection
    title: Schedule home inspection
    owner: buyer
    due: PS+7d

  - key: final_walkthrough
    title: Final walkthrough
    owner: buyer
    due: Closing-1d
    mandatory: true`}
              </pre>
            </details>
          </div>

          {/* Active Status */}
          <div className="flex items-center">
            <input
              id="is_active"
              type="checkbox"
              checked={formData.is_active}
              onChange={(e) => handleChange('is_active', e.target.checked)}
              className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
            />
            <label htmlFor="is_active" className="ml-2 block text-sm text-gray-700">
              Active
            </label>
          </div>
        </div>

        <ModalFooter>
          <Button type="button" variant="secondary" onClick={onClose}>
            Cancel
          </Button>
          <Button type="submit" variant="primary" isLoading={isLoading}>
            {template ? 'Update Template' : 'Create Template'}
          </Button>
        </ModalFooter>
      </form>
    </Modal>
  );
};
