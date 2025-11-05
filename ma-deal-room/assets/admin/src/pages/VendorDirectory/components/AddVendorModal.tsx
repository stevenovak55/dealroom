import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Building2, User, MapPin, FileText, Award } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/shared/Dialog';
import { Button } from '@/components/shared/Button';
import { Input } from '@/components/shared/Input';
import { Label } from '@/components/shared/Label';
import { Select } from '@/components/shared/Select';
import { Textarea } from '@/components/shared/Textarea';
import { Checkbox } from '@/components/shared/Checkbox';
import { vendorNetworkService } from '@/services/vendorNetworkService';
import { VENDOR_TYPE_LABELS } from '@/types/vendor-network';
import { useToast } from '@/hooks/useToast';

interface AddVendorModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess?: () => void;
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

interface VendorFormData {
  // Basic Information
  email: string;
  name: string;
  company?: string;
  phone?: string;
  website?: string;

  // Address
  address?: string;
  city?: string;
  state?: string;
  zip?: string;

  // Professional Details
  vendor_type: string;
  license_number?: string;
  license_state?: string;
  insurance_expires?: string;
  years_experience?: number;

  // Profile
  bio?: string;
  service_areas?: string;
  certifications?: string;
  languages?: string;

  // Settings
  is_verified?: boolean;
  is_active?: boolean;
}

export const AddVendorModal = ({ isOpen, onClose, onSuccess }: AddVendorModalProps) => {
  const queryClient = useQueryClient();
  const { toast } = useToast();

  const [formData, setFormData] = useState<VendorFormData>({
    email: '',
    name: '',
    company: '',
    phone: '',
    website: '',
    address: '',
    city: '',
    state: 'MA',
    zip: '',
    vendor_type: 'inspector',
    license_number: '',
    license_state: '',
    insurance_expires: '',
    years_experience: undefined,
    bio: '',
    service_areas: '',
    certifications: '',
    languages: 'English',
    is_verified: false,
    is_active: true,
  });

  const [errors, setErrors] = useState<Partial<Record<keyof VendorFormData, string>>>({});

  const createVendorMutation = useMutation({
    mutationFn: (data: VendorFormData) => vendorNetworkService.createVendor(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vendors'] });
      toast({
        title: 'Vendor Added',
        description: 'The vendor has been successfully added to the network.',
      });
      resetForm();
      onClose();
      onSuccess?.();
    },
    onError: (error: any) => {
      toast({
        title: 'Error',
        description: error.response?.data?.message || 'Failed to add vendor. Please try again.',
        variant: 'error',
      });
    },
  });

  const resetForm = () => {
    setFormData({
      email: '',
      name: '',
      company: '',
      phone: '',
      website: '',
      address: '',
      city: '',
      state: 'MA',
      zip: '',
      vendor_type: 'inspector',
      license_number: '',
      license_state: '',
      insurance_expires: '',
      years_experience: undefined,
      bio: '',
      service_areas: '',
      certifications: '',
      languages: 'English',
      is_verified: false,
      is_active: true,
    });
    setErrors({});
  };

  const validateForm = (): boolean => {
    const newErrors: Partial<Record<keyof VendorFormData, string>> = {};

    if (!formData.email) {
      newErrors.email = 'Email is required';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      newErrors.email = 'Invalid email format';
    }

    if (!formData.name) {
      newErrors.name = 'Name is required';
    }

    if (!formData.vendor_type) {
      newErrors.vendor_type = 'Vendor type is required';
    }

    if (formData.phone && !/^\d{3}-\d{3}-\d{4}$/.test(formData.phone)) {
      newErrors.phone = 'Phone must be in format XXX-XXX-XXXX';
    }

    if (formData.zip && !/^\d{5}(-\d{4})?$/.test(formData.zip)) {
      newErrors.zip = 'Invalid ZIP code format';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (validateForm()) {
      // Convert comma-separated strings to arrays
      const submitData = {
        ...formData,
        service_areas: formData.service_areas ? formData.service_areas.split(',').map(s => s.trim()) : [],
        certifications: formData.certifications ? formData.certifications.split(',').map(s => s.trim()) : [],
        languages: formData.languages ? formData.languages.split(',').map(s => s.trim()) : [],
        years_experience: formData.years_experience ? Number(formData.years_experience) : undefined,
      };
      createVendorMutation.mutate(submitData as any);
    }
  };

  const handleChange = (field: keyof VendorFormData, value: any) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    // Clear error for this field when user starts typing
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: undefined }));
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Building2 className="h-5 w-5" />
            Add New Vendor
          </DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Basic Information */}
          <div className="space-y-4">
            <h3 className="font-semibold text-gray-900 flex items-center gap-2">
              <User className="h-4 w-4" />
              Basic Information
            </h3>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label htmlFor="name">Name *</Label>
                <Input
                  id="name"
                  type="text"
                  value={formData.name}
                  onChange={(e) => handleChange('name', e.target.value)}
                  error={errors.name}
                  placeholder="John Smith"
                />
              </div>

              <div>
                <Label htmlFor="company">Company</Label>
                <Input
                  id="company"
                  type="text"
                  value={formData.company}
                  onChange={(e) => handleChange('company', e.target.value)}
                  placeholder="Smith Inspections LLC"
                />
              </div>

              <div>
                <Label htmlFor="email">Email *</Label>
                <Input
                  id="email"
                  type="email"
                  value={formData.email}
                  onChange={(e) => handleChange('email', e.target.value)}
                  error={errors.email}
                  placeholder="vendor@example.com"
                />
              </div>

              <div>
                <Label htmlFor="phone">Phone</Label>
                <Input
                  id="phone"
                  type="tel"
                  value={formData.phone}
                  onChange={(e) => handleChange('phone', e.target.value)}
                  error={errors.phone}
                  placeholder="617-555-0123"
                />
              </div>

              <div>
                <Label htmlFor="website">Website</Label>
                <Input
                  id="website"
                  type="url"
                  value={formData.website}
                  onChange={(e) => handleChange('website', e.target.value)}
                  placeholder="https://example.com"
                />
              </div>

              <div>
                <Label htmlFor="vendor_type">Vendor Type *</Label>
                <Select
                  id="vendor_type"
                  value={formData.vendor_type}
                  onValueChange={(value: string) => handleChange('vendor_type', value)}
                >
                  {Object.entries(VENDOR_TYPE_LABELS).map(([value, label]) => (
                    <option key={value} value={value}>
                      {label}
                    </option>
                  ))}
                </Select>
              </div>
            </div>
          </div>

          {/* Location */}
          <div className="space-y-4">
            <h3 className="font-semibold text-gray-900 flex items-center gap-2">
              <MapPin className="h-4 w-4" />
              Location
            </h3>

            <div className="grid grid-cols-2 gap-4">
              <div className="col-span-2">
                <Label htmlFor="address">Address</Label>
                <Input
                  id="address"
                  type="text"
                  value={formData.address}
                  onChange={(e) => handleChange('address', e.target.value)}
                  placeholder="123 Main Street"
                />
              </div>

              <div>
                <Label htmlFor="city">City</Label>
                <Input
                  id="city"
                  type="text"
                  value={formData.city}
                  onChange={(e) => handleChange('city', e.target.value)}
                  placeholder="Boston"
                />
              </div>

              <div>
                <Label htmlFor="state">State</Label>
                <Select
                  id="state"
                  value={formData.state}
                  onValueChange={(value: string) => handleChange('state', value)}
                >
                  {US_STATES.map((state) => (
                    <option key={state.value} value={state.value}>
                      {state.label}
                    </option>
                  ))}
                </Select>
              </div>

              <div>
                <Label htmlFor="zip">ZIP Code</Label>
                <Input
                  id="zip"
                  type="text"
                  value={formData.zip}
                  onChange={(e) => handleChange('zip', e.target.value)}
                  error={errors.zip}
                  placeholder="02134"
                  maxLength={10}
                />
              </div>
            </div>
          </div>

          {/* Professional Information */}
          <div className="space-y-4">
            <h3 className="font-semibold text-gray-900 flex items-center gap-2">
              <Award className="h-4 w-4" />
              Professional Information
            </h3>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label htmlFor="license_number">License Number</Label>
                <Input
                  id="license_number"
                  type="text"
                  value={formData.license_number}
                  onChange={(e) => handleChange('license_number', e.target.value)}
                  placeholder="LIC-123456"
                />
              </div>

              <div>
                <Label htmlFor="license_state">License State</Label>
                <Select
                  id="license_state"
                  value={formData.license_state}
                  onValueChange={(value: string) => handleChange('license_state', value)}
                >
                  <option value="">Select State</option>
                  {US_STATES.map((state) => (
                    <option key={state.value} value={state.value}>
                      {state.label}
                    </option>
                  ))}
                </Select>
              </div>

              <div>
                <Label htmlFor="insurance_expires">Insurance Expiry Date</Label>
                <Input
                  id="insurance_expires"
                  type="date"
                  value={formData.insurance_expires}
                  onChange={(e) => handleChange('insurance_expires', e.target.value)}
                />
              </div>

              <div>
                <Label htmlFor="years_experience">Years of Experience</Label>
                <Input
                  id="years_experience"
                  type="number"
                  value={formData.years_experience || ''}
                  onChange={(e) => handleChange('years_experience', e.target.value)}
                  min="0"
                  max="100"
                />
              </div>
            </div>

            <div>
              <Label htmlFor="certifications">
                Certifications (comma-separated)
              </Label>
              <Input
                id="certifications"
                type="text"
                value={formData.certifications}
                onChange={(e) => handleChange('certifications', e.target.value)}
                placeholder="NACHI Certified, Radon Testing Certified"
              />
            </div>

            <div>
              <Label htmlFor="service_areas">
                Service Areas (comma-separated)
              </Label>
              <Input
                id="service_areas"
                type="text"
                value={formData.service_areas}
                onChange={(e) => handleChange('service_areas', e.target.value)}
                placeholder="Boston, Cambridge, Somerville"
              />
            </div>

            <div>
              <Label htmlFor="languages">
                Languages (comma-separated)
              </Label>
              <Input
                id="languages"
                type="text"
                value={formData.languages}
                onChange={(e) => handleChange('languages', e.target.value)}
                placeholder="English, Spanish"
              />
            </div>
          </div>

          {/* Bio */}
          <div className="space-y-4">
            <h3 className="font-semibold text-gray-900 flex items-center gap-2">
              <FileText className="h-4 w-4" />
              Profile
            </h3>

            <div>
              <Label htmlFor="bio">Bio</Label>
              <Textarea
                id="bio"
                value={formData.bio}
                onChange={(e) => handleChange('bio', e.target.value)}
                placeholder="Brief description of services and experience..."
                rows={4}
              />
            </div>
          </div>

          {/* Settings */}
          <div className="space-y-4">
            <h3 className="font-semibold text-gray-900">Settings</h3>

            <div className="space-y-2">
              <div className="flex items-center gap-2">
                <Checkbox
                  id="is_verified"
                  checked={formData.is_verified}
                  onCheckedChange={(checked) => handleChange('is_verified', checked)}
                />
                <Label htmlFor="is_verified" className="font-normal cursor-pointer">
                  Mark as verified vendor
                </Label>
              </div>

              <div className="flex items-center gap-2">
                <Checkbox
                  id="is_active"
                  checked={formData.is_active}
                  onCheckedChange={(checked) => handleChange('is_active', checked)}
                />
                <Label htmlFor="is_active" className="font-normal cursor-pointer">
                  Active vendor (available for requests)
                </Label>
              </div>
            </div>
          </div>

          {/* Actions */}
          <div className="flex justify-end gap-2 pt-4 border-t">
            <Button
              type="button"
              variant="outline"
              onClick={() => {
                resetForm();
                onClose();
              }}
              disabled={createVendorMutation.isPending}
            >
              Cancel
            </Button>
            <Button
              type="submit"
              disabled={createVendorMutation.isPending}
              className="flex items-center gap-2"
            >
              <Plus className="h-4 w-4" />
              {createVendorMutation.isPending ? 'Adding...' : 'Add Vendor'}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
};