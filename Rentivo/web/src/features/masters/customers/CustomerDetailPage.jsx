import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ChevronRight, Eye, Pencil, Trash2 } from 'lucide-react'
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

const NEXT_STATUS = { ACTIVE: 'INACTIVE', INACTIVE: 'ACTIVE', BLOCKED: 'ACTIVE' }
const STATUS_LABEL = { ACTIVE: 'Set Inactive', INACTIVE: 'Set Active', BLOCKED: 'Set Active' }

export default function CustomerDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const qc = useQueryClient()
  const [deleteOpen, setDeleteOpen] = useState(false)
  const [blockOpen, setBlockOpen] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['customer', id],
    queryFn: () => get(`/customers/${id}`),
  })

  const statusMutation = useMutation({
    mutationFn: ({ cust, nextStatus }) => put(`/customers/${cust.id}`, { ...cust, status: nextStatus }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['customer', id] }),
  })

  const deleteMutation = useMutation({
    mutationFn: () => del(`/customers/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['customers'] }); navigate('/masters/customers') },
  })

  if (isLoading) return <div className="animate-pulse h-8 w-48 rounded bg-gray-200" />
  const c = data?.data ?? data
  if (!c) return <p className="text-gray-500">Customer not found.</p>

  return (
    <div className="max-w-2xl">
      <nav className="mb-6 flex items-center gap-1.5 text-sm text-gray-500">
        <button onClick={() => navigate('/masters/customers')} className="hover:text-gray-900">Customers</button>
        <ChevronRight className="h-3.5 w-3.5" />
        <span className="text-gray-900">{c.name}</span>
      </nav>
      <div className="mb-6 flex items-start justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{c.name}</h1>
          <p className="mt-1 text-sm text-gray-500">{c.code}{c.legal_name ? ` · ${c.legal_name}` : ''}</p>
        </div>
        <div className="flex items-center gap-2">
          <button onClick={() => navigate(`/customers/${id}/360`)} className="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            <Eye className="h-4 w-4" /> 360°
          </button>
          <button onClick={() => statusMutation.mutate({ cust: c, nextStatus: NEXT_STATUS[c.status] })} disabled={statusMutation.isPending}
            className="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-60">
            {STATUS_LABEL[c.status]}
          </button>
          <button onClick={() => statusMutation.mutate({ cust: c, nextStatus: 'BLOCKED' })} disabled={statusMutation.isPending || c.status === 'BLOCKED'}
            className="rounded-lg border border-orange-200 px-3 py-2 text-sm font-medium text-orange-600 hover:bg-orange-50 disabled:opacity-40">
            Block
          </button>
          <button onClick={() => navigate(`/masters/customers/${id}/edit`)} className="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            <Pencil className="h-4 w-4" /> Edit
          </button>
          <button onClick={() => setDeleteOpen(true)} className="flex items-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50">
            <Trash2 className="h-4 w-4" /> Delete
          </button>
        </div>
      </div>

      <div className="space-y-4">
        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
          <h2 className="mb-4 text-xs font-semibold uppercase tracking-wide text-gray-400">Basic Information</h2>
          <dl className="grid grid-cols-2 gap-6">
            <DetailField label="Code" value={c.code} />
            <div>
              <dt className="text-xs font-medium uppercase tracking-wide text-gray-400">Status</dt>
              <dd className="mt-1"><StatusBadge status={c.status} /></dd>
            </div>
            <DetailField label="Name" value={c.name} />
            <DetailField label="Legal Name" value={c.legal_name} />
          </dl>
        </div>

        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
          <h2 className="mb-4 text-xs font-semibold uppercase tracking-wide text-gray-400">Contact</h2>
          <dl className="grid grid-cols-2 gap-6">
            <DetailField label="Contact Person" value={c.contact_person} />
            <DetailField label="Phone" value={c.phone} />
            <DetailField label="Email" value={c.email} />
            <div className="col-span-2"><DetailField label="Billing Address" value={c.billing_address} /></div>
          </dl>
        </div>

        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
          <h2 className="mb-4 text-xs font-semibold uppercase tracking-wide text-gray-400">Financial</h2>
          <dl className="grid grid-cols-2 gap-6">
            <DetailField label="Tax Number" value={c.tax_number} />
            <DetailField label="Credit Limit" value={c.credit_limit ? `₹${c.credit_limit}` : null} />
            <DetailField label="Payment Terms" value={c.payment_terms} />
          </dl>
        </div>

        {c.notes && (
          <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <h2 className="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Notes</h2>
            <p className="text-sm text-gray-700">{c.notes}</p>
          </div>
        )}
      </div>

      <Modal isOpen={deleteOpen} onClose={() => setDeleteOpen(false)} title="Delete Customer" confirmLabel="Delete" confirmVariant="danger" onConfirm={() => deleteMutation.mutate()} loading={deleteMutation.isPending}>
        <p className="text-sm text-gray-600">Are you sure you want to delete <span className="font-medium">{c.name}</span>?</p>
        {deleteMutation.error && <p className="mt-3 text-sm text-red-600">{deleteMutation.error.response?.data?.message ?? 'Delete failed'}</p>}
      </Modal>
    </div>
  )
}
