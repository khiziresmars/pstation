import { useState } from 'react';
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

type TabType = 'overview' | 'bookings' | 'settings' | 'about';

export default function ProfilePage() {
  const navigate = useNavigate();
  const { t, i18n } = useTranslation();
  const { user, language, setLanguage, currency, setCurrency } = useAppStore();
  const { formatPrice } = usePrice();
  const { hapticImpact, shareUrl } = useTelegram();
  const [copied, setCopied] = useState(false);
  const [activeTab, setActiveTab] = useState<TabType>('overview');

  const { data: profile, isLoading, error, refetch } = useQuery({
    queryKey: ['userProfile'],
    queryFn: userApi.getProfile,
  });

  const { data: referralStats } = useQuery({
    queryKey: ['referralStats'],
    queryFn: userApi.getReferrals,
  });

  const { data: bookingsData } = useQuery({
    queryKey: ['userBookings'],
    queryFn: () => userApi.getBookings(),
    enabled: activeTab === 'bookings',
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
        t('share_referral_text')
      );
    }
  };

  if (isLoading) {
    return <ProfileSkeleton />;
  }

  if (error) {
    return <ErrorState onRetry={() => refetch()} />;
  }

  const displayName = profile?.firstName || user?.firstName || t('guest_user');

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
            {profile?.email && (
              <p className="text-xs opacity-60">{profile.email}</p>
            )}
          </div>
        </div>
      </div>

      {/* Balance Card */}
      <div className="px-4 -mt-12 relative z-10">
        <motion.div
          className="card p-4"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
        >
          <div className="grid grid-cols-2 gap-4">
            <div className="text-center">
              <p className="text-sm text-tg-hint">{t('cashback_balance')}</p>
              <p className="text-2xl font-bold text-tg-accent-text mt-1">
                {formatPrice(profile?.cashbackBalance || 0)}
              </p>
              <p className="text-xs text-green-600 mt-1">
                {t('cashback_info')}
              </p>
            </div>
            <div className="text-center border-l border-tg-secondary-bg">
              <p className="text-sm text-tg-hint">{t('total_bookings')}</p>
              <p className="text-2xl font-bold text-tg-text mt-1">
                {bookingsData?.meta?.total || 0}
              </p>
              <p className="text-xs text-tg-hint mt-1">
                {t('all_time')}
              </p>
            </div>
          </div>
        </motion.div>
      </div>

      {/* Tabs */}
      <div className="px-4 mt-4">
        <div className="flex gap-2 overflow-x-auto scrollbar-hide">
          {(['overview', 'bookings', 'settings', 'about'] as TabType[]).map((tab) => (
            <button
              key={tab}
              onClick={() => {
                hapticImpact('light');
                setActiveTab(tab);
              }}
              className={`px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all ${
                activeTab === tab
                  ? 'bg-tg-button text-tg-button-text'
                  : 'bg-tg-secondary-bg text-tg-text'
              }`}
            >
              {t(`profile_tab_${tab}`)}
            </button>
          ))}
        </div>
      </div>

      {/* Tab Content */}
      <div className="px-4 mt-4">
        {activeTab === 'overview' && (
          <div className="space-y-4">
            {/* Quick Actions */}
            <div className="grid grid-cols-2 gap-3">
              <button onClick={() => navigate('/bookings')} className="card p-4 text-center">
                <span className="text-2xl">📋</span>
                <p className="text-sm font-medium mt-1">{t('my_bookings')}</p>
              </button>
              <button onClick={() => navigate('/favorites')} className="card p-4 text-center">
                <span className="text-2xl">❤️</span>
                <p className="text-sm font-medium mt-1">{t('favorites')}</p>
              </button>
              <button onClick={() => navigate('/gift-cards')} className="card p-4 text-center">
                <span className="text-2xl">🎁</span>
                <p className="text-sm font-medium mt-1">{t('gift_cards')}</p>
              </button>
              <button onClick={() => navigate('/search')} className="card p-4 text-center">
                <span className="text-2xl">🔍</span>
                <p className="text-sm font-medium mt-1">{t('search')}</p>
              </button>
            </div>

            {/* Referral Program */}
            <div className="card p-4">
              <h2 className="font-semibold text-tg-text mb-3">🎁 {t('referral_program')}</h2>
              {referralStats && (
                <>
                  <div className="grid grid-cols-2 gap-3 mb-4">
                    <div className="bg-tg-secondary-bg rounded-lg p-3 text-center">
                      <p className="text-2xl font-bold text-tg-accent-text">{referralStats.total_referrals}</p>
                      <p className="text-xs text-tg-hint">{t('total_referrals')}</p>
                    </div>
                    <div className="bg-tg-secondary-bg rounded-lg p-3 text-center">
                      <p className="text-2xl font-bold text-green-600">{formatPrice(referralStats.total_earned_thb)}</p>
                      <p className="text-xs text-tg-hint">{t('total_earned')}</p>
                    </div>
                  </div>
                  <p className="text-sm text-tg-hint mb-2">{t('your_referral_link')}:</p>
                  <div className="flex gap-2">
                    <input type="text" value={referralStats.referral_link} readOnly className="input flex-1 text-xs" />
                    <button onClick={handleCopyReferral} className="btn-secondary text-sm">{copied ? '✓' : '📋'}</button>
                  </div>
                  <button onClick={handleShareReferral} className="btn-primary w-full mt-3">{t('invite_friends')}</button>
                </>
              )}
            </div>
          </div>
        )}

        {activeTab === 'bookings' && (
          <BookingsTab bookings={bookingsData?.data || []} formatPrice={formatPrice} navigate={navigate} t={t} />
        )}

        {activeTab === 'settings' && (
          <SettingsTab
            language={language}
            currency={currency}
            profile={profile}
            onLanguageChange={handleLanguageChange}
            onCurrencyChange={handleCurrencyChange}
            t={t}
          />
        )}

        {activeTab === 'about' && <AboutTab t={t} />}
      </div>
    </div>
  );
}

function BookingsTab({ bookings, formatPrice, navigate, t }: {
  bookings: Array<Record<string, unknown>>;
  formatPrice: (n: number) => string;
  navigate: (p: string) => void;
  t: (k: string) => string;
}) {
  const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    paid: 'bg-blue-100 text-blue-800',
    confirmed: 'bg-green-100 text-green-800',
    completed: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800',
  };

  if (!bookings.length) {
    return (
      <div className="card p-8 text-center">
        <p className="text-4xl mb-3">📋</p>
        <p className="text-tg-hint">{t('no_bookings_yet')}</p>
        <button onClick={() => navigate('/')} className="btn-primary mt-4">{t('browse_catalog')}</button>
      </div>
    );
  }

  return (
    <div className="space-y-3">
      {bookings.map((b) => (
        <motion.div
          key={b.id as number}
          className="card p-4"
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          onClick={() => navigate(`/booking/confirm/${b.booking_reference}`)}
        >
          <div className="flex justify-between items-start">
            <div>
              <p className="font-medium text-tg-text">{b.item_name as string}</p>
              <p className="text-sm text-tg-hint">{b.booking_reference as string}</p>
              <p className="text-sm text-tg-hint">{b.booking_date as string}</p>
            </div>
            <div className="text-right">
              <span className={`px-2 py-1 rounded-full text-xs ${statusColors[b.status as string] || 'bg-gray-100'}`}>
                {t(`status_${b.status}`)}
              </span>
              <p className="font-bold text-tg-accent-text mt-1">{formatPrice(b.total_price_thb as number)}</p>
            </div>
          </div>
        </motion.div>
      ))}
    </div>
  );
}

function SettingsTab({ language, currency, profile, onLanguageChange, onCurrencyChange, t }: {
  language: string;
  currency: Currency;
  profile: { email?: string } | undefined;
  onLanguageChange: (l: 'en' | 'ru' | 'th') => void;
  onCurrencyChange: (c: Currency) => void;
  t: (k: string) => string;
}) {
  const [showPwdForm, setShowPwdForm] = useState(false);
  const [notifications, setNotifications] = useState({ bookingUpdates: true, promotions: true, reminders: true });

  return (
    <div className="space-y-4">
      <div className="card p-4">
        <h3 className="font-semibold text-tg-text mb-3">🌍 {t('language')}</h3>
        <div className="flex gap-2">
          {(['en', 'ru', 'th'] as const).map((l) => (
            <button
              key={l}
              onClick={() => onLanguageChange(l)}
              className={`px-4 py-2 rounded-lg text-sm font-medium flex-1 ${language === l ? 'bg-tg-button text-tg-button-text' : 'bg-tg-secondary-bg text-tg-text'}`}
            >
              {l === 'en' ? '🇬🇧 EN' : l === 'ru' ? '🇷🇺 RU' : '🇹🇭 TH'}
            </button>
          ))}
        </div>
      </div>

      <div className="card p-4">
        <h3 className="font-semibold text-tg-text mb-3">💰 {t('currency')}</h3>
        <div className="flex flex-wrap gap-2">
          {(['THB', 'USD', 'EUR', 'RUB'] as const).map((c) => (
            <button
              key={c}
              onClick={() => onCurrencyChange(c)}
              className={`px-4 py-2 rounded-lg text-sm font-medium ${currency === c ? 'bg-tg-button text-tg-button-text' : 'bg-tg-secondary-bg text-tg-text'}`}
            >
              {c}
            </button>
          ))}
        </div>
      </div>

      <div className="card p-4">
        <h3 className="font-semibold text-tg-text mb-3">🔔 {t('notifications')}</h3>
        <div className="space-y-3">
          <Toggle label={t('booking_updates')} checked={notifications.bookingUpdates} onChange={(v) => setNotifications({ ...notifications, bookingUpdates: v })} />
          <Toggle label={t('promotions')} checked={notifications.promotions} onChange={(v) => setNotifications({ ...notifications, promotions: v })} />
          <Toggle label={t('reminders')} checked={notifications.reminders} onChange={(v) => setNotifications({ ...notifications, reminders: v })} />
        </div>
      </div>

      {profile?.email && (
        <div className="card p-4">
          <h3 className="font-semibold text-tg-text mb-3">🔐 {t('security')}</h3>
          {!showPwdForm ? (
            <button onClick={() => setShowPwdForm(true)} className="btn-secondary w-full">{t('change_password')}</button>
          ) : (
            <PasswordForm onCancel={() => setShowPwdForm(false)} t={t} />
          )}
        </div>
      )}
    </div>
  );
}

function Toggle({ label, checked, onChange }: { label: string; checked: boolean; onChange: (v: boolean) => void }) {
  return (
    <div className="flex items-center justify-between">
      <span className="text-sm text-tg-text">{label}</span>
      <label className="relative inline-flex items-center cursor-pointer">
        <input type="checkbox" checked={checked} onChange={(e) => onChange(e.target.checked)} className="sr-only peer" />
        <div className="w-11 h-6 bg-tg-secondary-bg peer-checked:bg-tg-button rounded-full peer after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
      </label>
    </div>
  );
}

function PasswordForm({ onCancel, t }: { onCancel: () => void; t: (k: string) => string }) {
  const [form, setForm] = useState({ current: '', newPwd: '', confirm: '' });
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (form.newPwd !== form.confirm) { setError(t('passwords_dont_match')); return; }
    if (form.newPwd.length < 6) { setError(t('password_too_short')); return; }
    try {
      await userApi.changePassword(form.current, form.newPwd);
      setSuccess(true);
      setTimeout(onCancel, 2000);
    } catch (err: unknown) {
      const e = err as { response?: { data?: { error?: string } } };
      setError(e.response?.data?.error || t('password_change_failed'));
    }
  };

  if (success) return <div className="text-center py-4 text-green-600">✓ {t('password_changed')}</div>;

  return (
    <form onSubmit={handleSubmit} className="space-y-3">
      {error && <p className="text-red-500 text-sm">{error}</p>}
      <input type="password" placeholder={t('current_password')} value={form.current} onChange={(e) => setForm({ ...form, current: e.target.value })} className="input" required />
      <input type="password" placeholder={t('new_password')} value={form.newPwd} onChange={(e) => setForm({ ...form, newPwd: e.target.value })} className="input" required />
      <input type="password" placeholder={t('confirm_password')} value={form.confirm} onChange={(e) => setForm({ ...form, confirm: e.target.value })} className="input" required />
      <div className="flex gap-2">
        <button type="button" onClick={onCancel} className="btn-secondary flex-1">{t('cancel')}</button>
        <button type="submit" className="btn-primary flex-1">{t('save')}</button>
      </div>
    </form>
  );
}

function AboutTab({ t }: { t: (k: string) => string }) {
  return (
    <div className="space-y-4">
      <div className="card p-6 text-center">
        <div className="w-20 h-20 bg-gradient-to-br from-ocean-500 to-ocean-700 rounded-2xl mx-auto flex items-center justify-center text-white text-3xl mb-4">PS</div>
        <h2 className="text-xl font-bold text-tg-text">Phuket Station</h2>
        <p className="text-tg-hint text-sm mt-1">{t('app_description')}</p>
        <p className="text-xs text-tg-hint mt-4">Version 1.0.0</p>
      </div>

      <div className="card p-4">
        <h3 className="font-semibold text-tg-text mb-3">{t('features')}</h3>
        <ul className="space-y-2 text-sm text-tg-hint">
          <li className="flex items-center gap-2">🚤 {t('feature_yachts')}</li>
          <li className="flex items-center gap-2">🏝️ {t('feature_tours')}</li>
          <li className="flex items-center gap-2">💳 {t('feature_payments')}</li>
          <li className="flex items-center gap-2">🎁 {t('feature_loyalty')}</li>
          <li className="flex items-center gap-2">🌍 {t('feature_languages')}</li>
        </ul>
      </div>

      <div className="card p-4">
        <h3 className="font-semibold text-tg-text mb-3">{t('contact_us')}</h3>
        <div className="space-y-2 text-sm">
          <a href="https://t.me/phuket_station_support" className="flex items-center gap-2 text-tg-link">📱 @phuket_station_support</a>
          <a href="mailto:support@phuket-station.com" className="flex items-center gap-2 text-tg-link">✉️ support@phuket-station.com</a>
        </div>
      </div>

      <div className="text-center text-xs text-tg-hint py-4">
        <p>Made with ❤️ in Phuket</p>
        <p>© 2024 Phuket Station</p>
      </div>
    </div>
  );
}
