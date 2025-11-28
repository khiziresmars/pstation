import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { toursApi } from '@/services/api';
import { TourCard } from '@/components/TourCard';
import { CardSkeleton } from '@/components/common/Skeleton';
import { ErrorState } from '@/components/common/ErrorState';
import { EmptyState } from '@/components/common/EmptyState';
import type { TourFilters, TourCategory } from '@/types';

export default function ToursPage() {
  const { t } = useTranslation();
  const [filters, setFilters] = useState<TourFilters>({});

  const { data, isLoading, error, refetch } = useQuery({
    queryKey: ['tours', filters],
    queryFn: () => toursApi.getAll(filters),
  });

  const categoryOptions: { value: TourCategory | ''; label: string; icon: string }[] = [
    { value: '', label: 'All', icon: '🌊' },
    { value: 'islands', label: 'Islands', icon: '🏝️' },
    { value: 'snorkeling', label: 'Snorkeling', icon: '🤿' },
    { value: 'fishing', label: 'Fishing', icon: '🎣' },
    { value: 'sunset', label: 'Sunset', icon: '🌅' },
    { value: 'adventure', label: 'Adventure', icon: '🚀' },
  ];

  return (
    <div className="pb-4">
      {/* Header */}
      <div className="sticky top-0 bg-tg-bg z-30 px-4 py-3 border-b border-tg-secondary-bg">
        <h1 className="text-xl font-bold text-tg-text mb-3">{t('tours')}</h1>

        {/* Category tabs */}
        <div className="flex gap-2 overflow-x-auto scrollbar-hide -mx-4 px-4">
          {categoryOptions.map((cat) => (
            <button
              key={cat.value}
              onClick={() => setFilters({ ...filters, category: cat.value || undefined })}
              className={`flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm whitespace-nowrap transition-all ${
                filters.category === cat.value || (!filters.category && !cat.value)
                  ? 'bg-tg-button text-tg-button-text'
                  : 'bg-tg-secondary-bg text-tg-text'
              }`}
            >
              <span>{cat.icon}</span>
              <span>{cat.label}</span>
            </button>
          ))}
        </div>
      </div>

      {/* Results */}
      <div className="px-4 py-4">
        {error ? (
          <ErrorState onRetry={() => refetch()} />
        ) : isLoading ? (
          <div className="grid grid-cols-1 gap-4">
            {[1, 2, 3, 4].map((i) => (
              <CardSkeleton key={i} />
            ))}
          </div>
        ) : !data?.data.length ? (
          <EmptyState
            icon="🏝️"
            title="No tours found"
            message="Try selecting a different category"
            action={
              <button
                onClick={() => setFilters({})}
                className="btn-primary"
              >
                View All Tours
              </button>
            }
          />
        ) : (
          <>
            <p className="text-sm text-tg-hint mb-4">
              {data.pagination.total} {data.pagination.total === 1 ? 'tour' : 'tours'} available
            </p>

            <div className="grid grid-cols-1 gap-4">
              {data.data.map((tour) => (
                <TourCard key={tour.id} tour={tour} />
              ))}
            </div>
          </>
        )}
      </div>
    </div>
  );
}
