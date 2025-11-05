import React from 'react';

interface ScrollAreaProps {
  children: React.ReactNode;
  className?: string;
}

export const ScrollArea: React.FC<ScrollAreaProps> = ({
  children,
  className = '',
}) => {
  return (
    <div className={`overflow-auto ${className}`}>
      {children}
    </div>
  );
};

interface ScrollBarProps {
  orientation?: 'horizontal' | 'vertical';
  className?: string;
}

export const ScrollBar: React.FC<ScrollBarProps> = () => {
  // This is a stub component - the browser's native scrollbar will be used
  return null;
};
