import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { bookingsApi } from '@/services/api';

export default function BookingsPage() {
  const [filter, setFilter] = useState({ status: '', search: '' });
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ['bookings', filter],
    queryFn: () => bookingsApi.getAll(filter).then(res => res.data),
  });

  const updateStatusMutation = useMutation({
    mutationFn: ({ reference, status }: { reference: string; status: string }) =>
      bookingsApi.updateStatus(reference, status),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['bookings'] }),
  });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Bookings</h1>
        <button
          onClick={() => bookingsApi.export(filter)}
          className="btn-secondary"
        >
          Export CSV
        </button>
      </div>

      {/* Filters */}
      <div className="card">
        <div className="flex flex-wrap gap-4">
          <input
            type="text"
            placeholder="Search by reference..."
            value={filter.search}
            onChange={(e) => setFilter({ ...filter, search: e.target.value })}
            className="input max-w-xs"
          />
          <select
            value={filter.status}
            onChange={(e) => setFilter({ ...filter, status: e.target.value })}
            className="input max-w-xs"
          >
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="confirmed">Confirmed</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>

      {/* Table */}
      <div className="card overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center text-gray-500">Loading...</div>
        ) : (
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="table-header">Reference</th>
                <th className="table-header">Customer</th>
                <th className="table-header">Item</th>
                <th className="table-header">Date</th>
                <th className="table-header">Amount</th>
                <th className="table-header">Status</th>
                <th className="table-header">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {data?.bookings?.map((booking: Record<string, unknown>) => (
                <tr key={booking.id as number}>
                  <td className="table-cell font-medium">{booking.booking_reference as string}</td>
                  <td className="table-cell">{booking.customer_name as string || '-'}</td>
                  <td className="table-cell">{booking.item_name as string}</td>
                  <td className="table-cell">{booking.booking_date as string}</td>
                  <td className="table-cell">฿{((booking.total_price_thb as number) || 0).toLocaleString()}</td>
                  <td className="table-cell">
                    <StatusBadge status={booking.status as string} />
                  </td>
                  <td className="table-cell">
                    <select
                      value={booking.status as string}
                      onChange={(e) =>
                        updateStatusMutation.mutate({
                          reference: booking.booking_reference as string,
                          status: e.target.value,
                        })
                      }
                      className="text-sm border rounded px-2 py-1"
                    >
                      <option value="pending">Pending</option>
                      <option value="paid">Paid</option>
                      <option value="confirmed">Confirmed</option>
                      <option value="completed">Completed</option>
                      <option value="cancelled">Cancelled</option>
                    </select>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
}

function StatusBadge({ status }: { status: string }) {
  const colors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    paid: 'bg-green-100 text-green-800',
    confirmed: 'bg-blue-100 text-blue-800',
    completed: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800',
  };

  return (
    <span className={`px-2 py-1 rounded-full text-xs font-medium ${colors[status] || 'bg-gray-100'}`}>
      {status}
    </span>
  );
}
