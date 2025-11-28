import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';
import { userApi } from '@/services/api';
import { useAppStore } from '@/store/appStore';
import { usePrice } from '@/hooks/usePrice';
import { useTelegram } from '@/hooks/useTelegram';
import { ProfileSkeleton } from '@/components/common/Skeleton';
import { ErrorState } from '@/components/common/ErrorState';
import type { Currency } from '@/types';

export default function ProfilePage() {
  const navigate = useNavigate();
  const { t, i18n } = useTranslation();
  const { user, language, setLanguage, currency, setCurrency } = useAppStore();
  const { formatPrice } = usePrice();
  const { hapticImpact, shareUrl } = useTelegram();
  const [copied, setCopied] = useState(false);

  const { data: profile, isLoading, error, refetch } = useQuery({
    queryKey: ['userProfile'],
    queryFn: userApi.getProfile,
  });

  const { data: referralStats } = useQuery({
    queryKey: ['referralStats'],
    queryFn: userApi.getReferrals,
  });

  const handleLanguageChange = (lang: 'en' | 'ru' | 'th') => {
    hapticImpact('light');
    setLanguage(lang);
    i18n.changeLanguage(lang);
  };

  const handleCurrencyChange = (curr: Currency) => {
    hapticImpact('light');
    setCurrency(curr);
  };

  const handleCopyReferral = () => {
    if (referralStats?.referral_link) {
      navigator.clipboard.writeText(referralStats.referral_link);
      hapticImpact('medium');
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    }
  };

  const handleShareReferral = () => {
    if (referralStats?.referral_link) {
      hapticImpact('medium');
      shareUrl(
        referralStats.referral_link,
        '🚤 Book premium yachts & island tours in Phuket! Use my link to get started:'
      );
    }
  };

  if (isLoading) {
    return <ProfileSkeleton />;
  }

  if (error) {
    return <ErrorState onRetry={() => refetch()} />;
  }

  const displayName = profile?.firstName || user?.firstName || 'Guest';

  return (
    <div className="pb-4">
      {/* Header */}
      <div className="bg-gradient-to-br from-ocean-600 to-ocean-800 p-6 pb-20">
        <div className="flex items-center gap-4">
          <div className="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center text-3xl">
            {profile?.photoUrl ? (
              <img src={profile.photoUrl} alt="" className="w-full h-full rounded-full object-cover" />
            ) : (
              '👤'
            )}
          </div>
          <div className="text-white">
            <h1 className="text-xl font-bold">{displayName}</h1>
            {profile?.username && (
              <p className="text-sm opacity-80">@{profile.username}</p>
            )}
          </div>
        </div>
      </div>

      {/* Cashback Card */}
      <div className="px-4 -mt-12 relative z-10">
        <motion.div
          className="card p-4"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
        >
          <div className="text-center">
            <p className="text-sm text-tg-hint">{t('cashback_balance')}</p>
            <p className="text-3xl font-bold text-tg-accent-text mt-1">
              {formatPrice(profile?.cashbackBalance || 0)}
            </p>
            <p className="text-xs text-green-600 mt-1">
              Earn 5% cashback on every booking!
            </p>
          </div>
        </motion.div>
      </div>

      {/* Quick Actions */}
      <div className="px-4 mt-4 grid grid-cols-2 gap-3">
        <button
          onClick={() => navigate('/bookings')}
          className="card p-4 text-center"
        >
          <span className="text-2xl">📋</span>
          <p className="text-sm font-medium mt-1">{t('bookings')}</p>
        </button>
        <button
          onClick={() => navigate('/favorites')}
          className="card p-4 text-center"
        >
          <span className="text-2xl">❤️</span>
          <p className="text-sm font-medium mt-1">{t('favorites')}</p>
        </button>
      </div>

      {/* Referral Program */}
      <div className="px-4 mt-4">
        <div className="card p-4">
          <h2 className="font-semibold text-tg-text mb-3">🎁 {t('referral_program')}</h2>

          {referralStats && (
            <>
              <div className="grid grid-cols-2 gap-3 mb-4">
                <div className="bg-tg-secondary-bg rounded-lg p-3 text-center">
                  <p className="text-2xl font-bold text-tg-accent-text">
                    {referralStats.total_referrals}
                  </p>
                  <p className="text-xs text-tg-hint">{t('total_referrals')}</p>
                </div>
                <div className="bg-tg-secondary-bg rounded-lg p-3 text-center">
                  <p className="text-2xl font-bold text-green-600">
                    {formatPrice(referralStats.total_earned_thb)}
                  </p>
                  <p className="text-xs text-tg-hint">{t('total_earned')}</p>
                </div>
              </div>

              <p className="text-sm text-tg-hint mb-2">{t('your_referral_link')}:</p>
              <div className="flex gap-2">
                <input
                  type="text"
                  value={referralStats.referral_link}
                  readOnly
                  className="input flex-1 text-xs"
                />
                <button
                  onClick={handleCopyReferral}
                  className="btn-secondary text-sm"
                >
                  {copied ? '✓' : '📋'}
                </button>
              </div>

              <button
                onClick={handleShareReferral}
                className="btn-primary w-full mt-3"
              >
                {t('invite_friends')} 🚀
              </button>
            </>
          )}
        </div>
      </div>

      {/* Settings */}
      <div className="px-4 mt-4">
        <div className="card p-4">
          <h2 className="font-semibold text-tg-text mb-3">⚙️ {t('settings')}</h2>

          {/* Language */}
          <div className="mb-4">
            <label className="text-sm text-tg-hint">{t('language')}</label>
            <div className="flex gap-2 mt-2">
              {(['en', 'ru', 'th'] as const).map((lang) => (
                <button
                  key={lang}
                  onClick={() => handleLanguageChange(lang)}
                  className={`px-4 py-2 rounded-lg text-sm font-medium transition-all ${
                    language === lang
                      ? 'bg-tg-button text-tg-button-text'
                      : 'bg-tg-secondary-bg text-tg-text'
                  }`}
                >
                  {lang === 'en' ? '🇬🇧 EN' : lang === 'ru' ? '🇷🇺 RU' : '🇹🇭 TH'}
                </button>
              ))}
            </div>
          </div>

          {/* Currency */}
          <div>
            <label className="text-sm text-tg-hint">{t('currency')}</label>
            <div className="flex gap-2 mt-2">
              {(['THB', 'USD', 'EUR', 'RUB'] as const).map((curr) => (
                <button
                  key={curr}
                  onClick={() => handleCurrencyChange(curr)}
                  className={`px-4 py-2 rounded-lg text-sm font-medium transition-all ${
                    currency === curr
                      ? 'bg-tg-button text-tg-button-text'
                      : 'bg-tg-secondary-bg text-tg-text'
                  }`}
                >
                  {curr}
                </button>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
