import { useNavigate } from 'react-router-dom';
import { FileText, CheckSquare, Users, File, Layout, Loader2 } from 'lucide-react';
import type { SearchResult, SearchResults } from '@/api/queries/useSearch';

interface GlobalSearchDropdownProps {
  results: SearchResults;
  isLoading: boolean;
  query: string;
  onClose: () => void;
}

const typeIcons = {
  transaction: Layout,
  task: CheckSquare,
  party: Users,
  document: File,
  template: FileText,
};

const typeLabels = {
  transactions: 'Transactions',
  tasks: 'Tasks',
  parties: 'Parties',
  documents: 'Documents',
  templates: 'Templates',
};

export const GlobalSearchDropdown = ({ results, isLoading, query, onClose }: GlobalSearchDropdownProps) => {
  const navigate = useNavigate();

  const handleResultClick = (result: SearchResult) => {
    navigate(result.url);
    onClose();
  };

  const handleKeyDown = (e: React.KeyboardEvent, result: SearchResult) => {
    if (e.key === 'Enter') {
      handleResultClick(result);
    }
  };

  if (query.length === 0) {
    return null;
  }

  if (query.length < 2) {
    return (
      <div className="absolute top-full left-0 right-0 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg z-50 p-4">
        <p className="text-sm text-gray-500 text-center">
          Type at least 2 characters to search
        </p>
      </div>
    );
  }

  if (isLoading) {
    return (
      <div className="absolute top-full left-0 right-0 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg z-50 p-4">
        <div className="flex items-center justify-center gap-2">
          <Loader2 className="h-4 w-4 animate-spin text-primary-600" />
          <p className="text-sm text-gray-500">Searching...</p>
        </div>
      </div>
    );
  }

  if (results.total === 0) {
    return (
      <div className="absolute top-full left-0 right-0 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg z-50 p-4">
        <p className="text-sm text-gray-500 text-center">
          No results found for "{query}"
        </p>
      </div>
    );
  }

  return (
    <div className="absolute top-full left-0 right-0 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg z-50 max-h-96 overflow-y-auto">
      {/* Render each category */}
      {Object.entries(results).map(([key, items]) => {
        if (key === 'total' || !Array.isArray(items) || items.length === 0) {
          return null;
        }

        const typedKey = key as keyof typeof typeLabels;
        const firstItem = items[0] as SearchResult;
        const Icon = typeIcons[firstItem.type];

        return (
          <div key={key} className="border-b border-gray-200 last:border-0">
            <div className="px-4 py-2 bg-gray-50">
              <h3 className="text-xs font-semibold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                <Icon className="h-3.5 w-3.5" />
                {typeLabels[typedKey]}
              </h3>
            </div>
            <div>
              {(items as SearchResult[]).map((result) => {
                const ResultIcon = typeIcons[result.type];
                return (
                  <div
                    key={`${result.type}-${result.id}`}
                    onClick={() => handleResultClick(result)}
                    onKeyDown={(e) => handleKeyDown(e, result)}
                    className="px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors focus:bg-gray-50 focus:outline-none"
                    role="button"
                    tabIndex={0}
                  >
                    <div className="flex items-start gap-3">
                      <ResultIcon className="h-5 w-5 text-gray-400 mt-0.5 flex-shrink-0" />
                      <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium text-gray-900 truncate">
                          {result.title}
                        </p>
                        {result.subtitle && (
                          <p className="text-sm text-gray-500 truncate">
                            {result.subtitle}
                          </p>
                        )}
                        {result.meta && (
                          <p className="text-xs text-gray-400 mt-1">
                            {result.meta}
                          </p>
                        )}
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        );
      })}

      {/* Footer with total count */}
      <div className="px-4 py-2 bg-gray-50 border-t border-gray-200">
        <p className="text-xs text-gray-500 text-center">
          Showing {results.total} result{results.total !== 1 ? 's' : ''} for "{query}"
        </p>
      </div>
    </div>
  );
};
