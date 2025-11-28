import { useTranslation } from 'react-i18next';
import { ReactNode } from 'react';

interface EmptyStateProps {
  icon?: string;
  title?: string;
  message?: string;
  action?: ReactNode;
}

export function EmptyState({ icon = '📭', title, message, action }: EmptyStateProps) {
  const { t } = useTranslation();

  return (
    <div className="flex flex-col items-center justify-center p-8 text-center">
      <div className="text-5xl mb-4">{icon}</div>
      <h3 className="text-lg font-medium text-tg-text mb-2">
        {title || t('no_results')}
      </h3>
      {message && (
        <p className="text-sm text-tg-hint mb-4">{message}</p>
      )}
      {action}
    </div>
  );
}
