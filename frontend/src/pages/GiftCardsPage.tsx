import { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { giftCardsApi, userApi } from '@/services/api';
import { usePrice } from '@/hooks/usePrice';
import { useTelegram } from '@/hooks/useTelegram';
import { Skeleton } from '@/components/common/Skeleton';

const PRESET_AMOUNTS = [1000, 2000, 5000, 10000, 20000, 50000];

const DESIGNS = [
  { id: 'ocean', name: 'Ocean Breeze', gradient: 'from-blue-400 to-cyan-500', icon: '🌊' },
  { id: 'sunset', name: 'Sunset Cruise', gradient: 'from-orange-400 to-pink-500', icon: '🌅' },
  { id: 'tropical', name: 'Tropical Paradise', gradient: 'from-green-400 to-teal-500', icon: '🌴' },
  { id: 'luxury', name: 'Luxury Gold', gradient: 'from-yellow-400 to-amber-500', icon: '✨' },
  { id: 'adventure', name: 'Adventure Time', gradient: 'from-purple-400 to-indigo-500', icon: '🏄' },
  { id: 'celebration', name: 'Celebration', gradient: 'from-pink-400 to-rose-500', icon: '🎉' },
];

export default function GiftCardsPage() {
  const navigate = useNavigate();
  const { t } = useTranslation();
  const { formatPrice } = usePrice();
  const { hapticImpact, hapticNotification, showBackButton, hideBackButton } = useTelegram();

  // Form state
  const [step, setStep] = useState<'amount' | 'design' | 'details' | 'preview'>('amount');
  const [amount, setAmount] = useState<number>(5000);
  const [customAmount, setCustomAmount] = useState('');
  const [design, setDesign] = useState(DESIGNS[0]);
  const [recipientName, setRecipientName] = useState('');
  const [recipientEmail, setRecipientEmail] = useState('');
  const [senderName, setSenderName] = useState('');
  const [message, setMessage] = useState('');
  const [deliveryMethod, setDeliveryMethod] = useState<'email' | 'telegram'>('email');

  // Fetch user's gift cards
  const { data: myCards, isLoading: cardsLoading } = useQuery({
    queryKey: ['user-gift-cards'],
    queryFn: () => userApi.getGiftCards(),
  });

  // Purchase mutation
  const purchaseMutation = useMutation({
    mutationFn: (data: any) => giftCardsApi.purchase(data),
    onSuccess: (result) => {
      hapticNotification('success');
      navigate(`/gift-cards/success/${result.gift_card.code}`);
    },
    onError: () => {
      hapticNotification('error');
    },
  });

  const finalAmount = customAmount ? parseInt(customAmount) : amount;

  const handlePurchase = () => {
    purchaseMutation.mutate({
      amount_thb: finalAmount,
      design_template: design.id,
      recipient_name: recipientName,
      recipient_email: deliveryMethod === 'email' ? recipientEmail : undefined,
      purchaser_name: senderName,
      personal_message: message,
      delivery_method: deliveryMethod,
      valid_months: 12,
      applies_to: 'all',
    });
  };

  const nextStep = () => {
    hapticImpact('light');
    const steps: typeof step[] = ['amount', 'design', 'details', 'preview'];
    const currentIndex = steps.indexOf(step);
    if (currentIndex < steps.length - 1) {
      setStep(steps[currentIndex + 1]);
    }
  };

  const prevStep = () => {
    hapticImpact('light');
    const steps: typeof step[] = ['amount', 'design', 'details', 'preview'];
    const currentIndex = steps.indexOf(step);
    if (currentIndex > 0) {
      setStep(steps[currentIndex - 1]);
    }
  };

  return (
    <div className="pb-24">
      {/* Header */}
      <div className="bg-gradient-to-r from-purple-500 to-pink-500 p-6 text-white">
        <h1 className="text-2xl font-bold">🎁 {t('gift_cards')}</h1>
        <p className="text-white/80 mt-1">{t('gift_cards_subtitle')}</p>
      </div>

      {/* Tabs */}
      <div className="flex border-b border-tg-secondary-bg">
        <button
          onClick={() => setStep('amount')}
          className={`flex-1 py-3 text-center font-medium ${
            step !== 'amount' ? 'text-tg-hint' : 'text-tg-button border-b-2 border-tg-button'
          }`}
        >
          {t('buy_gift_card')}
        </button>
        <button
          onClick={() => navigate('/gift-cards/my')}
          className="flex-1 py-3 text-center font-medium text-tg-hint"
        >
          {t('my_gift_cards')} {myCards?.length ? `(${myCards.length})` : ''}
        </button>
      </div>

      <div className="p-4">
        {/* Step 1: Amount */}
        {step === 'amount' && (
          <div className="space-y-6">
            <div>
              <h2 className="font-semibold text-tg-text mb-4">{t('select_amount')}</h2>
              <div className="grid grid-cols-3 gap-3">
                {PRESET_AMOUNTS.map((preset) => (
                  <button
                    key={preset}
                    onClick={() => {
                      setAmount(preset);
                      setCustomAmount('');
                      hapticImpact('light');
                    }}
                    className={`py-4 rounded-xl font-semibold transition-all ${
                      amount === preset && !customAmount
                        ? 'bg-tg-button text-tg-button-text'
                        : 'bg-tg-secondary-bg text-tg-text hover:bg-tg-button/20'
                    }`}
                  >
                    {formatPrice(preset)}
                  </button>
                ))}
              </div>
            </div>

            <div>
              <label className="text-sm text-tg-hint">{t('or_enter_custom')}</label>
              <div className="flex items-center gap-2 mt-2">
                <span className="text-tg-text font-medium">฿</span>
                <input
                  type="number"
                  value={customAmount}
                  onChange={(e) => setCustomAmount(e.target.value)}
                  placeholder="1,000 - 500,000"
                  min={1000}
                  max={500000}
                  className="input flex-1"
                />
              </div>
            </div>

            <button
              onClick={nextStep}
              disabled={!finalAmount || finalAmount < 1000 || finalAmount > 500000}
              className="w-full btn-primary py-4 text-lg disabled:opacity-50"
            >
              {t('continue')} - {formatPrice(finalAmount)}
            </button>
          </div>
        )}

        {/* Step 2: Design */}
        {step === 'design' && (
          <div className="space-y-6">
            <div>
              <h2 className="font-semibold text-tg-text mb-4">{t('choose_design')}</h2>
              <div className="grid grid-cols-2 gap-3">
                {DESIGNS.map((d) => (
                  <button
                    key={d.id}
                    onClick={() => {
                      setDesign(d);
                      hapticImpact('light');
                    }}
                    className={`relative overflow-hidden rounded-xl p-4 h-32 transition-all ${
                      design.id === d.id ? 'ring-2 ring-tg-button ring-offset-2' : ''
                    }`}
                  >
                    <div className={`absolute inset-0 bg-gradient-to-br ${d.gradient}`} />
                    <div className="relative text-white text-left">
                      <span className="text-3xl">{d.icon}</span>
                      <div className="mt-2 font-medium text-sm">{d.name}</div>
                    </div>
                  </button>
                ))}
              </div>
            </div>

            {/* Preview */}
            <div className={`rounded-2xl p-6 bg-gradient-to-br ${design.gradient} text-white`}>
              <div className="flex justify-between items-start">
                <span className="text-4xl">{design.icon}</span>
                <div className="text-right">
                  <div className="text-xs opacity-80">Gift Card</div>
                  <div className="text-2xl font-bold">{formatPrice(finalAmount)}</div>
                </div>
              </div>
              <div className="mt-8">
                <div className="text-xs opacity-80">Phuket Yacht & Tours</div>
                <div className="font-mono text-lg mt-1">XXXX-XXXX-XXXX</div>
              </div>
            </div>

            <div className="flex gap-3">
              <button onClick={prevStep} className="flex-1 btn-secondary py-4">
                {t('back')}
              </button>
              <button onClick={nextStep} className="flex-1 btn-primary py-4">
                {t('continue')}
              </button>
            </div>
          </div>
        )}

        {/* Step 3: Details */}
        {step === 'details' && (
          <div className="space-y-4">
            <h2 className="font-semibold text-tg-text">{t('recipient_details')}</h2>

            {/* Delivery method */}
            <div className="flex gap-3">
              <button
                onClick={() => setDeliveryMethod('email')}
                className={`flex-1 py-3 rounded-xl flex items-center justify-center gap-2 ${
                  deliveryMethod === 'email'
                    ? 'bg-tg-button text-tg-button-text'
                    : 'bg-tg-secondary-bg text-tg-text'
                }`}
              >
                📧 Email
              </button>
              <button
                onClick={() => setDeliveryMethod('telegram')}
                className={`flex-1 py-3 rounded-xl flex items-center justify-center gap-2 ${
                  deliveryMethod === 'telegram'
                    ? 'bg-tg-button text-tg-button-text'
                    : 'bg-tg-secondary-bg text-tg-text'
                }`}
              >
                ✈️ Telegram
              </button>
            </div>

            <div>
              <label className="text-sm text-tg-hint">{t('recipient_name')}</label>
              <input
                type="text"
                value={recipientName}
                onChange={(e) => setRecipientName(e.target.value)}
                placeholder="John"
                className="input mt-1"
              />
            </div>

            {deliveryMethod === 'email' && (
              <div>
                <label className="text-sm text-tg-hint">{t('recipient_email')}</label>
                <input
                  type="email"
                  value={recipientEmail}
                  onChange={(e) => setRecipientEmail(e.target.value)}
                  placeholder="john@example.com"
                  className="input mt-1"
                />
              </div>
            )}

            <div>
              <label className="text-sm text-tg-hint">{t('your_name')}</label>
              <input
                type="text"
                value={senderName}
                onChange={(e) => setSenderName(e.target.value)}
                placeholder="From..."
                className="input mt-1"
              />
            </div>

            <div>
              <label className="text-sm text-tg-hint">{t('personal_message')}</label>
              <textarea
                value={message}
                onChange={(e) => setMessage(e.target.value)}
                placeholder={t('message_placeholder')}
                rows={3}
                className="input mt-1 resize-none"
                maxLength={200}
              />
              <div className="text-xs text-tg-hint text-right mt-1">
                {message.length}/200
              </div>
            </div>

            <div className="flex gap-3 pt-4">
              <button onClick={prevStep} className="flex-1 btn-secondary py-4">
                {t('back')}
              </button>
              <button
                onClick={nextStep}
                disabled={!recipientName || (deliveryMethod === 'email' && !recipientEmail)}
                className="flex-1 btn-primary py-4 disabled:opacity-50"
              >
                {t('preview')}
              </button>
            </div>
          </div>
        )}

        {/* Step 4: Preview */}
        {step === 'preview' && (
          <div className="space-y-6">
            <h2 className="font-semibold text-tg-text">{t('review_gift_card')}</h2>

            {/* Card preview */}
            <div className={`rounded-2xl p-6 bg-gradient-to-br ${design.gradient} text-white`}>
              <div className="flex justify-between items-start">
                <span className="text-4xl">{design.icon}</span>
                <div className="text-right">
                  <div className="text-xs opacity-80">Gift Card</div>
                  <div className="text-2xl font-bold">{formatPrice(finalAmount)}</div>
                </div>
              </div>
              {recipientName && (
                <div className="mt-4 text-lg">
                  For: <span className="font-semibold">{recipientName}</span>
                </div>
              )}
              {message && (
                <div className="mt-2 text-sm opacity-90 italic">"{message}"</div>
              )}
              {senderName && (
                <div className="mt-2 text-sm">From: {senderName}</div>
              )}
              <div className="mt-6">
                <div className="text-xs opacity-80">Phuket Yacht & Tours</div>
                <div className="text-xs opacity-60 mt-1">Valid for 12 months</div>
              </div>
            </div>

            {/* Summary */}
            <div className="card p-4">
              <h3 className="font-semibold text-tg-text mb-3">{t('order_summary')}</h3>
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-tg-hint">{t('gift_card_value')}</span>
                  <span className="text-tg-text">{formatPrice(finalAmount)}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-tg-hint">{t('delivery_method')}</span>
                  <span className="text-tg-text capitalize">{deliveryMethod}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-tg-hint">{t('recipient')}</span>
                  <span className="text-tg-text">{recipientName}</span>
                </div>
                <div className="border-t border-tg-secondary-bg my-2" />
                <div className="flex justify-between font-semibold">
                  <span className="text-tg-text">{t('total')}</span>
                  <span className="text-tg-accent-text text-lg">{formatPrice(finalAmount)}</span>
                </div>
              </div>
            </div>

            <div className="flex gap-3">
              <button onClick={prevStep} className="flex-1 btn-secondary py-4">
                {t('back')}
              </button>
              <button
                onClick={handlePurchase}
                disabled={purchaseMutation.isPending}
                className="flex-1 btn-primary py-4 disabled:opacity-50"
              >
                {purchaseMutation.isPending ? t('processing') : t('purchase')}
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
