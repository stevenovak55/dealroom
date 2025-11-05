import { ReactNode } from 'react';
import { cn } from '@/utils/cn';

interface Column<T> {
  header: string;
  accessor: keyof T | ((item: T) => ReactNode);
  className?: string;
  mobileLabel?: string;
}

interface ResponsiveTableProps<T> {
  data: T[];
  columns: Column<T>[];
  keyExtractor: (item: T) => string | number;
  onRowClick?: (item: T) => void;
  emptyMessage?: string;
  mobileCardClassName?: string;
}

export function ResponsiveTable<T>({
  data,
  columns,
  keyExtractor,
  onRowClick,
  emptyMessage = 'No data available',
  mobileCardClassName,
}: ResponsiveTableProps<T>) {
  const getCellValue = (item: T, column: Column<T>) => {
    if (typeof column.accessor === 'function') {
      return column.accessor(item);
    }
    return item[column.accessor] as ReactNode;
  };

  if (data.length === 0) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-500">{emptyMessage}</p>
      </div>
    );
  }

  return (
    <>
      {/* Desktop Table View */}
      <div className="hidden md:block overflow-x-auto">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              {columns.map((column, index) => (
                <th
                  key={index}
                  className={cn(
                    'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider',
                    column.className
                  )}
                >
                  {column.header}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {data.map((item) => (
              <tr
                key={keyExtractor(item)}
                onClick={() => onRowClick?.(item)}
                className={cn(
                  'hover:bg-gray-50 transition-colors',
                  onRowClick && 'cursor-pointer'
                )}
              >
                {columns.map((column, index) => (
                  <td
                    key={index}
                    className={cn('px-6 py-4 text-sm text-gray-900', column.className)}
                  >
                    {getCellValue(item, column)}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Mobile Card View */}
      <div className="md:hidden space-y-3">
        {data.map((item) => (
          <div
            key={keyExtractor(item)}
            onClick={() => onRowClick?.(item)}
            className={cn(
              'bg-white rounded-lg border border-gray-200 p-4 shadow-sm',
              onRowClick && 'active:bg-gray-50 cursor-pointer touch-feedback',
              mobileCardClassName
            )}
          >
            {columns.map((column, index) => {
              const value = getCellValue(item, column);
              if (!value) return null;
              
              return (
                <div key={index} className="mb-3 last:mb-0">
                  <div className="text-xs font-medium text-gray-500 uppercase mb-1">
                    {column.mobileLabel || column.header}
                  </div>
                  <div className="text-sm text-gray-900">{value}</div>
                </div>
              );
            })}
          </div>
        ))}
      </div>
    </>
  );
}
