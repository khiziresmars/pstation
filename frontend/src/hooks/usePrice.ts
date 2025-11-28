import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useAppStore } from '@/store/appStore';
import { exchangeRatesApi } from '@/services/api';
import type { Currency } from '@/types';

export function usePrice() {
  const { currency } = useAppStore();

  const { data: exchangeRates } = useQuery({
    queryKey: ['exchangeRates'],
    queryFn: exchangeRatesApi.getAll,
    staleTime: 30 * 60 * 1000, // 30 minutes
  });

  const ratesMap = useMemo(() => {
    if (!exchangeRates) return {};
    return exchangeRates.reduce(
      (acc, rate) => {
        acc[rate.currency_code] = rate;
        return acc;
      },
      {} as Record<string, (typeof exchangeRates)[0]>
    );
  }, [exchangeRates]);

  const formatPrice = (amountTHB: number, targetCurrency?: Currency): string => {
    const curr = targetCurrency || currency;

    if (curr === 'THB' || !ratesMap[curr]) {
      return `฿${amountTHB.toLocaleString('en-US', { maximumFractionDigits: 0 })}`;
    }

    const rate = ratesMap[curr];
    const converted = amountTHB * rate.rate_from_thb;

    return `${rate.currency_symbol}${converted.toLocaleString('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })}`;
  };

  const convertPrice = (amountTHB: number, targetCurrency?: Currency): number => {
    const curr = targetCurrency || currency;

    if (curr === 'THB' || !ratesMap[curr]) {
      return amountTHB;
    }

    return amountTHB * ratesMap[curr].rate_from_thb;
  };

  const getCurrencySymbol = (curr?: Currency): string => {
    const c = curr || currency;
    if (c === 'THB') return '฿';
    return ratesMap[c]?.currency_symbol || c;
  };

  return {
    currency,
    exchangeRates,
    formatPrice,
    convertPrice,
    getCurrencySymbol,
  };
}
