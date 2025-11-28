import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { vesselsApi, toursApi } from '@/services/api';
import { VesselCard } from '@/components/VesselCard';
import { TourCard } from '@/components/TourCard';
import { CardSkeleton } from '@/components/common/Skeleton';
import { ErrorState } from '@/components/common/ErrorState';

export default function HomePage() {
  const navigate = useNavigate();
  const { t } = useTranslation();

  const { data: featuredVessels, isLoading: vesselsLoading, error: vesselsError } = useQuery({
    queryKey: ['featuredVessels'],
    queryFn: () => vesselsApi.getFeatured(4),
  });

  const { data: featuredTours, isLoading: toursLoading, error: toursError } = useQuery({
    queryKey: ['featuredTours'],
    queryFn: () => toursApi.getFeatured(4),
  });

  return (
    <div className="pb-4">
      {/* Hero Section */}
      <div className="relative h-48 bg-gradient-to-br from-ocean-600 to-ocean-800 overflow-hidden">
        <div
          className="absolute inset-0 bg-cover bg-center opacity-30"
          style={{
            backgroundImage: 'url(https://images.unsplash.com/photo-1567899378494-47b22a2ae96a?w=800)',
          }}
        />
        <div className="relative z-10 flex flex-col items-center justify-center h-full text-white text-center px-4">
          <motion.h1
            className="text-2xl font-bold mb-2"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
          >
            {t('welcome')} 🌴
          </motion.h1>
          <motion.p
            className="text-sm opacity-90"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.1 }}
          >
            {t('tagline')}
          </motion.p>
        </div>
      </div>

      {/* Quick Actions */}
      <div className="px-4 -mt-6 relative z-20">
        <div className="grid grid-cols-2 gap-3">
          <motion.button
            className="card p-4 flex items-center gap-3"
            onClick={() => navigate('/vessels')}
            whileTap={{ scale: 0.98 }}
          >
            <span className="text-3xl">🚤</span>
            <div className="text-left">
              <div className="font-semibold text-tg-text">{t('vessels')}</div>
              <div className="text-xs text-tg-hint">Yachts & Boats</div>
            </div>
          </motion.button>

          <motion.button
            className="card p-4 flex items-center gap-3"
            onClick={() => navigate('/tours')}
            whileTap={{ scale: 0.98 }}
          >
            <span className="text-3xl">🏝️</span>
            <div className="text-left">
              <div className="font-semibold text-tg-text">{t('tours')}</div>
              <div className="text-xs text-tg-hint">Island Adventures</div>
            </div>
          </motion.button>
        </div>
      </div>

      {/* Featured Vessels */}
      <section className="mt-6 px-4">
        <div className="flex items-center justify-between mb-3">
          <h2 className="section-title mb-0">{t('featured_vessels')}</h2>
          <button
            onClick={() => navigate('/vessels')}
            className="text-sm text-tg-link font-medium"
          >
            {t('view_all')} →
          </button>
        </div>

        {vesselsError ? (
          <ErrorState />
        ) : vesselsLoading ? (
          <div className="grid grid-cols-1 gap-4">
            <CardSkeleton />
            <CardSkeleton />
          </div>
        ) : (
          <div className="grid grid-cols-1 gap-4">
            {featuredVessels?.map((vessel) => (
              <VesselCard key={vessel.id} vessel={vessel} />
            ))}
          </div>
        )}
      </section>

      {/* Featured Tours */}
      <section className="mt-6 px-4">
        <div className="flex items-center justify-between mb-3">
          <h2 className="section-title mb-0">{t('featured_tours')}</h2>
          <button
            onClick={() => navigate('/tours')}
            className="text-sm text-tg-link font-medium"
          >
            {t('view_all')} →
          </button>
        </div>

        {toursError ? (
          <ErrorState />
        ) : toursLoading ? (
          <div className="grid grid-cols-1 gap-4">
            <CardSkeleton />
            <CardSkeleton />
          </div>
        ) : (
          <div className="grid grid-cols-1 gap-4">
            {featuredTours?.map((tour) => (
              <TourCard key={tour.id} tour={tour} />
            ))}
          </div>
        )}
      </section>

      {/* Promo Banner */}
      <section className="mt-6 px-4">
        <motion.div
          className="card bg-gradient-to-r from-purple-500 to-pink-500 p-4 text-white"
          initial={{ opacity: 0, scale: 0.95 }}
          animate={{ opacity: 1, scale: 1 }}
        >
          <div className="flex items-center gap-3">
            <span className="text-4xl">🎁</span>
            <div>
              <h3 className="font-bold">Get 5% Cashback!</h3>
              <p className="text-sm opacity-90">On every booking. Invite friends & earn more!</p>
            </div>
          </div>
        </motion.div>
      </section>
    </div>
  );
}
