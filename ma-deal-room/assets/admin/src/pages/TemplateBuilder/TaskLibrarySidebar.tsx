import React, { useState, useMemo, useCallback } from 'react';
import { Search, GripVertical, Plus } from 'lucide-react';
import { useGetTaskDefinitions } from '@/api/queries/useTaskDefinitions';
import type { TaskDefinition } from '@/api/types';
import { TASK_PHASES } from '@/api/types';

interface TaskLibrarySidebarProps {
  onTaskSelect?: (task: TaskDefinition) => void;
}

const CATEGORY_COLORS: Record<string, string> = {
  'buyer-dd': 'bg-blue-100 text-blue-800',
  'seller-dd': 'bg-green-100 text-green-800',
  'inspections': 'bg-yellow-100 text-yellow-800',
  'legal': 'bg-purple-100 text-purple-800',
  'municipal': 'bg-red-100 text-red-800',
  'closing': 'bg-indigo-100 text-indigo-800',
  'post-closing': 'bg-gray-100 text-gray-800',
};

const PHASE_COLORS: Record<string, string> = {
  'pre_listing': 'bg-sky-100 text-sky-800',
  'post_listing': 'bg-blue-100 text-blue-800',
  'pre_agreement': 'bg-amber-100 text-amber-800',
  'post_agreement': 'bg-orange-100 text-orange-800',
  'pre_closing': 'bg-purple-100 text-purple-800',
  'post_closing': 'bg-emerald-100 text-emerald-800',
  'any': 'bg-gray-100 text-gray-600',
};

const TaskLibrarySidebarComponent: React.FC<TaskLibrarySidebarProps> = ({ onTaskSelect }) => {
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState<string>('all');
  const [expandedCategories, setExpandedCategories] = useState<Set<string>>(new Set(['all']));

  const { data: taskDefsData, isLoading } = useGetTaskDefinitions({ grouped: true });

  // Handle grouped response - when grouped=true, API returns object with category keys
  const groupedTasks = useMemo(() => {
    if (!taskDefsData) {
      return [];
    }

    // Check if it's already an array (grouped response)
    if (Array.isArray(taskDefsData)) {
      return taskDefsData as any[];
    }

    // Check if it has a .data property (paginated response)
    if ('data' in taskDefsData && Array.isArray(taskDefsData.data)) {
      return taskDefsData.data as any[];
    }

    // Maybe it's wrapped in pagination object
    if ('data' in taskDefsData && taskDefsData.data && 'data' in taskDefsData.data) {
      const innerData = (taskDefsData.data as any).data;
      if (Array.isArray(innerData)) {
        return innerData as any[];
      }
    }

    // API returns object with category keys when grouped=true: { deal_setup: {...}, party_onboarding: {...} }
    // Convert to array of category objects
    if (typeof taskDefsData === 'object' && !Array.isArray(taskDefsData)) {
      const categoriesArray = Object.values(taskDefsData);
      return categoriesArray as any[];
    }

    return [];
  }, [taskDefsData]);

  // Filter tasks
  const filteredGroups = useMemo(() => {
    if (!groupedTasks || groupedTasks.length === 0) return [];

    let filtered = groupedTasks;

    // Category filter
    if (selectedCategory !== 'all') {
      filtered = filtered.filter((group) => group.category_key === selectedCategory);
    }

    // Search filter
    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      filtered = filtered
        .map((group) => {
          // Ensure group has tasks array
          if (!group.tasks || !Array.isArray(group.tasks)) {
            return { ...group, tasks: [] };
          }

          return {
            ...group,
            tasks: group.tasks.filter(
              (task: any) =>
                task?.title?.toLowerCase().includes(query) ||
                task?.description?.toLowerCase().includes(query) ||
                task?.category?.toLowerCase().includes(query)
            ),
          };
        })
        .filter((group) => group.tasks && group.tasks.length > 0);
    }

    return filtered;
  }, [groupedTasks, selectedCategory, searchQuery]);

  // Toggle category expansion
  const toggleCategory = useCallback((categoryKey: string) => {
    setExpandedCategories(prev => {
      const newExpanded = new Set(prev);
      if (newExpanded.has(categoryKey)) {
        newExpanded.delete(categoryKey);
      } else {
        newExpanded.add(categoryKey);
      }
      return newExpanded;
    });
  }, []);

  // Handle drag start
  const handleDragStart = useCallback((e: React.DragEvent, task: TaskDefinition) => {
    e.dataTransfer.effectAllowed = 'copy';
    e.dataTransfer.setData('application/json', JSON.stringify(task));
    e.dataTransfer.setData('text/plain', task.title);
  }, []);

  // Get category badge color
  const getCategoryColor = (categoryKey: string) => {
    return CATEGORY_COLORS[categoryKey] || 'bg-gray-100 text-gray-800';
  };

  // Get phase badge color
  const getPhaseColor = (phase?: string) => {
    if (!phase) return null;
    return PHASE_COLORS[phase] || 'bg-gray-100 text-gray-600';
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500" />
      </div>
    );
  }

  return (
    <div className="h-full flex flex-col bg-white border-r border-gray-200">
      {/* Header */}
      <div className="p-4 border-b border-gray-200">
        <h2 className="text-lg font-semibold text-gray-900 mb-3">Task Library</h2>

        {/* Search */}
        <div className="relative mb-3">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input
            type="text"
            placeholder="Search tasks..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        {/* Category Filter */}
        <select
          value={selectedCategory}
          onChange={(e) => setSelectedCategory(e.target.value)}
          className="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
          <option value="all">All Categories</option>
          {groupedTasks.map((group) => (
            <option key={group.category_key} value={group.category_key}>
              {group.category_name} ({group.tasks.length})
            </option>
          ))}
        </select>
      </div>

      {/* Task List */}
      <div className="flex-1 overflow-y-auto">
        {filteredGroups.length === 0 ? (
          <div className="p-4 text-center text-gray-500 text-sm">
            No tasks found
          </div>
        ) : (
          filteredGroups.map((group) => (
            <div key={group.category_key} className="border-b border-gray-200">
              {/* Category Header */}
              <button
                onClick={() => toggleCategory(group.category_key)}
                className="w-full flex items-center justify-between p-3 hover:bg-gray-50 transition-colors text-left"
              >
                <div className="flex items-center gap-2">
                  <span className={`text-xs px-2 py-1 rounded font-medium ${getCategoryColor(group.category_key)}`}>
                    {group.category_name}
                  </span>
                  <span className="text-xs text-gray-500">({group.tasks.length})</span>
                </div>
                <svg
                  className={`w-4 h-4 text-gray-400 transition-transform ${
                    expandedCategories.has(group.category_key) ? 'transform rotate-180' : ''
                  }`}
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              {/* Tasks */}
              {expandedCategories.has(group.category_key) && (
                <div className="bg-gray-50">
                  {group.tasks.map((task: any) => (
                    <div
                      key={task.id}
                      draggable
                      onDragStart={(e) => handleDragStart(e, task)}
                      className="p-3 border-b border-gray-200 hover:bg-white cursor-move transition-colors"
                      title="Drag to add to template"
                    >
                      <div className="flex items-start gap-2">
                        <GripVertical className="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" />
                        <div className="flex-1 min-w-0">
                          <h4 className="text-sm font-medium text-gray-900 mb-1">
                            {task.title}
                          </h4>
                          {task.description && (
                            <p className="text-xs text-gray-600 line-clamp-2 mb-2">
                              {task.description}
                            </p>
                          )}
                          <div className="flex items-center gap-2 flex-wrap">
                            <span className="text-xs text-gray-500">
                              {task.owner_role.replace('_', ' ')}
                            </span>
                            {task.phase && (
                              <span className={`text-xs px-1.5 py-0.5 rounded ${getPhaseColor(task.phase)}`}>
                                {(TASK_PHASES as any)[task.phase]?.label || task.phase}
                              </span>
                            )}
                            {task.is_milestone && (
                              <span className="text-xs bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded">
                                Milestone
                              </span>
                            )}
                            {task.is_required && (
                              <span className="text-xs bg-red-100 text-red-800 px-1.5 py-0.5 rounded">
                                Required
                              </span>
                            )}
                          </div>
                        </div>
                        {onTaskSelect && (
                          <button
                            onClick={() => onTaskSelect(task)}
                            className="p-1 hover:bg-gray-200 rounded"
                            title="Add to template"
                          >
                            <Plus className="w-4 h-4 text-gray-600" />
                          </button>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          ))
        )}
      </div>

      {/* Footer Hint */}
      <div className="p-3 border-t border-gray-200 bg-gray-50">
        <p className="text-xs text-gray-600 text-center">
          💡 Drag tasks to the canvas to build your template
        </p>
      </div>
    </div>
  );
};

// Memoize component to prevent unnecessary re-renders
export const TaskLibrarySidebar = React.memo(TaskLibrarySidebarComponent);
