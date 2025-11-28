import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { format, addDays } from 'date-fns';
import { vesselsApi, toursApi, bookingsApi } from '@/services/api';
import { usePrice } from '@/hooks/usePrice';
import { useTelegram } from '@/hooks/useTelegram';
import { Skeleton } from '@/components/common/Skeleton';
import { ErrorState } from '@/components/common/ErrorState';
import type { BookableType } from '@/types';

export default function BookingPage() {
  const { type, id } = useParams<{ type: BookableType; id: string }>();
  const navigate = useNavigate();
  const { t } = useTranslation();
  const { formatPrice } = usePrice();
  const { showBackButton, hideBackButton, showMainButton, hideMainButton, setMainButtonLoading, hapticImpact, hapticNotification } = useTelegram();

  const [date, setDate] = useState(format(addDays(new Date(), 1), 'yyyy-MM-dd'));
  const [hours, setHours] = useState(8);
  const [startTime, setStartTime] = useState('09:00');
  const [adults, setAdults] = useState(2);
  const [children, setChildren] = useState(0);
  const [pickup, setPickup] = useState(false);
  const [promoCode, setPromoCode] = useState('');
  const [useCashback, setUseCashback] = useState(0);
  const [specialRequests, setSpecialRequests] = useState('');

  const isVessel = type === 'vessel';
  const itemId = parseInt(id!, 10);

  // Fetch item details
  const { data: item, isLoading: itemLoading } = useQuery({
    queryKey: [type, itemId],
    queryFn: () => isVessel ? vesselsApi.getBySlug(id!) : toursApi.getBySlug(id!),
    enabled: !!id,
  });

  // Calculate price
  const { data: calculation, isLoading: calcLoading, refetch: recalculate } = useQuery({
    queryKey: ['booking-calc', type, itemId, date, hours, adults, children, pickup, promoCode, useCashback],
    queryFn: () => bookingsApi.calculate({
      type: type!,
      item_id: itemId,
      date,
      hours: isVessel ? hours : undefined,
      adults,
      children,
      pickup,
      promo_code: promoCode || undefined,
      use_cashback: useCashback,
    }),
    enabled: !!item,
  });

  // Create booking mutation
  const createBooking = useMutation({
    mutationFn: () => bookingsApi.create({
      type: type!,
      item_id: itemId,
      date,
      start_time: isVessel ? startTime : undefined,
      hours: isVessel ? hours : undefined,
      adults,
      children,
      pickup,
      promo_code: promoCode || undefined,
      use_cashback: useCashback,
      special_requests: specialRequests || undefined,
    }),
    onSuccess: (booking) => {
      hapticNotification('success');
      navigate(`/booking/confirm/${booking.booking_reference}`);
    },
    onError: () => {
      hapticNotification('error');
    },
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
    if (item && calculation) {
      showMainButton(`${t('book_now')} - ${formatPrice(calculation.total_thb)}`, () => {
        hapticImpact('medium');
        createBooking.mutate();
      });
    }
  }, [item, calculation, showMainButton, hapticImpact, t, formatPrice, createBooking]);

  useEffect(() => {
    setMainButtonLoading(createBooking.isPending);
  }, [createBooking.isPending, setMainButtonLoading]);

  if (itemLoading) {
    return (
      <div className="p-4 space-y-4">
        <Skeleton className="h-20 w-full" />
        <Skeleton className="h-40 w-full" />
        <Skeleton className="h-40 w-full" />
      </div>
    );
  }

  if (!item) {
    return <ErrorState />;
  }

  return (
    <div className="pb-24">
      {/* Header */}
      <div className="bg-tg-section-bg border-b border-tg-secondary-bg p-4">
        <div className="flex items-center gap-3">
          <img
            src={(item as any).thumbnail || (item as any).images?.[0]}
            alt={(item as any).name || (item as any).name_en}
            className="w-16 h-16 rounded-lg object-cover"
          />
          <div>
            <h1 className="font-semibold text-tg-text">
              {(item as any).name || (item as any).name_en}
            </h1>
            <p className="text-sm text-tg-hint capitalize">{type}</p>
          </div>
        </div>
      </div>

      <div className="p-4 space-y-4">
        {/* Date Selection */}
        <div className="card p-4">
          <h2 className="font-semibold text-tg-text mb-3">{t('select_date')}</h2>
          <input
            type="date"
            value={date}
            min={format(addDays(new Date(), 1), 'yyyy-MM-dd')}
            max={format(addDays(new Date(), 90), 'yyyy-MM-dd')}
            onChange={(e) => setDate(e.target.value)}
            className="input"
          />
        </div>

        {/* Time & Duration (Vessels only) */}
        {isVessel && (
          <div className="card p-4">
            <h2 className="font-semibold text-tg-text mb-3">{t('select_time')}</h2>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="text-sm text-tg-hint">Start Time</label>
                <select
                  value={startTime}
                  onChange={(e) => setStartTime(e.target.value)}
                  className="input"
                >
                  {['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00'].map((time) => (
                    <option key={time} value={time}>{time}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="text-sm text-tg-hint">{t('rental_hours')}</label>
                <select
                  value={hours}
                  onChange={(e) => setHours(parseInt(e.target.value))}
                  className="input"
                >
                  {[4, 5, 6, 7, 8, 10, 12, 24].map((h) => (
                    <option key={h} value={h}>{h} {t('hours')}</option>
                  ))}
                </select>
              </div>
            </div>
          </div>
        )}

        {/* Guests */}
        <div className="card p-4">
          <h2 className="font-semibold text-tg-text mb-3">{t('number_of_guests')}</h2>
          <div className="space-y-3">
            <div className="flex items-center justify-between">
              <span className="text-tg-text">{t('adults')}</span>
              <div className="flex items-center gap-3">
                <button
                  onClick={() => setAdults(Math.max(1, adults - 1))}
                  className="w-8 h-8 rounded-full bg-tg-secondary-bg flex items-center justify-center"
                >
                  -
                </button>
                <span className="w-8 text-center font-semibold">{adults}</span>
                <button
                  onClick={() => setAdults(Math.min(20, adults + 1))}
                  className="w-8 h-8 rounded-full bg-tg-secondary-bg flex items-center justify-center"
                >
                  +
                </button>
              </div>
            </div>

            <div className="flex items-center justify-between">
              <span className="text-tg-text">{t('children')}</span>
              <div className="flex items-center gap-3">
                <button
                  onClick={() => setChildren(Math.max(0, children - 1))}
                  className="w-8 h-8 rounded-full bg-tg-secondary-bg flex items-center justify-center"
                >
                  -
                </button>
                <span className="w-8 text-center font-semibold">{children}</span>
                <button
                  onClick={() => setChildren(Math.min(10, children + 1))}
                  className="w-8 h-8 rounded-full bg-tg-secondary-bg flex items-center justify-center"
                >
                  +
                </button>
              </div>
            </div>
          </div>
        </div>

        {/* Pickup (Tours only) */}
        {!isVessel && (item as any).pickup_available && (
          <div className="card p-4">
            <label className="flex items-center justify-between cursor-pointer">
              <div>
                <span className="text-tg-text">🚐 {t('pickup_available')}</span>
                <p className="text-sm text-tg-hint">+{formatPrice((item as any).pickup_fee_thb)}</p>
              </div>
              <input
                type="checkbox"
                checked={pickup}
                onChange={(e) => setPickup(e.target.checked)}
                className="w-5 h-5 accent-tg-button"
              />
            </label>
          </div>
        )}

        {/* Promo Code */}
        <div className="card p-4">
          <h2 className="font-semibold text-tg-text mb-3">{t('promo_code')}</h2>
          <div className="flex gap-2">
            <input
              type="text"
              value={promoCode}
              onChange={(e) => setPromoCode(e.target.value.toUpperCase())}
              placeholder="Enter code"
              className="input flex-1"
            />
            <button
              onClick={() => recalculate()}
              className="btn-primary"
            >
              {t('apply')}
            </button>
          </div>
          {calculation?.promo && 'discount' in calculation.promo && (
            <p className="text-sm text-green-600 mt-2">
              ✓ -{formatPrice(calculation.promo.discount)} discount applied!
            </p>
          )}
        </div>

        {/* Cashback */}
        {calculation?.cashback && calculation.cashback.available > 0 && (
          <div className="card p-4">
            <h2 className="font-semibold text-tg-text mb-3">{t('use_cashback')}</h2>
            <p className="text-sm text-tg-hint mb-2">
              {t('available_cashback')}: {formatPrice(calculation.cashback.available)}
            </p>
            <input
              type="range"
              min={0}
              max={calculation.cashback.max_usage}
              value={useCashback}
              onChange={(e) => setUseCashback(parseFloat(e.target.value))}
              className="w-full"
            />
            <p className="text-sm text-tg-accent-text mt-1">
              Using: {formatPrice(useCashback)}
            </p>
          </div>
        )}

        {/* Special Requests */}
        <div className="card p-4">
          <h2 className="font-semibold text-tg-text mb-3">{t('special_requests')}</h2>
          <textarea
            value={specialRequests}
            onChange={(e) => setSpecialRequests(e.target.value)}
            placeholder="Any special requirements..."
            rows={3}
            className="input resize-none"
          />
        </div>

        {/* Price Summary */}
        {calculation && (
          <div className="card p-4">
            <h2 className="font-semibold text-tg-text mb-3">Price Summary</h2>
            <div className="space-y-2 text-sm">
              <div className="flex justify-between">
                <span className="text-tg-hint">{t('subtotal')}</span>
                <span className="text-tg-text">{formatPrice(calculation.pricing.subtotal_thb)}</span>
              </div>

              {calculation.discounts.total > 0 && (
                <div className="flex justify-between text-green-600">
                  <span>{t('discount')}</span>
                  <span>-{formatPrice(calculation.discounts.total)}</span>
                </div>
              )}

              <div className="divider" />

              <div className="flex justify-between font-semibold">
                <span className="text-tg-text">{t('total')}</span>
                <span className="text-tg-accent-text text-lg">{formatPrice(calculation.total_thb)}</span>
              </div>

              <div className="flex justify-between text-green-600">
                <span>{t('cashback_earned')}</span>
                <span>+{formatPrice(calculation.cashback.will_earn)}</span>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
