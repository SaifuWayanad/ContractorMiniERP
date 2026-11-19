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

const STATUS_CYCLE = { ACTIVE: 'INACTIVE', INACTIVE: 'MAINTENANCE', MAINTENANCE: 'ACTIVE' }

export default function VehicleDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const qc = useQueryClient()
  const [deleteOpen, setDeleteOpen] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['vehicle', id],
    queryFn: () => get(`/vehicles/${id}`),
  })

  const statusMutation = useMutation({
    mutationFn: ({ vehicle, nextStatus }) => put(`/vehicles/${vehicle.id}`, { ...vehicle, status: nextStatus }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['vehicle', id] }),
  })

  const deleteMutation = useMutation({
    mutationFn: () => del(`/vehicles/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['vehicles'] }); navigate('/masters/vehicles') },
  })

  if (isLoading) return <div className="animate-pulse h-8 w-48 rounded bg-gray-200" />
  const v = data?.data ?? data
  if (!v) return <p className="text-gray-500">Vehicle not found.</p>

  const nextStatus = STATUS_CYCLE[v.status] ?? 'ACTIVE'

  return (
    <div className="max-w-2xl">
      <nav className="mb-6 flex items-center gap-1.5 text-sm text-gray-500">
        <button onClick={() => navigate('/masters/vehicles')} className="hover:text-gray-900">Vehicles</button>
        <ChevronRight className="h-3.5 w-3.5" />
        <span className="text-gray-900">{v.registration_number}</span>
      </nav>
      <div className="mb-6 flex items-start justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{v.registration_number}</h1>
          <p className="mt-1 text-sm text-gray-500">{v.make_model}</p>
        </div>
        <div className="flex items-center gap-2">
          <button onClick={() => statusMutation.mutate({ vehicle: v, nextStatus })} disabled={statusMutation.isPending}
            className="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-60">
            Set {nextStatus.charAt(0) + nextStatus.slice(1).toLowerCase()}
          </button>
          <button onClick={() => navigate(`/masters/vehicles/${id}/edit`)} className="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            <Pencil className="h-4 w-4" /> Edit
          </button>
          <button onClick={() => setDeleteOpen(true)} className="flex items-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50">
            <Trash2 className="h-4 w-4" /> Delete
          </button>
        </div>
      </div>
      <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <dl className="grid grid-cols-2 gap-6">
          <DetailField label="Registration Number" value={v.registration_number} />
          <div>
            <dt className="text-xs font-medium uppercase tracking-wide text-gray-400">Status</dt>
            <dd className="mt-1"><StatusBadge status={v.status} /></dd>
          </div>
          <DetailField label="Vehicle Type" value={v.vehicle_type} />
          <DetailField label="Make / Model" value={v.make_model} />
          <DetailField label="Capacity" value={v.capacity} />
          <div className="col-span-2"><DetailField label="Notes" value={v.notes} /></div>
        </dl>
      </div>
      <Modal isOpen={deleteOpen} onClose={() => setDeleteOpen(false)} title="Delete Vehicle" confirmLabel="Delete" confirmVariant="danger" onConfirm={() => deleteMutation.mutate()} loading={deleteMutation.isPending}>
        <p className="text-sm text-gray-600">Are you sure you want to delete <span className="font-medium">{v.registration_number}</span>?</p>
        {deleteMutation.error && <p className="mt-3 text-sm text-red-600">{deleteMutation.error.response?.data?.message ?? 'Delete failed'}</p>}
      </Modal>
    </div>
  )
}
