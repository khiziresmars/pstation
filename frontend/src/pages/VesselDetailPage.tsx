import { useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { vesselsApi } from '@/services/api';
import { usePrice } from '@/hooks/usePrice';
import { useTelegram } from '@/hooks/useTelegram';
import { ImageGallery } from '@/components/common/ImageGallery';
import { Rating } from '@/components/common/Rating';
import { Skeleton } from '@/components/common/Skeleton';
import { ErrorState } from '@/components/common/ErrorState';

export default function VesselDetailPage() {
  const { slug } = useParams<{ slug: string }>();
  const navigate = useNavigate();
  const { t, i18n } = useTranslation();
  const { formatPrice } = usePrice();
  const { showBackButton, hideBackButton, showMainButton, hideMainButton, hapticImpact } = useTelegram();

  const { data: vessel, isLoading, error, refetch } = useQuery({
    queryKey: ['vessel', slug],
    queryFn: () => vesselsApi.getBySlug(slug!),
    enabled: !!slug,
  });

  useEffect(() => {
    showBackButton(() => {
      hideBackButton();
      navigate(-1);
    });

    return () => {
      hideBackButton();
      hideMainButton();
    };
  }, [showBackButton, hideBackButton, hideMainButton, navigate]);

  useEffect(() => {
    if (vessel) {
      showMainButton(t('book_now'), () => {
        hapticImpact('medium');
        navigate(`/booking/vessel/${vessel.id}`);
      });
    }
  }, [vessel, showMainButton, hapticImpact, navigate, t]);

  const getDescription = () => {
    if (!vessel) return '';
    const lang = i18n.language;
    if (lang === 'ru' && vessel.description_ru) return vessel.description_ru;
    if (lang === 'th' && vessel.description_th) return vessel.description_th;
    return vessel.description_en;
  };

  if (isLoading) {
    return (
      <div>
        <Skeleton className="w-full aspect-video" />
        <div className="p-4 space-y-4">
          <Skeleton className="h-8 w-3/4" />
          <Skeleton className="h-4 w-1/2" />
          <Skeleton className="h-20 w-full" />
        </div>
      </div>
    );
  }

  if (error || !vessel) {
    return <ErrorState onRetry={() => refetch()} />;
  }

  const typeLabels: Record<string, string> = {
    yacht: t('yacht'),
    speedboat: t('speedboat'),
    catamaran: t('catamaran'),
    sailboat: t('sailboat'),
  };

  return (
    <div className="pb-24">
      {/* Image Gallery */}
      <ImageGallery images={vessel.images} alt={vessel.name} />

      {/* Content */}
      <div className="p-4 space-y-4">
        {/* Header */}
        <div>
          <div className="flex items-center gap-2 mb-1">
            <span className="badge-primary">{typeLabels[vessel.type]}</span>
            {vessel.is_featured && <span className="badge-warning">⭐ Featured</span>}
          </div>
          <h1 className="text-2xl font-bold text-tg-text">{vessel.name}</h1>

          {vessel.rating > 0 && (
            <div className="mt-2">
              <Rating value={vessel.rating} count={vessel.reviews_count} />
            </div>
          )}
        </div>

        {/* Quick Info */}
        <div className="grid grid-cols-3 gap-3">
          <div className="card p-3 text-center">
            <div className="text-2xl">👥</div>
            <div className="text-sm text-tg-hint">{t('capacity')}</div>
            <div className="font-semibold">{vessel.capacity}</div>
          </div>
          <div className="card p-3 text-center">
            <div className="text-2xl">📏</div>
            <div className="text-sm text-tg-hint">{t('length')}</div>
            <div className="font-semibold">{vessel.length_meters}m</div>
          </div>
          <div className="card p-3 text-center">
            <div className="text-2xl">🛏️</div>
            <div className="text-sm text-tg-hint">Cabins</div>
            <div className="font-semibold">{vessel.cabins || 0}</div>
          </div>
        </div>

        {/* Price */}
        <div className="card p-4">
          <div className="flex justify-between items-center">
            <div>
              <div className="text-sm text-tg-hint">{t('per_hour')}</div>
              <div className="text-xl font-bold text-tg-accent-text">
                {formatPrice(vessel.price_per_hour_thb)}
              </div>
            </div>
            <div className="text-right">
              <div className="text-sm text-tg-hint">{t('per_day')}</div>
              <div className="text-xl font-bold text-tg-accent-text">
                {formatPrice(vessel.price_per_day_thb)}
              </div>
            </div>
          </div>

          <div className="divider" />

          <div className="flex gap-4 text-sm">
            <div className={vessel.captain_included ? 'text-green-600' : 'text-tg-hint'}>
              {vessel.captain_included ? '✓' : '✗'} {t('captain_included')}
            </div>
            <div className={vessel.fuel_included ? 'text-green-600' : 'text-tg-hint'}>
              {vessel.fuel_included ? '✓' : '✗'} {t('fuel_included')}
            </div>
          </div>
        </div>

        {/* Description */}
        <div className="card p-4">
          <h2 className="font-semibold text-tg-text mb-2">About</h2>
          <p className="text-sm text-tg-text leading-relaxed">{getDescription()}</p>
        </div>

        {/* Features */}
        {vessel.features.length > 0 && (
          <div className="card p-4">
            <h2 className="font-semibold text-tg-text mb-3">{t('features')}</h2>
            <div className="flex flex-wrap gap-2">
              {vessel.features.map((feature, idx) => (
                <span key={idx} className="badge bg-tg-secondary-bg text-tg-text">
                  {feature}
                </span>
              ))}
            </div>
          </div>
        )}

        {/* Amenities */}
        {vessel.amenities.length > 0 && (
          <div className="card p-4">
            <h2 className="font-semibold text-tg-text mb-3">{t('amenities')}</h2>
            <div className="grid grid-cols-2 gap-2 text-sm">
              {vessel.amenities.map((amenity, idx) => (
                <div key={idx} className="flex items-center gap-2 text-tg-text">
                  <span className="text-green-500">✓</span>
                  {amenity}
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Location */}
        <div className="card p-4">
          <h2 className="font-semibold text-tg-text mb-2">📍 Location</h2>
          <p className="text-sm text-tg-text">{vessel.location}</p>
        </div>
      </div>
    </div>
  );
}
