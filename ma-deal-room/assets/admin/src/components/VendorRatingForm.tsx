/**
 * Vendor Rating Form Component
 *
 * Allows agents to rate vendors after work completion.
 *
 * @package MADealRoom
 * @since 2.0.0
 */

import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useForm } from 'react-hook-form';
import toast from 'react-hot-toast';
import { Star, CheckCircle } from 'lucide-react';
import { vendorRequestService, VendorRatingPayload } from '../api/vendorRequestService';
import { Modal, ModalFooter } from './shared/Modal';
import { Button } from './shared/Button';

interface VendorRatingFormProps {
  isOpen: boolean;
  onClose: () => void;
  vendorRequestId: number;
  onSuccess?: () => void;
}

interface FormData {
  rating: number;
  timeliness_rating: number;
  quality_rating: number;
  communication_rating: number;
  review: string;
  would_recommend: boolean;
}

const StarRating: React.FC<{
  value: number;
  onChange: (value: number) => void;
  label: string;
}> = ({ value, onChange, label }) => {
  const [hover, setHover] = useState(0);

  return (
    <div>
      <label className="block text-sm font-medium text-gray-700 mb-2">{label}</label>
      <div className="flex gap-1">
        {[1, 2, 3, 4, 5].map((star) => (
          <button
            key={star}
            type="button"
            onClick={() => onChange(star)}
            onMouseEnter={() => setHover(star)}
            onMouseLeave={() => setHover(0)}
            className="focus:outline-none"
          >
            <Star
              className={`w-8 h-8 transition-colors ${
                (hover || value) >= star
                  ? 'text-yellow-500 fill-yellow-500'
                  : 'text-gray-300'
              }`}
            />
          </button>
        ))}
        <span className="ml-2 text-sm text-gray-600">
          {value > 0 ? `${value} star${value > 1 ? 's' : ''}` : 'Not rated'}
        </span>
      </div>
    </div>
  );
};

export const VendorRatingForm: React.FC<VendorRatingFormProps> = ({
  isOpen,
  onClose,
  vendorRequestId,
  onSuccess,
}) => {
  const queryClient = useQueryClient();
  const [showSuccess, setShowSuccess] = useState(false);

  const {
    register,
    handleSubmit,
    setValue,
    watch,
    reset,
  } = useForm<FormData>({
    defaultValues: {
      rating: 0,
      timeliness_rating: 0,
      quality_rating: 0,
      communication_rating: 0,
      review: '',
      would_recommend: true,
    },
  });

  const ratingValue = watch('rating');
  const timelinessValue = watch('timeliness_rating');
  const qualityValue = watch('quality_rating');
  const communicationValue = watch('communication_rating');

  const rateMutation = useMutation({
    mutationFn: (data: VendorRatingPayload) => vendorRequestService.rate(vendorRequestId, data),
    onSuccess: () => {
      setShowSuccess(true);
      toast.success('Rating submitted successfully!');
      queryClient.invalidateQueries({ queryKey: ['vendor-requests'] });
      if (onSuccess) {
        onSuccess();
      }
      setTimeout(() => {
        handleClose();
      }, 2000);
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to submit rating');
    },
  });

  const onSubmit = (data: FormData) => {
    if (data.rating === 0) {
      toast.error('Please provide an overall rating');
      return;
    }

    const payload: VendorRatingPayload = {
      rating: data.rating,
      timeliness_rating: data.timeliness_rating > 0 ? data.timeliness_rating : undefined,
      quality_rating: data.quality_rating > 0 ? data.quality_rating : undefined,
      communication_rating: data.communication_rating > 0 ? data.communication_rating : undefined,
      review: data.review || undefined,
      would_recommend: data.would_recommend,
    };

    rateMutation.mutate(payload);
  };

  const handleClose = () => {
    reset();
    setShowSuccess(false);
    onClose();
  };

  if (!isOpen) return null;

  // Success State
  if (showSuccess) {
    return (
      <Modal isOpen={isOpen} onClose={handleClose} title="Rating Submitted!" size="md">
        <div className="text-center py-8">
          <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <CheckCircle className="w-10 h-10 text-green-600" />
          </div>
          <h3 className="text-lg font-semibold text-gray-900 mb-2">
            Thank you for your feedback!
          </h3>
          <p className="text-gray-600">
            Your rating will help us maintain quality standards and help others choose the right vendors.
          </p>
        </div>
      </Modal>
    );
  }

  // Form State
  return (
    <Modal isOpen={isOpen} onClose={handleClose} title="Rate Vendor Performance" size="lg">
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
        {/* Overall Rating */}
        <div>
          <StarRating
            value={ratingValue}
            onChange={(value) => setValue('rating', value)}
            label="Overall Rating *"
          />
          {ratingValue === 0 && (
            <p className="text-xs text-gray-500 mt-1">
              Required: How would you rate this vendor overall?
            </p>
          )}
        </div>

        {/* Detailed Ratings */}
        <div className="space-y-4 pt-4 border-t border-gray-200">
          <h4 className="text-sm font-medium text-gray-700">Detailed Ratings (Optional)</h4>

          <StarRating
            value={timelinessValue}
            onChange={(value) => setValue('timeliness_rating', value)}
            label="Timeliness"
          />

          <StarRating
            value={qualityValue}
            onChange={(value) => setValue('quality_rating', value)}
            label="Quality of Work"
          />

          <StarRating
            value={communicationValue}
            onChange={(value) => setValue('communication_rating', value)}
            label="Communication"
          />
        </div>

        {/* Review */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Review (Optional)
          </label>
          <textarea
            {...register('review')}
            rows={4}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Share your experience with this vendor..."
          />
          <p className="text-xs text-gray-500 mt-1">
            Your review will be visible to others considering this vendor
          </p>
        </div>

        {/* Would Recommend */}
        <div className="flex items-center gap-2">
          <input
            type="checkbox"
            {...register('would_recommend')}
            id="would_recommend"
            className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
          />
          <label htmlFor="would_recommend" className="text-sm text-gray-700">
            I would recommend this vendor to others
          </label>
        </div>

        {/* Info Box */}
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-3">
          <p className="text-xs text-blue-800">
            Your ratings help build a vendor quality database and assist other agents in choosing reliable vendors.
          </p>
        </div>

        <ModalFooter>
          <Button type="button" variant="secondary" onClick={handleClose}>
            Cancel
          </Button>
          <Button
            type="submit"
            variant="primary"
            disabled={rateMutation.isPending || ratingValue === 0}
          >
            <Star className="w-4 h-4 mr-2" />
            {rateMutation.isPending ? 'Submitting...' : 'Submit Rating'}
          </Button>
        </ModalFooter>
      </form>
    </Modal>
  );
};

export default VendorRatingForm;
