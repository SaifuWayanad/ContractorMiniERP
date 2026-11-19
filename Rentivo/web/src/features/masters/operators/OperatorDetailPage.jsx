import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ChevronRight, Pencil, Trash2 } from 'lucide-react'
import { get, put, del } from '../../../services/apiClient'
import StatusBadge from '../../../components/StatusBadge/StatusBadge'
import Modal from '../../../components/Modal/Modal'

function DetailField({ label, value }) {
  return (
    <div>
      <dt className="text-xs font-medium uppercase tracking-wide text-gray-400">{label}</dt>
      <dd className="mt-1 text-sm text-gray-900">{value ?? <span className="text-gray-400">—</span>}</dd>
    </div>
  )
}

export default function OperatorDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const qc = useQueryClient()
  const [deleteOpen, setDeleteOpen] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['operator', id],
    queryFn: () => get(`/operators/${id}`),
  })

  const toggleMutation = useMutation({
    mutationFn: (op) => put(`/operators/${op.id}`, { ...op, status: op.status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE' }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['operator', id] }),
  })

  const deleteMutation = useMutation({
    mutationFn: () => del(`/operators/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['operators'] }); navigate('/masters/operators') },
  })

  if (isLoading) return <div className="animate-pulse h-8 w-48 rounded bg-gray-200" />
  const op = data?.data ?? data
  if (!op) return <p className="text-gray-500">Operator not found.</p>

  const isActive = op.status === 'ACTIVE'

  return (
    <div className="max-w-2xl">
      <nav className="mb-6 flex items-center gap-1.5 text-sm text-gray-500">
        <button onClick={() => navigate('/masters/operators')} className="hover:text-gray-900">Operators</button>
        <ChevronRight className="h-3.5 w-3.5" />
        <span className="text-gray-900">{op.name}</span>
      </nav>
      <div className="mb-6 flex items-start justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{op.name}</h1>
          <p className="mt-1 text-sm text-gray-500">{op.phone}</p>
        </div>
        <div className="flex items-center gap-2">
          <button onClick={() => toggleMutation.mutate(op)} disabled={toggleMutation.isPending}
            className={`rounded-lg border px-3 py-2 text-sm font-medium transition-colors disabled:opacity-60 ${isActive ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100' : 'border-gray-200 bg-gray-50 text-gray-600 hover:bg-gray-100'}`}>
            Set {isActive ? 'Inactive' : 'Active'}
          </button>
          <button onClick={() => navigate(`/masters/operators/${id}/edit`)} className="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            <Pencil className="h-4 w-4" /> Edit
          </button>
          <button onClick={() => setDeleteOpen(true)} className="flex items-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50">
            <Trash2 className="h-4 w-4" /> Delete
          </button>
        </div>
      </div>
      <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <dl className="grid grid-cols-2 gap-6">
          <DetailField label="Name" value={op.name} />
          <div>
            <dt className="text-xs font-medium uppercase tracking-wide text-gray-400">Status</dt>
            <dd className="mt-1"><StatusBadge status={op.status} /></dd>
          </div>
          <DetailField label="Phone" value={op.phone} />
          <DetailField label="License Number" value={op.license_number} />
          <DetailField label="License Expiry" value={op.license_expiry} />
          <div className="col-span-2"><DetailField label="Notes" value={op.notes} /></div>
        </dl>
      </div>
      <Modal isOpen={deleteOpen} onClose={() => setDeleteOpen(false)} title="Delete Operator" confirmLabel="Delete" confirmVariant="danger" onConfirm={() => deleteMutation.mutate()} loading={deleteMutation.isPending}>
        <p className="text-sm text-gray-600">Are you sure you want to delete <span className="font-medium">{op.name}</span>?</p>
        {deleteMutation.error && <p className="mt-3 text-sm text-red-600">{deleteMutation.error.response?.data?.message ?? 'Delete failed'}</p>}
      </Modal>
    </div>
  )
}
