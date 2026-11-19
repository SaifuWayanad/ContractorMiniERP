import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ChevronRight, Pencil, ToggleLeft, ToggleRight, Trash2 } from 'lucide-react'
import { get, put, del } from '../../../services/apiClient'
import StatusBadge from '../../../components/StatusBadge/StatusBadge'
import Modal from '../../../components/Modal/Modal'

function DetailField({ label, value, colSpan }) {
  return (
    <div className={colSpan ? `col-span-${colSpan}` : ''}>
      <dt className="text-xs font-medium uppercase tracking-wide text-gray-400">{label}</dt>
      <dd className="mt-1 text-sm text-gray-900">{value ?? <span className="text-gray-400">—</span>}</dd>
    </div>
  )
}

export default function LocationDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const qc = useQueryClient()
  const [deleteOpen, setDeleteOpen] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['location', id],
    queryFn: () => get(`/locations/${id}`),
  })

  const toggleMutation = useMutation({
    mutationFn: (loc) => put(`/locations/${loc.id}`, { ...loc, is_active: !loc.is_active }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['location', id] }),
  })

  const deleteMutation = useMutation({
    mutationFn: () => del(`/locations/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['locations'] }); navigate('/masters/locations') },
  })

  if (isLoading) return <div className="animate-pulse h-8 w-48 rounded bg-gray-200" />
  const loc = data?.data ?? data
  if (!loc) return <p className="text-gray-500">Location not found.</p>

  return (
    <div className="max-w-2xl">
      <nav className="mb-6 flex items-center gap-1.5 text-sm text-gray-500">
        <button onClick={() => navigate('/masters/locations')} className="hover:text-gray-900">Locations</button>
        <ChevronRight className="h-3.5 w-3.5" />
        <span className="text-gray-900">{loc.name}</span>
      </nav>

      <div className="mb-6 flex items-start justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{loc.name}</h1>
          <p className="mt-1 text-sm text-gray-500">{loc.code} · {loc.location_type}</p>
        </div>
        <div className="flex items-center gap-2">
          <button onClick={() => toggleMutation.mutate(loc)} disabled={toggleMutation.isPending}
            className={`flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition-colors disabled:opacity-60 ${loc.is_active ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100' : 'border-gray-200 bg-gray-50 text-gray-600 hover:bg-gray-100'}`}>
            {loc.is_active ? <ToggleRight className="h-4 w-4" /> : <ToggleLeft className="h-4 w-4" />}
            {loc.is_active ? 'Active' : 'Inactive'}
          </button>
          <button onClick={() => navigate(`/masters/locations/${id}/edit`)} className="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            <Pencil className="h-4 w-4" /> Edit
          </button>
          <button onClick={() => setDeleteOpen(true)} className="flex items-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50">
            <Trash2 className="h-4 w-4" /> Delete
          </button>
        </div>
      </div>

      <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <dl className="grid grid-cols-2 gap-6">
          <DetailField label="Code" value={loc.code} />
          <DetailField label="Name" value={loc.name} />
          <DetailField label="Type" value={loc.location_type} />
          <div>
            <dt className="text-xs font-medium uppercase tracking-wide text-gray-400">Status</dt>
            <dd className="mt-1"><StatusBadge status={loc.is_active ? 'ACTIVE' : 'INACTIVE'} /></dd>
          </div>
          <DetailField label="Contact Person" value={loc.contact_person} />
          <DetailField label="Phone" value={loc.phone} />
          <DetailField label="Email" value={loc.email} />
          <div className="col-span-2"><DetailField label="Address" value={loc.address} /></div>
        </dl>
      </div>

      <Modal isOpen={deleteOpen} onClose={() => setDeleteOpen(false)} title="Delete Location" confirmLabel="Delete" confirmVariant="danger" onConfirm={() => deleteMutation.mutate()} loading={deleteMutation.isPending}>
        <p className="text-sm text-gray-600">Are you sure you want to delete <span className="font-medium">{loc.name}</span>?</p>
        {deleteMutation.error && <p className="mt-3 text-sm text-red-600">{deleteMutation.error.response?.data?.message ?? 'Delete failed'}</p>}
      </Modal>
    </div>
  )
}
