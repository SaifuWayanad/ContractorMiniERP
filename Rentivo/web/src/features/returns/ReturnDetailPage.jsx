import { useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { get, post } from '../../services/apiClient'
import StatusBadge from '../../components/StatusBadge/StatusBadge'

export default function ReturnDetailPage() {
  const { id } = useParams()
  const qc = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['return', id],
    queryFn: () => get(`/returns/${id}`),
  })

  const equipmentReturn = data?.data ?? data

  const actionMutation = useMutation({
    mutationFn: (action) => post(`/returns/${id}/${action}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['return', id] }),
  })

  if (isLoading) return <div className="animate-pulse h-8 w-48 rounded bg-gray-200" />
  if (!equipmentReturn) return <p className="text-gray-500">Return not found.</p>

  const canSubmit = equipmentReturn.status === 'DRAFT'
  const canApprove = equipmentReturn.status === 'SUBMITTED'
  const canPost = equipmentReturn.status === 'APPROVED'

  return (
    <div className="max-w-3xl">
      <div className="mb-6 flex items-start justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{equipmentReturn.return_number}</h1>
          <p className="mt-1 text-sm text-gray-500">Return from {equipmentReturn.project?.name}</p>
        </div>
        <StatusBadge status={equipmentReturn.status} />
      </div>

      <div className="mb-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 grid grid-cols-2 gap-4 text-sm">
        <div><span className="font-medium text-gray-600">Customer:</span> {equipmentReturn.customer?.name}</div>
        <div><span className="font-medium text-gray-600">Project:</span> {equipmentReturn.project?.name}</div>
        <div><span className="font-medium text-gray-600">Return Date:</span> {equipmentReturn.return_date}</div>
        <div><span className="font-medium text-gray-600">Warehouse:</span> {equipmentReturn.warehouse_location?.name}</div>
      </div>

      <div className="mb-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
        <div className="border-b border-gray-200 px-6 py-4"><h3 className="font-semibold">Line Items</h3></div>
        <table className="w-full text-sm">
          <thead className="bg-gray-50 border-b border-gray-200">
            <tr>
              <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th>
              <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Qty Returned</th>
              <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Condition</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {(equipmentReturn.details ?? []).map((d) => (
              <tr key={d.id}>
                <td className="px-4 py-3">{d.product?.name}</td>
                <td className="px-4 py-3">{d.quantity_returned}</td>
                <td className="px-4 py-3"><span className="text-xs font-medium uppercase">{d.condition}</span></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className="flex items-center gap-3">
        {canSubmit && <button onClick={() => actionMutation.mutate('submit')} disabled={actionMutation.isPending} className="rounded-lg bg-yellow-600 px-4 py-2 text-sm font-semibold text-white hover:bg-yellow-700 disabled:opacity-60">Submit</button>}
        {canApprove && <button onClick={() => actionMutation.mutate('approve')} disabled={actionMutation.isPending} className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">Approve</button>}
        {canPost && <button onClick={() => actionMutation.mutate('post')} disabled={actionMutation.isPending} className="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-60">Post</button>}
      </div>
    </div>
  )
}
