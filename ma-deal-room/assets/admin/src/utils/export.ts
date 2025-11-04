import type { TaskDefinition } from '@/api/types';

/**
 * Export tasks to JSON format
 */
export const exportToJSON = (tasks: TaskDefinition[], filename = 'tasks') => {
  const dataStr = JSON.stringify(tasks, null, 2);
  const dataBlob = new Blob([dataStr], { type: 'application/json' });
  downloadFile(dataBlob, `${filename}.json`);
};

/**
 * Export tasks to CSV format
 */
export const exportToCSV = (tasks: TaskDefinition[], filename = 'tasks') => {
  if (tasks.length === 0) {
    return;
  }

  // Define CSV headers
  const headers = [
    'Task Key',
    'Title',
    'Category',
    'Owner Role',
    'Priority',
    'Description',
    'Due Calculation',
    'Estimated Duration',
    'Is Milestone',
    'Is Required',
    'Is System',
    'Applies If',
    'Depends On',
  ];

  // Convert tasks to CSV rows
  const rows = tasks.map(task => [
    task.task_key,
    escapeCsvField(task.title),
    task.category,
    task.owner_role,
    task.priority,
    escapeCsvField(task.description || ''),
    escapeCsvField(task.due_calculation || ''),
    task.estimated_duration || '',
    task.is_milestone ? 'Yes' : 'No',
    task.is_required ? 'Yes' : 'No',
    task.is_system ? 'Yes' : 'No',
    escapeCsvField(task.applies_if || ''),
    task.depends_on?.join('; ') || '',
  ]);

  // Combine headers and rows
  const csvContent = [
    headers.join(','),
    ...rows.map(row => row.join(','))
  ].join('\n');

  const dataBlob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  downloadFile(dataBlob, `${filename}.csv`);
};

/**
 * Escape CSV field (handle quotes and commas)
 */
const escapeCsvField = (field: string): string => {
  if (field.includes(',') || field.includes('"') || field.includes('\n')) {
    return `"${field.replace(/"/g, '""')}"`;
  }
  return field;
};

/**
 * Download a blob as a file
 */
const downloadFile = (blob: Blob, filename: string) => {
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
};

/**
 * Copy tasks to clipboard as JSON
 */
export const copyToClipboard = async (tasks: TaskDefinition[]) => {
  const dataStr = JSON.stringify(tasks, null, 2);
  await navigator.clipboard.writeText(dataStr);
};

/**
 * Export selected tasks summary
 */
export const exportSummary = (tasks: TaskDefinition[], filename = 'task-summary') => {
  const summary = `# Task Summary Report
Generated: ${new Date().toLocaleString()}
Total Tasks: ${tasks.length}

## Tasks by Category
${getCategorySummary(tasks)}

## Tasks by Priority
${getPrioritySummary(tasks)}

## Task Details
${tasks.map((task, idx) => `
${idx + 1}. ${task.title}
   - Key: ${task.task_key}
   - Category: ${task.category}
   - Owner: ${task.owner_role}
   - Priority: ${task.priority}
   ${task.description ? `- Description: ${task.description}` : ''}
   ${task.is_milestone ? '- ⭐ Milestone' : ''}
   ${task.is_required ? '- ⚠️ Required' : ''}
`).join('\n')}
`;

  const dataBlob = new Blob([summary], { type: 'text/markdown;charset=utf-8;' });
  downloadFile(dataBlob, `${filename}.md`);
};

const getCategorySummary = (tasks: TaskDefinition[]): string => {
  const categoryCounts: Record<string, number> = {};
  tasks.forEach(task => {
    categoryCounts[task.category] = (categoryCounts[task.category] || 0) + 1;
  });
  return Object.entries(categoryCounts)
    .sort((a, b) => b[1] - a[1])
    .map(([category, count]) => `- ${category}: ${count}`)
    .join('\n');
};

const getPrioritySummary = (tasks: TaskDefinition[]): string => {
  const priorityCounts: Record<string, number> = {};
  tasks.forEach(task => {
    priorityCounts[task.priority] = (priorityCounts[task.priority] || 0) + 1;
  });
  return Object.entries(priorityCounts)
    .sort((a, b) => b[1] - a[1])
    .map(([priority, count]) => `- ${priority}: ${count}`)
    .join('\n');
};
