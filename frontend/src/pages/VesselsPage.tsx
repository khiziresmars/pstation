import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { vesselsApi } from '@/services/api';
import { VesselCard } from '@/components/VesselCard';
import { CardSkeleton } from '@/components/common/Skeleton';
import { ErrorState } from '@/components/common/ErrorState';
import { EmptyState } from '@/components/common/EmptyState';
import type { VesselFilters, VesselType } from '@/types';

export default function VesselsPage() {
  const { t } = useTranslation();
  const [filters, setFilters] = useState<VesselFilters>({});
  const [showFilters, setShowFilters] = useState(false);

  const { data, isLoading, error, refetch } = useQuery({
    queryKey: ['vessels', filters],
    queryFn: () => vesselsApi.getAll(filters),
  });

  const vesselTypes: { value: VesselType | ''; label: string }[] = [
    { value: '', label: t('all_types') },
    { value: 'yacht', label: t('yacht') },
    { value: 'speedboat', label: t('speedboat') },
    { value: 'catamaran', label: t('catamaran') },
    { value: 'sailboat', label: t('sailboat') },
  ];

  const sortOptions = [
    { value: 'popular', label: t('most_popular') },
    { value: 'price_asc', label: t('price_low_high') },
    { value: 'price_desc', label: t('price_high_low') },
    { value: 'rating', label: t('highest_rated') },
  ];

  return (
    <div className="pb-4">
      {/* Header */}
      <div className="sticky top-0 bg-tg-bg z-30 px-4 py-3 border-b border-tg-secondary-bg">
        <div className="flex items-center justify-between">
          <h1 className="text-xl font-bold text-tg-text">{t('vessels')}</h1>
          <button
            onClick={() => setShowFilters(!showFilters)}
            className="btn-secondary text-sm"
          >
            {t('filter')} 🔽
          </button>
        </div>

        {/* Filters */}
        {showFilters && (
          <div className="mt-3 space-y-3 animate-fade-in">
            {/* Type filter */}
            <div className="flex gap-2 overflow-x-auto scrollbar-hide">
              {vesselTypes.map((type) => (
                <button
                  key={type.value}
                  onClick={() => setFilters({ ...filters, type: type.value || undefined })}
                  className={`btn text-sm whitespace-nowrap ${
                    filters.type === type.value || (!filters.type && !type.value)
                      ? 'bg-tg-button text-tg-button-text'
                      : 'bg-tg-secondary-bg text-tg-text'
                  }`}
                >
                  {type.label}
                </button>
              ))}
            </div>

            {/* Sort */}
            <select
              value={filters.sort || 'popular'}
              onChange={(e) => setFilters({ ...filters, sort: e.target.value as VesselFilters['sort'] })}
              className="input"
            >
              {sortOptions.map((opt) => (
                <option key={opt.value} value={opt.value}>
                  {opt.label}
                </option>
              ))}
            </select>
          </div>
        )}
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
            icon="🚤"
            title={t('no_vessels_found')}
            message={t('try_adjusting_filters')}
            action={
              <button
                onClick={() => setFilters({})}
                className="btn-primary"
              >
                {t('clear_filters')}
              </button>
            }
          />
        ) : (
          <>
            <p className="text-sm text-tg-hint mb-4">
              {data.pagination.total} {data.pagination.total === 1 ? t('vessel_found') : t('vessels_found')}
            </p>

            <div className="grid grid-cols-1 gap-4">
              {data.data.map((vessel) => (
                <VesselCard key={vessel.id} vessel={vessel} />
              ))}
            </div>
          </>
        )}
      </div>
    </div>
  );
}
