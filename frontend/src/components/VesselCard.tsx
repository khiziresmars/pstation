import { useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { usePrice } from '@/hooks/usePrice';
import { useTelegram } from '@/hooks/useTelegram';
import { useAppStore } from '@/store/appStore';
import { Rating } from '@/components/common/Rating';
import type { Vessel } from '@/types';

interface VesselCardProps {
  vessel: Vessel;
}

export function VesselCard({ vessel }: VesselCardProps) {
  const navigate = useNavigate();
  const { t } = useTranslation();
  const { formatPrice } = usePrice();
  const { hapticImpact } = useTelegram();
  const { isFavorite, addFavorite, removeFavorite } = useAppStore();

  const handleClick = () => {
    hapticImpact('light');
    navigate(`/vessels/${vessel.slug}`);
  };

  const handleFavorite = (e: React.MouseEvent) => {
    e.stopPropagation();
    hapticImpact('medium');

    if (isFavorite('vessels', vessel.id)) {
      removeFavorite('vessels', vessel.id);
    } else {
      addFavorite('vessels', vessel.id);
    }
  };

  const typeLabels: Record<string, string> = {
    yacht: t('yacht'),
    speedboat: t('speedboat'),
    catamaran: t('catamaran'),
    sailboat: t('sailboat'),
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
          src={vessel.thumbnail || vessel.images[0]}
          alt={vessel.name}
          className="w-full h-full object-cover"
          loading="lazy"
        />

        {/* Featured badge */}
        {vessel.is_featured && (
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
            {isFavorite('vessels', vessel.id) ? '❤️' : '🤍'}
          </span>
        </button>

        {/* Type badge */}
        <div className="absolute bottom-2 left-2 badge bg-white/90 text-gray-800">
          {typeLabels[vessel.type] || vessel.type}
        </div>
      </div>

      {/* Content */}
      <div className="p-3">
        <h3 className="font-semibold text-tg-text line-clamp-1">{vessel.name}</h3>

        <div className="flex items-center gap-3 mt-1 text-sm text-tg-hint">
          <span>👥 {vessel.capacity} {t('guests')}</span>
          <span>📏 {vessel.length_meters}m</span>
        </div>

        {/* Rating */}
        {vessel.rating > 0 && (
          <div className="mt-2">
            <Rating value={vessel.rating} count={vessel.reviews_count} size="sm" />
          </div>
        )}

        {/* Price & Features */}
        <div className="flex items-center justify-between mt-3">
          <div>
            <span className="price-tag">{formatPrice(vessel.price_per_day_thb)}</span>
            <span className="text-xs text-tg-hint">{t('per_day')}</span>
          </div>

          <div className="flex gap-1">
            {vessel.captain_included && (
              <span className="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded">
                👨‍✈️
              </span>
            )}
            {vessel.fuel_included && (
              <span className="text-xs bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded">
                ⛽
              </span>
            )}
          </div>
        </div>
      </div>
    </motion.div>
  );
}
