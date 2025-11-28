import { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { settingsApi } from '@/services/api';

const tabs = [
  { id: 'general', name: 'General' },
  { id: 'payments', name: 'Payment Methods' },
  { id: 'notifications', name: 'Notifications' },
  { id: 'exchange', name: 'Exchange Rates' },
];

export default function SettingsPage() {
  const { tab = 'general' } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const { data: settings, isLoading } = useQuery({
    queryKey: ['settings'],
    queryFn: () => settingsApi.getAll().then(res => res.data),
  });

  const updateMutation = useMutation({
    mutationFn: (data: Record<string, unknown>) => settingsApi.update(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['settings'] });
    },
  });

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Settings</h1>

      {/* Tabs */}
      <div className="border-b">
        <nav className="flex gap-4">
          {tabs.map((t) => (
            <button
              key={t.id}
              onClick={() => navigate(`/admin/settings/${t.id}`)}
              className={`pb-3 px-1 border-b-2 font-medium text-sm ${
                tab === t.id
                  ? 'border-primary-600 text-primary-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700'
              }`}
            >
              {t.name}
            </button>
          ))}
        </nav>
      </div>

      {isLoading ? (
        <div className="card p-8 text-center">Loading...</div>
      ) : (
        <>
          {tab === 'general' && <GeneralSettings settings={settings} onSave={updateMutation.mutate} />}
          {tab === 'payments' && <PaymentSettings settings={settings} onSave={updateMutation.mutate} />}
          {tab === 'notifications' && <NotificationSettings settings={settings} onSave={updateMutation.mutate} />}
          {tab === 'exchange' && <ExchangeRateSettings />}
        </>
      )}
    </div>
  );
}

function GeneralSettings({ settings, onSave }: { settings: Record<string, unknown>; onSave: (data: Record<string, unknown>) => void }) {
  const [form, setForm] = useState({
    app_name: (settings?.app_name as string) || 'Phuket Station',
    admin_email: (settings?.admin_email as string) || '',
    default_currency: (settings?.default_currency as string) || 'THB',
    cashback_percent: (settings?.cashback_percent as number) || 5,
    referral_bonus: (settings?.referral_bonus as number) || 200,
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSave(form);
  };

  return (
    <form onSubmit={handleSubmit} className="card space-y-4">
      <h2 className="text-lg font-semibold">General Settings</h2>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label className="block text-sm font-medium mb-1">App Name</label>
          <input
            type="text"
            value={form.app_name}
            onChange={(e) => setForm({ ...form, app_name: e.target.value })}
            className="input"
          />
        </div>

        <div>
          <label className="block text-sm font-medium mb-1">Admin Email</label>
          <input
            type="email"
            value={form.admin_email}
            onChange={(e) => setForm({ ...form, admin_email: e.target.value })}
            className="input"
          />
        </div>

        <div>
          <label className="block text-sm font-medium mb-1">Default Currency</label>
          <select
            value={form.default_currency}
            onChange={(e) => setForm({ ...form, default_currency: e.target.value })}
            className="input"
          >
            <option value="THB">THB (Thai Baht)</option>
            <option value="USD">USD (US Dollar)</option>
            <option value="EUR">EUR (Euro)</option>
            <option value="RUB">RUB (Russian Ruble)</option>
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium mb-1">Cashback %</label>
          <input
            type="number"
            value={form.cashback_percent}
            onChange={(e) => setForm({ ...form, cashback_percent: Number(e.target.value) })}
            className="input"
            min="0"
            max="100"
          />
        </div>

        <div>
          <label className="block text-sm font-medium mb-1">Referral Bonus (THB)</label>
          <input
            type="number"
            value={form.referral_bonus}
            onChange={(e) => setForm({ ...form, referral_bonus: Number(e.target.value) })}
            className="input"
            min="0"
          />
        </div>
      </div>

      <button type="submit" className="btn-primary">
        Save Settings
      </button>
    </form>
  );
}

function PaymentSettings({ settings, onSave }: { settings: Record<string, unknown>; onSave: (data: Record<string, unknown>) => void }) {
  const [form, setForm] = useState({
    stripe_enabled: (settings?.stripe_enabled as boolean) || false,
    promptpay_enabled: (settings?.promptpay_enabled as boolean) || false,
    yookassa_enabled: (settings?.yookassa_enabled as boolean) || false,
    crypto_enabled: (settings?.crypto_enabled as boolean) || false,
    telegram_stars_enabled: (settings?.telegram_stars_enabled as boolean) || true,
    promptpay_account_type: (settings?.promptpay_account_type as string) || 'phone',
    promptpay_account_id: (settings?.promptpay_account_id as string) || '',
    promptpay_merchant_name: (settings?.promptpay_merchant_name as string) || 'Phuket Station',
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSave(form);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <div className="card">
        <h2 className="text-lg font-semibold mb-4">Payment Methods</h2>
        <p className="text-sm text-gray-500 mb-6">
          Enable or disable payment methods. Configure API keys in environment variables.
        </p>

        <div className="space-y-4">
          <PaymentToggle
            name="Stripe (Cards)"
            description="Credit/Debit card payments via Stripe"
            enabled={form.stripe_enabled}
            onChange={(v) => setForm({ ...form, stripe_enabled: v })}
          />
          <PaymentToggle
            name="PromptPay (Thai QR)"
            description="Thai instant bank transfers via QR code"
            enabled={form.promptpay_enabled}
            onChange={(v) => setForm({ ...form, promptpay_enabled: v })}
          />
          <PaymentToggle
            name="YooKassa (Russian)"
            description="Russian payment gateway (cards, YooMoney, SberPay)"
            enabled={form.yookassa_enabled}
            onChange={(v) => setForm({ ...form, yookassa_enabled: v })}
          />
          <PaymentToggle
            name="Cryptocurrency"
            description="Bitcoin, Ethereum, USDT via NowPayments"
            enabled={form.crypto_enabled}
            onChange={(v) => setForm({ ...form, crypto_enabled: v })}
          />
          <PaymentToggle
            name="Telegram Stars"
            description="In-app Telegram currency"
            enabled={form.telegram_stars_enabled}
            onChange={(v) => setForm({ ...form, telegram_stars_enabled: v })}
          />
        </div>
      </div>

      {form.promptpay_enabled && (
        <div className="card">
          <h2 className="text-lg font-semibold mb-4">PromptPay Configuration</h2>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium mb-1">Account Type</label>
              <select
                value={form.promptpay_account_type}
                onChange={(e) => setForm({ ...form, promptpay_account_type: e.target.value })}
                className="input"
              >
                <option value="phone">Phone Number</option>
                <option value="national_id">National ID</option>
                <option value="ewallet">E-Wallet ID</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium mb-1">Account ID</label>
              <input
                type="text"
                value={form.promptpay_account_id}
                onChange={(e) => setForm({ ...form, promptpay_account_id: e.target.value })}
                className="input"
                placeholder="e.g., 0812345678"
              />
              <p className="text-xs text-gray-500 mt-1">
                Phone number without dashes, or 13-digit National ID
              </p>
            </div>

            <div className="md:col-span-2">
              <label className="block text-sm font-medium mb-1">Merchant Name</label>
              <input
                type="text"
                value={form.promptpay_merchant_name}
                onChange={(e) => setForm({ ...form, promptpay_merchant_name: e.target.value })}
                className="input"
              />
            </div>
          </div>
        </div>
      )}

      <button type="submit" className="btn-primary">
        Save Payment Settings
      </button>
    </form>
  );
}

function PaymentToggle({ name, description, enabled, onChange }: {
  name: string;
  description: string;
  enabled: boolean;
  onChange: (value: boolean) => void;
}) {
  return (
    <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
      <div>
        <p className="font-medium">{name}</p>
        <p className="text-sm text-gray-500">{description}</p>
      </div>
      <label className="relative inline-flex items-center cursor-pointer">
        <input
          type="checkbox"
          checked={enabled}
          onChange={(e) => onChange(e.target.checked)}
          className="sr-only peer"
        />
        <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
      </label>
    </div>
  );
}

function NotificationSettings({ settings, onSave }: { settings: Record<string, unknown>; onSave: (data: Record<string, unknown>) => void }) {
  const [form, setForm] = useState({
    email_notifications: (settings?.email_notifications as boolean) ?? true,
    telegram_notifications: (settings?.telegram_notifications as boolean) ?? true,
    booking_confirmation: (settings?.booking_confirmation as boolean) ?? true,
    payment_received: (settings?.payment_received as boolean) ?? true,
    booking_reminder: (settings?.booking_reminder as boolean) ?? true,
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSave(form);
  };

  return (
    <form onSubmit={handleSubmit} className="card space-y-4">
      <h2 className="text-lg font-semibold">Notification Settings</h2>

      <div className="space-y-4">
        <PaymentToggle
          name="Email Notifications"
          description="Send notifications via email"
          enabled={form.email_notifications}
          onChange={(v) => setForm({ ...form, email_notifications: v })}
        />
        <PaymentToggle
          name="Telegram Notifications"
          description="Send notifications via Telegram bot"
          enabled={form.telegram_notifications}
          onChange={(v) => setForm({ ...form, telegram_notifications: v })}
        />
        <PaymentToggle
          name="Booking Confirmation"
          description="Notify customers when booking is confirmed"
          enabled={form.booking_confirmation}
          onChange={(v) => setForm({ ...form, booking_confirmation: v })}
        />
        <PaymentToggle
          name="Payment Received"
          description="Notify when payment is received"
          enabled={form.payment_received}
          onChange={(v) => setForm({ ...form, payment_received: v })}
        />
        <PaymentToggle
          name="Booking Reminder"
          description="Send reminder 24h before booking"
          enabled={form.booking_reminder}
          onChange={(v) => setForm({ ...form, booking_reminder: v })}
        />
      </div>

      <button type="submit" className="btn-primary">
        Save Notification Settings
      </button>
    </form>
  );
}

function ExchangeRateSettings() {
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ['exchange-rates'],
    queryFn: () => settingsApi.getExchangeRates().then(res => res.data),
  });

  const updateMutation = useMutation({
    mutationFn: (rates: Record<string, unknown>) => settingsApi.updateExchangeRates(rates),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['exchange-rates'] }),
  });

  const [rates, setRates] = useState<Record<string, number>>({});

  if (isLoading) return <div className="card p-8 text-center">Loading...</div>;

  return (
    <div className="card space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-lg font-semibold">Exchange Rates</h2>
          <p className="text-sm text-gray-500">Rates are updated automatically every 6 hours</p>
        </div>
        <button
          onClick={() => updateMutation.mutate({ action: 'refresh' })}
          className="btn-secondary"
        >
          Refresh Rates
        </button>
      </div>

      <table className="min-w-full divide-y divide-gray-200">
        <thead className="bg-gray-50">
          <tr>
            <th className="table-header">Currency</th>
            <th className="table-header">Rate to THB</th>
            <th className="table-header">Last Updated</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-200">
          {data?.rates?.map((rate: Record<string, unknown>) => (
            <tr key={rate.currency_code as string}>
              <td className="table-cell font-medium">{rate.currency_code as string}</td>
              <td className="table-cell">{((rate.rate_to_thb as number) || 0).toFixed(4)}</td>
              <td className="table-cell text-gray-500">{rate.last_updated_at as string}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
