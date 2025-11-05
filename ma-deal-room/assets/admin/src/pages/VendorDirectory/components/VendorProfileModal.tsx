import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  Star,
  MapPin,
  Phone,
  Mail,
  Globe,
  Shield,
  Calendar,
  CheckCircle,
  Award,
  Heart,
  Send,
  FileText,
} from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/shared/Dialog';
import { Button } from '@/components/shared/Button';
import { Badge } from '@/components/shared/Badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/shared/Tabs';
import { Card } from '@/components/shared/Card';
import { Textarea } from '@/components/shared/Textarea';
import { Label } from '@/components/shared/Label';
import { Select } from '@/components/shared/Select';
import { vendorNetworkService } from '@/services/vendorNetworkService';
import { VendorProfile, VENDOR_TYPE_LABELS, CreateReviewPayload } from '@/types/vendor-network';
import { useToast } from '@/hooks/useToast';
import { formatDate } from '@/lib/utils';

interface VendorProfileModalProps {
  vendor: VendorProfile;
  isOpen: boolean;
  onClose: () => void;
  onRefresh: () => void;
}

export const VendorProfileModal = ({
  vendor,
  isOpen,
  onClose,
  onRefresh,
}: VendorProfileModalProps) => {
  const queryClient = useQueryClient();
  const { toast } = useToast();
  const [activeTab, setActiveTab] = useState('overview');
  const [reviewForm, setReviewForm] = useState<Partial<CreateReviewPayload>>({
    rating: 5,
  });

  // Fetch detailed vendor profile
  const { data: detailedVendor } = useQuery({
    queryKey: ['vendor', vendor.id],
    queryFn: () => vendorNetworkService.getVendorProfile(vendor.id),
    enabled: isOpen,
  });

  // Fetch vendor stats
  const { data: vendorStats } = useQuery({
    queryKey: ['vendor-stats', vendor.id],
    queryFn: () => vendorNetworkService.getVendorStats(vendor.id),
    enabled: isOpen,
  });

  // Fetch vendor reviews
  const { data: reviewsData } = useQuery({
    queryKey: ['vendor-reviews', vendor.id],
    queryFn: () => vendorNetworkService.getVendorReviews(vendor.id, { per_page: 10 }),
    enabled: isOpen && activeTab === 'reviews',
  });

  // Toggle preferred status
  const togglePreferredMutation = useMutation({
    mutationFn: async () => {
      if (vendor.is_preferred) {
        await vendorNetworkService.removeFromPreferred(vendor.id);
      } else {
        const primaryCategory = vendor.categories?.[0];
        await vendorNetworkService.addToPreferred(vendor.id, {
          vendor_type: primaryCategory?.vendor_type || 'general',
        });
      }
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vendors'] });
      onRefresh();
      toast({
        title: vendor.is_preferred ? 'Removed from Preferred' : 'Added to Preferred',
        description: vendor.is_preferred
          ? 'Vendor removed from your preferred list'
          : 'Vendor added to your preferred list',
      });
    },
  });

  // Submit review
  const submitReviewMutation = useMutation({
    mutationFn: (data: CreateReviewPayload) =>
      vendorNetworkService.createReview(vendor.id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vendor-reviews', vendor.id] });
      toast({
        title: 'Review Submitted',
        description: 'Your review has been submitted successfully',
      });
      setReviewForm({ rating: 5 });
    },
  });

  const handleSubmitReview = () => {
    if (!reviewForm.rating) {
      toast({
        title: 'Error',
        description: 'Please provide a rating',
        variant: 'error',
      });
      return;
    }
    submitReviewMutation.mutate(reviewForm as CreateReviewPayload);
  };

  const currentVendor = detailedVendor || vendor;
  const primaryCategory = currentVendor.categories?.[0];

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <div className="flex justify-between items-start">
            <div>
              <DialogTitle className="text-xl">
                {currentVendor.display_name || currentVendor.company || currentVendor.name}
              </DialogTitle>
              {primaryCategory && (
                <Badge variant="secondary" className="mt-2">
                  {VENDOR_TYPE_LABELS[primaryCategory.vendor_type as keyof typeof VENDOR_TYPE_LABELS]}
                </Badge>
              )}
            </div>
            <Button
              size="sm"
              variant="ghost"
              onClick={() => togglePreferredMutation.mutate()}
              disabled={togglePreferredMutation.isPending}
            >
              <Heart
                className={`h-5 w-5 ${
                  currentVendor.is_preferred ? 'fill-red-500 text-red-500' : 'text-gray-400'
                }`}
              />
            </Button>
          </div>
        </DialogHeader>

        <Tabs value={activeTab} onValueChange={setActiveTab}>
          <TabsList className="grid w-full grid-cols-4">
            <TabsTrigger value="overview">Overview</TabsTrigger>
            <TabsTrigger value="stats">Statistics</TabsTrigger>
            <TabsTrigger value="reviews">Reviews</TabsTrigger>
            <TabsTrigger value="documents">Documents</TabsTrigger>
          </TabsList>

          <TabsContent value="overview" className="space-y-4">
            {/* Contact Information */}
            <Card className="p-4">
              <h3 className="font-semibold mb-3">Contact Information</h3>
              <div className="space-y-2">
                {currentVendor.email && (
                  <div className="flex items-center gap-2">
                    <Mail className="h-4 w-4 text-gray-400" />
                    <a
                      href={`mailto:${currentVendor.email}`}
                      className="text-blue-600 hover:underline"
                    >
                      {currentVendor.email}
                    </a>
                  </div>
                )}
                {currentVendor.phone && (
                  <div className="flex items-center gap-2">
                    <Phone className="h-4 w-4 text-gray-400" />
                    <a
                      href={`tel:${currentVendor.phone}`}
                      className="text-blue-600 hover:underline"
                    >
                      {currentVendor.phone}
                    </a>
                  </div>
                )}
                {currentVendor.website && (
                  <div className="flex items-center gap-2">
                    <Globe className="h-4 w-4 text-gray-400" />
                    <a
                      href={currentVendor.website}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-blue-600 hover:underline"
                    >
                      {currentVendor.website}
                    </a>
                  </div>
                )}
                {currentVendor.location && (
                  <div className="flex items-center gap-2">
                    <MapPin className="h-4 w-4 text-gray-400" />
                    <span>{currentVendor.location}</span>
                  </div>
                )}
              </div>
            </Card>

            {/* Credentials */}
            <Card className="p-4">
              <h3 className="font-semibold mb-3">Credentials & Experience</h3>
              <div className="grid grid-cols-2 gap-4">
                {currentVendor.is_verified && (
                  <div className="flex items-center gap-2">
                    <Shield className="h-4 w-4 text-green-600" />
                    <span className="text-sm">
                      Verified {currentVendor.verified_at && `on ${formatDate(currentVendor.verified_at)}`}
                    </span>
                  </div>
                )}
                {currentVendor.has_license && (
                  <div className="flex items-center gap-2">
                    <Award className="h-4 w-4 text-blue-600" />
                    <span className="text-sm">
                      License: {currentVendor.license_number} ({currentVendor.license_state})
                    </span>
                  </div>
                )}
                {currentVendor.has_insurance && (
                  <div className="flex items-center gap-2">
                    <Shield className="h-4 w-4 text-purple-600" />
                    <span className="text-sm">
                      Insurance expires: {formatDate(currentVendor.insurance_expires!)}
                    </span>
                  </div>
                )}
                {currentVendor.years_experience && (
                  <div className="flex items-center gap-2">
                    <Calendar className="h-4 w-4 text-gray-600" />
                    <span className="text-sm">{currentVendor.years_experience} years experience</span>
                  </div>
                )}
              </div>
            </Card>

            {/* Bio */}
            {currentVendor.bio && (
              <Card className="p-4">
                <h3 className="font-semibold mb-3">About</h3>
                <p className="text-sm text-gray-600">{currentVendor.bio}</p>
              </Card>
            )}

            {/* Service Areas */}
            {currentVendor.service_areas && currentVendor.service_areas.length > 0 && (
              <Card className="p-4">
                <h3 className="font-semibold mb-3">Service Areas</h3>
                <div className="flex flex-wrap gap-2">
                  {currentVendor.service_areas.map((area, index) => (
                    <Badge key={index} variant="secondary">
                      {area}
                    </Badge>
                  ))}
                </div>
              </Card>
            )}
          </TabsContent>

          <TabsContent value="stats" className="space-y-4">
            {vendorStats ? (
              <>
                {/* Performance Metrics */}
                <Card className="p-4">
                  <h3 className="font-semibold mb-4">Performance Metrics</h3>
                  <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="text-center">
                      <div className="text-2xl font-bold text-blue-600">
                        {vendorStats.total_requests}
                      </div>
                      <div className="text-sm text-gray-600">Total Requests</div>
                    </div>
                    <div className="text-center">
                      <div className="text-2xl font-bold text-green-600">
                        {vendorStats.completed_requests}
                      </div>
                      <div className="text-sm text-gray-600">Completed</div>
                    </div>
                    <div className="text-center">
                      <div className="text-2xl font-bold text-purple-600">
                        {vendorStats.completion_rate}%
                      </div>
                      <div className="text-sm text-gray-600">Completion Rate</div>
                    </div>
                    <div className="text-center">
                      <div className="text-2xl font-bold text-orange-600">
                        {vendorStats.avg_response_hours}h
                      </div>
                      <div className="text-sm text-gray-600">Avg Response</div>
                    </div>
                  </div>
                </Card>

                {/* Review Breakdown */}
                {vendorStats.review_breakdown && (
                  <Card className="p-4">
                    <h3 className="font-semibold mb-4">Review Breakdown</h3>
                    <div className="space-y-3">
                      {Object.entries(vendorStats.review_breakdown).map(([key, value]) => {
                        if (key === 'recommendation_rate') {
                          return (
                            <div key={key} className="flex justify-between items-center">
                              <span className="text-sm capitalize">Would Recommend</span>
                              <div className="flex items-center gap-2">
                                <div className="w-32 bg-gray-200 rounded-full h-2">
                                  <div
                                    className="bg-green-600 h-2 rounded-full"
                                    style={{ width: `${value}%` }}
                                  />
                                </div>
                                <span className="text-sm font-medium">{value}%</span>
                              </div>
                            </div>
                          );
                        }
                        return (
                          <div key={key} className="flex justify-between items-center">
                            <span className="text-sm capitalize">{key.replace('_', ' ')}</span>
                            <div className="flex items-center gap-2">
                              {Array.from({ length: 5 }).map((_, i) => (
                                <Star
                                  key={i}
                                  className={`h-3 w-3 ${
                                    i < Math.floor(value)
                                      ? 'text-yellow-400 fill-current'
                                      : 'text-gray-300'
                                  }`}
                                />
                              ))}
                              <span className="text-sm font-medium">{value}</span>
                            </div>
                          </div>
                        );
                      })}
                    </div>
                  </Card>
                )}
              </>
            ) : (
              <div className="text-center py-8 text-gray-500">Loading statistics...</div>
            )}
          </TabsContent>

          <TabsContent value="reviews" className="space-y-4">
            {/* Write Review */}
            <Card className="p-4">
              <h3 className="font-semibold mb-3">Write a Review</h3>
              <div className="space-y-3">
                <div>
                  <Label>Rating</Label>
                  <div className="flex gap-1 mt-1">
                    {Array.from({ length: 5 }).map((_, i) => (
                      <Star
                        key={i}
                        className={`h-6 w-6 cursor-pointer ${
                          i < (reviewForm.rating || 0)
                            ? 'text-yellow-400 fill-current'
                            : 'text-gray-300'
                        }`}
                        onClick={() => setReviewForm({ ...reviewForm, rating: i + 1 })}
                      />
                    ))}
                  </div>
                </div>
                <div>
                  <Label>Service Type</Label>
                  <Select
                    value={reviewForm.service_type || ''}
                    onValueChange={(value: string) =>
                      setReviewForm({ ...reviewForm, service_type: value })
                    }
                  >
                    <option value="">Select service type</option>
                    {Object.entries(VENDOR_TYPE_LABELS).map(([value, label]) => (
                      <option key={value} value={value}>
                        {label}
                      </option>
                    ))}
                  </Select>
                </div>
                <div>
                  <Label>Review</Label>
                  <Textarea
                    placeholder="Share your experience with this vendor..."
                    value={reviewForm.review_text || ''}
                    onChange={(e) =>
                      setReviewForm({ ...reviewForm, review_text: e.target.value })
                    }
                    rows={4}
                  />
                </div>
                <Button
                  onClick={handleSubmitReview}
                  disabled={submitReviewMutation.isPending}
                  className="w-full"
                >
                  Submit Review
                </Button>
              </div>
            </Card>

            {/* Reviews List */}
            {reviewsData?.reviews && reviewsData.reviews.length > 0 ? (
              reviewsData.reviews.map((review) => (
                <Card key={review.id} className="p-4">
                  <div className="flex justify-between items-start mb-2">
                    <div>
                      <div className="font-medium">{review.reviewer_name}</div>
                      <div className="flex items-center gap-1 mt-1">
                        {Array.from({ length: 5 }).map((_, i) => (
                          <Star
                            key={i}
                            className={`h-4 w-4 ${
                              i < review.rating
                                ? 'text-yellow-400 fill-current'
                                : 'text-gray-300'
                            }`}
                          />
                        ))}
                      </div>
                    </div>
                    <div className="text-sm text-gray-500">{formatDate(review.created_at)}</div>
                  </div>
                  {review.review_text && (
                    <p className="text-sm text-gray-600 mb-2">{review.review_text}</p>
                  )}
                  {review.is_verified_transaction && (
                    <Badge variant="success" className="text-xs">
                      <CheckCircle className="h-3 w-3 mr-1" />
                      Verified Transaction
                    </Badge>
                  )}
                </Card>
              ))
            ) : (
              <Card className="p-8 text-center text-gray-500">
                No reviews yet. Be the first to review this vendor!
              </Card>
            )}
          </TabsContent>

          <TabsContent value="documents" className="space-y-4">
            <Card className="p-8 text-center text-gray-500">
              <FileText className="h-12 w-12 mx-auto text-gray-400 mb-3" />
              <p>No documents available</p>
              <p className="text-sm mt-1">
                Documents such as licenses and insurance will appear here
              </p>
            </Card>
          </TabsContent>
        </Tabs>

        <div className="flex justify-end gap-2 mt-4">
          <Button variant="outline" onClick={onClose}>
            Close
          </Button>
          <Button className="flex items-center gap-2">
            <Send className="h-4 w-4" />
            Quick Invite
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
};