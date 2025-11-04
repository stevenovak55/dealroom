import { Keyboard } from 'lucide-react';
import { Modal } from './Modal';
import type { KeyboardShortcut } from '@/hooks/useKeyboardShortcuts';
import { getShortcutDisplay } from '@/hooks/useKeyboardShortcuts';

interface KeyboardShortcutsDialogProps {
  isOpen: boolean;
  onClose: () => void;
  shortcuts: KeyboardShortcut[];
}

export const KeyboardShortcutsDialog = ({
  isOpen,
  onClose,
  shortcuts,
}: KeyboardShortcutsDialogProps) => {
  // Group shortcuts by category
  const grouped = shortcuts.reduce((acc, shortcut) => {
    const category = shortcut.category || 'General';
    if (!acc[category]) {
      acc[category] = [];
    }
    acc[category].push(shortcut);
    return acc;
  }, {} as Record<string, KeyboardShortcut[]>);

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title="Keyboard Shortcuts"
      size="lg"
    >
      <div className="p-6">
        <div className="flex items-center gap-3 mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
          <Keyboard className="h-6 w-6 text-blue-600" />
          <div className="flex-1">
            <p className="text-sm text-blue-900 font-medium">
              Use keyboard shortcuts to navigate and perform actions quickly
            </p>
            <p className="text-xs text-blue-700 mt-1">
              Press <kbd className="px-1.5 py-0.5 bg-white rounded border border-blue-300 font-mono text-xs">?</kbd> to open this dialog anytime
            </p>
          </div>
        </div>

        <div className="space-y-6">
          {Object.entries(grouped).map(([category, categoryShortcuts]) => (
            <div key={category}>
              <h3 className="text-sm font-semibold text-gray-900 mb-3 uppercase tracking-wide">
                {category}
              </h3>
              <div className="space-y-2">
                {categoryShortcuts.map((shortcut, index) => (
                  <div
                    key={index}
                    className="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50"
                  >
                    <span className="text-sm text-gray-700">
                      {shortcut.description}
                    </span>
                    <kbd className="px-3 py-1.5 bg-gray-100 text-gray-900 rounded border border-gray-300 font-mono text-sm font-semibold">
                      {getShortcutDisplay(shortcut)}
                    </kbd>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>

        <div className="mt-6 pt-6 border-t border-gray-200">
          <button
            onClick={onClose}
            className="w-full px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors"
          >
            Close
          </button>
        </div>
      </div>
    </Modal>
  );
};
