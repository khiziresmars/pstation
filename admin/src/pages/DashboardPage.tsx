import { useQuery } from '@tanstack/react-query';
import { dashboardApi } from '@/services/api';

export default function DashboardPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['dashboard'],
    queryFn: () => dashboardApi.getStats().then(res => res.data),
  });

  if (isLoading) {
    return (
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Dashboard</h1>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="card animate-pulse">
              <div className="h-4 bg-gray-200 rounded w-1/2 mb-2" />
              <div className="h-8 bg-gray-200 rounded w-3/4" />
            </div>
          ))}
        </div>
      </div>
    );
  }

  const stats = data?.stats || {};

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Dashboard</h1>
        <span className="text-sm text-gray-500">
          Last updated: {new Date().toLocaleTimeString()}
        </span>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <StatCard
          title="Total Revenue"
          value={`฿${(stats.total_revenue || 0).toLocaleString()}`}
          change="+12%"
          color="blue"
        />
        <StatCard
          title="Total Bookings"
          value={stats.total_bookings || 0}
          change="+8%"
          color="green"
        />
        <StatCard
          title="Active Users"
          value={stats.active_users || 0}
          change="+15%"
          color="purple"
        />
        <StatCard
          title="Pending Payments"
          value={stats.pending_payments || 0}
          color="yellow"
        />
      </div>

      {/* Recent Activity */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="card">
          <h2 className="text-lg font-semibold mb-4">Recent Bookings</h2>
          {data?.recent_bookings?.length > 0 ? (
            <div className="space-y-3">
              {data.recent_bookings.slice(0, 5).map((booking: Record<string, unknown>) => (
                <div
                  key={booking.id as string}
                  className="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                >
                  <div>
                    <p className="font-medium">{booking.booking_reference as string}</p>
                    <p className="text-sm text-gray-500">{booking.item_name as string}</p>
                  </div>
                  <div className="text-right">
                    <p className="font-medium">฿{((booking.total_price_thb as number) || 0).toLocaleString()}</p>
                    <StatusBadge status={booking.status as string} />
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <p className="text-gray-500 text-center py-4">No recent bookings</p>
          )}
        </div>

        <div className="card">
          <h2 className="text-lg font-semibold mb-4">Quick Actions</h2>
          <div className="grid grid-cols-2 gap-4">
            <QuickAction
              icon="+"
              label="Add Vessel"
              href="/admin/vessels"
            />
            <QuickAction
              icon="+"
              label="Add Tour"
              href="/admin/tours"
            />
            <QuickAction
              icon="T"
              label="Create Promo"
              href="/admin/promos"
            />
            <QuickAction
              icon="S"
              label="Settings"
              href="/admin/settings"
            />
          </div>
        </div>
      </div>
    </div>
  );
}

function StatCard({ title, value, change, color }: {
  title: string;
  value: string | number;
  change?: string;
  color: 'blue' | 'green' | 'purple' | 'yellow';
}) {
  const colors = {
    blue: 'border-blue-500 bg-blue-50',
    green: 'border-green-500 bg-green-50',
    purple: 'border-purple-500 bg-purple-50',
    yellow: 'border-yellow-500 bg-yellow-50',
  };

  return (
    <div className={`stat-card ${colors[color]}`}>
      <p className="text-sm text-gray-600">{title}</p>
      <p className="text-2xl font-bold mt-1">{value}</p>
      {change && (
        <p className="text-sm text-green-600 mt-1">{change} from last month</p>
      )}
    </div>
  );
}

function StatusBadge({ status }: { status: string }) {
  const colors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    paid: 'bg-green-100 text-green-800',
    confirmed: 'bg-blue-100 text-blue-800',
    cancelled: 'bg-red-100 text-red-800',
  };

  return (
    <span className={`px-2 py-1 rounded-full text-xs font-medium ${colors[status] || 'bg-gray-100 text-gray-800'}`}>
      {status}
    </span>
  );
}

function QuickAction({ icon, label, href }: { icon: string; label: string; href: string }) {
  return (
    <a
      href={href}
      className="flex flex-col items-center justify-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
    >
      <span className="w-10 h-10 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center text-xl mb-2">
        {icon}
      </span>
      <span className="text-sm font-medium">{label}</span>
    </a>
  );
}
