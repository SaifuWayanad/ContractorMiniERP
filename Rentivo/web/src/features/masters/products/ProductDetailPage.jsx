import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ChevronRight, Pencil, ToggleLeft, ToggleRight, Trash2 } from 'lucide-react'
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

export default function ProductDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const qc = useQueryClient()
  const [deleteOpen, setDeleteOpen] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['product', id],
    queryFn: () => get(`/products/${id}`),
  })

  const toggleMutation = useMutation({
    mutationFn: (p) => put(`/products/${p.id}`, { ...p, category_id: p.category_id, is_active: !p.is_active }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['product', id] }),
  })

  const deleteMutation = useMutation({
    mutationFn: () => del(`/products/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['products'] }); navigate('/masters/products') },
  })

  if (isLoading) return <div className="animate-pulse h-8 w-48 rounded bg-gray-200" />
  const p = data?.data ?? data
  if (!p) return <p className="text-gray-500">Product not found.</p>

  return (
    <div className="max-w-2xl">
      <nav className="mb-6 flex items-center gap-1.5 text-sm text-gray-500">
        <button onClick={() => navigate('/masters/products')} className="hover:text-gray-900">Products</button>
        <ChevronRight className="h-3.5 w-3.5" />
        <span className="text-gray-900">{p.name}</span>
      </nav>
      <div className="mb-6 flex items-start justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{p.name}</h1>
          <p className="mt-1 text-sm text-gray-500">{p.code} · {p.category?.name}</p>
        </div>
        <div className="flex items-center gap-2">
          <button onClick={() => toggleMutation.mutate(p)} disabled={toggleMutation.isPending}
            className={`flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition-colors disabled:opacity-60 ${p.is_active ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100' : 'border-gray-200 bg-gray-50 text-gray-600 hover:bg-gray-100'}`}>
            {p.is_active ? <ToggleRight className="h-4 w-4" /> : <ToggleLeft className="h-4 w-4" />}
            {p.is_active ? 'Active' : 'Inactive'}
          </button>
          <button onClick={() => navigate(`/masters/products/${id}/edit`)} className="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            <Pencil className="h-4 w-4" /> Edit
          </button>
          <button onClick={() => setDeleteOpen(true)} className="flex items-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50">
            <Trash2 className="h-4 w-4" /> Delete
          </button>
        </div>
      </div>

      <div className="space-y-4">
        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
          <h2 className="mb-4 text-xs font-semibold uppercase tracking-wide text-gray-400">General</h2>
          <dl className="grid grid-cols-2 gap-6">
            <DetailField label="Code" value={p.code} />
            <DetailField label="Category" value={p.category?.name} />
            <DetailField label="Unit of Measure" value={p.unit_of_measure} />
            <DetailField label="Tracking Method" value={p.tracking_method} />
            <div>
              <dt className="text-xs font-medium uppercase tracking-wide text-gray-400">Status</dt>
              <dd className="mt-1"><StatusBadge status={p.is_active ? 'ACTIVE' : 'INACTIVE'} /></dd>
            </div>
            <div className="col-span-2"><DetailField label="Description" value={p.description} /></div>
          </dl>
        </div>

        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
          <h2 className="mb-4 text-xs font-semibold uppercase tracking-wide text-gray-400">Rental Pricing</h2>
          <dl className="grid grid-cols-3 gap-6">
            <DetailField label="Min Days" value={p.minimum_rental_days} />
            <DetailField label="Min Amount" value={p.minimum_rental_amount ? `₹${p.minimum_rental_amount}` : null} />
            <DetailField label="Add-on / Day" value={p.addon_amount_per_day ? `₹${p.addon_amount_per_day}` : null} />
          </dl>
        </div>
      </div>

      <Modal isOpen={deleteOpen} onClose={() => setDeleteOpen(false)} title="Delete Product" confirmLabel="Delete" confirmVariant="danger" onConfirm={() => deleteMutation.mutate()} loading={deleteMutation.isPending}>
        <p className="text-sm text-gray-600">Are you sure you want to delete <span className="font-medium">{p.name}</span>?</p>
        {deleteMutation.error && <p className="mt-3 text-sm text-red-600">{deleteMutation.error.response?.data?.message ?? 'Delete failed'}</p>}
      </Modal>
    </div>
  )
}
