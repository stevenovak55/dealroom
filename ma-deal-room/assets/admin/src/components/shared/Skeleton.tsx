import { cn } from '@/utils/cn';

interface SkeletonProps {
  className?: string;
}

export const Skeleton = ({ className }: SkeletonProps) => {
  return (
    <div
      className={cn(
        'animate-pulse rounded-md bg-gray-200',
        className
      )}
    />
  );
};

export const TaskCardSkeleton = () => {
  return (
    <div className="bg-white rounded-lg border border-gray-200 p-4 space-y-3">
      <div className="flex items-start justify-between">
        <div className="flex-1 space-y-2">
          <Skeleton className="h-4 w-3/4" />
          <div className="flex gap-2">
            <Skeleton className="h-5 w-16" />
            <Skeleton className="h-5 w-16" />
          </div>
        </div>
      </div>
      <Skeleton className="h-3 w-full" />
      <Skeleton className="h-3 w-5/6" />
      <div className="space-y-2 pt-2">
        <Skeleton className="h-3 w-2/3" />
        <Skeleton className="h-3 w-1/2" />
      </div>
    </div>
  );
};

export const TaskLibrarySkeleton = () => {
  return (
    <div className="space-y-8">
      {[1, 2, 3].map((categoryIndex) => (
        <div key={categoryIndex}>
          <div className="flex items-center gap-3 mb-4">
            <Skeleton className="w-1 h-6 rounded-full" />
            <Skeleton className="h-6 w-48" />
            <Skeleton className="ml-auto h-4 w-16" />
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {[1, 2, 3].map((taskIndex) => (
              <TaskCardSkeleton key={taskIndex} />
            ))}
          </div>
        </div>
      ))}
    </div>
  );
};
