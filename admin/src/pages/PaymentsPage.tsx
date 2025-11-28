import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { paymentsApi } from '@/services/api';

export default function PaymentsPage() {
  const [activeTab, setActiveTab] = useState<'promptpay' | 'overview'>('overview');
  const queryClient = useQueryClient();

  const { data: pendingPayments, isLoading } = useQuery({
    queryKey: ['promptpay-pending'],
    queryFn: () => paymentsApi.getPromptPayPending().then(res => res.data),
    enabled: activeTab === 'promptpay',
  });

  const confirmMutation = useMutation({
    mutationFn: ({ paymentId, transactionRef }: { paymentId: string; transactionRef: string }) =>
      paymentsApi.confirmPromptPay(paymentId, transactionRef),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['promptpay-pending'] });
    },
  });

  const handleConfirm = (paymentId: string) => {
    const ref = prompt('Enter bank transaction reference:');
    if (ref) {
      confirmMutation.mutate({ paymentId, transactionRef: ref });
    }
  };

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Payments</h1>

      {/* Tabs */}
      <div className="border-b">
        <nav className="flex gap-4">
          {['overview', 'promptpay'].map((tab) => (
            <button
              key={tab}
              onClick={() => setActiveTab(tab as typeof activeTab)}
              className={`pb-3 px-1 border-b-2 font-medium text-sm ${
                activeTab === tab
                  ? 'border-primary-600 text-primary-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700'
              }`}
            >
              {tab === 'promptpay' ? 'PromptPay Pending' : 'Overview'}
            </button>
          ))}
        </nav>
      </div>

      {activeTab === 'overview' && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <PaymentMethodCard
            name="Stripe"
            icon="card"
            enabled={true}
            description="Credit/Debit Cards"
          />
          <PaymentMethodCard
            name="PromptPay"
            icon="qr"
            enabled={true}
            description="Thai QR Payment"
          />
          <PaymentMethodCard
            name="YooKassa"
            icon="ru"
            enabled={true}
            description="Russian Payments"
          />
          <PaymentMethodCard
            name="Crypto"
            icon="crypto"
            enabled={true}
            description="NowPayments"
          />
          <PaymentMethodCard
            name="Telegram Stars"
            icon="star"
            enabled={true}
            description="In-app Currency"
          />
        </div>
      )}

      {activeTab === 'promptpay' && (
        <div className="card">
          <h2 className="text-lg font-semibold mb-4">Pending PromptPay Payments</h2>
          <p className="text-sm text-gray-500 mb-4">
            Review and confirm payments after verifying bank transfers.
          </p>

          {isLoading ? (
            <div className="text-center py-8">Loading...</div>
          ) : pendingPayments?.payments?.length > 0 ? (
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="table-header">Booking</th>
                  <th className="table-header">Customer</th>
                  <th className="table-header">Amount</th>
                  <th className="table-header">Created</th>
                  <th className="table-header">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {pendingPayments.payments.map((payment: Record<string, unknown>) => (
                  <tr key={payment.id as number}>
                    <td className="table-cell font-medium">{payment.booking_reference as string}</td>
                    <td className="table-cell">
                      {payment.first_name as string} {payment.last_name as string}
                      <br />
                      <span className="text-xs text-gray-500">{payment.phone as string}</span>
                    </td>
                    <td className="table-cell">฿{((payment.amount_thb as number) || 0).toLocaleString()}</td>
                    <td className="table-cell">{payment.created_at as string}</td>
                    <td className="table-cell">
                      <button
                        onClick={() => handleConfirm(String(payment.id))}
                        className="btn-success text-sm"
                        disabled={confirmMutation.isPending}
                      >
                        Confirm
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          ) : (
            <div className="text-center py-8 text-gray-500">
              No pending PromptPay payments
            </div>
          )}
        </div>
      )}
    </div>
  );
}

function PaymentMethodCard({ name, icon, enabled, description }: {
  name: string;
  icon: string;
  enabled: boolean;
  description: string;
}) {
  return (
    <div className="card flex items-center gap-4">
      <div className="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center text-2xl">
        {icon === 'card' && '💳'}
        {icon === 'qr' && '📱'}
        {icon === 'ru' && '🇷🇺'}
        {icon === 'crypto' && '₿'}
        {icon === 'star' && '⭐'}
      </div>
      <div className="flex-1">
        <div className="flex items-center gap-2">
          <h3 className="font-semibold">{name}</h3>
          <span className={`w-2 h-2 rounded-full ${enabled ? 'bg-green-500' : 'bg-gray-300'}`} />
        </div>
        <p className="text-sm text-gray-500">{description}</p>
      </div>
    </div>
  );
}
