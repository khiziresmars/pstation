import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { format } from 'date-fns';
import { userApi } from '@/services/api';
import { usePrice } from '@/hooks/usePrice';
import { useTelegram } from '@/hooks/useTelegram';
import { ListItemSkeleton } from '@/components/common/Skeleton';
import { ErrorState } from '@/components/common/ErrorState';
import { EmptyState } from '@/components/common/EmptyState';

export default function BookingsHistoryPage() {
  const navigate = useNavigate();
  const { t } = useTranslation();
  const { formatPrice } = usePrice();
  const { showBackButton, hideBackButton, hapticImpact } = useTelegram();

  const { data, isLoading, error, refetch } = useQuery({
    queryKey: ['userBookings'],
    queryFn: () => userApi.getBookings(),
  });

  useEffect(() => {
    showBackButton(() => {
      hideBackButton();
      navigate(-1);
    });

    return () => hideBackButton();
  }, [showBackButton, hideBackButton, navigate]);

  const handleBookingClick = (reference: string) => {
    hapticImpact('light');
    navigate(`/booking/confirm/${reference}`);
  };

  const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-700',
    confirmed: 'bg-blue-100 text-blue-700',
    paid: 'bg-green-100 text-green-700',
    completed: 'bg-gray-100 text-gray-700',
    cancelled: 'bg-red-100 text-red-700',
  };

  if (isLoading) {
    return (
      <div className="p-4">
        <h1 className="text-xl font-bold text-tg-text mb-4">{t('bookings')}</h1>
        {[1, 2, 3].map((i) => (
          <ListItemSkeleton key={i} />
        ))}
      </div>
    );
  }

  if (error) {
    return <ErrorState onRetry={() => refetch()} />;
  }

  const bookings = data?.data || [];

  return (
    <div className="p-4">
      <h1 className="text-xl font-bold text-tg-text mb-4">{t('bookings')}</h1>

      {!bookings.length ? (
        <EmptyState
          icon="📋"
          title="No bookings yet"
          message="Your booking history will appear here"
          action={
            <button onClick={() => navigate('/')} className="btn-primary">
              Book Now
            </button>
          }
        />
      ) : (
        <div className="space-y-3">
          {bookings.map((booking) => (
            <div
              key={booking.id}
              onClick={() => handleBookingClick(booking.booking_reference)}
              className="card p-3 cursor-pointer active:scale-98"
            >
              <div className="flex items-center gap-3">
                {booking.item_thumbnail && (
                  <img
                    src={booking.item_thumbnail}
                    alt={booking.item_name}
                    className="w-16 h-16 rounded-lg object-cover"
                  />
                )}
                <div className="flex-1 min-w-0">
                  <h3 className="font-medium text-tg-text truncate">{booking.item_name}</h3>
                  <p className="text-sm text-tg-hint">
                    {format(new Date(booking.booking_date), 'MMM d, yyyy')}
                  </p>
                  <div className="flex items-center gap-2 mt-1">
                    <span className={`badge text-xs ${statusColors[booking.status]}`}>
                      {t(booking.status)}
                    </span>
                    <span className="text-xs text-tg-hint font-mono">
                      {booking.booking_reference}
                    </span>
                  </div>
                </div>
                <div className="text-right">
                  <p className="font-semibold text-tg-accent-text">
                    {formatPrice(booking.total_price_thb)}
                  </p>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
