import { useState, useEffect } from 'react';
import { Modal, ModalFooter } from '@/components/shared/Modal';
import { Button } from '@/components/shared/Button';
import { Input } from '@/components/shared/Input';
import { Select } from '@/components/shared/Select';
import { useGetTaskCategories } from '@/api/queries/useTaskCategories';
import {
  useCreateTaskDefinition,
  useUpdateTaskDefinition,
} from '@/api/queries/useTaskDefinitions';
import { showToast } from '@/utils/toast';
import type { TaskDefinition, CreateTaskDefinitionInput } from '@/api/types';

interface TaskDefinitionFormProps {
  task?: TaskDefinition | null;
  isOpen: boolean;
  onClose: () => void;
}

export const TaskDefinitionForm = ({
  task,
  isOpen,
  onClose,
}: TaskDefinitionFormProps) => {
  const { data: categories } = useGetTaskCategories();
  const createMutation = useCreateTaskDefinition();
  const updateMutation = useUpdateTaskDefinition();

  const [formData, setFormData] = useState<CreateTaskDefinitionInput>({
    task_key: '',
    category: '',
    title: '',
    description: '',
    owner_role: 'agent',
    priority: 'normal',
    estimated_duration: undefined,
    due_calculation: '',
    applies_if: '',
    depends_on: [],
    metadata: {},
    is_milestone: false,
    is_required: false,
  });

  const [dependsOnInput, setDependsOnInput] = useState('');
  const [errors, setErrors] = useState<Record<string, string>>({});

  useEffect(() => {
    if (task) {
      setFormData({
        task_key: task.task_key || '',
        category: task.category || '',
        title: task.title || '',
        description: task.description || '',
        owner_role: task.owner_role || 'agent',
        priority: task.priority || 'normal',
        estimated_duration: task.estimated_duration || undefined,
        due_calculation: task.due_calculation || '',
        applies_if: task.applies_if || '',
        depends_on: task.depends_on || [],
        metadata: task.metadata || {},
        is_milestone: task.is_milestone || false,
        is_required: task.is_required || false,
      });
      setDependsOnInput(task.depends_on?.join(', ') || '');
    } else {
      setFormData({
        task_key: '',
        category: '',
        title: '',
        description: '',
        owner_role: 'agent',
        priority: 'normal',
        estimated_duration: undefined,
        due_calculation: '',
        applies_if: '',
        depends_on: [],
        metadata: {},
        is_milestone: false,
        is_required: false,
      });
      setDependsOnInput('');
    }
    setErrors({});
  }, [task, isOpen]);

  const handleChange = (field: keyof CreateTaskDefinitionInput, value: any) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    if (errors[field]) {
      setErrors((prev) => {
        const newErrors = { ...prev };
        delete newErrors[field];
        return newErrors;
      });
    }
  };

  const handleDependsOnChange = (value: string) => {
    setDependsOnInput(value);
    const deps = value
      .split(',')
      .map((d) => d.trim())
      .filter((d) => d.length > 0);
    handleChange('depends_on', deps);
  };

  const validate = () => {
    const newErrors: Record<string, string> = {};

    if (!formData.task_key.trim()) {
      newErrors.task_key = 'Task key is required';
    } else if (!/^[a-z0-9_-]+$/.test(formData.task_key)) {
      newErrors.task_key = 'Task key must contain only lowercase letters, numbers, hyphens, and underscores';
    }

    if (!formData.category) {
      newErrors.category = 'Category is required';
    }

    if (!formData.title.trim()) {
      newErrors.title = 'Title is required';
    }

    if (!formData.owner_role) {
      newErrors.owner_role = 'Owner role is required';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!validate()) {
      showToast.error('Please fix validation errors');
      return;
    }

    try {
      const submitData = {
        ...formData,
        estimated_duration: formData.estimated_duration || undefined,
        due_calculation: formData.due_calculation || undefined,
        applies_if: formData.applies_if || undefined,
      };

      if (task) {
        await updateMutation.mutateAsync({
          id: task.id,
          data: submitData,
        });
        showToast.success('Task updated successfully');
      } else {
        await createMutation.mutateAsync(submitData);
        showToast.success('Task created successfully');
      }

      onClose();
    } catch (error: any) {
      console.error('Failed to save task definition:', error);
      const message = error.response?.data?.message || 'Failed to save task definition. Please try again.';
      showToast.error(message);
    }
  };

  const isLoading = createMutation.isPending || updateMutation.isPending;

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={task ? 'Edit Custom Task' : 'Create Custom Task'}
      size="lg"
    >
      <form onSubmit={handleSubmit} className="space-y-4">
        {/* Task Key */}
        <Input
          label="Task Key"
          value={formData.task_key}
          onChange={(e) => handleChange('task_key', e.target.value)}
          error={errors.task_key}
          placeholder="e.g., custom_inspection_followup"
          disabled={!!task} // Can't change task key after creation
          helperText="Unique identifier (lowercase, numbers, hyphens, underscores only)"
        />

        {/* Title */}
        <Input
          label="Title"
          value={formData.title}
          onChange={(e) => handleChange('title', e.target.value)}
          error={errors.title}
          placeholder="e.g., Follow up on inspection results"
          required
        />

        {/* Description */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Description
          </label>
          <textarea
            value={formData.description}
            onChange={(e) => handleChange('description', e.target.value)}
            rows={3}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            placeholder="Describe what this task involves..."
          />
        </div>

        {/* Category and Owner Role - Two columns */}
        <div className="grid grid-cols-2 gap-4">
          <Select
            label="Category"
            value={formData.category}
            onChange={(e) => handleChange('category', e.target.value)}
            error={errors.category}
            required
            options={[
              { value: '', label: 'Select category...' },
              ...(categories?.map((cat) => ({
                value: cat.category_key,
                label: cat.name,
              })) || []),
            ]}
          />

          <Select
            label="Default Owner"
            value={formData.owner_role}
            onChange={(e) => handleChange('owner_role', e.target.value)}
            error={errors.owner_role}
            required
            options={[
              { value: 'agent', label: 'Agent' },
              { value: 'seller', label: 'Seller' },
              { value: 'buyer', label: 'Buyer' },
              { value: 'seller_attorney', label: 'Seller Attorney' },
              { value: 'buyer_attorney', label: 'Buyer Attorney' },
              { value: 'vendor', label: 'Vendor' },
            ]}
          />
        </div>

        {/* Priority and Estimated Duration - Two columns */}
        <div className="grid grid-cols-2 gap-4">
          <Select
            label="Priority"
            value={formData.priority}
            onChange={(e) => handleChange('priority', e.target.value)}
            options={[
              { value: 'low', label: 'Low' },
              { value: 'normal', label: 'Normal' },
              { value: 'high', label: 'High' },
              { value: 'urgent', label: 'Urgent' },
            ]}
          />

          <Input
            label="Estimated Duration (days)"
            type="number"
            value={formData.estimated_duration || ''}
            onChange={(e) =>
              handleChange('estimated_duration', e.target.value ? parseInt(e.target.value) : undefined)
            }
            placeholder="e.g., 3"
            min="1"
          />
        </div>

        {/* Due Calculation */}
        <Input
          label="Due Date Calculation"
          value={formData.due_calculation}
          onChange={(e) => handleChange('due_calculation', e.target.value)}
          placeholder="e.g., Closing-21d, PS+7d, listing_date+5d"
          helperText="Relative to closing, P&S (PS), or listing_date. Use format: Closing-21d"
        />

        {/* Advanced Section */}
        <div className="border-t border-gray-200 pt-4">
          <h3 className="text-sm font-medium text-gray-900 mb-3">Advanced Options</h3>

          {/* Conditional Application */}
          <Input
            label="Applies If (Condition)"
            value={formData.applies_if}
            onChange={(e) => handleChange('applies_if', e.target.value)}
            placeholder="e.g., property.has_septic == true"
            helperText="Task will only be created if this condition is met"
          />

          {/* Dependencies */}
          <div className="mt-3">
            <Input
              label="Depends On (Task Keys)"
              value={dependsOnInput}
              onChange={(e) => handleDependsOnChange(e.target.value)}
              placeholder="e.g., task1, task2, task3"
              helperText="Comma-separated list of task keys this task depends on"
            />
          </div>

          {/* Checkboxes */}
          <div className="mt-3 space-y-2">
            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                checked={formData.is_milestone}
                onChange={(e) => handleChange('is_milestone', e.target.checked)}
                className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
              />
              <span className="text-sm text-gray-700">Mark as milestone</span>
            </label>

            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                checked={formData.is_required}
                onChange={(e) => handleChange('is_required', e.target.checked)}
                className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
              />
              <span className="text-sm text-gray-700">Required task (cannot be skipped)</span>
            </label>
          </div>
        </div>

        {/* Footer */}
        <ModalFooter>
          <Button variant="ghost" onClick={onClose} disabled={isLoading}>
            Cancel
          </Button>
          <Button type="submit" variant="primary" isLoading={isLoading}>
            {task ? 'Update Task' : 'Create Task'}
          </Button>
        </ModalFooter>
      </form>
    </Modal>
  );
};
