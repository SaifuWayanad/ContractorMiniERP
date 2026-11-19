import { useParams, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ChevronRight } from 'lucide-react'
import { get, post } from '../../services/apiClient'
import StatusBadge from '../../components/StatusBadge/StatusBadge'

export default function TransferDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const qc = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['transfer', id],
    queryFn: () => get(`/transfers/${id}`),
  })

  const transfer = data?.data ?? data

  const actionMutation = useMutation({
    mutationFn: (action) => post(`/transfers/${id}/${action}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['transfer', id] }),
  })

  if (isLoading) return <div className="animate-pulse h-8 w-48 rounded bg-gray-200" />
  if (!transfer) return <p className="text-gray-500">Transfer not found.</p>

  const canSubmit = transfer.status === 'DRAFT'
  const canApprove = transfer.status === 'SUBMITTED'
  const canPost = transfer.status === 'APPROVED'
  const canCancel = ['DRAFT', 'SUBMITTED'].includes(transfer.status)

  return (
    <div>
      <nav className="mb-6 flex items-center gap-1.5 text-sm text-gray-500">
        <button onClick={() => navigate('/transfers')} className="hover:text-gray-900">Transfers</button>
        <ChevronRight className="h-3.5 w-3.5" />
        <span className="text-gray-900">{transfer.transfer_number}</span>
      </nav>

      <div className="mb-6 flex items-start justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{transfer.transfer_number}</h1>
          <p className="mt-1 text-sm text-gray-500">{transfer.from_project?.name} → {transfer.to_project?.name}</p>
        </div>
        <StatusBadge status={transfer.status} />
      </div>

      <div className="space-y-4">
        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 grid grid-cols-2 gap-4 text-sm">
          <div><span className="font-medium text-gray-600">From Project:</span> <span className="text-gray-900">{transfer.from_project?.name}</span></div>
          <div><span className="font-medium text-gray-600">To Project:</span> <span className="text-gray-900">{transfer.to_project?.name}</span></div>
          <div><span className="font-medium text-gray-600">From Customer:</span> <span className="text-gray-900">{transfer.from_customer?.name}</span></div>
          <div><span className="font-medium text-gray-600">To Customer:</span> <span className="text-gray-900">{transfer.to_customer?.name}</span></div>
          <div><span className="font-medium text-gray-600">Transfer Date:</span> <span className="text-gray-900">{transfer.transfer_date}</span></div>
          <div><span className="font-medium text-gray-600">Vehicle:</span> <span className="text-gray-900">{transfer.vehicle?.registration_number ?? '—'}</span></div>
        </div>

        <div className="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
          <div className="border-b border-gray-200 px-6 py-4"><h3 className="font-semibold text-gray-900">Line Items</h3></div>
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th>
                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Qty Transferred</th>
                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Remarks</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {(transfer.details ?? []).map((d) => (
                <tr key={d.id}>
                  <td className="px-4 py-3">{d.product?.name}</td>
                  <td className="px-4 py-3">{d.quantity_transferred}</td>
                  <td className="px-4 py-3 text-gray-500">{d.remarks ?? '—'}</td>
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
    </div>
  )
}
