import React, { useState } from 'react';
import { Search, Filter, X, Save, ChevronDown } from 'lucide-react';
import { Button } from '@/components/shared/Button';
import { Card, CardContent } from '@/components/shared/Card';
import type { Transaction } from '@/api/types';

export interface SearchFilter {
  field: string;
  operator: 'equals' | 'not_equals' | 'contains' | 'greater_than' | 'less_than' | 'between' | 'in';
  value: any;
  label?: string;
}

export interface SavedSearch {
  id: string;
  name: string;
  filters: SearchFilter[];
  created_at: string;
}

interface AdvancedSearchProps {
  onSearch: (filters: SearchFilter[]) => void;
  onSaveSearch?: (search: SavedSearch) => void;
  savedSearches?: SavedSearch[];
}

const FILTER_FIELDS = [
  { value: 'status', label: 'Status', type: 'select', options: ['prospect', 'listing_active', 'under_agreement', 'closed', 'cancelled'] },
  { value: 'property_type', label: 'Property Type', type: 'select', options: ['SFH', 'Condo', 'Multifamily', 'Land', 'Commercial'] },
  { value: 'property_city', label: 'City', type: 'text' },
  { value: 'property_state', label: 'State', type: 'text' },
  { value: 'property_zip', label: 'ZIP Code', type: 'text' },
  { value: 'sale_price', label: 'Sale Price', type: 'number' },
  { value: 'listing_date', label: 'Listing Date', type: 'date' },
  { value: 'closing_date', label: 'Closing Date', type: 'date' },
  { value: 'assigned_agent_id', label: 'Agent', type: 'number' },
];

const OPERATORS = {
  text: [
    { value: 'contains', label: 'Contains' },
    { value: 'equals', label: 'Equals' },
    { value: 'not_equals', label: 'Does not equal' },
  ],
  number: [
    { value: 'equals', label: 'Equals' },
    { value: 'greater_than', label: 'Greater than' },
    { value: 'less_than', label: 'Less than' },
    { value: 'between', label: 'Between' },
  ],
  date: [
    { value: 'equals', label: 'On' },
    { value: 'greater_than', label: 'After' },
    { value: 'less_than', label: 'Before' },
    { value: 'between', label: 'Between' },
  ],
  select: [
    { value: 'equals', label: 'Is' },
    { value: 'not_equals', label: 'Is not' },
    { value: 'in', label: 'Is one of' },
  ],
};

export const AdvancedSearch: React.FC<AdvancedSearchProps> = ({
  onSearch,
  onSaveSearch,
  savedSearches = [],
}) => {
  const [isExpanded, setIsExpanded] = useState(false);
  const [quickSearch, setQuickSearch] = useState('');
  const [filters, setFilters] = useState<SearchFilter[]>([]);
  const [showSaveDialog, setShowSaveDialog] = useState(false);
  const [searchName, setSearchName] = useState('');

  const handleAddFilter = () => {
    setFilters([...filters, { field: 'status', operator: 'equals', value: '' }]);
  };

  const handleRemoveFilter = (index: number) => {
    setFilters(filters.filter((_, i) => i !== index));
  };

  const handleUpdateFilter = (index: number, updates: Partial<SearchFilter>) => {
    const newFilters = [...filters];
    newFilters[index] = { ...newFilters[index], ...updates };
    setFilters(newFilters);
  };

  const handleSearch = () => {
    // Include quick search as a filter if present
    const allFilters = [...filters];
    if (quickSearch) {
      allFilters.push({
        field: '_quick_search',
        operator: 'contains',
        value: quickSearch,
        label: `Quick search: "${quickSearch}"`,
      });
    }
    onSearch(allFilters);
  };

  const handleSaveSearch = () => {
    if (!searchName.trim()) {
      alert('Please enter a name for this search');
      return;
    }

    const savedSearch: SavedSearch = {
      id: `search-${Date.now()}`,
      name: searchName,
      filters,
      created_at: new Date().toISOString(),
    };

    onSaveSearch?.(savedSearch);
    setShowSaveDialog(false);
    setSearchName('');
  };

  const handleLoadSearch = (search: SavedSearch) => {
    setFilters(search.filters);
    setIsExpanded(true);
    onSearch(search.filters);
  };

  const handleClear = () => {
    setFilters([]);
    setQuickSearch('');
    onSearch([]);
  };

  const getFieldType = (fieldName: string): string => {
    return FILTER_FIELDS.find(f => f.value === fieldName)?.type || 'text';
  };

  const getFieldOptions = (fieldName: string): string[] => {
    return FILTER_FIELDS.find(f => f.value === fieldName)?.options || [];
  };

  return (
    <Card>
      <CardContent className="p-4">
        {/* Quick Search Bar */}
        <div className="flex items-center gap-2 mb-4">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
            <input
              type="text"
              placeholder="Quick search by address, city, or status..."
              value={quickSearch}
              onChange={(e) => setQuickSearch(e.target.value)}
              onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          <Button
            variant="secondary"
            onClick={() => setIsExpanded(!isExpanded)}
            className="flex items-center gap-2"
          >
            <Filter className="h-4 w-4" />
            Advanced
            <ChevronDown className={`h-4 w-4 transition-transform ${isExpanded ? 'rotate-180' : ''}`} />
          </Button>
          <Button variant="primary" onClick={handleSearch}>
            Search
          </Button>
        </div>

        {/* Saved Searches */}
        {savedSearches.length > 0 && (
          <div className="mb-4">
            <p className="text-sm font-medium text-gray-700 mb-2">Saved Searches</p>
            <div className="flex flex-wrap gap-2">
              {savedSearches.map((search) => (
                <button
                  key={search.id}
                  onClick={() => handleLoadSearch(search)}
                  className="px-3 py-1 text-sm bg-blue-50 text-blue-700 rounded-full hover:bg-blue-100 transition-colors"
                >
                  {search.name}
                </button>
              ))}
            </div>
          </div>
        )}

        {/* Advanced Filters */}
        {isExpanded && (
          <div className="space-y-4 pt-4 border-t border-gray-200">
            <div className="flex items-center justify-between">
              <h4 className="font-medium text-gray-900">Advanced Filters</h4>
              <div className="flex items-center gap-2">
                <Button size="sm" variant="secondary" onClick={handleClear}>
                  Clear All
                </Button>
                <Button size="sm" variant="secondary" onClick={handleAddFilter}>
                  <Filter className="h-3 w-3 mr-1" />
                  Add Filter
                </Button>
                {filters.length > 0 && onSaveSearch && (
                  <Button size="sm" variant="primary" onClick={() => setShowSaveDialog(true)}>
                    <Save className="h-3 w-3 mr-1" />
                    Save Search
                  </Button>
                )}
              </div>
            </div>

            {/* Filter List */}
            {filters.length === 0 ? (
              <div className="text-center py-8 text-gray-500">
                <Filter className="h-12 w-12 mx-auto mb-3 text-gray-300" />
                <p>No filters added</p>
                <p className="text-sm mt-1">Click "Add Filter" to create custom search criteria</p>
              </div>
            ) : (
              <div className="space-y-3">
                {filters.map((filter, index) => {
                  const fieldType = getFieldType(filter.field);
                  const operators = OPERATORS[fieldType as keyof typeof OPERATORS] || OPERATORS.text;
                  const options = getFieldOptions(filter.field);

                  return (
                    <div key={index} className="flex items-center gap-2 p-3 bg-gray-50 rounded-lg">
                      {/* Field Selector */}
                      <select
                        value={filter.field}
                        onChange={(e) => handleUpdateFilter(index, { field: e.target.value, value: '' })}
                        className="px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-sm"
                      >
                        {FILTER_FIELDS.map((field) => (
                          <option key={field.value} value={field.value}>
                            {field.label}
                          </option>
                        ))}
                      </select>

                      {/* Operator Selector */}
                      <select
                        value={filter.operator}
                        onChange={(e) => handleUpdateFilter(index, { operator: e.target.value as any })}
                        className="px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-sm"
                      >
                        {operators.map((op) => (
                          <option key={op.value} value={op.value}>
                            {op.label}
                          </option>
                        ))}
                      </select>

                      {/* Value Input */}
                      {fieldType === 'select' ? (
                        filter.operator === 'in' ? (
                          <select
                            multiple
                            value={Array.isArray(filter.value) ? filter.value : []}
                            onChange={(e) => {
                              const selected = Array.from(e.target.selectedOptions, option => option.value);
                              handleUpdateFilter(index, { value: selected });
                            }}
                            className="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-sm"
                          >
                            {options.map((opt) => (
                              <option key={opt} value={opt}>
                                {opt}
                              </option>
                            ))}
                          </select>
                        ) : (
                          <select
                            value={filter.value}
                            onChange={(e) => handleUpdateFilter(index, { value: e.target.value })}
                            className="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-sm"
                          >
                            <option value="">Select...</option>
                            {options.map((opt) => (
                              <option key={opt} value={opt}>
                                {opt}
                              </option>
                            ))}
                          </select>
                        )
                      ) : filter.operator === 'between' ? (
                        <div className="flex-1 flex items-center gap-2">
                          <input
                            type={fieldType === 'date' ? 'date' : fieldType}
                            value={Array.isArray(filter.value) ? filter.value[0] : ''}
                            onChange={(e) => {
                              const current = Array.isArray(filter.value) ? filter.value : ['', ''];
                              handleUpdateFilter(index, { value: [e.target.value, current[1]] });
                            }}
                            className="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-sm"
                          />
                          <span className="text-gray-500">and</span>
                          <input
                            type={fieldType === 'date' ? 'date' : fieldType}
                            value={Array.isArray(filter.value) ? filter.value[1] : ''}
                            onChange={(e) => {
                              const current = Array.isArray(filter.value) ? filter.value : ['', ''];
                              handleUpdateFilter(index, { value: [current[0], e.target.value] });
                            }}
                            className="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-sm"
                          />
                        </div>
                      ) : (
                        <input
                          type={fieldType === 'date' ? 'date' : fieldType}
                          value={filter.value}
                          onChange={(e) => handleUpdateFilter(index, { value: e.target.value })}
                          className="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-sm"
                          placeholder={`Enter ${FILTER_FIELDS.find(f => f.value === filter.field)?.label.toLowerCase()}...`}
                        />
                      )}

                      {/* Remove Button */}
                      <button
                        onClick={() => handleRemoveFilter(index)}
                        className="p-2 text-red-600 hover:bg-red-50 rounded transition-colors"
                      >
                        <X className="h-4 w-4" />
                      </button>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        )}

        {/* Active Filters Display */}
        {(filters.length > 0 || quickSearch) && (
          <div className="mt-4 pt-4 border-t border-gray-200">
            <p className="text-sm font-medium text-gray-700 mb-2">Active Filters</p>
            <div className="flex flex-wrap gap-2">
              {quickSearch && (
                <div className="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm flex items-center gap-2">
                  <Search className="h-3 w-3" />
                  {quickSearch}
                  <button onClick={() => setQuickSearch('')} className="hover:text-blue-900">
                    <X className="h-3 w-3" />
                  </button>
                </div>
              )}
              {filters.map((filter, index) => (
                <div key={index} className="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm flex items-center gap-2">
                  {FILTER_FIELDS.find(f => f.value === filter.field)?.label}:{' '}
                  {filter.operator.replace('_', ' ')} {Array.isArray(filter.value) ? filter.value.join(', ') : filter.value}
                  <button onClick={() => handleRemoveFilter(index)} className="hover:text-gray-900">
                    <X className="h-3 w-3" />
                  </button>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Save Search Dialog */}
        {showSaveDialog && (
          <div className="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h4 className="font-medium text-gray-900 mb-2">Save this search</h4>
            <div className="flex items-center gap-2">
              <input
                type="text"
                value={searchName}
                onChange={(e) => setSearchName(e.target.value)}
                placeholder="Enter search name..."
                className="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
              />
              <Button size="sm" variant="primary" onClick={handleSaveSearch}>
                Save
              </Button>
              <Button size="sm" variant="secondary" onClick={() => setShowSaveDialog(false)}>
                Cancel
              </Button>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
};

// Export utility function for applying filters to data
export const applyFilters = (data: Transaction[], filters: SearchFilter[]): Transaction[] => {
  if (filters.length === 0) return data;

  return data.filter(item => {
    return filters.every(filter => {
      const value = (item as any)[filter.field];

      // Quick search special case
      if (filter.field === '_quick_search') {
        const searchTerm = filter.value.toLowerCase();
        return (
          item.property_address?.toLowerCase().includes(searchTerm) ||
          item.property_city?.toLowerCase().includes(searchTerm) ||
          item.property_state?.toLowerCase().includes(searchTerm) ||
          item.status?.toLowerCase().includes(searchTerm)
        );
      }

      switch (filter.operator) {
        case 'equals':
          return value === filter.value;
        case 'not_equals':
          return value !== filter.value;
        case 'contains':
          return value?.toString().toLowerCase().includes(filter.value.toLowerCase());
        case 'greater_than':
          return Number(value) > Number(filter.value);
        case 'less_than':
          return Number(value) < Number(filter.value);
        case 'between':
          return Array.isArray(filter.value) &&
            Number(value) >= Number(filter.value[0]) &&
            Number(value) <= Number(filter.value[1]);
        case 'in':
          return Array.isArray(filter.value) && filter.value.includes(value);
        default:
          return true;
      }
    });
  });
};
