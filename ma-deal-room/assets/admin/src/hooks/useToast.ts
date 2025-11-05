import { showToast } from '@/utils/toast';

interface ToastOptions {
  title: string;
  description?: string;
  variant?: 'success' | 'error' | 'warning' | 'info';
}

export const useToast = () => {
  const toast = (options: ToastOptions) => {
    const message = options.description
      ? `${options.title}: ${options.description}`
      : options.title;

    switch (options.variant) {
      case 'error':
        showToast.error(message);
        break;
      case 'warning':
        showToast.warning(message);
        break;
      case 'info':
        showToast.info(message);
        break;
      case 'success':
      default:
        showToast.success(message);
        break;
    }
  };

  return { toast };
};
