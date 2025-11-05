import { Star, MapPin, Shield, Heart, Clock, CheckCircle } from 'lucide-react';
import { Card } from '@/components/shared/Card';
import { Button } from '@/components/shared/Button';
import { Badge } from '@/components/shared/Badge';
import { VendorProfile, VENDOR_TYPE_LABELS } from '@/types/vendor-network';

interface VendorCardProps {
  vendor: VendorProfile;
  onClick: () => void;
  onTogglePreferred: () => void;
}

export const VendorCard = ({ vendor, onClick, onTogglePreferred }: VendorCardProps) => {
  const primaryCategory = vendor.categories?.[0];
  const vendorTypeLabel = primaryCategory
    ? VENDOR_TYPE_LABELS[primaryCategory.vendor_type as keyof typeof VENDOR_TYPE_LABELS]
    : 'Vendor';

  return (
    <Card
      className="p-4 hover:shadow-lg transition-shadow cursor-pointer"
      onClick={onClick}
    >
      <div className="flex justify-between items-start mb-3">
        <div className="flex-1">
          {vendor.is_verified && (
            <Badge variant="success" className="mb-2">
              <Shield className="h-3 w-3 mr-1" />
              Verified
            </Badge>
          )}
          <h3 className="font-semibold text-lg text-gray-900">
            {vendor.display_name || vendor.company || vendor.name}
          </h3>
          <p className="text-sm text-gray-600">{vendorTypeLabel}</p>
        </div>
        <Button
          size="sm"
          variant="ghost"
          onClick={(e: React.MouseEvent) => {
            e.stopPropagation();
            onTogglePreferred();
          }}
          className="p-1"
        >
          <Heart
            className={`h-5 w-5 ${
              vendor.is_preferred ? 'fill-red-500 text-red-500' : 'text-gray-400'
            }`}
          />
        </Button>
      </div>

      {/* Rating */}
      <div className="flex items-center gap-2 mb-3">
        <div className="flex items-center">
          {Array.from({ length: 5 }).map((_, i) => (
            <Star
              key={i}
              className={`h-4 w-4 ${
                i < Math.floor(vendor.average_rating)
                  ? 'text-yellow-400 fill-current'
                  : 'text-gray-300'
              }`}
            />
          ))}
        </div>
        <span className="text-sm font-medium">{vendor.average_rating.toFixed(1)}</span>
        <span className="text-sm text-gray-500">({vendor.total_reviews} reviews)</span>
      </div>

      {/* Location */}
      {vendor.location && (
        <div className="flex items-center gap-1 text-sm text-gray-600 mb-3">
          <MapPin className="h-3 w-3" />
          {vendor.location}
        </div>
      )}

      {/* Stats */}
      <div className="grid grid-cols-2 gap-2 mb-3">
        <div className="flex items-center gap-1 text-sm text-gray-600">
          <CheckCircle className="h-3 w-3 text-green-600" />
          <span>{vendor.total_completed} completed</span>
        </div>
        {vendor.response_time_hours && (
          <div className="flex items-center gap-1 text-sm text-gray-600">
            <Clock className="h-3 w-3 text-blue-600" />
            <span>{vendor.response_time_hours}h response</span>
          </div>
        )}
      </div>

      {/* Specialties */}
      {primaryCategory?.specialties && primaryCategory.specialties.length > 0 && (
        <div className="flex flex-wrap gap-1">
          {primaryCategory.specialties.slice(0, 3).map((specialty, index) => (
            <Badge key={index} variant="secondary" className="text-xs">
              {specialty}
            </Badge>
          ))}
          {primaryCategory.specialties.length > 3 && (
            <Badge variant="secondary" className="text-xs">
              +{primaryCategory.specialties.length - 3}
            </Badge>
          )}
        </div>
      )}

      {/* Quick Actions */}
      <div className="mt-4 pt-3 border-t flex gap-2">
        <Button
          size="sm"
          variant="outline"
          className="flex-1"
          onClick={(e: React.MouseEvent) => {
            e.stopPropagation();
            onClick();
          }}
        >
          View Profile
        </Button>
        <Button
          size="sm"
          className="flex-1"
          onClick={(e: React.MouseEvent) => {
            e.stopPropagation();
            // Handle quick invite
          }}
        >
          Quick Invite
        </Button>
      </div>
    </Card>
  );
};