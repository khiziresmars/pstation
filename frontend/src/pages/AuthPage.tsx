import { useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useMutation } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { motion, AnimatePresence } from 'framer-motion';
import { authApi } from '@/services/api';
import { useAppStore } from '@/store/appStore';
import { useTelegram } from '@/hooks/useTelegram';

type AuthMode = 'login' | 'register' | 'forgot';

export default function AuthPage() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const { t } = useTranslation();
  const { setUser, setAuthToken } = useAppStore();
  const { hapticImpact, hapticNotification } = useTelegram();

  const returnUrl = searchParams.get('return') || '/';

  const [mode, setMode] = useState<AuthMode>('login');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [phone, setPhone] = useState('');
  const [whatsapp, setWhatsapp] = useState('');
  const [error, setError] = useState('');

  // Login mutation
  const loginMutation = useMutation({
    mutationFn: () => authApi.login(email, password),
    onSuccess: (data) => {
      hapticNotification('success');
      setUser(data.user);
      setAuthToken(data.token);
      navigate(returnUrl);
    },
    onError: (err: any) => {
      hapticNotification('error');
      setError(err.response?.data?.error || t('error_login'));
    },
  });

  // Register mutation
  const registerMutation = useMutation({
    mutationFn: () => authApi.register({
      email,
      password,
      first_name: firstName,
      last_name: lastName,
      phone,
      whatsapp,
    }),
    onSuccess: (data) => {
      hapticNotification('success');
      setUser(data.user);
      setAuthToken(data.token);
      navigate(returnUrl);
    },
    onError: (err: any) => {
      hapticNotification('error');
      setError(err.response?.data?.error || t('error_register'));
    },
  });

  // Forgot password mutation
  const forgotMutation = useMutation({
    mutationFn: () => authApi.forgotPassword(email),
    onSuccess: () => {
      hapticNotification('success');
      setError('');
      setMode('login');
    },
    onError: (err: any) => {
      hapticNotification('error');
      setError(err.response?.data?.error || t('error'));
    },
  });

  // Google auth
  const handleGoogleAuth = async () => {
    hapticImpact('medium');
    try {
      const redirectUri = `${window.location.origin}/auth/google/callback`;
      const response = await authApi.getGoogleUrl(redirectUri);
      window.location.href = response.url;
    } catch (err) {
      setError(t('error_google_auth'));
    }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    hapticImpact('light');

    if (mode === 'login') {
      loginMutation.mutate();
    } else if (mode === 'register') {
      if (password !== confirmPassword) {
        setError(t('passwords_dont_match'));
        return;
      }
      if (password.length < 6) {
        setError(t('password_too_short'));
        return;
      }
      registerMutation.mutate();
    } else if (mode === 'forgot') {
      forgotMutation.mutate();
    }
  };

  const isLoading = loginMutation.isPending || registerMutation.isPending || forgotMutation.isPending;

  return (
    <div className="min-h-screen bg-gradient-to-br from-ocean-600 to-ocean-800 flex flex-col">
      {/* Header */}
      <div className="p-6 text-white text-center pt-12">
        <motion.div
          initial={{ opacity: 0, y: -20 }}
          animate={{ opacity: 1, y: 0 }}
          className="text-4xl mb-3"
        >
          🚤
        </motion.div>
        <h1 className="text-2xl font-bold">Phuket Station</h1>
        <p className="text-white/70 mt-1">{t('tagline')}</p>
      </div>

      {/* Auth Card */}
      <div className="flex-1 bg-tg-bg rounded-t-3xl p-6 mt-6">
        {/* Tabs */}
        <div className="flex bg-tg-secondary-bg rounded-xl p-1 mb-6">
          <button
            onClick={() => { setMode('login'); setError(''); }}
            className={`flex-1 py-3 rounded-lg text-sm font-medium transition-all ${
              mode === 'login'
                ? 'bg-tg-button text-tg-button-text'
                : 'text-tg-hint'
            }`}
          >
            {t('login')}
          </button>
          <button
            onClick={() => { setMode('register'); setError(''); }}
            className={`flex-1 py-3 rounded-lg text-sm font-medium transition-all ${
              mode === 'register'
                ? 'bg-tg-button text-tg-button-text'
                : 'text-tg-hint'
            }`}
          >
            {t('register')}
          </button>
        </div>

        {/* Social Auth */}
        <div className="space-y-3 mb-6">
          <button
            onClick={handleGoogleAuth}
            className="w-full card p-4 flex items-center justify-center gap-3 hover:shadow-md transition-shadow"
          >
            <svg className="w-5 h-5" viewBox="0 0 24 24">
              <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
              <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
              <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
              <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            <span className="text-tg-text font-medium">{t('continue_with_google')}</span>
          </button>
        </div>

        {/* Divider */}
        <div className="flex items-center gap-4 mb-6">
          <div className="flex-1 h-px bg-tg-secondary-bg" />
          <span className="text-sm text-tg-hint">{t('or')}</span>
          <div className="flex-1 h-px bg-tg-secondary-bg" />
        </div>

        {/* Error message */}
        <AnimatePresence>
          {error && (
            <motion.div
              initial={{ opacity: 0, y: -10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0 }}
              className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4 text-sm"
            >
              {error}
            </motion.div>
          )}
        </AnimatePresence>

        {/* Form */}
        <form onSubmit={handleSubmit} className="space-y-4">
          {/* Name fields (register only) */}
          {mode === 'register' && (
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="text-sm text-tg-hint mb-1 block">{t('first_name')}</label>
                <input
                  type="text"
                  value={firstName}
                  onChange={(e) => setFirstName(e.target.value)}
                  className="input"
                  placeholder="John"
                />
              </div>
              <div>
                <label className="text-sm text-tg-hint mb-1 block">{t('last_name')}</label>
                <input
                  type="text"
                  value={lastName}
                  onChange={(e) => setLastName(e.target.value)}
                  className="input"
                  placeholder="Doe"
                />
              </div>
            </div>
          )}

          {/* Email */}
          <div>
            <label className="text-sm text-tg-hint mb-1 block">Email</label>
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="input"
              placeholder="your@email.com"
              required
            />
          </div>

          {/* Password (not for forgot mode) */}
          {mode !== 'forgot' && (
            <div>
              <label className="text-sm text-tg-hint mb-1 block">{t('password')}</label>
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="input"
                placeholder="••••••••"
                required
                minLength={6}
              />
            </div>
          )}

          {/* Confirm password (register only) */}
          {mode === 'register' && (
            <div>
              <label className="text-sm text-tg-hint mb-1 block">{t('confirm_password')}</label>
              <input
                type="password"
                value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)}
                className="input"
                placeholder="••••••••"
                required
                minLength={6}
              />
            </div>
          )}

          {/* Contact fields (register only) */}
          {mode === 'register' && (
            <>
              <div>
                <label className="text-sm text-tg-hint mb-1 block">
                  {t('phone_number')} <span className="text-xs">({t('optional')})</span>
                </label>
                <input
                  type="tel"
                  value={phone}
                  onChange={(e) => setPhone(e.target.value)}
                  className="input"
                  placeholder="+66 XX XXX XXXX"
                />
              </div>
              <div>
                <label className="text-sm text-tg-hint mb-1 block">
                  WhatsApp <span className="text-xs">({t('optional')})</span>
                </label>
                <input
                  type="tel"
                  value={whatsapp}
                  onChange={(e) => setWhatsapp(e.target.value)}
                  className="input"
                  placeholder="+66 XX XXX XXXX"
                />
              </div>
            </>
          )}

          {/* Forgot password link */}
          {mode === 'login' && (
            <button
              type="button"
              onClick={() => { setMode('forgot'); setError(''); }}
              className="text-sm text-tg-link"
            >
              {t('forgot_password')}
            </button>
          )}

          {/* Submit button */}
          <button
            type="submit"
            disabled={isLoading}
            className="w-full btn-primary py-4 text-lg disabled:opacity-50"
          >
            {isLoading ? (
              <span className="flex items-center justify-center gap-2">
                <span className="animate-spin">⏳</span>
                {t('processing')}
              </span>
            ) : mode === 'login' ? (
              t('login')
            ) : mode === 'register' ? (
              t('create_account')
            ) : (
              t('send_reset_link')
            )}
          </button>
        </form>

        {/* Back to login (forgot mode) */}
        {mode === 'forgot' && (
          <button
            onClick={() => { setMode('login'); setError(''); }}
            className="w-full text-center text-tg-link mt-4"
          >
            {t('back_to_login')}
          </button>
        )}

        {/* Terms */}
        {mode === 'register' && (
          <p className="text-xs text-tg-hint text-center mt-4">
            {t('by_registering')} <a href="/terms" className="text-tg-link">{t('terms_of_service')}</a> {t('and')} <a href="/privacy" className="text-tg-link">{t('privacy_policy')}</a>
          </p>
        )}
      </div>
    </div>
  );
}
