import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { reviewsApi } from '@/services/api';

export default function ReviewsPage() {
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ['reviews'],
    queryFn: () => reviewsApi.getAll().then(res => res.data),
  });

  const approveMutation = useMutation({
    mutationFn: (id: number) => reviewsApi.approve(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['reviews'] }),
  });

  const rejectMutation = useMutation({
    mutationFn: (id: number) => reviewsApi.reject(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['reviews'] }),
  });

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Reviews</h1>

      <div className="space-y-4">
        {isLoading ? (
          <div className="card p-8 text-center text-gray-500">Loading...</div>
        ) : data?.reviews?.length === 0 ? (
          <div className="card p-8 text-center text-gray-500">No reviews yet</div>
        ) : (
          data?.reviews?.map((review: Record<string, unknown>) => (
            <div key={review.id as number} className="card">
              <div className="flex items-start justify-between">
                <div>
                  <div className="flex items-center gap-2">
                    <span className="font-medium">{review.user_name as string}</span>
                    <span className="text-yellow-500">
                      {'★'.repeat(review.rating as number)}{'☆'.repeat(5 - (review.rating as number))}
                    </span>
                  </div>
                  <p className="text-sm text-gray-500">{review.item_name as string}</p>
                </div>
                <span className={`px-2 py-1 rounded-full text-xs ${
                  review.status === 'approved' ? 'bg-green-100 text-green-800' :
                  review.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                  'bg-red-100 text-red-800'
                }`}>
                  {review.status as string}
                </span>
              </div>
              <p className="mt-3 text-gray-700">{review.comment as string}</p>
              {review.status === 'pending' && (
                <div className="mt-4 flex gap-2">
                  <button
                    onClick={() => approveMutation.mutate(review.id as number)}
                    className="btn-success text-sm"
                  >
                    Approve
                  </button>
                  <button
                    onClick={() => rejectMutation.mutate(review.id as number)}
                    className="btn-danger text-sm"
                  >
                    Reject
                  </button>
                </div>
              )}
            </div>
          ))
        )}
      </div>
    </div>
  );
}
