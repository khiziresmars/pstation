import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useTelegram } from '@/hooks/useTelegram';

export interface ContactInfo {
  phone: string;
  whatsapp: string;
  email: string;
  preferredContact: 'telegram' | 'whatsapp' | 'phone' | 'email';
  addToCalendar: boolean;
}

interface ContactInfoSectionProps {
  contactInfo: ContactInfo;
  onChange: (info: ContactInfo) => void;
  userEmail?: string;
  userPhone?: string;
}

export function ContactInfoSection({
  contactInfo,
  onChange,
  userEmail,
  userPhone,
}: ContactInfoSectionProps) {
  const { t } = useTranslation();
  const { hapticImpact } = useTelegram();
  const [isExpanded, setIsExpanded] = useState(false);

  const handleChange = (field: keyof ContactInfo, value: string | boolean) => {
    onChange({ ...contactInfo, [field]: value });
  };

  const contactMethods = [
    { id: 'telegram', icon: '✈️', label: 'Telegram' },
    { id: 'whatsapp', icon: '💬', label: 'WhatsApp' },
    { id: 'phone', icon: '📞', label: t('phone') },
    { id: 'email', icon: '📧', label: 'Email' },
  ] as const;

  return (
    <div className="card overflow-hidden">
      {/* Header */}
      <button
        onClick={() => {
          setIsExpanded(!isExpanded);
          hapticImpact('light');
        }}
        className="w-full p-4 flex items-center justify-between"
      >
        <div className="flex items-center gap-3">
          <span className="text-2xl">📱</span>
          <div className="text-left">
            <h3 className="font-semibold text-tg-text">{t('contact_info')}</h3>
            <p className="text-xs text-tg-hint">{t('contact_info_subtitle')}</p>
          </div>
        </div>
        <span className={`transition-transform ${isExpanded ? 'rotate-180' : ''}`}>
          ▼
        </span>
      </button>

      {/* Content */}
      {isExpanded && (
        <div className="border-t border-tg-secondary-bg p-4 space-y-4">
          {/* Preferred contact method */}
          <div>
            <label className="text-sm text-tg-hint mb-2 block">
              {t('preferred_contact_method')}
            </label>
            <div className="grid grid-cols-4 gap-2">
              {contactMethods.map((method) => (
                <button
                  key={method.id}
                  onClick={() => {
                    handleChange('preferredContact', method.id);
                    hapticImpact('light');
                  }}
                  className={`p-3 rounded-xl flex flex-col items-center gap-1 transition-all ${
                    contactInfo.preferredContact === method.id
                      ? 'bg-tg-button text-tg-button-text'
                      : 'bg-tg-secondary-bg text-tg-text'
                  }`}
                >
                  <span className="text-xl">{method.icon}</span>
                  <span className="text-xs">{method.label}</span>
                </button>
              ))}
            </div>
          </div>

          {/* Phone */}
          {(contactInfo.preferredContact === 'phone' || contactInfo.preferredContact === 'whatsapp') && (
            <div>
              <label className="text-sm text-tg-hint mb-1 block">
                {t('phone_number')}
              </label>
              <input
                type="tel"
                value={contactInfo.phone}
                onChange={(e) => handleChange('phone', e.target.value)}
                placeholder="+66 XX XXX XXXX"
                className="input"
              />
            </div>
          )}

          {/* WhatsApp */}
          {contactInfo.preferredContact === 'whatsapp' && (
            <div>
              <label className="text-sm text-tg-hint mb-1 block">
                WhatsApp {t('number')}
                <span className="text-xs text-tg-hint ml-2">
                  ({t('if_different')})
                </span>
              </label>
              <input
                type="tel"
                value={contactInfo.whatsapp}
                onChange={(e) => handleChange('whatsapp', e.target.value)}
                placeholder={contactInfo.phone || '+66 XX XXX XXXX'}
                className="input"
              />
              <p className="text-xs text-tg-hint mt-1">
                {t('leave_empty_same_as_phone')}
              </p>
            </div>
          )}

          {/* Email */}
          {contactInfo.preferredContact === 'email' && (
            <div>
              <label className="text-sm text-tg-hint mb-1 block">
                Email
              </label>
              <input
                type="email"
                value={contactInfo.email}
                onChange={(e) => handleChange('email', e.target.value)}
                placeholder={userEmail || 'your@email.com'}
                className="input"
              />
            </div>
          )}

          {/* Calendar integration */}
          <div className="pt-2 border-t border-tg-secondary-bg">
            <label className="flex items-center justify-between cursor-pointer">
              <div className="flex items-center gap-3">
                <span className="text-xl">📅</span>
                <div>
                  <span className="text-tg-text">{t('add_to_calendar')}</span>
                  <p className="text-xs text-tg-hint">{t('calendar_reminder_info')}</p>
                </div>
              </div>
              <input
                type="checkbox"
                checked={contactInfo.addToCalendar}
                onChange={(e) => handleChange('addToCalendar', e.target.checked)}
                className="w-5 h-5 accent-tg-button"
              />
            </label>
          </div>

          {/* Quick fill from profile */}
          {(userEmail || userPhone) && (
            <div className="pt-2">
              <button
                onClick={() => {
                  onChange({
                    ...contactInfo,
                    phone: userPhone || contactInfo.phone,
                    email: userEmail || contactInfo.email,
                  });
                  hapticImpact('light');
                }}
                className="text-sm text-tg-link"
              >
                {t('use_profile_info')}
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

export default ContactInfoSection;
