import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Plus } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { get, del } from '../../../services/apiClient'
import DataTable from '../../../components/DataTable/DataTable'
import StatusBadge from '../../../components/StatusBadge/StatusBadge'
import ActionButtons from '../../../components/ActionButtons'

const columns = [
  { key: 'registration_number', label: 'Reg. Number' },
  { key: 'vehicle_type', label: 'Type' },
  { key: 'make_model', label: 'Make/Model' },
  { key: 'capacity', label: 'Capacity' },
  { key: 'status', label: 'Status', render: (v) => <StatusBadge status={v} /> },
  { key: 'actions', label: '', width: 'w-28' },
]

export default function VehiclesPage() {
  const navigate = useNavigate()
  const qc = useQueryClient()
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['vehicles', page, search],
    queryFn: () => get('/vehicles', { page, search, per_page: 20 }),
  })

  const deleteMutation = useMutation({
    mutationFn: (id) => del(`/vehicles/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['vehicles'] }),
  })

  const rows = (data?.data ?? []).map((row) => ({
    ...row,
    actions: (
      <ActionButtons
        onView={() => navigate(`/masters/vehicles/${row.id}`)}
        onEdit={() => navigate(`/masters/vehicles/${row.id}/edit`)}
        onDelete={() => deleteMutation.mutate(row.id)}
        deleteLabel={row.registration_number}
      />
    ),
  }))

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Vehicles</h1>
          <p className="mt-1 text-sm text-gray-500">Manage transport vehicles</p>
        </div>
        <button onClick={() => navigate('/masters/vehicles/new')} className="flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
          <Plus className="h-4 w-4" /> Add Vehicle
        </button>
      </div>
      <DataTable columns={columns} data={rows} loading={isLoading}
        pagination={data ? { page, perPage: 20, total: data.meta?.total ?? data.total ?? rows.length } : undefined}
        onPageChange={setPage} onSearch={setSearch} searchPlaceholder="Search vehicles…" />
    </div>
  )
}
