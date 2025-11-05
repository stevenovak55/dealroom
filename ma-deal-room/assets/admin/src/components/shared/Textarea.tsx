import React from 'react';

interface TextareaProps {
  id?: string;
  value?: string;
  placeholder?: string;
  onChange?: (e: React.ChangeEvent<HTMLTextAreaElement>) => void;
  className?: string;
  rows?: number;
  disabled?: boolean;
}

export const Textarea: React.FC<TextareaProps> = ({
  id,
  value,
  placeholder,
  onChange,
  className = '',
  rows = 3,
  disabled = false,
}) => {
  return (
    <textarea
      id={id}
      value={value}
      placeholder={placeholder}
      onChange={onChange}
      rows={rows}
      disabled={disabled}
      className={`w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 ${className}`}
    />
  );
};
