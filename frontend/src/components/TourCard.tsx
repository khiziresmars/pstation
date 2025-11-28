import { useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { usePrice } from '@/hooks/usePrice';
import { useTelegram } from '@/hooks/useTelegram';
import { useAppStore } from '@/store/appStore';
import { Rating } from '@/components/common/Rating';
import type { Tour } from '@/types';

interface TourCardProps {
  tour: Tour;
}

export function TourCard({ tour }: TourCardProps) {
  const navigate = useNavigate();
  const { t, i18n } = useTranslation();
  const { formatPrice } = usePrice();
  const { hapticImpact } = useTelegram();
  const { isFavorite, addFavorite, removeFavorite } = useAppStore();

  const handleClick = () => {
    hapticImpact('light');
    navigate(`/tours/${tour.slug}`);
  };

  const handleFavorite = (e: React.MouseEvent) => {
    e.stopPropagation();
    hapticImpact('medium');

    if (isFavorite('tours', tour.id)) {
      removeFavorite('tours', tour.id);
    } else {
      addFavorite('tours', tour.id);
    }
  };

  // Get localized name
  const getName = () => {
    const lang = i18n.language;
    if (lang === 'ru' && tour.name_ru) return tour.name_ru;
    if (lang === 'th' && tour.name_th) return tour.name_th;
    return tour.name_en;
  };

  const categoryIcons: Record<string, string> = {
    islands: '🏝️',
    snorkeling: '🤿',
    fishing: '🎣',
    sunset: '🌅',
    adventure: '🚀',
    private: '👑',
  };

  return (
    <motion.div
      className="card cursor-pointer"
      onClick={handleClick}
      whileTap={{ scale: 0.98 }}
    >
      {/* Image */}
      <div className="relative aspect-video">
        <img
          src={tour.thumbnail || tour.images[0]}
          alt={getName()}
          className="w-full h-full object-cover"
          loading="lazy"
        />

        {/* Featured badge */}
        {tour.is_featured && (
          <div className="absolute top-2 left-2 badge-primary">
            ⭐ Featured
          </div>
        )}

        {/* Favorite button */}
        <button
          onClick={handleFavorite}
          className="absolute top-2 right-2 w-8 h-8 rounded-full bg-black/30 flex items-center justify-center"
        >
          <span className="text-lg">
            {isFavorite('tours', tour.id) ? '❤️' : '🤍'}
          </span>
        </button>

        {/* Duration badge */}
        <div className="absolute bottom-2 left-2 badge bg-white/90 text-gray-800">
          ⏱️ {tour.duration_hours}h
        </div>

        {/* Category icon */}
        <div className="absolute bottom-2 right-2">
          <span className="text-2xl drop-shadow-lg">
            {categoryIcons[tour.category] || '🌊'}
          </span>
        </div>
      </div>

      {/* Content */}
      <div className="p-3">
        <h3 className="font-semibold text-tg-text line-clamp-1">{getName()}</h3>

        <div className="flex items-center gap-3 mt-1 text-sm text-tg-hint">
          <span>🕐 {tour.departure_time.slice(0, 5)}</span>
          <span>👥 max {tour.max_participants}</span>
        </div>

        {/* Rating */}
        {tour.rating > 0 && (
          <div className="mt-2">
            <Rating value={tour.rating} count={tour.reviews_count} size="sm" />
          </div>
        )}

        {/* Price */}
        <div className="flex items-center justify-between mt-3">
          <div>
            <span className="text-xs text-tg-hint">{t('from')} </span>
            <span className="price-tag">{formatPrice(tour.price_adult_thb)}</span>
            <span className="text-xs text-tg-hint">/{t('adult')}</span>
          </div>

          {tour.pickup_available && (
            <span className="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded">
              🚐 Pickup
            </span>
          )}
        </div>
      </div>
    </motion.div>
  );
}
