import { useQuery } from '@tanstack/react-query';
import { analyticsApi } from '@/services/api';

export default function AnalyticsPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['analytics'],
    queryFn: () => analyticsApi.getDashboard().then(res => res.data),
  });

  if (isLoading) {
    return (
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Analytics</h1>
        <div className="card p-8 text-center">Loading...</div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Analytics</h1>

      {/* Revenue Stats */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <StatCard title="Today's Revenue" value={`฿${(data?.today_revenue || 0).toLocaleString()}`} />
        <StatCard title="This Week" value={`฿${(data?.week_revenue || 0).toLocaleString()}`} />
        <StatCard title="This Month" value={`฿${(data?.month_revenue || 0).toLocaleString()}`} />
        <StatCard title="Total Revenue" value={`฿${(data?.total_revenue || 0).toLocaleString()}`} />
      </div>

      {/* Bookings Stats */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="card">
          <h2 className="text-lg font-semibold mb-4">Bookings by Status</h2>
          <div className="space-y-3">
            {data?.bookings_by_status?.map((item: Record<string, unknown>) => (
              <div key={item.status as string} className="flex items-center justify-between">
                <span className="capitalize">{item.status as string}</span>
                <span className="font-medium">{item.count as number}</span>
              </div>
            ))}
          </div>
        </div>

        <div className="card">
          <h2 className="text-lg font-semibold mb-4">Payment Methods</h2>
          <div className="space-y-3">
            {data?.payment_methods?.map((item: Record<string, unknown>) => (
              <div key={item.method as string} className="flex items-center justify-between">
                <span className="capitalize">{item.method as string || 'Unknown'}</span>
                <div className="text-right">
                  <span className="font-medium">{item.count as number} bookings</span>
                  <span className="text-gray-500 text-sm ml-2">
                    ฿{((item.total as number) || 0).toLocaleString()}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Top Items */}
      <div className="card">
        <h2 className="text-lg font-semibold mb-4">Top Booked Items</h2>
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="table-header">Item</th>
              <th className="table-header">Type</th>
              <th className="table-header">Bookings</th>
              <th className="table-header">Revenue</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200">
            {data?.top_items?.map((item: Record<string, unknown>, i: number) => (
              <tr key={i}>
                <td className="table-cell font-medium">{item.name as string}</td>
                <td className="table-cell capitalize">{item.type as string}</td>
                <td className="table-cell">{item.bookings as number}</td>
                <td className="table-cell">฿{((item.revenue as number) || 0).toLocaleString()}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}

function StatCard({ title, value }: { title: string; value: string }) {
  return (
    <div className="card">
      <p className="text-sm text-gray-600">{title}</p>
      <p className="text-2xl font-bold mt-1">{value}</p>
    </div>
  );
}
