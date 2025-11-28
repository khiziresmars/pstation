import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { giftCardsApi } from '@/services/api';
import { usePrice } from '@/hooks/usePrice';

interface GiftCardInputProps {
  orderAmount: number;
  appliesTo: 'vessel' | 'tour' | 'all';
  appliedCard: AppliedGiftCard | null;
  onApply: (card: AppliedGiftCard | null) => void;
}

export interface AppliedGiftCard {
  code: string;
  balance: number;
  amountToUse: number;
}

export function GiftCardInput({
  orderAmount,
  appliesTo,
  appliedCard,
  onApply,
}: GiftCardInputProps) {
  const { t } = useTranslation();
  const { formatPrice } = usePrice();
  const [code, setCode] = useState('');
  const [isValidating, setIsValidating] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const validateCard = async () => {
    if (!code.trim()) return;

    setIsValidating(true);
    setError(null);

    try {
      const result = await giftCardsApi.validate(code.trim().toUpperCase(), orderAmount, appliesTo);

      if (result.valid) {
        onApply({
          code: result.code,
          balance: result.balance,
          amountToUse: result.applicable_amount,
        });
        setCode('');
      } else {
        setError(result.error || t('invalid_gift_card'));
      }
    } catch (err) {
      setError(t('gift_card_validation_failed'));
    } finally {
      setIsValidating(false);
    }
  };

  const removeCard = () => {
    onApply(null);
  };

  if (appliedCard) {
    return (
      <div className="card p-4 bg-gradient-to-r from-purple-500/10 to-pink-500/10 border border-purple-500/20">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <span className="text-2xl">🎁</span>
            <div>
              <div className="font-medium text-tg-text">
                {t('gift_card_applied')}
              </div>
              <div className="text-sm text-tg-hint">
                {appliedCard.code} • {t('balance')}: {formatPrice(appliedCard.balance)}
              </div>
            </div>
          </div>
          <div className="text-right">
            <div className="text-lg font-bold text-green-600">
              -{formatPrice(appliedCard.amountToUse)}
            </div>
            <button
              onClick={removeCard}
              className="text-xs text-red-500 hover:underline"
            >
              {t('remove')}
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="card p-4">
      <h2 className="font-semibold text-tg-text mb-3 flex items-center gap-2">
        🎁 {t('gift_card')}
      </h2>

      <div className="flex gap-2">
        <input
          type="text"
          value={code}
          onChange={(e) => {
            setCode(e.target.value.toUpperCase());
            setError(null);
          }}
          placeholder="XXXX-XXXX-XXXX"
          className="input flex-1 font-mono"
          maxLength={14}
        />
        <button
          onClick={validateCard}
          disabled={isValidating || !code.trim()}
          className="btn-primary disabled:opacity-50"
        >
          {isValidating ? '...' : t('apply')}
        </button>
      </div>

      {error && (
        <p className="text-sm text-red-500 mt-2">{error}</p>
      )}

      <p className="text-xs text-tg-hint mt-2">
        {t('gift_card_hint')}
      </p>
    </div>
  );
}

export default GiftCardInput;
