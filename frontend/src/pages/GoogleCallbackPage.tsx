import { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { authApi } from '@/services/api';
import { useAppStore } from '@/store/appStore';
import { Skeleton } from '@/components/common/Skeleton';

export default function GoogleCallbackPage() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const { t } = useTranslation();
  const { setUser, setAuthToken } = useAppStore();
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const handleCallback = async () => {
      const code = searchParams.get('code');
      const errorParam = searchParams.get('error');

      if (errorParam) {
        setError(t('error_google_auth'));
        setTimeout(() => navigate('/auth'), 3000);
        return;
      }

      if (!code) {
        setError(t('error_google_auth'));
        setTimeout(() => navigate('/auth'), 3000);
        return;
      }

      try {
        const redirectUri = `${window.location.origin}/auth/google/callback`;
        const result = await authApi.googleCallback(code, redirectUri);

        setUser(result.user);
        setAuthToken(result.token);

        // Redirect to home or return URL
        const returnUrl = localStorage.getItem('auth_return_url') || '/';
        localStorage.removeItem('auth_return_url');
        navigate(returnUrl);
      } catch (err) {
        console.error('Google auth error:', err);
        setError(t('error_google_auth'));
        setTimeout(() => navigate('/auth'), 3000);
      }
    };

    handleCallback();
  }, [searchParams, navigate, setUser, setAuthToken, t]);

  if (error) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center p-6 text-center">
        <div className="text-6xl mb-4">❌</div>
        <h1 className="text-xl font-bold text-tg-text mb-2">{t('error')}</h1>
        <p className="text-tg-hint">{error}</p>
        <p className="text-sm text-tg-hint mt-4">{t('redirecting')}...</p>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex flex-col items-center justify-center p-6 text-center">
      <div className="animate-spin text-6xl mb-4">⏳</div>
      <h1 className="text-xl font-bold text-tg-text mb-2">{t('processing')}</h1>
      <p className="text-tg-hint">{t('please_wait')}</p>
      <div className="mt-6 space-y-3 w-full max-w-xs">
        <Skeleton className="h-4 w-full" />
        <Skeleton className="h-4 w-3/4" />
      </div>
    </div>
  );
}
