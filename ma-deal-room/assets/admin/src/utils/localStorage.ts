/**
 * Local Storage utility for managing user preferences
 */

const STORAGE_KEYS = {
  FAVORITES: 'ma-deal-room:favorites',
  RECENT_TASKS: 'ma-deal-room:recent-tasks',
  KEYBOARD_SHORTCUTS_ENABLED: 'ma-deal-room:keyboard-shortcuts',
  SELECTED_TASKS: 'ma-deal-room:selected-tasks',
};

/**
 * Favorites Management
 */
export const favorites = {
  get: (): number[] => {
    try {
      const data = localStorage.getItem(STORAGE_KEYS.FAVORITES);
      return data ? JSON.parse(data) : [];
    } catch {
      return [];
    }
  },

  add: (taskId: number): void => {
    const current = favorites.get();
    if (!current.includes(taskId)) {
      localStorage.setItem(
        STORAGE_KEYS.FAVORITES,
        JSON.stringify([...current, taskId])
      );
    }
  },

  remove: (taskId: number): void => {
    const current = favorites.get();
    localStorage.setItem(
      STORAGE_KEYS.FAVORITES,
      JSON.stringify(current.filter(id => id !== taskId))
    );
  },

  toggle: (taskId: number): boolean => {
    const current = favorites.get();
    const isFavorite = current.includes(taskId);
    if (isFavorite) {
      favorites.remove(taskId);
    } else {
      favorites.add(taskId);
    }
    return !isFavorite;
  },

  isFavorite: (taskId: number): boolean => {
    return favorites.get().includes(taskId);
  },

  clear: (): void => {
    localStorage.removeItem(STORAGE_KEYS.FAVORITES);
  },
};

/**
 * Recent Tasks Management
 */
interface RecentTask {
  id: number;
  timestamp: number;
  title: string;
}

export const recentTasks = {
  get: (limit = 10): RecentTask[] => {
    try {
      const data = localStorage.getItem(STORAGE_KEYS.RECENT_TASKS);
      const tasks: RecentTask[] = data ? JSON.parse(data) : [];
      return tasks
        .sort((a, b) => b.timestamp - a.timestamp)
        .slice(0, limit);
    } catch {
      return [];
    }
  },

  add: (taskId: number, title: string): void => {
    const current = recentTasks.get(50); // Keep max 50
    const filtered = current.filter(t => t.id !== taskId);
    const updated = [
      { id: taskId, timestamp: Date.now(), title },
      ...filtered,
    ].slice(0, 50);
    localStorage.setItem(STORAGE_KEYS.RECENT_TASKS, JSON.stringify(updated));
  },

  clear: (): void => {
    localStorage.removeItem(STORAGE_KEYS.RECENT_TASKS);
  },
};

/**
 * Keyboard Shortcuts Preference
 */
export const keyboardShortcuts = {
  isEnabled: (): boolean => {
    try {
      const data = localStorage.getItem(STORAGE_KEYS.KEYBOARD_SHORTCUTS_ENABLED);
      return data === null ? true : data === 'true'; // Default to enabled
    } catch {
      return true;
    }
  },

  setEnabled: (enabled: boolean): void => {
    localStorage.setItem(STORAGE_KEYS.KEYBOARD_SHORTCUTS_ENABLED, String(enabled));
  },

  toggle: (): boolean => {
    const current = keyboardShortcuts.isEnabled();
    keyboardShortcuts.setEnabled(!current);
    return !current;
  },
};

/**
 * Selected Tasks for Bulk Operations
 */
export const selectedTasks = {
  get: (): number[] => {
    try {
      const data = sessionStorage.getItem(STORAGE_KEYS.SELECTED_TASKS);
      return data ? JSON.parse(data) : [];
    } catch {
      return [];
    }
  },

  set: (taskIds: number[]): void => {
    sessionStorage.setItem(STORAGE_KEYS.SELECTED_TASKS, JSON.stringify(taskIds));
  },

  add: (taskId: number): void => {
    const current = selectedTasks.get();
    if (!current.includes(taskId)) {
      selectedTasks.set([...current, taskId]);
    }
  },

  remove: (taskId: number): void => {
    const current = selectedTasks.get();
    selectedTasks.set(current.filter(id => id !== taskId));
  },

  toggle: (taskId: number): boolean => {
    const current = selectedTasks.get();
    const isSelected = current.includes(taskId);
    if (isSelected) {
      selectedTasks.remove(taskId);
    } else {
      selectedTasks.add(taskId);
    }
    return !isSelected;
  },

  clear: (): void => {
    sessionStorage.removeItem(STORAGE_KEYS.SELECTED_TASKS);
  },

  isSelected: (taskId: number): boolean => {
    return selectedTasks.get().includes(taskId);
  },
};

/**
 * Clear all stored data
 */
export const clearAll = (): void => {
  Object.values(STORAGE_KEYS).forEach(key => {
    localStorage.removeItem(key);
    sessionStorage.removeItem(key);
  });
};
