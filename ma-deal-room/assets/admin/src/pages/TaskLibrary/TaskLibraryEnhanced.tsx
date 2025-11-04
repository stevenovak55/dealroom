import { useState, useMemo } from 'react';
import { Library, Plus, Filter, X, ArrowUpDown, Eye, Copy } from 'lucide-react';
import { Toaster } from 'react-hot-toast';
import { useGetTaskDefinitions } from '@/api/queries/useTaskDefinitions';
import { useGetTaskCategories } from '@/api/queries/useTaskCategories';
import { Card, CardContent } from '@/components/shared/Card';
import { Button } from '@/components/shared/Button';
import { EmptyState } from '@/components/shared/EmptyState';
import { TaskLibrarySkeleton } from '@/components/shared/Skeleton';
import { DebouncedSearchInput } from '@/components/shared/DebouncedSearchInput';
import { TaskCard } from './TaskCard';
import { TaskDefinitionForm } from './TaskDefinitionForm';
import { TaskDetailDrawer } from './TaskDetailDrawer';
import { TaskLibraryStats } from './TaskLibraryStats';
import { showToast } from '@/utils/toast';
import type { TaskDefinition, TaskDefinitionGrouped, CreateTaskDefinitionInput } from '@/api/types';

type SortOption = 'name' | 'priority' | 'category' | 'recent';
type SortDirection = 'asc' | 'desc';

export const TaskLibraryEnhanced = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategories, setSelectedCategories] = useState<string[]>([]);
  const [selectedPriorities, setSelectedPriorities] = useState<string[]>([]);
  const [showSystemOnly, setShowSystemOnly] = useState(true);
  const [sortBy, setSortBy] = useState<SortOption>('category');
  const [sortDirection, setSortDirection] = useState<SortDirection>('asc');

  const [isFormOpen, setIsFormOpen] = useState(false);
  const [selectedTask, setSelectedTask] = useState<TaskDefinition | null>(null);
  const [detailDrawerTask, setDetailDrawerTask] = useState<TaskDefinition | null>(null);
  const [isDetailDrawerOpen, setIsDetailDrawerOpen] = useState(false);
  const [showFilters, setShowFilters] = useState(false);

  // Fetch grouped task definitions
  const { data: groupedData, isLoading } = useGetTaskDefinitions({
    grouped: true,
    search: searchTerm || undefined,
    is_system: showSystemOnly || undefined,
  });

  const { data: categories } = useGetTaskCategories();

  // Convert and filter grouped data
  const taskGroups = useMemo(() => {
    if (!groupedData || typeof groupedData !== 'object') return [];

    let groups = Object.entries(groupedData as Record<string, TaskDefinitionGrouped>)
      .map(([key, group]) => ({
        ...group,
        category_key: key,
      }))
      .filter(group => group.tasks && group.tasks.length > 0);

    // Filter by selected categories
    if (selectedCategories.length > 0) {
      groups = groups.filter(group => selectedCategories.includes(group.category_key));
    }

    // Filter by selected priorities
    if (selectedPriorities.length > 0) {
      groups = groups.map(group => ({
        ...group,
        tasks: group.tasks.filter(task => selectedPriorities.includes(task.priority)),
      })).filter(group => group.tasks.length > 0);
    }

    // Sort groups
    if (sortBy === 'category') {
      groups.sort((a, b) => {
        const comparison = a.category_name.localeCompare(b.category_name);
        return sortDirection === 'asc' ? comparison : -comparison;
      });
    }

    // Sort tasks within groups
    groups.forEach(group => {
      group.tasks.sort((a, b) => {
        let comparison = 0;
        switch (sortBy) {
          case 'name':
            comparison = a.title.localeCompare(b.title);
            break;
          case 'priority':
            const priorityOrder = { urgent: 4, high: 3, normal: 2, low: 1 };
            comparison = (priorityOrder[b.priority as keyof typeof priorityOrder] || 0) -
                        (priorityOrder[a.priority as keyof typeof priorityOrder] || 0);
            break;
          default:
            comparison = 0;
        }
        return sortDirection === 'asc' ? comparison : -comparison;
      });
    });

    return groups;
  }, [groupedData, selectedCategories, selectedPriorities, sortBy, sortDirection]);

  const totalTasks = useMemo(() => {
    return taskGroups.reduce((sum, group) => sum + (group.tasks?.length || 0), 0);
  }, [taskGroups]);

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

  const handleViewDetails = (task: TaskDefinition) => {
    setDetailDrawerTask(task);
    setIsDetailDrawerOpen(true);
  };

  const handleCloneTask = async (task: TaskDefinition) => {
    const clonedData: CreateTaskDefinitionInput = {
      task_key: `${task.task_key}_copy_${Date.now()}`,
      category: task.category,
      title: `${task.title} (Copy)`,
      description: task.description,
      owner_role: task.owner_role,
      priority: task.priority,
      estimated_duration: task.estimated_duration,
      due_calculation: task.due_calculation,
      applies_if: task.applies_if,
      depends_on: task.depends_on,
      metadata: task.metadata,
      is_milestone: task.is_milestone,
      is_required: task.is_required,
    };

    setSelectedTask({
      ...task,
      id: 0,
      task_key: clonedData.task_key,
      title: clonedData.title,
      is_system: false,
    } as TaskDefinition);
    setIsFormOpen(true);
    showToast.info('Task cloned! Modify and save to create.');
  };

  const handleClearFilters = () => {
    setSearchTerm('');
    setSelectedCategories([]);
    setSelectedPriorities([]);
    setShowSystemOnly(true);
  };

  const toggleCategory = (categoryKey: string) => {
    setSelectedCategories(prev =>
      prev.includes(categoryKey)
        ? prev.filter(c => c !== categoryKey)
        : [...prev, categoryKey]
    );
  };

  const togglePriority = (priority: string) => {
    setSelectedPriorities(prev =>
      prev.includes(priority)
        ? prev.filter(p => p !== priority)
        : [...prev, priority]
    );
  };

  const toggleSort = (option: SortOption) => {
    if (sortBy === option) {
      setSortDirection(prev => prev === 'asc' ? 'desc' : 'asc');
    } else {
      setSortBy(option);
      setSortDirection('asc');
    }
  };

  const hasActiveFilters = searchTerm || selectedCategories.length > 0 ||
                          selectedPriorities.length > 0 || !showSystemOnly;

  if (isLoading) {
    return (
      <div className="space-y-6">
        <Toaster position="top-right" />
        <TaskLibrarySkeleton />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <Toaster position="top-right" />

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

      {/* Statistics Cards */}
      <TaskLibraryStats taskGroups={taskGroups} />

      {/* Search and Filters */}
      <Card>
        <CardContent className="p-4">
          <div className="flex flex-col gap-4">
            {/* Search and action buttons row */}
            <div className="flex flex-col md:flex-row gap-4">
              {/* Search input */}
              <DebouncedSearchInput
                value={searchTerm}
                onChange={setSearchTerm}
                placeholder="Search tasks by title or description..."
                className="flex-1"
              />

              {/* Action buttons */}
              <div className="flex items-center gap-2">
                <Button
                  size="sm"
                  variant={showFilters ? 'primary' : 'secondary'}
                  onClick={() => setShowFilters(!showFilters)}
                >
                  <Filter className="h-4 w-4 mr-2" />
                  Filters
                  {(selectedCategories.length + selectedPriorities.length) > 0 && (
                    <span className="ml-2 bg-primary-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                      {selectedCategories.length + selectedPriorities.length}
                    </span>
                  )}
                </Button>

                <Button
                  size="sm"
                  variant="secondary"
                  onClick={() => toggleSort(sortBy)}
                >
                  <ArrowUpDown className="h-4 w-4 mr-2" />
                  Sort
                </Button>

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
            </div>

            {/* Advanced filters */}
            {showFilters && (
              <div className="border-t border-gray-200 pt-4 space-y-4">
                {/* Category filters */}
                <div>
                  <label className="text-sm font-medium text-gray-700 mb-2 block">
                    Categories
                  </label>
                  <div className="flex flex-wrap gap-2">
                    {categories?.map((cat) => (
                      <button
                        key={cat.category_key}
                        onClick={() => toggleCategory(cat.category_key)}
                        className={`px-3 py-1 text-sm rounded-full border transition-colors ${
                          selectedCategories.includes(cat.category_key)
                            ? 'bg-primary-100 border-primary-500 text-primary-700'
                            : 'bg-gray-50 border-gray-300 text-gray-700 hover:bg-gray-100'
                        }`}
                      >
                        {cat.name}
                      </button>
                    ))}
                  </div>
                </div>

                {/* Priority filters */}
                <div>
                  <label className="text-sm font-medium text-gray-700 mb-2 block">
                    Priority
                  </label>
                  <div className="flex flex-wrap gap-2">
                    {['low', 'normal', 'high', 'urgent'].map((priority) => (
                      <button
                        key={priority}
                        onClick={() => togglePriority(priority)}
                        className={`px-3 py-1 text-sm rounded-full border capitalize transition-colors ${
                          selectedPriorities.includes(priority)
                            ? 'bg-primary-100 border-primary-500 text-primary-700'
                            : 'bg-gray-50 border-gray-300 text-gray-700 hover:bg-gray-100'
                        }`}
                      >
                        {priority}
                      </button>
                    ))}
                  </div>
                </div>

                {/* Sort options */}
                <div>
                  <label className="text-sm font-medium text-gray-700 mb-2 block">
                    Sort By
                  </label>
                  <div className="flex flex-wrap gap-2">
                    {[
                      { value: 'category', label: 'Category' },
                      { value: 'name', label: 'Name' },
                      { value: 'priority', label: 'Priority' },
                    ].map((option) => (
                      <button
                        key={option.value}
                        onClick={() => toggleSort(option.value as SortOption)}
                        className={`px-3 py-1 text-sm rounded-full border transition-colors ${
                          sortBy === option.value
                            ? 'bg-primary-100 border-primary-500 text-primary-700'
                            : 'bg-gray-50 border-gray-300 text-gray-700 hover:bg-gray-100'
                        }`}
                      >
                        {option.label}
                        {sortBy === option.value && (
                          <span className="ml-1">{sortDirection === 'asc' ? '↑' : '↓'}</span>
                        )}
                      </button>
                    ))}
                  </div>
                </div>

                {/* System tasks toggle */}
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={showSystemOnly}
                    onChange={(e) => setShowSystemOnly(e.target.checked)}
                    className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                  />
                  <span className="text-sm text-gray-700">Show system tasks only</span>
                </label>
              </div>
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
            <div key={group.category_key} className="animate-fade-in">
              {/* Category header */}
              <div className="flex items-center gap-3 mb-4">
                <div
                  className="w-1 h-6 rounded-full"
                  style={{ backgroundColor: group.color || '#6366f1' }}
                />
                <div className="flex-1">
                  <h2 className="text-lg font-semibold text-gray-900">
                    {group.category_name}
                  </h2>
                  {group.description && (
                    <p className="text-sm text-gray-500">{group.description}</p>
                  )}
                </div>
                <span className="text-sm text-gray-500">
                  {group.tasks.length} {group.tasks.length === 1 ? 'task' : 'tasks'}
                </span>
              </div>

              {/* Task cards */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {group.tasks.map((task) => (
                  <div key={task.id} className="relative group">
                    <TaskCard
                      task={task}
                      onEdit={handleOpenEdit}
                    />
                    {/* Quick action buttons overlay */}
                    <div className="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
                      <button
                        onClick={() => handleViewDetails(task)}
                        className="p-2 bg-white rounded-lg shadow-md hover:bg-gray-50 transition-colors"
                        title="View details"
                      >
                        <Eye className="h-4 w-4 text-gray-600" />
                      </button>
                      <button
                        onClick={() => handleCloneTask(task)}
                        className="p-2 bg-white rounded-lg shadow-md hover:bg-gray-50 transition-colors"
                        title="Clone task"
                      >
                        <Copy className="h-4 w-4 text-gray-600" />
                      </button>
                    </div>
                  </div>
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

      {/* Task detail drawer */}
      <TaskDetailDrawer
        task={detailDrawerTask}
        isOpen={isDetailDrawerOpen}
        onClose={() => setIsDetailDrawerOpen(false)}
        onEdit={handleOpenEdit}
        onClone={handleCloneTask}
      />
    </div>
  );
};
