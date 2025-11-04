import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { format, addDays } from 'date-fns';
import { Calendar, Clock, Plus, Trash2, Send } from 'lucide-react';
import {
  VendorRequest,
  VendorAvailability,
  ScheduleData,
  AvailabilityWindow,
} from '../../api/vendorPortalClient';

interface SchedulingFormProps {
  vendorRequest: VendorRequest;
  availability: VendorAvailability[];
  onSchedule: (data: ScheduleData) => void;
  onSubmitAvailability: (windows: AvailabilityWindow[]) => void;
  isSubmitting: boolean;
}

type FormData = {
  scheduled_date: string;
  scheduled_time: string;
  vendor_name: string;
  vendor_company: string;
};

type AvailabilityFormData = {
  available_date: string;
  start_time: string;
  end_time: string;
  notes: string;
};

export default function SchedulingForm({
  vendorRequest,
  availability,
  onSchedule,
  onSubmitAvailability,
  isSubmitting,
}: SchedulingFormProps) {
  const [showAvailabilityForm, setShowAvailabilityForm] = useState(false);
  const [availabilityWindows, setAvailabilityWindows] = useState<AvailabilityWindow[]>(
    availability.map((a) => ({
      available_date: a.available_date,
      start_time: a.start_time,
      end_time: a.end_time,
      timezone: a.timezone,
      notes: a.notes || '',
    }))
  );

  const {
    register: registerSchedule,
    handleSubmit: handleScheduleSubmit,
    formState: { errors: scheduleErrors },
  } = useForm<FormData>({
    defaultValues: {
      scheduled_date: vendorRequest.scheduled_date || '',
      scheduled_time: vendorRequest.scheduled_time || '',
      vendor_name: vendorRequest.vendor_name || '',
      vendor_company: vendorRequest.vendor_company || '',
    },
  });

  const {
    register: registerAvailability,
    handleSubmit: handleAvailabilitySubmit,
    reset: resetAvailability,
    formState: { errors: availabilityErrors },
  } = useForm<AvailabilityFormData>();

  const isScheduled = vendorRequest.status === 'scheduled';
  const minDate = format(new Date(), 'yyyy-MM-dd');
  const maxDate = format(addDays(new Date(), 90), 'yyyy-MM-dd');

  const handleAddAvailability = (data: AvailabilityFormData) => {
    const newWindow: AvailabilityWindow = {
      available_date: data.available_date,
      start_time: data.start_time,
      end_time: data.end_time,
      timezone: 'America/New_York',
      notes: data.notes,
    };

    setAvailabilityWindows([...availabilityWindows, newWindow]);
    resetAvailability();
  };

  const handleRemoveWindow = (index: number) => {
    setAvailabilityWindows(availabilityWindows.filter((_, i) => i !== index));
  };

  const handleSubmitAllAvailability = () => {
    if (availabilityWindows.length === 0) {
      alert('Please add at least one availability window');
      return;
    }
    onSubmitAvailability(availabilityWindows);
    setShowAvailabilityForm(false);
  };

  const formatTimeRange = (startTime: string, endTime: string) => {
    const start = format(new Date(`2000-01-01T${startTime}`), 'h:mm a');
    const end = format(new Date(`2000-01-01T${endTime}`), 'h:mm a');
    return `${start} - ${end}`;
  };

  return (
    <div className="bg-white rounded-lg shadow-sm overflow-hidden">
      <div className="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 text-white">
        <h2 className="text-xl font-bold">Scheduling</h2>
        <p className="text-indigo-100 text-sm mt-1">
          {isScheduled
            ? 'Your appointment is scheduled'
            : 'Schedule your appointment or submit your availability'}
        </p>
      </div>

      <div className="p-6 space-y-6">
        {!isScheduled ? (
          <>
            {/* Direct Scheduling Form */}
            <div>
              <h3 className="text-lg font-semibold text-gray-900 mb-4">
                Schedule Specific Date & Time
              </h3>
              <form onSubmit={handleScheduleSubmit(onSchedule)} className="space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      <Calendar className="w-4 h-4 inline mr-1" />
                      Date *
                    </label>
                    <input
                      type="date"
                      min={minDate}
                      max={maxDate}
                      {...registerSchedule('scheduled_date', {
                        required: 'Date is required',
                      })}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                    {scheduleErrors.scheduled_date && (
                      <p className="mt-1 text-sm text-red-600">
                        {scheduleErrors.scheduled_date.message}
                      </p>
                    )}
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      <Clock className="w-4 h-4 inline mr-1" />
                      Time (optional)
                    </label>
                    <input
                      type="time"
                      {...registerSchedule('scheduled_time')}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Your Name
                    </label>
                    <input
                      type="text"
                      {...registerSchedule('vendor_name')}
                      placeholder="John Doe"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Company Name
                    </label>
                    <input
                      type="text"
                      {...registerSchedule('vendor_company')}
                      placeholder="ABC Inspections"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                  </div>
                </div>

                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="w-full bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
                >
                  {isSubmitting ? (
                    <>
                      <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                      Scheduling...
                    </>
                  ) : (
                    <>
                      <Calendar className="w-5 h-5" />
                      Schedule Appointment
                    </>
                  )}
                </button>
              </form>
            </div>

            {/* Divider */}
            <div className="relative">
              <div className="absolute inset-0 flex items-center">
                <div className="w-full border-t border-gray-300" />
              </div>
              <div className="relative flex justify-center text-sm">
                <span className="px-2 bg-white text-gray-500">OR</span>
              </div>
            </div>

            {/* Availability Windows */}
            <div>
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-semibold text-gray-900">
                  Submit Your Availability
                </h3>
                <button
                  onClick={() => setShowAvailabilityForm(!showAvailabilityForm)}
                  className="text-indigo-600 hover:text-indigo-700 text-sm font-medium flex items-center gap-1"
                >
                  <Plus className="w-4 h-4" />
                  {showAvailabilityForm ? 'Hide Form' : 'Add Window'}
                </button>
              </div>

              {/* Existing Availability Windows */}
              {availabilityWindows.length > 0 && (
                <div className="space-y-2 mb-4">
                  {availabilityWindows.map((window, index) => (
                    <div
                      key={index}
                      className="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                    >
                      <div>
                        <p className="font-medium text-gray-900">
                          {format(new Date(window.available_date), 'EEEE, MMM dd, yyyy')}
                        </p>
                        <p className="text-sm text-gray-600">
                          {formatTimeRange(window.start_time, window.end_time)}
                        </p>
                        {window.notes && (
                          <p className="text-sm text-gray-500 mt-1">{window.notes}</p>
                        )}
                      </div>
                      <button
                        onClick={() => handleRemoveWindow(index)}
                        className="text-red-600 hover:text-red-700 p-2"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </div>
                  ))}
                </div>
              )}

              {/* Add Availability Form */}
              {showAvailabilityForm && (
                <form
                  onSubmit={handleAvailabilitySubmit(handleAddAvailability)}
                  className="space-y-4 p-4 bg-indigo-50 rounded-lg"
                >
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Date *
                    </label>
                    <input
                      type="date"
                      min={minDate}
                      max={maxDate}
                      {...registerAvailability('available_date', {
                        required: 'Date is required',
                      })}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                    {availabilityErrors.available_date && (
                      <p className="mt-1 text-sm text-red-600">
                        {availabilityErrors.available_date.message}
                      </p>
                    )}
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Start Time *
                      </label>
                      <input
                        type="time"
                        {...registerAvailability('start_time', {
                          required: 'Start time is required',
                        })}
                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                      />
                      {availabilityErrors.start_time && (
                        <p className="mt-1 text-sm text-red-600">
                          {availabilityErrors.start_time.message}
                        </p>
                      )}
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        End Time *
                      </label>
                      <input
                        type="time"
                        {...registerAvailability('end_time', {
                          required: 'End time is required',
                        })}
                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                      />
                      {availabilityErrors.end_time && (
                        <p className="mt-1 text-sm text-red-600">
                          {availabilityErrors.end_time.message}
                        </p>
                      )}
                    </div>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Notes (optional)
                    </label>
                    <input
                      type="text"
                      {...registerAvailability('notes')}
                      placeholder="e.g., Prefer morning appointments"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                  </div>

                  <button
                    type="submit"
                    className="w-full bg-indigo-600 text-white py-2 rounded-lg font-medium hover:bg-indigo-700 transition-colors flex items-center justify-center gap-2"
                  >
                    <Plus className="w-4 h-4" />
                    Add This Window
                  </button>
                </form>
              )}

              {/* Submit All Availability Button */}
              {availabilityWindows.length > 0 && (
                <button
                  onClick={handleSubmitAllAvailability}
                  disabled={isSubmitting}
                  className="w-full bg-purple-600 text-white py-3 rounded-lg font-medium hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2 mt-4"
                >
                  {isSubmitting ? (
                    <>
                      <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                      Submitting...
                    </>
                  ) : (
                    <>
                      <Send className="w-5 h-5" />
                      Submit {availabilityWindows.length} Availability Window
                      {availabilityWindows.length !== 1 ? 's' : ''}
                    </>
                  )}
                </button>
              )}
            </div>
          </>
        ) : (
          /* Scheduled - Show Appointment Details */
          <div className="text-center py-8">
            <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <Calendar className="w-8 h-8 text-green-600" />
            </div>
            <h3 className="text-xl font-bold text-gray-900 mb-2">Appointment Scheduled</h3>
            <p className="text-gray-600 mb-4">
              Your appointment is confirmed for:
            </p>
            <div className="inline-block bg-gray-50 rounded-lg p-6">
              <p className="text-2xl font-bold text-gray-900 mb-2">
                {format(new Date(vendorRequest.scheduled_date!), 'EEEE, MMMM dd, yyyy')}
              </p>
              {vendorRequest.scheduled_time && (
                <p className="text-lg text-gray-700">
                  {format(
                    new Date(`2000-01-01T${vendorRequest.scheduled_time}`),
                    'h:mm a'
                  )}
                </p>
              )}
            </div>
            <p className="text-sm text-gray-500 mt-4">
              The agent has been notified of your scheduled appointment.
            </p>
          </div>
        )}
      </div>
    </div>
  );
}
