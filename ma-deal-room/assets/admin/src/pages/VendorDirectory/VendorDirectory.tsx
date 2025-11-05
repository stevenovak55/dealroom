import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
  Search,
  Filter,
  Star,
  MapPin,
  Award,
  Users,
  Download,
  Plus
} from 'lucide-react';
import { Card } from '@/components/shared/Card';
import { Button } from '@/components/shared/Button';
import { Input } from '@/components/shared/Input';
import { Select } from '@/components/shared/Select';
import { Badge } from '@/components/shared/Badge';
import { vendorNetworkService } from '@/services/vendorNetworkService';
import { VendorCard } from './components/VendorCard';
import { VendorSearchFilters } from './components/VendorSearchFilters';
import { VendorProfileModal } from './components/VendorProfileModal';
import { PreferredVendorsSection } from './components/PreferredVendorsSection';
import { AddVendorModal } from './components/AddVendorModal';
import {
  VendorProfile,
  VendorSearchFilters as VendorSearchFiltersType,
  VendorSearchOptions
} from '@/types/vendor-network';
import { useAuthStore } from '@/store/useAuthStore';
import { useToast } from '@/hooks/useToast';

export const VendorDirectory = () => {
  const { user } = useAuthStore();
  const { toast } = useToast();
  const [searchQuery, setSearchQuery] = useState('');
  const [filters, setFilters] = useState<VendorSearchFiltersType>({});
  const [searchOptions, setSearchOptions] = useState<VendorSearchOptions>({
    order: 'rating',
    limit: 20,
    page: 1,
  });
  const [selectedVendor, setSelectedVendor] = useState<VendorProfile | null>(null);
  const [showFilters, setShowFilters] = useState(false);
  const [showAddVendor, setShowAddVendor] = useState(false);

  // Fetch vendors
  const {
    data: vendorsData,
    isLoading,
    error,
    refetch,
  } = useQuery({
    queryKey: ['vendors', { ...filters, search: searchQuery }, searchOptions],
    queryFn: () =>
      vendorNetworkService.searchVendors(
        { ...filters, search: searchQuery },
        searchOptions
      ),
  });

  // Import vendors (admin only)
  const handleImportVendors = async () => {
    try {
      const result = await vendorNetworkService.importVendors();
      toast({
        title: 'Import Successful',
        description: `Imported ${result.imported} vendors, skipped ${result.skipped} duplicates`,
      });
      refetch();
    } catch (error) {
      toast({
        title: 'Import Failed',
        description: 'Failed to import vendors from existing requests',
        variant: 'error',
      });
    }
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    setSearchOptions((prev) => ({ ...prev, page: 1 }));
  };

  const handleFilterChange = (newFilters: VendorSearchFiltersType) => {
    setFilters(newFilters);
    setSearchOptions((prev) => ({ ...prev, page: 1 }));
  };

  const handleSortChange = (order: VendorSearchOptions['order']) => {
    setSearchOptions((prev) => ({ ...prev, order, page: 1 }));
  };

  const vendors = vendorsData?.vendors || [];
  const totalVendors = vendorsData?.total || 0;

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Vendor Network</h1>
          <p className="text-gray-600 mt-1">
            Browse and manage your network of trusted vendors
          </p>
        </div>
        <div className="flex gap-2">
          <Button
            onClick={() => setShowAddVendor(true)}
            className="flex items-center gap-2"
          >
            <Plus className="h-4 w-4" />
            Add Vendor
          </Button>
          {user?.roles?.includes('administrator') && (
            <Button
              variant="outline"
              onClick={handleImportVendors}
              className="flex items-center gap-2"
            >
              <Download className="h-4 w-4" />
              Import Vendors
            </Button>
          )}
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-4">
          <div className="flex items-center gap-3">
            <Users className="h-8 w-8 text-blue-600" />
            <div>
              <p className="text-sm text-gray-600">Total Vendors</p>
              <p className="text-2xl font-bold">{totalVendors}</p>
            </div>
          </div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-3">
            <Star className="h-8 w-8 text-yellow-500" />
            <div>
              <p className="text-sm text-gray-600">Avg Rating</p>
              <p className="text-2xl font-bold">4.7</p>
            </div>
          </div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-3">
            <Award className="h-8 w-8 text-green-600" />
            <div>
              <p className="text-sm text-gray-600">Verified</p>
              <p className="text-2xl font-bold">
                {vendors.filter((v) => v.is_verified).length}
              </p>
            </div>
          </div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-3">
            <MapPin className="h-8 w-8 text-purple-600" />
            <div>
              <p className="text-sm text-gray-600">Service Areas</p>
              <p className="text-2xl font-bold">12</p>
            </div>
          </div>
        </Card>
      </div>

      {/* Preferred Vendors Section */}
      <PreferredVendorsSection onSelectVendor={setSelectedVendor} />

      {/* Search Bar */}
      <Card className="p-4">
        <form onSubmit={handleSearch} className="flex gap-2">
          <div className="flex-1 relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
            <Input
              type="text"
              placeholder="Search vendors by name, company, or service..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="pl-10"
            />
          </div>
          <Button
            type="button"
            variant="outline"
            onClick={() => setShowFilters(!showFilters)}
            className="flex items-center gap-2"
          >
            <Filter className="h-4 w-4" />
            Filters
            {Object.keys(filters).length > 0 && (
              <Badge variant="secondary" className="ml-1">
                {Object.keys(filters).length}
              </Badge>
            )}
          </Button>
          <Select
            value={searchOptions.order}
            onValueChange={(value: string) => handleSortChange(value as VendorSearchOptions['order'])}
          >
            <option value="rating">Top Rated</option>
            <option value="reviews">Most Reviews</option>
            <option value="name">Name A-Z</option>
            <option value="recent">Recently Added</option>
          </Select>
          <Button type="submit">Search</Button>
        </form>

        {/* Filters Panel */}
        {showFilters && (
          <div className="mt-4 pt-4 border-t">
            <VendorSearchFilters
              filters={filters}
              onChange={handleFilterChange}
              onClose={() => setShowFilters(false)}
            />
          </div>
        )}
      </Card>

      {/* Results */}
      <div>
        <div className="flex justify-between items-center mb-4">
          <p className="text-sm text-gray-600">
            Showing {vendors.length} of {totalVendors} vendors
          </p>
        </div>

        {isLoading ? (
          <div className="flex justify-center py-12">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600" />
          </div>
        ) : error ? (
          <Card className="p-8 text-center">
            <p className="text-red-600">Failed to load vendors</p>
            <Button onClick={() => refetch()} className="mt-4">
              Retry
            </Button>
          </Card>
        ) : vendors.length === 0 ? (
          <Card className="p-8 text-center">
            <p className="text-gray-500">No vendors found matching your criteria</p>
          </Card>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {vendors.map((vendor) => (
              <VendorCard
                key={vendor.id}
                vendor={vendor}
                onClick={() => setSelectedVendor(vendor)}
                onTogglePreferred={async () => {
                  if (vendor.is_preferred) {
                    await vendorNetworkService.removeFromPreferred(vendor.id);
                  } else {
                    await vendorNetworkService.addToPreferred(vendor.id, {
                      vendor_type: vendor.categories?.[0]?.vendor_type || 'general',
                    });
                  }
                  refetch();
                }}
              />
            ))}
          </div>
        )}

        {/* Pagination */}
        {totalVendors > (searchOptions.limit || 20) && (
          <div className="mt-6 flex justify-center gap-2">
            <Button
              variant="outline"
              disabled={searchOptions.page === 1}
              onClick={() =>
                setSearchOptions((prev) => ({
                  ...prev,
                  page: Math.max(1, (prev.page || 1) - 1),
                }))
              }
            >
              Previous
            </Button>
            <span className="flex items-center px-4">
              Page {searchOptions.page} of{' '}
              {Math.ceil(totalVendors / (searchOptions.limit || 20))}
            </span>
            <Button
              variant="outline"
              disabled={
                searchOptions.page ===
                Math.ceil(totalVendors / (searchOptions.limit || 20))
              }
              onClick={() =>
                setSearchOptions((prev) => ({
                  ...prev,
                  page: (prev.page || 1) + 1,
                }))
              }
            >
              Next
            </Button>
          </div>
        )}
      </div>

      {/* Vendor Profile Modal */}
      {selectedVendor && (
        <VendorProfileModal
          vendor={selectedVendor}
          isOpen={!!selectedVendor}
          onClose={() => setSelectedVendor(null)}
          onRefresh={refetch}
        />
      )}

      {/* Add Vendor Modal */}
      <AddVendorModal
        isOpen={showAddVendor}
        onClose={() => setShowAddVendor(false)}
        onSuccess={() => {
          refetch();
          toast({
            title: 'Success',
            description: 'Vendor has been added to the network',
          });
        }}
      />
    </div>
  );
};