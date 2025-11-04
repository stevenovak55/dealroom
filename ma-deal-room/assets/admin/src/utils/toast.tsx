import toast from 'react-hot-toast';
import { CheckCircle, XCircle, AlertCircle, Info } from 'lucide-react';

// Custom toast configurations with icons
export const showToast = {
  success: (message: string) => {
    toast.success(message, {
      duration: 4000,
      icon: <CheckCircle className="h-5 w-5 text-green-500" />,
      style: {
        background: '#f0fdf4',
        border: '1px solid #86efac',
        color: '#166534',
      },
    });
  },

  error: (message: string) => {
    toast.error(message, {
      duration: 5000,
      icon: <XCircle className="h-5 w-5 text-red-500" />,
      style: {
        background: '#fef2f2',
        border: '1px solid #fca5a5',
        color: '#991b1b',
      },
    });
  },

  warning: (message: string) => {
    toast(message, {
      duration: 4000,
      icon: <AlertCircle className="h-5 w-5 text-orange-500" />,
      style: {
        background: '#fffbeb',
        border: '1px solid #fde68a',
        color: '#92400e',
      },
    });
  },

  info: (message: string) => {
    toast(message, {
      duration: 4000,
      icon: <Info className="h-5 w-5 text-blue-500" />,
      style: {
        background: '#eff6ff',
        border: '1px solid #93c5fd',
        color: '#1e40af',
      },
    });
  },

  promise: <T,>(
    promise: Promise<T>,
    messages: {
      loading: string;
      success: string | ((data: T) => string);
      error: string | ((error: any) => string);
    }
  ) => {
    return toast.promise(promise, messages, {
      success: {
        icon: <CheckCircle className="h-5 w-5 text-green-500" />,
      },
      error: {
        icon: <XCircle className="h-5 w-5 text-red-500" />,
      },
    });
  },
};

export default showToast;
