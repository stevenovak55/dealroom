import { InputHTMLAttributes, forwardRef } from 'react';
import { Input } from './Input';

export interface DatePickerProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
  label?: string;
  error?: string;
  helperText?: string;
}

export const DatePicker = forwardRef<HTMLInputElement, DatePickerProps>(
  ({ ...props }, ref) => {
    return <Input ref={ref} type="date" {...props} />;
  }
);

DatePicker.displayName = 'DatePicker';
