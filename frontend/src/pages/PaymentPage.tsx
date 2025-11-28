import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { bookingsApi, paymentsApi } from '@/services/api';
import { usePrice } from '@/hooks/usePrice';
import { useTelegram } from '@/hooks/useTelegram';
import { Skeleton } from '@/components/common/Skeleton';
import { ErrorState } from '@/components/common/ErrorState';

declare global {
  interface Window {
    Telegram?: {
      WebApp: {
        openInvoice: (invoiceId: string, callback: (status: string) => void) => void;
      };
    };
  }
}

const CRYPTO_CURRENCIES = [
  { id: 'btc', name: 'Bitcoin', symbol: 'BTC', icon: '₿', color: '#F7931A' },
  { id: 'eth', name: 'Ethereum', symbol: 'ETH', icon: 'Ξ', color: '#627EEA' },
  { id: 'usdt', name: 'Tether', symbol: 'USDT', icon: '₮', color: '#26A17B' },
  { id: 'usdc', name: 'USD Coin', symbol: 'USDC', icon: '$', color: '#2775CA' },
  { id: 'ltc', name: 'Litecoin', symbol: 'LTC', icon: 'Ł', color: '#BFBBBB' },
  { id: 'trx', name: 'Tron', symbol: 'TRX', icon: '◈', color: '#FF0013' },
];

type PaymentMethod = 'card' | 'crypto' | 'telegram_stars' | 'promptpay' | 'yookassa';

export default function PaymentPage() {
  const { reference } = useParams<{ reference: string }>();
  const navigate = useNavigate();
  const { t, i18n } = useTranslation();
  const { formatPrice } = usePrice();
  const { hapticImpact, hapticNotification, showBackButton, hideBackButton, hideMainButton } = useTelegram();

  const [selectedMethod, setSelectedMethod] = useState<PaymentMethod | null>(null);
  const [selectedCrypto, setSelectedCrypto] = useState('btc');
  const [cryptoPayment, setCryptoPayment] = useState<any>(null);
  const [promptPayPayment, setPromptPayPayment] = useState<any>(null);
  const [isProcessing, setIsProcessing] = useState(false);

  // Fetch booking details
  const { data: booking, isLoading: bookingLoading } = useQuery({
    queryKey: ['booking', reference],
    queryFn: () => bookingsApi.getByReference(reference!),
    enabled: !!reference,
  });

  // Fetch available payment methods (for future use)
  const { data: _methods } = useQuery({
    queryKey: ['payment-methods'],
    queryFn: () => paymentsApi.getMethods(),
  });

  // Stripe payment mutation
  const stripeMutation = useMutation({
    mutationFn: () => paymentsApi.createStripeIntent(reference!),
    onSuccess: (data) => {
      // In production, this would use Stripe.js to handle the payment
      // For now, redirect to a mock Stripe checkout
      window.location.href = `https://checkout.stripe.com/pay/${data.client_secret}`;
    },
    onError: () => {
      hapticNotification('error');
      setIsProcessing(false);
    },
  });

  // Crypto payment mutation
  const cryptoMutation = useMutation({
    mutationFn: (currency: string) => paymentsApi.createCryptoPayment(reference!, currency),
    onSuccess: (data) => {
      setCryptoPayment(data);
      hapticNotification('success');
      setIsProcessing(false);
    },
    onError: () => {
      hapticNotification('error');
      setIsProcessing(false);
    },
  });

  // Telegram Stars mutation
  const starsMutation = useMutation({
    mutationFn: () => paymentsApi.createTelegramStars(reference!),
    onSuccess: (data) => {
      // Use Telegram WebApp API to open invoice
      if (window.Telegram?.WebApp) {
        window.Telegram.WebApp.openInvoice(data.invoice_id, (status) => {
          if (status === 'paid') {
            hapticNotification('success');
            navigate(`/booking/success/${reference}`);
          } else {
            hapticNotification('error');
          }
          setIsProcessing(false);
        });
      }
    },
    onError: () => {
      hapticNotification('error');
      setIsProcessing(false);
    },
  });

  // PromptPay mutation
  const promptPayMutation = useMutation({
    mutationFn: () => paymentsApi.createPromptPay(reference!),
    onSuccess: (data) => {
      setPromptPayPayment(data);
      hapticNotification('success');
      setIsProcessing(false);
    },
    onError: () => {
      hapticNotification('error');
      setIsProcessing(false);
    },
  });

  // YooKassa mutation
  const yooKassaMutation = useMutation({
    mutationFn: () => paymentsApi.createYooKassa(reference!),
    onSuccess: (data) => {
      if (data.confirmation_url) {
        window.location.href = data.confirmation_url;
      }
    },
    onError: () => {
      hapticNotification('error');
      setIsProcessing(false);
    },
  });

  // Check crypto payment status
  const { data: cryptoStatus, refetch: recheckCryptoStatus } = useQuery({
    queryKey: ['crypto-status', cryptoPayment?.payment_id],
    queryFn: () => paymentsApi.getCryptoStatus(cryptoPayment.payment_id),
    enabled: !!cryptoPayment?.payment_id,
    refetchInterval: 10000, // Check every 10 seconds
  });

  useEffect(() => {
    if (cryptoStatus?.status === 'finished' || cryptoStatus?.status === 'confirmed') {
      hapticNotification('success');
      navigate(`/booking/success/${reference}`);
    }
  }, [cryptoStatus, navigate, reference, hapticNotification]);

  useEffect(() => {
    showBackButton(() => {
      if (cryptoPayment) {
        setCryptoPayment(null);
      } else if (promptPayPayment) {
        setPromptPayPayment(null);
      } else {
        hideBackButton();
        navigate(-1);
      }
    });

    return () => {
      hideBackButton();
      hideMainButton();
    };
  }, [cryptoPayment, promptPayPayment, showBackButton, hideBackButton, hideMainButton, navigate]);

  const handlePayment = () => {
    if (!selectedMethod) return;

    setIsProcessing(true);
    hapticImpact('medium');

    switch (selectedMethod) {
      case 'card':
        stripeMutation.mutate();
        break;
      case 'crypto':
        cryptoMutation.mutate(selectedCrypto);
        break;
      case 'telegram_stars':
        starsMutation.mutate();
        break;
      case 'promptpay':
        promptPayMutation.mutate();
        break;
      case 'yookassa':
        yooKassaMutation.mutate();
        break;
    }
  };

  const copyToClipboard = (text: string) => {
    navigator.clipboard.writeText(text);
    hapticNotification('success');
  };

  if (bookingLoading) {
    return (
      <div className="p-4 space-y-4">
        <Skeleton className="h-32 w-full" />
        <Skeleton className="h-48 w-full" />
      </div>
    );
  }

  if (!booking) {
    return <ErrorState />;
  }

  // Crypto payment view
  if (cryptoPayment) {
    const expiresAt = new Date(cryptoPayment.expires_at);
    const minutesLeft = Math.max(0, Math.round((expiresAt.getTime() - Date.now()) / 60000));

    return (
      <div className="p-4 space-y-6">
        <div className="text-center">
          <h1 className="text-xl font-bold text-tg-text">{t('crypto_payment')}</h1>
          <p className="text-tg-hint mt-1">{t('send_exact_amount')}</p>
        </div>

        {/* QR Code */}
        <div className="flex justify-center">
          <div className="bg-white p-4 rounded-2xl">
            <img
              src={cryptoPayment.qr_code}
              alt="Payment QR Code"
              className="w-48 h-48"
            />
          </div>
        </div>

        {/* Payment details */}
        <div className="card p-4 space-y-4">
          <div>
            <label className="text-sm text-tg-hint">{t('amount_to_send')}</label>
            <div className="flex items-center justify-between mt-1">
              <span className="text-2xl font-bold text-tg-text">
                {cryptoPayment.pay_amount} {cryptoPayment.pay_currency.toUpperCase()}
              </span>
              <button
                onClick={() => copyToClipboard(cryptoPayment.pay_amount.toString())}
                className="text-tg-link text-sm"
              >
                {t('copy')}
              </button>
            </div>
          </div>

          <div>
            <label className="text-sm text-tg-hint">{t('send_to_address')}</label>
            <div className="flex items-center gap-2 mt-1">
              <code className="flex-1 text-sm bg-tg-secondary-bg p-2 rounded break-all">
                {cryptoPayment.pay_address}
              </code>
              <button
                onClick={() => copyToClipboard(cryptoPayment.pay_address)}
                className="btn-secondary px-3 py-2"
              >
                📋
              </button>
            </div>
          </div>

          <div className="flex items-center justify-between text-sm">
            <span className="text-tg-hint">{t('time_remaining')}</span>
            <span className={`font-medium ${minutesLeft < 5 ? 'text-red-500' : 'text-tg-text'}`}>
              {minutesLeft} {t('minutes')}
            </span>
          </div>

          {/* Status */}
          <div className="flex items-center gap-3 p-3 bg-tg-secondary-bg rounded-xl">
            <div className="animate-pulse w-3 h-3 rounded-full bg-yellow-500" />
            <div>
              <span className="text-tg-text font-medium">{t('waiting_for_payment')}</span>
              <p className="text-xs text-tg-hint">
                {cryptoStatus?.status || 'pending'}
              </p>
            </div>
          </div>
        </div>

        <button
          onClick={() => recheckCryptoStatus()}
          className="w-full btn-secondary py-4"
        >
          {t('check_status')}
        </button>

        <a
          href={cryptoPayment.payment_url}
          target="_blank"
          rel="noopener noreferrer"
          className="block w-full text-center text-tg-link text-sm"
        >
          {t('open_in_wallet')} →
        </a>
      </div>
    );
  }

  // PromptPay payment view
  if (promptPayPayment) {
    const expiresAt = new Date(promptPayPayment.expires_at);
    const minutesLeft = Math.max(0, Math.round((expiresAt.getTime() - Date.now()) / 60000));
    const currentLang = i18n.language as 'en' | 'ru' | 'th';

    return (
      <div className="p-4 space-y-6">
        <div className="text-center">
          <h1 className="text-xl font-bold text-tg-text">{t('promptpay')}</h1>
          <p className="text-tg-hint mt-1">
            {promptPayPayment.instructions?.[currentLang] || promptPayPayment.instructions?.en}
          </p>
        </div>

        {/* QR Code */}
        <div className="flex justify-center">
          <div className="bg-white p-4 rounded-2xl shadow-lg">
            <img
              src={promptPayPayment.qr_image_url}
              alt="PromptPay QR Code"
              className="w-56 h-56"
            />
          </div>
        </div>

        {/* Payment details */}
        <div className="card p-4 space-y-4">
          <div>
            <label className="text-sm text-tg-hint">{t('amount_to_pay')}</label>
            <div className="flex items-center justify-between mt-1">
              <span className="text-2xl font-bold text-tg-text">
                ฿{promptPayPayment.amount.toLocaleString()}
              </span>
              <button
                onClick={() => copyToClipboard(promptPayPayment.amount.toString())}
                className="text-tg-link text-sm"
              >
                {t('copy')}
              </button>
            </div>
          </div>

          <div>
            <label className="text-sm text-tg-hint">{t('payment_recipient')}</label>
            <p className="font-medium text-tg-text mt-1">{promptPayPayment.account_name}</p>
            <p className="text-sm text-tg-hint">{promptPayPayment.account_id_masked}</p>
          </div>

          <div className="flex items-center justify-between text-sm">
            <span className="text-tg-hint">{t('time_remaining')}</span>
            <span className={`font-medium ${minutesLeft < 5 ? 'text-red-500' : 'text-tg-text'}`}>
              {minutesLeft} {t('minutes')}
            </span>
          </div>

          {/* Status */}
          <div className="flex items-center gap-3 p-3 bg-yellow-50 border border-yellow-200 rounded-xl">
            <div className="animate-pulse w-3 h-3 rounded-full bg-yellow-500" />
            <div>
              <span className="text-yellow-800 font-medium">{t('waiting_for_payment')}</span>
              <p className="text-xs text-yellow-600">
                {t('payment_will_be_confirmed_manually')}
              </p>
            </div>
          </div>
        </div>

        <p className="text-center text-sm text-tg-hint">
          {t('promptpay_note')}
        </p>
      </div>
    );
  }

  return (
    <div className="pb-24">
      {/* Booking Summary */}
      <div className="bg-gradient-to-r from-blue-500 to-purple-500 p-6 text-white">
        <h1 className="text-xl font-bold">{t('complete_payment')}</h1>
        <div className="mt-4 flex items-center justify-between">
          <div>
            <p className="text-white/80 text-sm">{booking.item_name}</p>
            <p className="text-white/60 text-xs">{booking.booking_date}</p>
          </div>
          <div className="text-right">
            <p className="text-2xl font-bold">{formatPrice(booking.total_price_thb)}</p>
            <p className="text-white/60 text-xs">#{booking.booking_reference}</p>
          </div>
        </div>
      </div>

      <div className="p-4 space-y-4">
        <h2 className="font-semibold text-tg-text">{t('select_payment_method')}</h2>

        {/* Card Payment */}
        <button
          onClick={() => {
            setSelectedMethod('card');
            hapticImpact('light');
          }}
          className={`w-full card p-4 flex items-center gap-4 transition-all ${
            selectedMethod === 'card' ? 'ring-2 ring-tg-button' : ''
          }`}
        >
          <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-2xl">
            💳
          </div>
          <div className="flex-1 text-left">
            <h3 className="font-semibold text-tg-text">{t('credit_debit_card')}</h3>
            <p className="text-sm text-tg-hint">Visa, Mastercard, American Express</p>
          </div>
          <div className={`w-6 h-6 rounded-full border-2 flex items-center justify-center ${
            selectedMethod === 'card' ? 'border-tg-button bg-tg-button text-white' : 'border-tg-hint'
          }`}>
            {selectedMethod === 'card' && '✓'}
          </div>
        </button>

        {/* Crypto Payment */}
        <button
          onClick={() => {
            setSelectedMethod('crypto');
            hapticImpact('light');
          }}
          className={`w-full card p-4 flex items-center gap-4 transition-all ${
            selectedMethod === 'crypto' ? 'ring-2 ring-tg-button' : ''
          }`}
        >
          <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-500 to-yellow-500 flex items-center justify-center text-white text-2xl">
            ₿
          </div>
          <div className="flex-1 text-left">
            <h3 className="font-semibold text-tg-text">{t('cryptocurrency')}</h3>
            <p className="text-sm text-tg-hint">BTC, ETH, USDT, USDC, LTC, TRX</p>
          </div>
          <div className={`w-6 h-6 rounded-full border-2 flex items-center justify-center ${
            selectedMethod === 'crypto' ? 'border-tg-button bg-tg-button text-white' : 'border-tg-hint'
          }`}>
            {selectedMethod === 'crypto' && '✓'}
          </div>
        </button>

        {/* Crypto currency selection */}
        {selectedMethod === 'crypto' && (
          <div className="card p-4">
            <h3 className="font-medium text-tg-text mb-3">{t('select_currency')}</h3>
            <div className="grid grid-cols-3 gap-2">
              {CRYPTO_CURRENCIES.map((crypto) => (
                <button
                  key={crypto.id}
                  onClick={() => {
                    setSelectedCrypto(crypto.id);
                    hapticImpact('light');
                  }}
                  className={`p-3 rounded-xl flex flex-col items-center gap-1 transition-all ${
                    selectedCrypto === crypto.id
                      ? 'bg-tg-button text-tg-button-text'
                      : 'bg-tg-secondary-bg text-tg-text'
                  }`}
                >
                  <span className="text-xl" style={{ color: selectedCrypto === crypto.id ? undefined : crypto.color }}>
                    {crypto.icon}
                  </span>
                  <span className="text-xs font-medium">{crypto.symbol}</span>
                </button>
              ))}
            </div>
          </div>
        )}

        {/* Telegram Stars */}
        <button
          onClick={() => {
            setSelectedMethod('telegram_stars');
            hapticImpact('light');
          }}
          className={`w-full card p-4 flex items-center gap-4 transition-all ${
            selectedMethod === 'telegram_stars' ? 'ring-2 ring-tg-button' : ''
          }`}
        >
          <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-2xl">
            ⭐
          </div>
          <div className="flex-1 text-left">
            <h3 className="font-semibold text-tg-text">Telegram Stars</h3>
            <p className="text-sm text-tg-hint">{t('pay_with_telegram')}</p>
          </div>
          <div className={`w-6 h-6 rounded-full border-2 flex items-center justify-center ${
            selectedMethod === 'telegram_stars' ? 'border-tg-button bg-tg-button text-white' : 'border-tg-hint'
          }`}>
            {selectedMethod === 'telegram_stars' && '✓'}
          </div>
        </button>

        {/* PromptPay (Thai QR) */}
        <button
          onClick={() => {
            setSelectedMethod('promptpay');
            hapticImpact('light');
          }}
          className={`w-full card p-4 flex items-center gap-4 transition-all ${
            selectedMethod === 'promptpay' ? 'ring-2 ring-tg-button' : ''
          }`}
        >
          <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-600 to-blue-800 flex items-center justify-center text-white text-lg font-bold">
            PP
          </div>
          <div className="flex-1 text-left">
            <h3 className="font-semibold text-tg-text">{t('promptpay')}</h3>
            <p className="text-sm text-tg-hint">{t('promptpay_desc')}</p>
          </div>
          <div className={`w-6 h-6 rounded-full border-2 flex items-center justify-center ${
            selectedMethod === 'promptpay' ? 'border-tg-button bg-tg-button text-white' : 'border-tg-hint'
          }`}>
            {selectedMethod === 'promptpay' && '✓'}
          </div>
        </button>

        {/* YooKassa (Russian Payments) */}
        <button
          onClick={() => {
            setSelectedMethod('yookassa');
            hapticImpact('light');
          }}
          className={`w-full card p-4 flex items-center gap-4 transition-all ${
            selectedMethod === 'yookassa' ? 'ring-2 ring-tg-button' : ''
          }`}
        >
          <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-lg font-bold">
            ЮK
          </div>
          <div className="flex-1 text-left">
            <h3 className="font-semibold text-tg-text">{t('yookassa')}</h3>
            <p className="text-sm text-tg-hint">{t('yookassa_methods')}</p>
          </div>
          <div className={`w-6 h-6 rounded-full border-2 flex items-center justify-center ${
            selectedMethod === 'yookassa' ? 'border-tg-button bg-tg-button text-white' : 'border-tg-hint'
          }`}>
            {selectedMethod === 'yookassa' && '✓'}
          </div>
        </button>

        {/* Payment summary */}
        {selectedMethod && (
          <div className="card p-4 bg-tg-section-bg">
            <div className="flex justify-between items-center">
              <span className="text-tg-hint">{t('total_to_pay')}</span>
              <span className="text-xl font-bold text-tg-accent-text">
                {formatPrice(booking.total_price_thb)}
              </span>
            </div>
            {selectedMethod === 'crypto' && (
              <p className="text-xs text-tg-hint mt-2">
                {t('crypto_rate_info')}
              </p>
            )}
          </div>
        )}
      </div>

      {/* Pay button */}
      <div className="fixed bottom-0 left-0 right-0 p-4 bg-tg-bg border-t border-tg-secondary-bg">
        <button
          onClick={handlePayment}
          disabled={!selectedMethod || isProcessing}
          className="w-full btn-primary py-4 text-lg disabled:opacity-50"
        >
          {isProcessing ? (
            <span className="flex items-center justify-center gap-2">
              <span className="animate-spin">⏳</span>
              {t('processing')}
            </span>
          ) : (
            `${t('pay')} ${formatPrice(booking.total_price_thb)}`
          )}
        </button>
      </div>
    </div>
  );
}
