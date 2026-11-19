import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Plus } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { get, del } from '../../../services/apiClient'
import DataTable from '../../../components/DataTable/DataTable'
import StatusBadge from '../../../components/StatusBadge/StatusBadge'
import ActionButtons from '../../../components/ActionButtons'

const columns = [
  { key: 'code', label: 'Code' },
  { key: 'name', label: 'Name' },
  { key: 'customer_name', label: 'Customer' },
  { key: 'start_date', label: 'Start' },
  { key: 'expected_end_date', label: 'Exp. End' },
  { key: 'status', label: 'Status', render: (v) => <StatusBadge status={v} /> },
  { key: 'actions', label: '', width: 'w-28' },
]

export default function ProjectsPage() {
  const qc = useQueryClient()
  const navigate = useNavigate()
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['projects', page, search],
    queryFn: () => get('/projects', { page, search, per_page: 20 }),
  })

  const deleteMutation = useMutation({
    mutationFn: (id) => del(`/projects/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['projects'] }),
  })

  const rows = (data?.data ?? []).map((row) => ({
    ...row,
    customer_name: row.customer?.name ?? row.customer_name,
    actions: (
      <ActionButtons
        onView={() => navigate(`/projects/${row.id}`)}
        onEdit={() => navigate(`/masters/projects/${row.id}/edit`)}
        onDelete={() => deleteMutation.mutate(row.id)}
        deleteLabel={row.name}
      />
    ),
  }))

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Projects</h1>
          <p className="mt-1 text-sm text-gray-500">Manage customer projects</p>
        </div>
        <button onClick={() => navigate('/masters/projects/new')} className="flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
          <Plus className="h-4 w-4" /> Add Project
        </button>
      </div>
      <DataTable columns={columns} data={rows} loading={isLoading}
        pagination={data ? { page, perPage: 20, total: data.meta?.total ?? data.total ?? rows.length } : undefined}
        onPageChange={setPage} onSearch={setSearch} searchPlaceholder="Search projects…" />
    </div>
  )
}
