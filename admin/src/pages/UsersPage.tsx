import { useQuery } from '@tanstack/react-query';
import { usersApi } from '@/services/api';

export default function UsersPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['users'],
    queryFn: () => usersApi.getAll().then(res => res.data),
  });

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Users</h1>

      <div className="card overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center text-gray-500">Loading...</div>
        ) : (
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="table-header">Name</th>
                <th className="table-header">Email / Telegram</th>
                <th className="table-header">Bookings</th>
                <th className="table-header">Cashback</th>
                <th className="table-header">Registered</th>
                <th className="table-header">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {data?.users?.map((user: Record<string, unknown>) => (
                <tr key={user.id as number}>
                  <td className="table-cell">
                    <div className="flex items-center gap-3">
                      <div className="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                        {((user.first_name as string) || 'U')[0]}
                      </div>
                      <span className="font-medium">{user.first_name as string} {user.last_name as string}</span>
                    </div>
                  </td>
                  <td className="table-cell">
                    {user.email as string || '-'}
                    {user.telegram_id && (
                      <span className="text-xs text-gray-500 block">TG: {user.telegram_id as string}</span>
                    )}
                  </td>
                  <td className="table-cell">{user.bookings_count as number || 0}</td>
                  <td className="table-cell">฿{((user.cashback_balance as number) || 0).toLocaleString()}</td>
                  <td className="table-cell">{user.created_at as string}</td>
                  <td className="table-cell">
                    <button className="text-primary-600 hover:text-primary-800">View</button>
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
