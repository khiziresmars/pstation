import { useTranslation } from 'react-i18next';

interface ErrorStateProps {
  message?: string;
  onRetry?: () => void;
}

export function ErrorState({ message, onRetry }: ErrorStateProps) {
  const { t } = useTranslation();

  return (
    <div className="flex flex-col items-center justify-center p-8 text-center">
      <div className="text-4xl mb-4">😕</div>
      <h3 className="text-lg font-medium text-tg-text mb-2">
        {t('error')}
      </h3>
      <p className="text-sm text-tg-hint mb-4">
        {message || 'Something went wrong. Please try again.'}
      </p>
      {onRetry && (
        <button
          onClick={onRetry}
          className="btn-primary px-6"
        >
          {t('retry')}
        </button>
      )}
    </div>
  );
}
