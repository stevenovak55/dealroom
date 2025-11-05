import { X, RotateCcw } from 'lucide-react';
import { Button } from '@/components/shared/Button';
import { Input } from '@/components/shared/Input';
import { Label } from '@/components/shared/Label';
import { Select } from '@/components/shared/Select';
import { Checkbox } from '@/components/shared/Checkbox';
import { Slider } from '@/components/shared/Slider';
import {
  VendorSearchFilters as VendorSearchFiltersType,
  VENDOR_TYPE_LABELS,
} from '@/types/vendor-network';

interface VendorSearchFiltersProps {
  filters: VendorSearchFiltersType;
  onChange: (filters: VendorSearchFiltersType) => void;
  onClose: () => void;
}

// US States for dropdown
const US_STATES = [
  { value: 'AL', label: 'Alabama' },
  { value: 'AK', label: 'Alaska' },
  { value: 'AZ', label: 'Arizona' },
  { value: 'AR', label: 'Arkansas' },
  { value: 'CA', label: 'California' },
  { value: 'CO', label: 'Colorado' },
  { value: 'CT', label: 'Connecticut' },
  { value: 'DE', label: 'Delaware' },
  { value: 'FL', label: 'Florida' },
  { value: 'GA', label: 'Georgia' },
  { value: 'HI', label: 'Hawaii' },
  { value: 'ID', label: 'Idaho' },
  { value: 'IL', label: 'Illinois' },
  { value: 'IN', label: 'Indiana' },
  { value: 'IA', label: 'Iowa' },
  { value: 'KS', label: 'Kansas' },
  { value: 'KY', label: 'Kentucky' },
  { value: 'LA', label: 'Louisiana' },
  { value: 'ME', label: 'Maine' },
  { value: 'MD', label: 'Maryland' },
  { value: 'MA', label: 'Massachusetts' },
  { value: 'MI', label: 'Michigan' },
  { value: 'MN', label: 'Minnesota' },
  { value: 'MS', label: 'Mississippi' },
  { value: 'MO', label: 'Missouri' },
  { value: 'MT', label: 'Montana' },
  { value: 'NE', label: 'Nebraska' },
  { value: 'NV', label: 'Nevada' },
  { value: 'NH', label: 'New Hampshire' },
  { value: 'NJ', label: 'New Jersey' },
  { value: 'NM', label: 'New Mexico' },
  { value: 'NY', label: 'New York' },
  { value: 'NC', label: 'North Carolina' },
  { value: 'ND', label: 'North Dakota' },
  { value: 'OH', label: 'Ohio' },
  { value: 'OK', label: 'Oklahoma' },
  { value: 'OR', label: 'Oregon' },
  { value: 'PA', label: 'Pennsylvania' },
  { value: 'RI', label: 'Rhode Island' },
  { value: 'SC', label: 'South Carolina' },
  { value: 'SD', label: 'South Dakota' },
  { value: 'TN', label: 'Tennessee' },
  { value: 'TX', label: 'Texas' },
  { value: 'UT', label: 'Utah' },
  { value: 'VT', label: 'Vermont' },
  { value: 'VA', label: 'Virginia' },
  { value: 'WA', label: 'Washington' },
  { value: 'WV', label: 'West Virginia' },
  { value: 'WI', label: 'Wisconsin' },
  { value: 'WY', label: 'Wyoming' },
];

export const VendorSearchFilters = ({
  filters,
  onChange,
  onClose,
}: VendorSearchFiltersProps) => {
  const handleChange = (key: keyof VendorSearchFiltersType, value: string | number | boolean | undefined) => {
    onChange({
      ...filters,
      [key]: value || undefined,
    });
  };

  const handleReset = () => {
    onChange({});
  };

  const activeFiltersCount = Object.keys(filters).filter(
    (key) => filters[key as keyof VendorSearchFiltersType] !== undefined
  ).length;

  return (
    <div className="space-y-4">
      <div className="flex justify-between items-center">
        <h3 className="font-semibold text-gray-900">Filter Vendors</h3>
        <div className="flex items-center gap-2">
          {activeFiltersCount > 0 && (
            <>
              <span className="text-sm text-gray-500">
                {activeFiltersCount} active filter{activeFiltersCount !== 1 ? 's' : ''}
              </span>
              <Button
                size="sm"
                variant="ghost"
                onClick={handleReset}
                className="text-gray-600"
              >
                <RotateCcw className="h-3 w-3 mr-1" />
                Reset
              </Button>
            </>
          )}
          <Button size="sm" variant="ghost" onClick={onClose}>
            <X className="h-4 w-4" />
          </Button>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {/* Vendor Type */}
        <div>
          <Label htmlFor="vendor-type">Vendor Type</Label>
          <Select
            id="vendor-type"
            value={filters.vendor_type || ''}
            onValueChange={(value: string) => handleChange('vendor_type', value || undefined)}
            className="w-full"
          >
            <option value="">All Types</option>
            {Object.entries(VENDOR_TYPE_LABELS).map(([value, label]) => (
              <option key={value} value={value}>
                {label}
              </option>
            ))}
          </Select>
        </div>

        {/* State */}
        <div>
          <Label htmlFor="state">State</Label>
          <Select
            id="state"
            value={filters.state || ''}
            onValueChange={(value: string) => handleChange('state', value || undefined)}
            className="w-full"
          >
            <option value="">All States</option>
            {US_STATES.map((state) => (
              <option key={state.value} value={state.value}>
                {state.label}
              </option>
            ))}
          </Select>
        </div>

        {/* City */}
        <div>
          <Label htmlFor="city">City</Label>
          <Input
            id="city"
            type="text"
            placeholder="Enter city"
            value={filters.city || ''}
            onChange={(e) => handleChange('city', e.target.value || undefined)}
          />
        </div>

        {/* ZIP Code */}
        <div>
          <Label htmlFor="zip">ZIP Code</Label>
          <Input
            id="zip"
            type="text"
            placeholder="Enter ZIP"
            value={filters.zip || ''}
            onChange={(e) => handleChange('zip', e.target.value || undefined)}
            maxLength={10}
          />
        </div>

        {/* Minimum Rating */}
        <div className="md:col-span-2">
          <Label htmlFor="min-rating">
            Minimum Rating: {filters.min_rating || 0} stars
          </Label>
          <div className="mt-2">
            <Slider
              id="min-rating"
              min={0}
              max={5}
              step={0.5}
              value={[filters.min_rating || 0]}
              onValueChange={(value) => handleChange('min_rating', value[0] || undefined)}
              className="w-full"
            />
            <div className="flex justify-between text-xs text-gray-500 mt-1">
              <span>0</span>
              <span>1</span>
              <span>2</span>
              <span>3</span>
              <span>4</span>
              <span>5</span>
            </div>
          </div>
        </div>

        {/* Verified Only */}
        <div className="flex items-center space-x-2">
          <Checkbox
            id="verified-only"
            checked={filters.verified_only || false}
            onCheckedChange={(checked) =>
              handleChange('verified_only', checked || undefined)
            }
          />
          <Label
            htmlFor="verified-only"
            className="text-sm font-normal cursor-pointer"
          >
            Show only verified vendors
          </Label>
        </div>
      </div>

      <div className="flex justify-end gap-2 pt-4 border-t">
        <Button variant="outline" onClick={onClose}>
          Cancel
        </Button>
        <Button onClick={onClose}>Apply Filters</Button>
      </div>
    </div>
  );
};