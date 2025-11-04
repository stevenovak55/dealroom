import { useState, useEffect } from 'react';
import { Modal, ModalFooter } from '@/components/shared/Modal';
import { useCreateTask, useUpdateTask } from '@/api/queries/useTasks';
import { useGetTaskDefinitions } from '@/api/queries/useTaskDefinitions';
import type { Task, CreateTaskInput, UpdateTaskInput, TaskDefinition } from '@/api/types';

interface TaskFormModalProps {
  isOpen: boolean;
  onClose: () => void;
  transactionId: number;
  task?: Task;
}

const TASK_STATUSES = [
  { value: 'pending', label: 'Pending' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
  { value: 'blocked', label: 'Blocked' },
  { value: 'skipped', label: 'Skipped' },
];

const OWNER_ROLES = [
  { value: 'agent', label: 'Agent' },
  { value: 'seller', label: 'Seller' },
  { value: 'buyer', label: 'Buyer' },
  { value: 'seller_attorney', label: 'Seller Attorney' },
  { value: 'buyer_attorney', label: 'Buyer Attorney' },
  { value: 'vendor', label: 'Vendor' },
  { value: 'system', label: 'System' },
];

export const TaskFormModal = ({
  isOpen,
  onClose,
  transactionId,
  task,
}: TaskFormModalProps) => {
  const isEditing = !!task;
  const createTask = useCreateTask();
  const updateTask = useUpdateTask(task?.id || 0);

  const [mode, setMode] = useState<'custom' | 'library'>('custom');
  const [selectedDefinition, setSelectedDefinition] = useState<TaskDefinition | null>(null);
  const [searchQuery, setSearchQuery] = useState('');

  const { data: taskDefinitionsData } = useGetTaskDefinitions();
  const taskDefinitions = (taskDefinitionsData as any)?.data || [];

  const [formData, setFormData] = useState<CreateTaskInput | UpdateTaskInput>({
    transaction_id: transactionId,
    task_key: '',
    title: '',
    description: '',
    owner_role: 'agent',
    status: 'pending',
    due_at: '',
    sort_order: 0,
  });

  // Load existing task data when editing
  useEffect(() => {
    if (task) {
      setMode('custom'); // Always use custom mode when editing
      setFormData({
        title: task.title,
        description: task.description || '',
        owner_role: task.owner_role,
        status: task.status,
        due_at: task.due_at ? task.due_at.split(' ')[0] : '', // Convert datetime to date
        sort_order: task.sort_order,
      });
    } else {
      // Reset form when creating new task
      setMode('custom');
      setSelectedDefinition(null);
      setSearchQuery('');
      setFormData({
        transaction_id: transactionId,
        task_key: `custom_task_${Date.now()}`,
        title: '',
        description: '',
        owner_role: 'agent',
        status: 'pending',
        due_at: '',
        sort_order: 0,
      });
    }
  }, [task, transactionId, isOpen]);

  // Handle selecting a task from the library
  const handleSelectFromLibrary = (definition: TaskDefinition) => {
    setSelectedDefinition(definition);
    setFormData({
      transaction_id: transactionId,
      task_key: definition.task_key,
      title: definition.title,
      description: definition.description || '',
      owner_role: definition.owner_role || 'agent',
      status: 'pending',
      due_at: '',
      sort_order: 0,
    });
  };

  // Filter task definitions based on search query
  const filteredDefinitions = taskDefinitions.filter((def: TaskDefinition) =>
    def.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
    def.description?.toLowerCase().includes(searchQuery.toLowerCase()) ||
    def.category?.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    try {
      if (isEditing) {
        await updateTask.mutateAsync(formData as UpdateTaskInput);
      } else {
        await createTask.mutateAsync(formData as CreateTaskInput);
      }
      onClose();
    } catch (error) {
      console.error('Failed to save task:', error);
    }
  };

  const handleChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>
  ) => {
    const { name, value } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: name === 'sort_order' ? parseInt(value) || 0 : value,
    }));
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={isEditing ? 'Edit Task' : 'Add Task'}
      size="lg"
    >
      <form onSubmit={handleSubmit}>
        {/* Mode Tabs - Only show when creating new task */}
        {!isEditing && (
          <div className="border-b border-gray-200 mb-4">
            <nav className="flex gap-4">
              <button
                type="button"
                onClick={() => setMode('custom')}
                className={`py-2 px-1 border-b-2 font-medium text-sm ${
                  mode === 'custom'
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                Custom Task
              </button>
              <button
                type="button"
                onClick={() => setMode('library')}
                className={`py-2 px-1 border-b-2 font-medium text-sm ${
                  mode === 'library'
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                From Task Library
              </button>
            </nav>
          </div>
        )}

        <div className="space-y-4">
          {/* Task Library Selection */}
          {mode === 'library' && !isEditing && (
            <div className="space-y-3">
              {/* Search */}
              <div>
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Search tasks..."
                  className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                />
              </div>

              {/* Task List */}
              <div className="max-h-96 overflow-y-auto border border-gray-200 rounded-md">
                {filteredDefinitions.length === 0 ? (
                  <div className="p-4 text-center text-gray-500">
                    No tasks found
                  </div>
                ) : (
                  <div className="divide-y divide-gray-200">
                    {filteredDefinitions.map((def: TaskDefinition) => (
                      <button
                        key={def.id}
                        type="button"
                        onClick={() => handleSelectFromLibrary(def)}
                        className={`w-full text-left p-3 hover:bg-gray-50 transition-colors ${
                          selectedDefinition?.id === def.id ? 'bg-blue-50 border-l-4 border-blue-500' : ''
                        }`}
                      >
                        <div className="font-medium text-gray-900">{def.title}</div>
                        {def.description && (
                          <div className="text-sm text-gray-500 mt-1 line-clamp-2">
                            {def.description}
                          </div>
                        )}
                        {def.category && (
                          <div className="text-xs text-gray-400 mt-1">
                            Category: {def.category}
                          </div>
                        )}
                      </button>
                    ))}
                  </div>
                )}
              </div>

              {selectedDefinition && (
                <div className="p-3 bg-blue-50 border border-blue-200 rounded-md">
                  <p className="text-sm text-blue-800">
                    Selected: <strong>{selectedDefinition.title}</strong>
                  </p>
                  <p className="text-xs text-blue-600 mt-1">
                    You can modify the details below before adding
                  </p>
                </div>
              )}
            </div>
          )}

          {/* Title */}
          <div>
            <label htmlFor="title" className="block text-sm font-medium text-gray-700 mb-1">
              Title <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              id="title"
              name="title"
              value={formData.title}
              onChange={handleChange}
              required
              className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              placeholder="Enter task title"
            />
          </div>

          {/* Description */}
          <div>
            <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-1">
              Description
            </label>
            <textarea
              id="description"
              name="description"
              value={formData.description}
              onChange={handleChange}
              rows={3}
              className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              placeholder="Enter task description"
            />
          </div>

          {/* Owner Role and Status (2 columns) */}
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label htmlFor="owner_role" className="block text-sm font-medium text-gray-700 mb-1">
                Owner Role <span className="text-red-500">*</span>
              </label>
              <select
                id="owner_role"
                name="owner_role"
                value={formData.owner_role}
                onChange={handleChange}
                required
                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              >
                {OWNER_ROLES.map((role) => (
                  <option key={role.value} value={role.value}>
                    {role.label}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label htmlFor="status" className="block text-sm font-medium text-gray-700 mb-1">
                Status <span className="text-red-500">*</span>
              </label>
              <select
                id="status"
                name="status"
                value={formData.status}
                onChange={handleChange}
                required
                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              >
                {TASK_STATUSES.map((status) => (
                  <option key={status.value} value={status.value}>
                    {status.label}
                  </option>
                ))}
              </select>
            </div>
          </div>

          {/* Due Date and Sort Order (2 columns) */}
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label htmlFor="due_at" className="block text-sm font-medium text-gray-700 mb-1">
                Due Date
              </label>
              <input
                type="date"
                id="due_at"
                name="due_at"
                value={formData.due_at}
                onChange={handleChange}
                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <div>
              <label htmlFor="sort_order" className="block text-sm font-medium text-gray-700 mb-1">
                Sort Order
              </label>
              <input
                type="number"
                id="sort_order"
                name="sort_order"
                value={formData.sort_order}
                onChange={handleChange}
                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                placeholder="0"
              />
            </div>
          </div>
        </div>

        <ModalFooter>
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Cancel
          </button>
          <button
            type="submit"
            disabled={createTask.isPending || updateTask.isPending}
            className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {createTask.isPending || updateTask.isPending
              ? 'Saving...'
              : isEditing
              ? 'Update Task'
              : 'Add Task'}
          </button>
        </ModalFooter>
      </form>
    </Modal>
  );
};
