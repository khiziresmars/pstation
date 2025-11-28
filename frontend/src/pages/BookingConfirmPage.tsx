import { useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { format } from 'date-fns';
import { motion } from 'framer-motion';
import { bookingsApi } from '@/services/api';
import { usePrice } from '@/hooks/usePrice';
import { useTelegram } from '@/hooks/useTelegram';
import { Skeleton } from '@/components/common/Skeleton';
import { ErrorState } from '@/components/common/ErrorState';

export default function BookingConfirmPage() {
  const { reference } = useParams<{ reference: string }>();
  const navigate = useNavigate();
  const { t } = useTranslation();
  const { formatPrice } = usePrice();
  const { showMainButton, hideMainButton, hapticImpact, shareUrl } = useTelegram();

  const { data: booking, isLoading, error } = useQuery({
    queryKey: ['booking', reference],
    queryFn: () => bookingsApi.getByReference(reference!),
    enabled: !!reference,
  });

  useEffect(() => {
    if (booking && booking.status === 'pending') {
      showMainButton(t('pay_with_stars'), () => {
        hapticImpact('medium');
        // In real app, this would trigger Telegram Stars payment
        alert('Payment integration would go here');
      });
    } else {
      showMainButton('View Bookings', () => {
        hapticImpact('light');
        navigate('/bookings');
      });
    }

    return () => hideMainButton();
  }, [booking, showMainButton, hideMainButton, hapticImpact, navigate, t]);

  if (isLoading) {
    return (
      <div className="p-4 space-y-4">
        <Skeleton className="h-32 w-full" />
        <Skeleton className="h-48 w-full" />
      </div>
    );
  }

  if (error || !booking) {
    return <ErrorState />;
  }

  const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-700',
    confirmed: 'bg-blue-100 text-blue-700',
    paid: 'bg-green-100 text-green-700',
    completed: 'bg-gray-100 text-gray-700',
    cancelled: 'bg-red-100 text-red-700',
  };

  return (
    <div className="p-4 pb-24 space-y-4">
      {/* Success Animation */}
      <motion.div
        className="text-center py-8"
        initial={{ scale: 0 }}
        animate={{ scale: 1 }}
        transition={{ type: 'spring', duration: 0.5 }}
      >
        <div className="text-6xl mb-4">
          {booking.status === 'paid' ? '✅' : booking.status === 'pending' ? '⏳' : '📋'}
        </div>
        <h1 className="text-2xl font-bold text-tg-text mb-2">
          {booking.status === 'paid' ? t('payment_success') : 'Booking Created!'}
        </h1>
        <p className="text-tg-hint">
          Reference: <span className="font-mono font-semibold">{booking.booking_reference}</span>
        </p>
      </motion.div>

      {/* Booking Details */}
      <div className="card p-4">
        <div className="flex items-center gap-3 mb-4">
          {booking.item_thumbnail && (
            <img
              src={booking.item_thumbnail}
              alt={booking.item_name}
              className="w-16 h-16 rounded-lg object-cover"
            />
          )}
          <div className="flex-1">
            <h2 className="font-semibold text-tg-text">{booking.item_name}</h2>
            <div className="flex items-center gap-2 mt-1">
              <span className={`badge ${statusColors[booking.status]}`}>
                {t(booking.status)}
              </span>
              <span className="text-sm text-tg-hint capitalize">{booking.bookable_type}</span>
            </div>
          </div>
        </div>

        <div className="divider" />

        <div className="space-y-3 text-sm">
          <div className="flex justify-between">
            <span className="text-tg-hint">📅 {t('date')}</span>
            <span className="text-tg-text font-medium">
              {format(new Date(booking.booking_date), 'MMMM d, yyyy')}
            </span>
          </div>

          {booking.start_time && (
            <div className="flex justify-between">
              <span className="text-tg-hint">🕐 {t('time')}</span>
              <span className="text-tg-text font-medium">
                {booking.start_time.slice(0, 5)}
                {booking.duration_hours && ` (${booking.duration_hours}h)`}
              </span>
            </div>
          )}

          <div className="flex justify-between">
            <span className="text-tg-hint">👥 Guests</span>
            <span className="text-tg-text font-medium">
              {booking.adults_count} {t('adults')}
              {booking.children_count > 0 && `, ${booking.children_count} ${t('children')}`}
            </span>
          </div>
        </div>
      </div>

      {/* Price Breakdown */}
      <div className="card p-4">
        <h2 className="font-semibold text-tg-text mb-3">Payment Summary</h2>
        <div className="space-y-2 text-sm">
          <div className="flex justify-between">
            <span className="text-tg-hint">Base Price</span>
            <span className="text-tg-text">{formatPrice(booking.base_price_thb)}</span>
          </div>

          {booking.extras_price_thb > 0 && (
            <div className="flex justify-between">
              <span className="text-tg-hint">Extras</span>
              <span className="text-tg-text">{formatPrice(booking.extras_price_thb)}</span>
            </div>
          )}

          {booking.pickup_fee_thb > 0 && (
            <div className="flex justify-between">
              <span className="text-tg-hint">Pickup</span>
              <span className="text-tg-text">{formatPrice(booking.pickup_fee_thb)}</span>
            </div>
          )}

          {booking.total_discount_thb > 0 && (
            <div className="flex justify-between text-green-600">
              <span>Discounts</span>
              <span>-{formatPrice(booking.total_discount_thb)}</span>
            </div>
          )}

          <div className="divider" />

          <div className="flex justify-between font-semibold">
            <span className="text-tg-text">{t('total')}</span>
            <span className="text-tg-accent-text text-lg">{formatPrice(booking.total_price_thb)}</span>
          </div>

          {booking.cashback_earned_thb > 0 && (
            <div className="flex justify-between text-green-600">
              <span>{t('cashback_earned')}</span>
              <span>+{formatPrice(booking.cashback_earned_thb)}</span>
            </div>
          )}
        </div>
      </div>

      {/* Actions */}
      <div className="space-y-3">
        <button
          onClick={() => navigate('/')}
          className="btn-secondary w-full"
        >
          Back to Home
        </button>

        <button
          onClick={() => shareUrl(`Check out Phuket Yachts!`, `I just booked a trip with Phuket Station!`)}
          className="btn-outline w-full"
        >
          Share with Friends
        </button>
      </div>
    </div>
  );
}
