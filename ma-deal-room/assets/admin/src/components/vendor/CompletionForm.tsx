import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { format } from 'date-fns';
import { CheckCircle, Calendar, FileText, AlertCircle } from 'lucide-react';
import { VendorRequest, CompletionData } from '../../api/vendorPortalClient';

interface CompletionFormProps {
  vendorRequest: VendorRequest;
  onComplete: (data: CompletionData) => void;
  isSubmitting: boolean;
}

interface FormData {
  completion_date: string;
  completion_notes: string;
}

export default function CompletionForm({
  vendorRequest,
  onComplete,
  isSubmitting,
}: CompletionFormProps) {
  const [showConfirmation, setShowConfirmation] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
    watch,
  } = useForm<FormData>({
    defaultValues: {
      completion_date: format(new Date(), 'yyyy-MM-dd'),
      completion_notes: '',
    },
  });

  const completionDate = watch('completion_date');
  const completionNotes = watch('completion_notes');

  const onSubmit = (data: FormData) => {
    if (!showConfirmation) {
      setShowConfirmation(true);
      return;
    }

    onComplete({
      completion_date: data.completion_date,
      completion_notes: data.completion_notes.trim() || undefined,
    });
  };

  const handleCancel = () => {
    setShowConfirmation(false);
  };

  return (
    <div className="bg-white rounded-lg shadow-sm overflow-hidden">
      <div className="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-4 text-white">
        <h2 className="text-xl font-bold">Complete Request</h2>
        <p className="text-green-100 text-sm mt-1">
          Mark this request as completed
        </p>
      </div>

      <div className="p-6">
        {/* Document Check */}
        {!vendorRequest.document_url && (
          <div className="mb-6 flex items-start gap-3 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <AlertCircle className="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" />
            <div>
              <p className="font-medium text-yellow-900">Upload Required</p>
              <p className="text-sm text-yellow-700 mt-1">
                Please upload a completion document before marking this request as complete.
              </p>
            </div>
          </div>
        )}

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
          {/* Completion Date */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              <div className="flex items-center gap-2">
                <Calendar className="w-4 h-4" />
                Completion Date
              </div>
            </label>
            <input
              type="date"
              {...register('completion_date', {
                required: 'Completion date is required',
              })}
              max={format(new Date(), 'yyyy-MM-dd')}
              className={`
                w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent
                ${errors.completion_date ? 'border-red-300' : 'border-gray-300'}
              `}
              disabled={isSubmitting}
            />
            {errors.completion_date && (
              <p className="mt-1 text-sm text-red-600">{errors.completion_date.message}</p>
            )}
            <p className="mt-1 text-xs text-gray-500">
              The date when the work was completed
            </p>
          </div>

          {/* Completion Notes */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              <div className="flex items-center gap-2">
                <FileText className="w-4 h-4" />
                Completion Notes (Optional)
              </div>
            </label>
            <textarea
              {...register('completion_notes', {
                maxLength: {
                  value: 1000,
                  message: 'Notes cannot exceed 1000 characters',
                },
              })}
              rows={4}
              maxLength={1000}
              placeholder="Add any notes about the completion (e.g., findings, recommendations, etc.)"
              className={`
                w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none
                ${errors.completion_notes ? 'border-red-300' : 'border-gray-300'}
              `}
              disabled={isSubmitting}
            />
            {errors.completion_notes && (
              <p className="mt-1 text-sm text-red-600">{errors.completion_notes.message}</p>
            )}
            <div className="flex items-center justify-between mt-1">
              <p className="text-xs text-gray-500">
                Optional: Provide any additional information
              </p>
              <p className="text-xs text-gray-400">
                {completionNotes.length}/1000
              </p>
            </div>
          </div>

          {/* Confirmation Message */}
          {showConfirmation && (
            <div className="p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <div className="flex items-start gap-3">
                <AlertCircle className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                <div className="flex-1">
                  <h4 className="font-medium text-blue-900 mb-2">
                    Confirm Completion
                  </h4>
                  <div className="text-sm text-blue-700 space-y-1">
                    <p>
                      <strong>Completion Date:</strong> {format(new Date(completionDate), 'MMMM dd, yyyy')}
                    </p>
                    {completionNotes && (
                      <p>
                        <strong>Notes:</strong> {completionNotes}
                      </p>
                    )}
                    <p className="mt-3 font-medium">
                      Are you sure you want to mark this request as completed?
                    </p>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Action Buttons */}
          <div className="flex gap-3">
            {showConfirmation ? (
              <>
                <button
                  type="button"
                  onClick={handleCancel}
                  disabled={isSubmitting}
                  className="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={isSubmitting || !vendorRequest.document_url}
                  className="flex-1 bg-green-600 text-white py-3 rounded-lg font-medium hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
                >
                  {isSubmitting ? (
                    <>
                      <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                      Submitting...
                    </>
                  ) : (
                    <>
                      <CheckCircle className="w-5 h-5" />
                      Confirm Completion
                    </>
                  )}
                </button>
              </>
            ) : (
              <button
                type="submit"
                disabled={isSubmitting || !vendorRequest.document_url}
                className="w-full bg-green-600 text-white py-3 rounded-lg font-medium hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
              >
                <CheckCircle className="w-5 h-5" />
                Mark as Complete
              </button>
            )}
          </div>
        </form>

        {/* Info Box */}
        <div className="mt-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
          <h4 className="font-medium text-gray-900 mb-2 flex items-center gap-2">
            <FileText className="w-4 h-4" />
            What happens next?
          </h4>
          <ul className="text-sm text-gray-700 space-y-1 list-disc list-inside">
            <li>The agent will be notified of the completion</li>
            <li>They can review your document and notes</li>
            <li>The agent may provide a rating for your work</li>
            <li>You'll receive a confirmation email</li>
          </ul>
        </div>
      </div>
    </div>
  );
}
