import { useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { get, post } from '../../services/apiClient'
import StatusBadge from '../../components/StatusBadge/StatusBadge'

export default function ProjectClosurePage() {
  const { id } = useParams()
  const qc = useQueryClient()

  const { data: reconciliationData, isLoading } = useQuery({
    queryKey: ['project-closure-reconciliation', id],
    queryFn: () => get(`/projects/${id}/closure/reconciliation`),
  })

  const initiateMutation = useMutation({
    mutationFn: () => post(`/projects/${id}/closure/initiate`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['project-closure-reconciliation', id] }),
  })

  const reconciliation = reconciliationData?.data ?? reconciliationData

  return (
    <div className="max-w-3xl">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Project Closure</h1>
        <p className="mt-1 text-sm text-gray-500">Reconcile and close this project</p>
      </div>

      {isLoading ? (
        <div className="animate-pulse h-40 rounded-xl bg-gray-200" />
      ) : !reconciliation ? (
        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 text-center">
          <p className="text-gray-500 mb-4">No active closure process. Initiate closure to begin reconciliation.</p>
          <button onClick={() => initiateMutation.mutate()} disabled={initiateMutation.isPending}
            className="rounded-lg bg-orange-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-60">
            Initiate Closure
          </button>
        </div>
      ) : (
        <div className="space-y-6">
          <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <div className="flex items-center justify-between mb-4">
              <h3 className="font-semibold text-gray-900">Closure Status</h3>
              <StatusBadge status={reconciliation.status} />
            </div>
            <div className="grid grid-cols-3 gap-4 text-sm">
              <div className="text-center"><p className="text-2xl font-bold">{reconciliation.total_dispatched_quantity ?? '—'}</p><p className="text-gray-500 text-xs">Dispatched</p></div>
              <div className="text-center"><p className="text-2xl font-bold">{reconciliation.total_returned_quantity ?? '—'}</p><p className="text-gray-500 text-xs">Returned</p></div>
              <div className="text-center"><p className={`text-2xl font-bold ${(reconciliation.outstanding_quantity ?? 0) > 0 ? 'text-red-600' : 'text-green-600'}`}>{reconciliation.outstanding_quantity ?? '—'}</p><p className="text-gray-500 text-xs">Outstanding</p></div>
            </div>
          </div>

          <div className="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
            <div className="border-b border-gray-200 px-6 py-4"><h3 className="font-semibold">Equipment Summary</h3></div>
            <table className="w-full text-sm">
              <thead className="bg-gray-50 border-b border-gray-200">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Dispatched</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Returned</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Outstanding</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {(reconciliation.details ?? []).map((d, i) => (
                  <tr key={d.product_id ?? i}>
                    <td className="px-4 py-3">{d.product?.name ?? d.product_name}</td>
                    <td className="px-4 py-3">{d.dispatched_quantity}</td>
                    <td className="px-4 py-3">{d.returned_quantity}</td>
                    <td className={`px-4 py-3 font-medium ${d.outstanding_quantity > 0 ? 'text-red-600' : 'text-green-600'}`}>{d.outstanding_quantity}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  )
}
