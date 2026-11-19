import { useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { get, post } from '../../services/apiClient'
import StatusBadge from '../../components/StatusBadge/StatusBadge'

export default function DispatchDetailPage() {
  const { id } = useParams()
  const qc = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['dispatch', id],
    queryFn: () => get(`/dispatches/${id}`),
  })

  const dispatch = data?.data ?? data

  const actionMutation = useMutation({
    mutationFn: (action) => post(`/dispatches/${id}/${action}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['dispatch', id] }),
  })

  if (isLoading) return <div className="animate-pulse h-8 w-48 rounded bg-gray-200" />
  if (!dispatch) return <p className="text-gray-500">Dispatch not found.</p>

  const canSubmit = dispatch.status === 'DRAFT'
  const canApprove = dispatch.status === 'SUBMITTED'
  const canPost = dispatch.status === 'APPROVED'
  const canCancel = ['DRAFT', 'SUBMITTED'].includes(dispatch.status)

  return (
    <div className="max-w-3xl">
      <div className="mb-6 flex items-start justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{dispatch.dispatch_number}</h1>
          <p className="mt-1 text-sm text-gray-500">Dispatch to {dispatch.project?.name}</p>
        </div>
        <StatusBadge status={dispatch.status} />
      </div>

      <div className="mb-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 grid grid-cols-2 gap-4 text-sm">
        <div><span className="font-medium text-gray-600">Customer:</span> <span className="text-gray-900">{dispatch.customer?.name}</span></div>
        <div><span className="font-medium text-gray-600">Project:</span> <span className="text-gray-900">{dispatch.project?.name}</span></div>
        <div><span className="font-medium text-gray-600">Date:</span> <span className="text-gray-900">{dispatch.dispatch_date}</span></div>
        <div><span className="font-medium text-gray-600">Source Location:</span> <span className="text-gray-900">{dispatch.source_location?.name}</span></div>
      </div>

      <div className="mb-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
        <div className="border-b border-gray-200 px-6 py-4"><h3 className="font-semibold text-gray-900">Line Items</h3></div>
        <table className="w-full text-sm">
          <thead className="bg-gray-50 border-b border-gray-200">
            <tr>
              <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th>
              <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Qty</th>
              <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Min Days</th>
              <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Min Amount</th>
              <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Add-on/Day</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {(dispatch.details ?? []).map((d) => (
              <tr key={d.id}>
                <td className="px-4 py-3">{d.product?.name}</td>
                <td className="px-4 py-3">{d.quantity} {d.unit_of_measure_snapshot}</td>
                <td className="px-4 py-3">{d.minimum_rental_days_snapshot}</td>
                <td className="px-4 py-3">₹{d.minimum_rental_amount_snapshot}</td>
                <td className="px-4 py-3">₹{d.addon_amount_per_day_snapshot}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className="flex items-center gap-3">
        {canSubmit && <button onClick={() => actionMutation.mutate('submit')} disabled={actionMutation.isPending} className="rounded-lg bg-yellow-600 px-4 py-2 text-sm font-semibold text-white hover:bg-yellow-700 disabled:opacity-60">Submit</button>}
        {canApprove && <button onClick={() => actionMutation.mutate('approve')} disabled={actionMutation.isPending} className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">Approve</button>}
        {canPost && <button onClick={() => actionMutation.mutate('post')} disabled={actionMutation.isPending} className="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-60">Post</button>}
        {canCancel && <button onClick={() => actionMutation.mutate('cancel')} disabled={actionMutation.isPending} className="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 disabled:opacity-60">Cancel</button>}
      </div>
    </div>
  )
}
