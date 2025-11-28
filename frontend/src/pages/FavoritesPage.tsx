import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { userApi } from '@/services/api';
import { usePrice } from '@/hooks/usePrice';
import { useTelegram } from '@/hooks/useTelegram';
import { ListItemSkeleton } from '@/components/common/Skeleton';
import { ErrorState } from '@/components/common/ErrorState';
import { EmptyState } from '@/components/common/EmptyState';

export default function FavoritesPage() {
  const navigate = useNavigate();
  const { t } = useTranslation();
  const { formatPrice } = usePrice();
  const { showBackButton, hideBackButton, hapticImpact } = useTelegram();

  const { data: favorites, isLoading, error, refetch } = useQuery({
    queryKey: ['favorites'],
    queryFn: userApi.getFavorites,
  });

  useEffect(() => {
    showBackButton(() => {
      hideBackButton();
      navigate(-1);
    });

    return () => hideBackButton();
  }, [showBackButton, hideBackButton, navigate]);

  const handleItemClick = (type: string, id: number) => {
    hapticImpact('light');
    navigate(`/${type}s/${id}`);
  };

  if (isLoading) {
    return (
      <div className="p-4">
        <h1 className="text-xl font-bold text-tg-text mb-4">{t('favorites')}</h1>
        {[1, 2, 3].map((i) => (
          <ListItemSkeleton key={i} />
        ))}
      </div>
    );
  }

  if (error) {
    return <ErrorState onRetry={() => refetch()} />;
  }

  return (
    <div className="p-4">
      <h1 className="text-xl font-bold text-tg-text mb-4">{t('favorites')}</h1>

      {!favorites?.length ? (
        <EmptyState
          icon="❤️"
          title="No favorites yet"
          message="Save your favorite yachts and tours to easily find them later"
          action={
            <button onClick={() => navigate('/')} className="btn-primary">
              Explore Now
            </button>
          }
        />
      ) : (
        <div className="space-y-3">
          {favorites.map((item) => (
            <div
              key={`${item.favoritable_type}-${item.favoritable_id}`}
              onClick={() => handleItemClick(item.favoritable_type, item.favoritable_id)}
              className="card p-3 flex items-center gap-3 cursor-pointer active:scale-98"
            >
              {item.item_thumbnail && (
                <img
                  src={item.item_thumbnail}
                  alt={item.item_name}
                  className="w-16 h-16 rounded-lg object-cover"
                />
              )}
              <div className="flex-1 min-w-0">
                <h3 className="font-medium text-tg-text truncate">{item.item_name}</h3>
                <p className="text-sm text-tg-hint capitalize">{item.favoritable_type}</p>
              </div>
              <div className="text-right">
                <p className="font-semibold text-tg-accent-text">{formatPrice(item.price_thb)}</p>
                <p className="text-xs text-tg-hint">
                  {item.favoritable_type === 'vessel' ? '/day' : '/person'}
                </p>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
