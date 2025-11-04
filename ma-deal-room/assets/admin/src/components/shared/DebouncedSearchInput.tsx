import { useState, useEffect } from 'react';
import { Search } from 'lucide-react';

interface DebouncedSearchInputProps {
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
  delay?: number;
  className?: string;
}

/**
 * Search input with debouncing to prevent excessive updates
 * Isolated component to prevent focus loss during parent re-renders
 */
export const DebouncedSearchInput = ({
  value,
  onChange,
  placeholder = 'Search...',
  delay = 300,
  className = '',
}: DebouncedSearchInputProps) => {
  const [searchInput, setSearchInput] = useState(value);

  // Debounce: Update parent after user stops typing
  useEffect(() => {
    const timer = setTimeout(() => {
      if (searchInput !== value) {
        onChange(searchInput);
      }
    }, delay);

    return () => clearTimeout(timer);
  }, [searchInput, delay]); // Intentionally exclude onChange and value to prevent loops

  // Sync: If parent changes value externally, update local state
  // IMPORTANT: Only depend on 'value', NOT 'searchInput' to avoid resetting user input
  useEffect(() => {
    if (value !== searchInput) {
      setSearchInput(value);
    }
  }, [value]); // eslint-disable-line react-hooks/exhaustive-deps

  return (
    <div className={`relative ${className}`}>
      <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
      <input
        type="text"
        placeholder={placeholder}
        value={searchInput}
        onChange={(e) => setSearchInput(e.target.value)}
        className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
      />
    </div>
  );
};
