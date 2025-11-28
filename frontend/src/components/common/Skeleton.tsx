import clsx from 'clsx';

interface SkeletonProps {
  className?: string;
  variant?: 'text' | 'circular' | 'rectangular';
  width?: string | number;
  height?: string | number;
}

export function Skeleton({ className, variant = 'rectangular', width, height }: SkeletonProps) {
  const style: React.CSSProperties = {};

  if (width) {
    style.width = typeof width === 'number' ? `${width}px` : width;
  }

  if (height) {
    style.height = typeof height === 'number' ? `${height}px` : height;
  }

  return (
    <div
      className={clsx(
        'skeleton',
        variant === 'circular' && 'rounded-full',
        variant === 'text' && 'h-4 rounded',
        className
      )}
      style={style}
    />
  );
}

// Card skeleton
export function CardSkeleton() {
  return (
    <div className="card p-4 space-y-3">
      <Skeleton className="w-full h-40 rounded-lg" />
      <Skeleton className="h-5 w-3/4" />
      <Skeleton className="h-4 w-1/2" />
      <div className="flex justify-between items-center pt-2">
        <Skeleton className="h-6 w-24" />
        <Skeleton className="h-8 w-20 rounded-lg" />
      </div>
    </div>
  );
}

// List item skeleton
export function ListItemSkeleton() {
  return (
    <div className="flex items-center gap-3 p-3">
      <Skeleton className="w-16 h-16 rounded-lg" />
      <div className="flex-1 space-y-2">
        <Skeleton className="h-4 w-3/4" />
        <Skeleton className="h-3 w-1/2" />
      </div>
      <Skeleton className="h-6 w-16" />
    </div>
  );
}

// Profile skeleton
export function ProfileSkeleton() {
  return (
    <div className="p-4 space-y-4">
      <div className="flex items-center gap-4">
        <Skeleton variant="circular" width={64} height={64} />
        <div className="space-y-2">
          <Skeleton className="h-5 w-32" />
          <Skeleton className="h-4 w-24" />
        </div>
      </div>
      <div className="card p-4 space-y-3">
        <Skeleton className="h-4 w-24" />
        <Skeleton className="h-8 w-full" />
      </div>
    </div>
  );
}
