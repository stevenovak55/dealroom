import { useQuery } from '@tanstack/react-query';
import { Heart, Star, Clock, Send, ChevronRight } from 'lucide-react';
import { Card } from '@/components/shared/Card';
import { Button } from '@/components/shared/Button';
import { Badge } from '@/components/shared/Badge';
import { ScrollArea, ScrollBar } from '@/components/shared/ScrollArea';
import { vendorNetworkService } from '@/services/vendorNetworkService';
import { VendorProfile, VENDOR_TYPE_LABELS } from '@/types/vendor-network';

interface PreferredVendorsSectionProps {
  onSelectVendor: (vendor: VendorProfile) => void;
}

export const PreferredVendorsSection = ({
  onSelectVendor,
}: PreferredVendorsSectionProps) => {
  // Fetch preferred vendors
  const { data: preferredVendors, isLoading } = useQuery({
    queryKey: ['preferred-vendors'],
    queryFn: () => vendorNetworkService.getPreferredVendors(),
  });

  if (isLoading) {
    return (
      <Card className="p-6">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-2">
            <Heart className="h-5 w-5 text-red-500" />
            <h2 className="text-lg font-semibold">Preferred Vendors</h2>
          </div>
        </div>
        <div className="flex justify-center py-8">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600" />
        </div>
      </Card>
    );
  }

  if (!preferredVendors || preferredVendors.length === 0) {
    return (
      <Card className="p-6">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-2">
            <Heart className="h-5 w-5 text-red-500" />
            <h2 className="text-lg font-semibold">Preferred Vendors</h2>
          </div>
        </div>
        <div className="text-center py-8">
          <Heart className="h-12 w-12 text-gray-300 mx-auto mb-3" />
          <p className="text-gray-500">No preferred vendors yet</p>
          <p className="text-sm text-gray-400 mt-1">
            Add vendors to your preferred list for quick access
          </p>
        </div>
      </Card>
    );
  }

  // Group vendors by type
  const vendorsByType = preferredVendors.reduce((acc, vendor) => {
    const type = vendor.categories?.[0]?.vendor_type || 'general';
    if (!acc[type]) {
      acc[type] = [];
    }
    acc[type].push(vendor);
    return acc;
  }, {} as Record<string, typeof preferredVendors>);

  return (
    <Card className="p-6">
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center gap-2">
          <Heart className="h-5 w-5 text-red-500 fill-current" />
          <h2 className="text-lg font-semibold">Preferred Vendors</h2>
          <Badge variant="secondary">{preferredVendors.length}</Badge>
        </div>
        <Button variant="ghost" size="sm" className="text-blue-600">
          View All
          <ChevronRight className="h-4 w-4 ml-1" />
        </Button>
      </div>

      <ScrollArea className="w-full">
        <div className="flex gap-4 pb-4">
          {preferredVendors.map((vendor) => (
            <Card
              key={vendor.id}
              className="min-w-[280px] p-4 hover:shadow-md transition-shadow cursor-pointer"
              onClick={() => onSelectVendor(vendor)}
            >
              <div className="flex justify-between items-start mb-3">
                <div className="flex-1">
                  <h3 className="font-medium text-sm line-clamp-1">
                    {vendor.display_name || vendor.company || vendor.name}
                  </h3>
                  <p className="text-xs text-gray-600 mt-1">
                    {vendor.categories?.[0]
                      ? VENDOR_TYPE_LABELS[
                          vendor.categories[0].vendor_type as keyof typeof VENDOR_TYPE_LABELS
                        ]
                      : 'Vendor'}
                  </p>
                </div>
                {vendor.preference_order && vendor.preference_order <= 3 && (
                  <Badge variant="default" className="text-xs">
                    #{vendor.preference_order}
                  </Badge>
                )}
              </div>

              {/* Rating */}
              <div className="flex items-center gap-1 mb-3">
                {Array.from({ length: 5 }).map((_, i) => (
                  <Star
                    key={i}
                    className={`h-3 w-3 ${
                      i < Math.floor(vendor.average_rating)
                        ? 'text-yellow-400 fill-current'
                        : 'text-gray-300'
                    }`}
                  />
                ))}
                <span className="text-xs ml-1">{vendor.average_rating.toFixed(1)}</span>
                <span className="text-xs text-gray-500">({vendor.total_reviews})</span>
              </div>

              {/* Stats */}
              <div className="flex items-center justify-between text-xs text-gray-600 mb-3">
                <div className="flex items-center gap-1">
                  <Clock className="h-3 w-3" />
                  {vendor.times_used} uses
                </div>
                {vendor.last_used_at && (
                  <span>
                    Last: {new Date(vendor.last_used_at).toLocaleDateString()}
                  </span>
                )}
              </div>

              {/* Tags */}
              {vendor.tags && vendor.tags.length > 0 && (
                <div className="flex flex-wrap gap-1 mb-3">
                  {vendor.tags.slice(0, 2).map((tag, index) => (
                    <Badge key={index} variant="outline" className="text-xs">
                      {tag}
                    </Badge>
                  ))}
                </div>
              )}

              {/* Quick Actions */}
              <div className="flex gap-2">
                <Button
                  size="sm"
                  variant="outline"
                  className="flex-1 text-xs"
                  onClick={(e: React.MouseEvent) => {
                    e.stopPropagation();
                    onSelectVendor(vendor);
                  }}
                >
                  View
                </Button>
                <Button
                  size="sm"
                  className="flex-1 text-xs"
                  onClick={(e: React.MouseEvent) => {
                    e.stopPropagation();
                    // Handle quick invite
                  }}
                >
                  <Send className="h-3 w-3 mr-1" />
                  Invite
                </Button>
              </div>
            </Card>
          ))}
        </div>
        <ScrollBar orientation="horizontal" />
      </ScrollArea>

      {/* Vendor Type Summary */}
      <div className="mt-4 pt-4 border-t">
        <p className="text-xs text-gray-600 mb-2">By Category:</p>
        <div className="flex flex-wrap gap-2">
          {Object.entries(vendorsByType).map(([type, vendors]) => (
            <Badge key={type} variant="outline" className="text-xs">
              {VENDOR_TYPE_LABELS[type as keyof typeof VENDOR_TYPE_LABELS] || type}
              <span className="ml-1 text-gray-500">({vendors.length})</span>
            </Badge>
          ))}
        </div>
      </div>
    </Card>
  );
};