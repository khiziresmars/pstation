import { useQuery } from '@tanstack/react-query';
import { toursApi } from '@/services/api';

export default function ToursPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['tours'],
    queryFn: () => toursApi.getAll().then(res => res.data),
  });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Tours</h1>
        <button className="btn-primary">Add Tour</button>
      </div>

      <div className="card overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center text-gray-500">Loading...</div>
        ) : (
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="table-header">Name</th>
                <th className="table-header">Category</th>
                <th className="table-header">Duration</th>
                <th className="table-header">Price</th>
                <th className="table-header">Status</th>
                <th className="table-header">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {data?.tours?.map((tour: Record<string, unknown>) => (
                <tr key={tour.id as number}>
                  <td className="table-cell font-medium">{tour.name as string}</td>
                  <td className="table-cell">{tour.category_name as string || '-'}</td>
                  <td className="table-cell">{tour.duration_hours as number}h</td>
                  <td className="table-cell">฿{((tour.adult_price_thb as number) || 0).toLocaleString()}</td>
                  <td className="table-cell">
                    <span className={`px-2 py-1 rounded-full text-xs ${
                      tour.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                    }`}>
                      {tour.is_active ? 'Active' : 'Inactive'}
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
