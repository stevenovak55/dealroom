import { useState, useMemo } from 'react';
import { Library, Plus, Search, Filter, X } from 'lucide-react';
import { useGetTaskDefinitions } from '@/api/queries/useTaskDefinitions';
import { useGetTaskCategories } from '@/api/queries/useTaskCategories';
import { Card, CardContent } from '@/components/shared/Card';
import { Button } from '@/components/shared/Button';
import { PageLoader } from '@/components/shared/Loader';
import { EmptyState } from '@/components/shared/EmptyState';
import { TaskCard } from './TaskCard';
import { TaskDefinitionForm } from './TaskDefinitionForm';
import type { TaskDefinition, TaskDefinitionGrouped } from '@/api/types';

export const TaskLibrary = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState<string>('');
  const [showSystemOnly, setShowSystemOnly] = useState(true);
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [selectedTask, setSelectedTask] = useState<TaskDefinition | null>(null);

  // Fetch grouped task definitions
  const { data: groupedData, isLoading } = useGetTaskDefinitions({
    grouped: true,
    search: searchTerm || undefined,
    category: selectedCategory || undefined,
    is_system: showSystemOnly || undefined,
  });

  const { data: categories } = useGetTaskCategories();

  // Convert grouped data to proper format
  const taskGroups = useMemo(() => {
    if (!groupedData || typeof groupedData !== 'object') return [];

    return Object.entries(groupedData as Record<string, TaskDefinitionGrouped>)
      .map(([key, group]) => ({
        ...group,
        category_key: key,
      }))
      .filter(group => group.tasks && group.tasks.length > 0)
      .sort((a, b) => a.category_name.localeCompare(b.category_name));
  }, [groupedData]);

  const totalTasks = useMemo(() => {
    return taskGroups.reduce((sum, group) => sum + (group.tasks?.length || 0), 0);
  }, [taskGroups]);

  if (isLoading) {
    return <PageLoader />;
  }

  const handleOpenCreate = () => {
    setSelectedTask(null);
    setIsFormOpen(true);
  };

  const handleOpenEdit = (task: TaskDefinition) => {
    setSelectedTask(task);
    setIsFormOpen(true);
  };

  const handleCloseForm = () => {
    setIsFormOpen(false);
    setSelectedTask(null);
  };

  const handleClearFilters = () => {
    setSearchTerm('');
    setSelectedCategory('');
    setShowSystemOnly(true);
  };

  const hasActiveFilters = searchTerm || selectedCategory || !showSystemOnly;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Task Library</h1>
          <p className="text-sm text-gray-500 mt-1">
            Browse {totalTasks} reusable task definitions across {taskGroups.length} categories
          </p>
        </div>
        <Button variant="primary" onClick={handleOpenCreate}>
          <Plus className="h-4 w-4 mr-2" />
          Create Custom Task
        </Button>
      </div>

      {/* Search and Filters */}
      <Card>
        <CardContent className="p-4">
          <div className="flex flex-col md:flex-row gap-4">
            {/* Search input */}
            <div className="flex-1 relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
              <input
                type="text"
                placeholder="Search tasks by title or description..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              />
            </div>

            {/* Category filter */}
            <div className="flex items-center gap-2">
              <Filter className="h-4 w-4 text-gray-400" />
              <select
                value={selectedCategory}
                onChange={(e) => setSelectedCategory(e.target.value)}
                className="px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              >
                <option value="">All Categories</option>
                {categories?.map((cat) => (
                  <option key={cat.category_key} value={cat.category_key}>
                    {cat.name}
                  </option>
                ))}
              </select>
            </div>

            {/* System tasks toggle */}
            <label className="flex items-center gap-2 cursor-pointer whitespace-nowrap">
              <input
                type="checkbox"
                checked={showSystemOnly}
                onChange={(e) => setShowSystemOnly(e.target.checked)}
                className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
              />
              <span className="text-sm text-gray-700">System tasks only</span>
            </label>

            {/* Clear filters */}
            {hasActiveFilters && (
              <Button
                size="sm"
                variant="ghost"
                onClick={handleClearFilters}
              >
                <X className="h-4 w-4 mr-1" />
                Clear
              </Button>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Task Groups */}
      {taskGroups.length === 0 ? (
        <Card>
          <EmptyState
            icon={Library}
            title="No tasks found"
            description={
              hasActiveFilters
                ? 'Try adjusting your filters or search terms'
                : 'No task definitions available'
            }
          />
        </Card>
      ) : (
        <div className="space-y-8">
          {taskGroups.map((group) => (
            <div key={group.category_key}>
              {/* Category header */}
              <div className="flex items-center gap-3 mb-4">
                <div
                  className="w-1 h-6 rounded-full"
                  style={{ backgroundColor: group.color || '#6366f1' }}
                />
                <div>
                  <h2 className="text-lg font-semibold text-gray-900">
                    {group.category_name}
                  </h2>
                  {group.description && (
                    <p className="text-sm text-gray-500">{group.description}</p>
                  )}
                </div>
                <span className="ml-auto text-sm text-gray-500">
                  {group.tasks.length} {group.tasks.length === 1 ? 'task' : 'tasks'}
                </span>
              </div>

              {/* Task cards */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {group.tasks.map((task) => (
                  <TaskCard
                    key={task.id}
                    task={task}
                    onEdit={handleOpenEdit}
                  />
                ))}
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Task definition form modal */}
      <TaskDefinitionForm
        task={selectedTask}
        isOpen={isFormOpen}
        onClose={handleCloseForm}
      />
    </div>
  );
};
