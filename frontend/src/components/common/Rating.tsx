import clsx from 'clsx';

interface RatingProps {
  value: number;
  count?: number;
  size?: 'sm' | 'md' | 'lg';
  showValue?: boolean;
}

export function Rating({ value, count, size = 'md', showValue = true }: RatingProps) {
  const stars = [];
  const roundedValue = Math.round(value * 2) / 2;

  for (let i = 1; i <= 5; i++) {
    if (i <= roundedValue) {
      stars.push('★');
    } else if (i - 0.5 === roundedValue) {
      stars.push('½');
    } else {
      stars.push('☆');
    }
  }

  const sizeClasses = {
    sm: 'text-xs',
    md: 'text-sm',
    lg: 'text-base',
  };

  return (
    <div className={clsx('flex items-center gap-1', sizeClasses[size])}>
      <span className="text-yellow-500">{stars.join('')}</span>
      {showValue && (
        <span className="text-tg-text font-medium">{value.toFixed(1)}</span>
      )}
      {count !== undefined && (
        <span className="text-tg-hint">({count})</span>
      )}
    </div>
  );
}
