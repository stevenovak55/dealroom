import { cn } from '@/utils/cn';

/**
 * Table Components (Mobile-First)
 *
 * Responsive table with:
 * - Horizontal scroll on mobile (optimized with scroll snap)
 * - Compact padding on mobile
 * - Traditional table layout preserved
 *
 * For complex tables, consider using card view on mobile instead
 */

interface TableProps {
  children: React.ReactNode;
  className?: string;
}

export const Table = ({ children, className }: TableProps) => {
  return (
    <div className={cn(
      'overflow-x-auto -mx-4 md:mx-0',
      // Smooth scrolling on mobile
      'overflow-scrolling-touch',
      // Scroll snap for better mobile experience
      'snap-x snap-mandatory md:snap-none'
    )}>
      <table className={cn(
        'w-full divide-y divide-gray-200',
        // Minimum width to prevent cramping on small screens
        'min-w-[640px] md:min-w-full',
        className
      )}>
        {children}
      </table>
    </div>
  );
};

interface TableHeaderProps {
  children: React.ReactNode;
  className?: string;
}

export const TableHeader = ({ children, className }: TableHeaderProps) => {
  return (
    <thead className={cn(
      'bg-gray-50',
      // Sticky header on mobile for better UX
      'sticky top-0 z-10 md:static md:z-auto',
      className
    )}>
      {children}
    </thead>
  );
};

interface TableBodyProps {
  children: React.ReactNode;
  className?: string;
}

export const TableBody = ({ children, className }: TableBodyProps) => {
  return (
    <tbody className={cn(
      'divide-y divide-gray-200 bg-white',
      className
    )}>
      {children}
    </tbody>
  );
};

interface TableRowProps {
  children: React.ReactNode;
  className?: string;
  onClick?: () => void;
}

export const TableRow = ({ children, className, onClick }: TableRowProps) => {
  return (
    <tr
      className={cn(
        onClick && 'cursor-pointer hover:bg-gray-50 active:bg-gray-100',
        onClick && 'transition-colors duration-fast',
        className
      )}
      onClick={onClick}
    >
      {children}
    </tr>
  );
};

interface TableHeadProps {
  children: React.ReactNode;
  className?: string;
}

export const TableHead = ({ children, className }: TableHeadProps) => {
  return (
    <th
      className={cn(
        // Mobile-first padding
        'px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider',
        'md:px-6 md:py-3',
        className
      )}
    >
      {children}
    </th>
  );
};

interface TableCellProps {
  children: React.ReactNode;
  className?: string;
}

export const TableCell = ({ children, className }: TableCellProps) => {
  return (
    <td className={cn(
      // Mobile-first padding and text
      'px-3 py-3 text-sm text-gray-900',
      'md:px-6 md:py-4',
      // Allow wrapping on mobile for better readability
      'md:whitespace-nowrap',
      className
    )}>
      {children}
    </td>
  );
};
