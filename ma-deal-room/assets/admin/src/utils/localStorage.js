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
    get: () => {
        try {
            const data = localStorage.getItem(STORAGE_KEYS.FAVORITES);
            return data ? JSON.parse(data) : [];
        }
        catch {
            return [];
        }
    },
    add: (taskId) => {
        const current = favorites.get();
        if (!current.includes(taskId)) {
            localStorage.setItem(STORAGE_KEYS.FAVORITES, JSON.stringify([...current, taskId]));
        }
    },
    remove: (taskId) => {
        const current = favorites.get();
        localStorage.setItem(STORAGE_KEYS.FAVORITES, JSON.stringify(current.filter(id => id !== taskId)));
    },
    toggle: (taskId) => {
        const current = favorites.get();
        const isFavorite = current.includes(taskId);
        if (isFavorite) {
            favorites.remove(taskId);
        }
        else {
            favorites.add(taskId);
        }
        return !isFavorite;
    },
    isFavorite: (taskId) => {
        return favorites.get().includes(taskId);
    },
    clear: () => {
        localStorage.removeItem(STORAGE_KEYS.FAVORITES);
    },
};
export const recentTasks = {
    get: (limit = 10) => {
        try {
            const data = localStorage.getItem(STORAGE_KEYS.RECENT_TASKS);
            const tasks = data ? JSON.parse(data) : [];
            return tasks
                .sort((a, b) => b.timestamp - a.timestamp)
                .slice(0, limit);
        }
        catch {
            return [];
        }
    },
    add: (taskId, title) => {
        const current = recentTasks.get(50); // Keep max 50
        const filtered = current.filter(t => t.id !== taskId);
        const updated = [
            { id: taskId, timestamp: Date.now(), title },
            ...filtered,
        ].slice(0, 50);
        localStorage.setItem(STORAGE_KEYS.RECENT_TASKS, JSON.stringify(updated));
    },
    clear: () => {
        localStorage.removeItem(STORAGE_KEYS.RECENT_TASKS);
    },
};
/**
 * Keyboard Shortcuts Preference
 */
export const keyboardShortcuts = {
    isEnabled: () => {
        try {
            const data = localStorage.getItem(STORAGE_KEYS.KEYBOARD_SHORTCUTS_ENABLED);
            return data === null ? true : data === 'true'; // Default to enabled
        }
        catch {
            return true;
        }
    },
    setEnabled: (enabled) => {
        localStorage.setItem(STORAGE_KEYS.KEYBOARD_SHORTCUTS_ENABLED, String(enabled));
    },
    toggle: () => {
        const current = keyboardShortcuts.isEnabled();
        keyboardShortcuts.setEnabled(!current);
        return !current;
    },
};
/**
 * Selected Tasks for Bulk Operations
 */
export const selectedTasks = {
    get: () => {
        try {
            const data = sessionStorage.getItem(STORAGE_KEYS.SELECTED_TASKS);
            return data ? JSON.parse(data) : [];
        }
        catch {
            return [];
        }
    },
    set: (taskIds) => {
        sessionStorage.setItem(STORAGE_KEYS.SELECTED_TASKS, JSON.stringify(taskIds));
    },
    add: (taskId) => {
        const current = selectedTasks.get();
        if (!current.includes(taskId)) {
            selectedTasks.set([...current, taskId]);
        }
    },
    remove: (taskId) => {
        const current = selectedTasks.get();
        selectedTasks.set(current.filter(id => id !== taskId));
    },
    toggle: (taskId) => {
        const current = selectedTasks.get();
        const isSelected = current.includes(taskId);
        if (isSelected) {
            selectedTasks.remove(taskId);
        }
        else {
            selectedTasks.add(taskId);
        }
        return !isSelected;
    },
    clear: () => {
        sessionStorage.removeItem(STORAGE_KEYS.SELECTED_TASKS);
    },
    isSelected: (taskId) => {
        return selectedTasks.get().includes(taskId);
    },
};
/**
 * Clear all stored data
 */
export const clearAll = () => {
    Object.values(STORAGE_KEYS).forEach(key => {
        localStorage.removeItem(key);
        sessionStorage.removeItem(key);
    });
};
