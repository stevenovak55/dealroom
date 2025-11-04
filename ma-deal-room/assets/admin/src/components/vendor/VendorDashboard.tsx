import { format } from 'date-fns';
import {
  Calendar,
  MapPin,
  Clock,
  CheckCircle,
  FileText,
  Star,
  Building2,
  Mail,
  Phone,
} from 'lucide-react';
import { VendorRequest, Transaction, VendorRating } from '../../api/vendorPortalClient';

interface VendorDashboardProps {
  vendorRequest: VendorRequest;
  transaction: Transaction;
  rating?: VendorRating;
}

export default function VendorDashboard({
  vendorRequest,
  transaction,
  rating,
}: VendorDashboardProps) {
  const formatDate = (date?: string) => {
    if (!date) return 'Not set';
    return format(new Date(date), 'MMM dd, yyyy');
  };

  const formatTime = (time?: string) => {
    if (!time) return '';
    return format(new Date(`2000-01-01T${time}`), 'h:mm a');
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'completed':
        return 'text-green-600 bg-green-50';
      case 'scheduled':
        return 'text-blue-600 bg-blue-50';
      case 'opened':
        return 'text-yellow-600 bg-yellow-50';
      case 'expired':
        return 'text-red-600 bg-red-50';
      default:
        return 'text-gray-600 bg-gray-50';
    }
  };

  return (
    <div className="bg-white rounded-lg shadow-sm overflow-hidden">
      {/* Header */}
      <div className="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-8 text-white">
        <h2 className="text-2xl font-bold mb-2">
          {vendorRequest.vendor_type.replace(/_/g, ' ').toUpperCase()} Request
        </h2>
        <p className="text-blue-100">
          Transaction ID: #{transaction.id}
        </p>
      </div>

      {/* Content */}
      <div className="p-6 space-y-6">
        {/* Property Information */}
        <div>
          <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
            Property Information
          </h3>
          <div className="space-y-2">
            <div className="flex items-start gap-3">
              <MapPin className="w-5 h-5 text-gray-400 mt-0.5" />
              <div>
                <p className="font-medium text-gray-900">{transaction.property_address}</p>
                <p className="text-sm text-gray-600">
                  {transaction.property_city}, {transaction.property_state} {transaction.property_zip}
                </p>
              </div>
            </div>
            {transaction.closing_date && (
              <div className="flex items-center gap-3">
                <Calendar className="w-5 h-5 text-gray-400" />
                <div>
                  <p className="text-sm text-gray-600">Closing Date</p>
                  <p className="font-medium text-gray-900">
                    {formatDate(transaction.closing_date)}
                  </p>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Request Status */}
        <div>
          <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
            Request Status
          </h3>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="flex items-center gap-3 p-4 rounded-lg bg-gray-50">
              <div className={`p-2 rounded-lg ${getStatusColor(vendorRequest.status)}`}>
                <CheckCircle className="w-5 h-5" />
              </div>
              <div>
                <p className="text-sm text-gray-600">Current Status</p>
                <p className="font-semibold text-gray-900 capitalize">
                  {vendorRequest.status.replace(/_/g, ' ')}
                </p>
              </div>
            </div>

            {vendorRequest.scheduled_date && (
              <div className="flex items-center gap-3 p-4 rounded-lg bg-gray-50">
                <div className="p-2 rounded-lg bg-blue-50 text-blue-600">
                  <Clock className="w-5 h-5" />
                </div>
                <div>
                  <p className="text-sm text-gray-600">Scheduled</p>
                  <p className="font-semibold text-gray-900">
                    {formatDate(vendorRequest.scheduled_date)}
                    {vendorRequest.scheduled_time && (
                      <span className="text-sm text-gray-600 ml-2">
                        {formatTime(vendorRequest.scheduled_time)}
                      </span>
                    )}
                  </p>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Vendor Information */}
        {(vendorRequest.vendor_name || vendorRequest.vendor_company) && (
          <div>
            <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
              Your Information
            </h3>
            <div className="space-y-2">
              {vendorRequest.vendor_company && (
                <div className="flex items-center gap-3">
                  <Building2 className="w-5 h-5 text-gray-400" />
                  <span className="font-medium text-gray-900">
                    {vendorRequest.vendor_company}
                  </span>
                </div>
              )}
              {vendorRequest.vendor_name && (
                <div className="flex items-center gap-3">
                  <Mail className="w-5 h-5 text-gray-400" />
                  <span className="text-gray-700">{vendorRequest.vendor_name}</span>
                </div>
              )}
              <div className="flex items-center gap-3">
                <Mail className="w-5 h-5 text-gray-400" />
                <span className="text-gray-700">{vendorRequest.vendor_email}</span>
              </div>
              {vendorRequest.vendor_phone && (
                <div className="flex items-center gap-3">
                  <Phone className="w-5 h-5 text-gray-400" />
                  <span className="text-gray-700">{vendorRequest.vendor_phone}</span>
                </div>
              )}
            </div>
          </div>
        )}

        {/* Document Status */}
        {vendorRequest.document_url && (
          <div>
            <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
              Uploaded Documents
            </h3>
            <div className="flex items-center gap-3 p-4 rounded-lg bg-green-50">
              <FileText className="w-5 h-5 text-green-600" />
              <div className="flex-1">
                <p className="font-medium text-gray-900">Completion Document</p>
                <p className="text-sm text-gray-600">Successfully uploaded</p>
              </div>
              <a
                href={vendorRequest.document_url}
                target="_blank"
                rel="noopener noreferrer"
                className="text-blue-600 hover:text-blue-700 text-sm font-medium"
              >
                View
              </a>
            </div>
          </div>
        )}

        {/* Completion Notes */}
        {vendorRequest.completion_notes && (
          <div>
            <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
              Completion Notes
            </h3>
            <div className="p-4 rounded-lg bg-gray-50">
              <p className="text-gray-700">{vendorRequest.completion_notes}</p>
            </div>
          </div>
        )}

        {/* Rating Display */}
        {vendorRequest.average_rating && (
          <div>
            <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
              Your Average Rating
            </h3>
            <div className="flex items-center gap-3 p-4 rounded-lg bg-yellow-50">
              <Star className="w-6 h-6 text-yellow-500 fill-yellow-500" />
              <div>
                <p className="text-2xl font-bold text-gray-900">
                  {vendorRequest.average_rating.toFixed(1)}
                </p>
                <p className="text-sm text-gray-600">out of 5.0</p>
              </div>
            </div>
          </div>
        )}

        {/* Expiration Warning */}
        {vendorRequest.status !== 'completed' && vendorRequest.status !== 'expired' && (
          <div className="border-t pt-4">
            <p className="text-sm text-gray-600">
              This link expires on{' '}
              <span className="font-medium text-gray-900">
                {formatDate(vendorRequest.token_expires_at)}
              </span>
            </p>
          </div>
        )}
      </div>
    </div>
  );
}
