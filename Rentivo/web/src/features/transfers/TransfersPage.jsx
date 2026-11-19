import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Plus, Eye } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { get } from '../../services/apiClient'
import DataTable from '../../components/DataTable/DataTable'
import StatusBadge from '../../components/StatusBadge/StatusBadge'

const columns = [
  { key: 'transfer_number', label: 'Transfer #' },
  { key: 'from_project_name', label: 'From Project' },
  { key: 'to_project_name', label: 'To Project' },
  { key: 'transfer_date', label: 'Date' },
  { key: 'status', label: 'Status', render: (v) => <StatusBadge status={v} /> },
  { key: 'actions', label: '' },
]

export default function TransfersPage() {
  const navigate = useNavigate()
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['transfers', page, search],
    queryFn: () => get('/transfers', { page, search, per_page: 20 }),
  })

  const rows = (data?.data ?? []).map((row) => ({
    ...row,
    from_project_name: row.from_project?.name ?? row.from_project_name,
    to_project_name: row.to_project?.name ?? row.to_project_name,
    actions: (
      <button onClick={() => navigate(`/transfers/${row.id}`)} className="rounded p-1 text-gray-400 hover:text-primary-600 hover:bg-primary-50">
        <Eye className="h-4 w-4" />
      </button>
    ),
  }))

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <div><h1 className="text-2xl font-bold text-gray-900">Transfers</h1><p className="mt-1 text-sm text-gray-500">Project-to-project equipment transfers</p></div>
        <button onClick={() => navigate('/transfers/new')} className="flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
          <Plus className="h-4 w-4" /> New Transfer
        </button>
      </div>
      <DataTable columns={columns} data={rows} loading={isLoading}
        pagination={data ? { page, perPage: 20, total: data.meta?.total ?? data.total ?? rows.length } : undefined}
        onPageChange={setPage} onSearch={setSearch} searchPlaceholder="Search transfers…" />
    </div>
  )
}
