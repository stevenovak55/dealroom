import { useEffect } from 'react';
import { keyboardShortcuts } from '@/utils/localStorage';
/**
 * Hook to register and manage keyboard shortcuts
 */
export const useKeyboardShortcuts = (shortcuts) => {
    useEffect(() => {
        if (!keyboardShortcuts.isEnabled()) {
            return;
        }
        const handleKeyDown = (event) => {
            // Don't trigger shortcuts when typing in inputs
            const target = event.target;
            if (target.tagName === 'INPUT' ||
                target.tagName === 'TEXTAREA' ||
                target.isContentEditable) {
                // Allow CMD+K even in inputs
                if (!((event.metaKey || event.ctrlKey) && event.key === 'k')) {
                    return;
                }
            }
            for (const shortcut of shortcuts) {
                const ctrlMatch = shortcut.ctrl ? event.ctrlKey : !event.ctrlKey || shortcut.meta;
                const shiftMatch = shortcut.shift ? event.shiftKey : !event.shiftKey;
                const metaMatch = shortcut.meta ? event.metaKey : !event.metaKey || shortcut.ctrl;
                const altMatch = shortcut.alt ? event.altKey : !event.altKey;
                const keyMatch = event.key.toLowerCase() === shortcut.key.toLowerCase();
                if (keyMatch && ctrlMatch && shiftMatch && metaMatch && altMatch) {
                    event.preventDefault();
                    shortcut.action();
                    break;
                }
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [shortcuts]);
};
/**
 * Get display string for a keyboard shortcut
 */
export const getShortcutDisplay = (shortcut) => {
    const parts = [];
    const isMac = navigator.platform.includes('Mac');
    if (shortcut.ctrl || shortcut.meta) {
        parts.push(isMac ? '⌘' : 'Ctrl');
    }
    if (shortcut.shift) {
        parts.push('Shift');
    }
    if (shortcut.alt) {
        parts.push(isMac ? '⌥' : 'Alt');
    }
    if (shortcut.key) {
        parts.push(shortcut.key.toUpperCase());
    }
    return parts.join(isMac ? '' : '+');
};
