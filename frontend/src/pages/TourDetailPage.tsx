import { useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { toursApi } from '@/services/api';
import { usePrice } from '@/hooks/usePrice';
import { useTelegram } from '@/hooks/useTelegram';
import { ImageGallery } from '@/components/common/ImageGallery';
import { Rating } from '@/components/common/Rating';
import { Skeleton } from '@/components/common/Skeleton';
import { ErrorState } from '@/components/common/ErrorState';

export default function TourDetailPage() {
  const { slug } = useParams<{ slug: string }>();
  const navigate = useNavigate();
  const { t, i18n } = useTranslation();
  const { formatPrice } = usePrice();
  const { showBackButton, hideBackButton, showMainButton, hideMainButton, hapticImpact } = useTelegram();

  const { data: tour, isLoading, error, refetch } = useQuery({
    queryKey: ['tour', slug],
    queryFn: () => toursApi.getBySlug(slug!),
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
    if (tour) {
      showMainButton(t('book_now'), () => {
        hapticImpact('medium');
        navigate(`/booking/tour/${tour.id}`);
      });
    }
  }, [tour, showMainButton, hapticImpact, navigate, t]);

  const getName = () => {
    if (!tour) return '';
    const lang = i18n.language;
    if (lang === 'ru' && tour.name_ru) return tour.name_ru;
    if (lang === 'th' && tour.name_th) return tour.name_th;
    return tour.name_en;
  };

  const getDescription = () => {
    if (!tour) return '';
    const lang = i18n.language;
    if (lang === 'ru' && tour.description_ru) return tour.description_ru;
    if (lang === 'th' && tour.description_th) return tour.description_th;
    return tour.description_en;
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

  if (error || !tour) {
    return <ErrorState onRetry={() => refetch()} />;
  }

  return (
    <div className="pb-24">
      {/* Image Gallery */}
      <ImageGallery images={tour.images} alt={getName()} />

      {/* Content */}
      <div className="p-4 space-y-4">
        {/* Header */}
        <div>
          <div className="flex items-center gap-2 mb-1">
            <span className="badge-primary capitalize">{tour.category}</span>
            {tour.is_featured && <span className="badge-warning">⭐ Featured</span>}
          </div>
          <h1 className="text-2xl font-bold text-tg-text">{getName()}</h1>

          {tour.rating > 0 && (
            <div className="mt-2">
              <Rating value={tour.rating} count={tour.reviews_count} />
            </div>
          )}
        </div>

        {/* Quick Info */}
        <div className="grid grid-cols-3 gap-3">
          <div className="card p-3 text-center">
            <div className="text-2xl">⏱️</div>
            <div className="text-sm text-tg-hint">{t('duration')}</div>
            <div className="font-semibold">{tour.duration_hours}h</div>
          </div>
          <div className="card p-3 text-center">
            <div className="text-2xl">🕐</div>
            <div className="text-sm text-tg-hint">{t('departure')}</div>
            <div className="font-semibold">{tour.departure_time.slice(0, 5)}</div>
          </div>
          <div className="card p-3 text-center">
            <div className="text-2xl">👥</div>
            <div className="text-sm text-tg-hint">Max</div>
            <div className="font-semibold">{tour.max_participants}</div>
          </div>
        </div>

        {/* Price */}
        <div className="card p-4">
          <div className="flex justify-between items-center">
            <div>
              <div className="text-sm text-tg-hint">{t('adult')}</div>
              <div className="text-xl font-bold text-tg-accent-text">
                {formatPrice(tour.price_adult_thb)}
              </div>
            </div>
            <div className="text-right">
              <div className="text-sm text-tg-hint">{t('child')} ({tour.child_age_from}-{tour.child_age_to})</div>
              <div className="text-xl font-bold text-tg-accent-text">
                {formatPrice(tour.price_child_thb)}
              </div>
            </div>
          </div>

          {tour.pickup_available && (
            <>
              <div className="divider" />
              <div className="flex items-center justify-between text-sm">
                <span className="text-tg-text">🚐 {t('pickup_available')}</span>
                <span className="text-tg-accent-text font-medium">
                  +{formatPrice(tour.pickup_fee_thb)}
                </span>
              </div>
            </>
          )}
        </div>

        {/* Description */}
        <div className="card p-4">
          <h2 className="font-semibold text-tg-text mb-2">About</h2>
          <p className="text-sm text-tg-text leading-relaxed">{getDescription()}</p>
        </div>

        {/* Highlights */}
        {tour.highlights.length > 0 && (
          <div className="card p-4">
            <h2 className="font-semibold text-tg-text mb-3">{t('highlights')}</h2>
            <ul className="space-y-2">
              {tour.highlights.map((item, idx) => (
                <li key={idx} className="flex items-start gap-2 text-sm text-tg-text">
                  <span className="text-yellow-500 mt-0.5">⭐</span>
                  {item}
                </li>
              ))}
            </ul>
          </div>
        )}

        {/* Includes */}
        {tour.includes.length > 0 && (
          <div className="card p-4">
            <h2 className="font-semibold text-tg-text mb-3">{t('includes')}</h2>
            <ul className="space-y-2">
              {tour.includes.map((item, idx) => (
                <li key={idx} className="flex items-start gap-2 text-sm text-tg-text">
                  <span className="text-green-500">✓</span>
                  {item}
                </li>
              ))}
            </ul>
          </div>
        )}

        {/* Excludes */}
        {tour.excludes.length > 0 && (
          <div className="card p-4">
            <h2 className="font-semibold text-tg-text mb-3">{t('excludes')}</h2>
            <ul className="space-y-2">
              {tour.excludes.map((item, idx) => (
                <li key={idx} className="flex items-start gap-2 text-sm text-tg-text">
                  <span className="text-red-500">✗</span>
                  {item}
                </li>
              ))}
            </ul>
          </div>
        )}

        {/* Itinerary */}
        {tour.itinerary.length > 0 && (
          <div className="card p-4">
            <h2 className="font-semibold text-tg-text mb-3">{t('itinerary')}</h2>
            <div className="space-y-3">
              {tour.itinerary.map((item, idx) => (
                <div key={idx} className="flex gap-3">
                  <div className="w-12 text-sm font-medium text-tg-accent-text">
                    {item.time}
                  </div>
                  <div className="flex-1">
                    <p className="text-sm text-tg-text">{item.activity}</p>
                    {item.duration > 0 && (
                      <p className="text-xs text-tg-hint">{item.duration} min</p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Meeting Point */}
        <div className="card p-4">
          <h2 className="font-semibold text-tg-text mb-2">📍 {t('meeting_point')}</h2>
          <p className="text-sm text-tg-text">{tour.meeting_point}</p>
        </div>
      </div>
    </div>
  );
}
