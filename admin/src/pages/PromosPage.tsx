import { useQuery } from '@tanstack/react-query';
import { promosApi } from '@/services/api';

export default function PromosPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['promos'],
    queryFn: () => promosApi.getAll().then(res => res.data),
  });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Promo Codes</h1>
        <button className="btn-primary">Create Promo Code</button>
      </div>

      <div className="card overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center text-gray-500">Loading...</div>
        ) : (
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="table-header">Code</th>
                <th className="table-header">Type</th>
                <th className="table-header">Discount</th>
                <th className="table-header">Uses</th>
                <th className="table-header">Valid Until</th>
                <th className="table-header">Status</th>
                <th className="table-header">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {data?.promo_codes?.map((promo: Record<string, unknown>) => (
                <tr key={promo.id as number}>
                  <td className="table-cell font-mono font-medium">{promo.code as string}</td>
                  <td className="table-cell capitalize">{promo.discount_type as string}</td>
                  <td className="table-cell">
                    {promo.discount_type === 'percent'
                      ? `${promo.discount_value}%`
                      : `฿${promo.discount_value}`}
                  </td>
                  <td className="table-cell">
                    {promo.times_used as number} / {(promo.max_uses as number) || '∞'}
                  </td>
                  <td className="table-cell">{promo.valid_until as string || '-'}</td>
                  <td className="table-cell">
                    <span className={`px-2 py-1 rounded-full text-xs ${
                      promo.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                    }`}>
                      {promo.is_active ? 'Active' : 'Inactive'}
                    </span>
                  </td>
                  <td className="table-cell">
                    <button className="text-primary-600 hover:text-primary-800 mr-3">Edit</button>
                    <button className="text-red-600 hover:text-red-800">Delete</button>
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
