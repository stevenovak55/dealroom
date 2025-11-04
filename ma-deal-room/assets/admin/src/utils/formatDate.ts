import { format, parseISO, formatDistanceToNow, isAfter, isBefore } from 'date-fns';

/**
 * Format date string to readable format
 */
export const formatDate = (dateString: string | undefined, formatStr = 'MMM dd, yyyy'): string => {
  if (!dateString) return 'Not set';
  try {
    return format(parseISO(dateString), formatStr);
  } catch {
    return 'Invalid date';
  }
};

/**
 * Format datetime string to readable format
 */
export const formatDateTime = (dateString: string | undefined): string => {
  if (!dateString) return 'Not set';
  try {
    return format(parseISO(dateString), 'MMM dd, yyyy h:mm a');
  } catch {
    return 'Invalid date';
  }
};

/**
 * Format relative time (e.g., "2 days ago")
 */
export const formatRelativeTime = (dateString: string | undefined): string => {
  if (!dateString) return 'Unknown';
  try {
    return formatDistanceToNow(parseISO(dateString), { addSuffix: true });
  } catch {
    return 'Invalid date';
  }
};

/**
 * Check if date is overdue
 */
export const isOverdue = (dateString: string | undefined): boolean => {
  if (!dateString) return false;
  try {
    return isBefore(parseISO(dateString), new Date());
  } catch {
    return false;
  }
};

/**
 * Check if date is in the future
 */
export const isFuture = (dateString: string | undefined): boolean => {
  if (!dateString) return false;
  try {
    return isAfter(parseISO(dateString), new Date());
  } catch {
    return false;
  }
};

/**
 * Get days until date
 */
export const getDaysUntil = (dateString: string | undefined): number => {
  if (!dateString) return 0;
  try {
    const date = parseISO(dateString);
    const now = new Date();
    const diffTime = date.getTime() - now.getTime();
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  } catch {
    return 0;
  }
};

/**
 * Format currency value
 */
export const formatCurrency = (value: number | undefined): string => {
  if (value === undefined || value === null) return '$0';
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value);
};
