import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import { Plus, Eye } from 'lucide-react'
import { get } from '../../services/apiClient'
import DataTable from '../../components/DataTable/DataTable'
import StatusBadge from '../../components/StatusBadge/StatusBadge'

const columns = [
  { key: 'return_number', label: 'Return #' },
  { key: 'customer_name', label: 'Customer' },
  { key: 'project_name', label: 'Project' },
  { key: 'return_date', label: 'Date' },
  { key: 'status', label: 'Status', render: (v) => <StatusBadge status={v} /> },
  { key: 'actions', label: '' },
]

export default function ReturnsPage() {
  const navigate = useNavigate()
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['returns', page, search],
    queryFn: () => get('/returns', { page, search, per_page: 20 }),
  })

  const rows = (data?.data ?? []).map((row) => ({
    ...row,
    customer_name: row.customer?.name ?? row.customer_name,
    project_name: row.project?.name ?? row.project_name,
    actions: <button onClick={() => navigate(`/returns/${row.id}`)} className="rounded p-1 text-gray-400 hover:text-primary-600 hover:bg-primary-50"><Eye className="h-4 w-4" /></button>,
  }))

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <div><h1 className="text-2xl font-bold text-gray-900">Returns</h1><p className="mt-1 text-sm text-gray-500">Equipment return records</p></div>
        <button onClick={() => navigate('/returns/new')} className="flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
          <Plus className="h-4 w-4" /> New Return
        </button>
      </div>
      <DataTable columns={columns} data={rows} loading={isLoading}
        pagination={data ? { page, perPage: 20, total: data.meta?.total ?? data.total ?? rows.length } : undefined}
        onPageChange={setPage} onSearch={setSearch} searchPlaceholder="Search returns…" />
    </div>
  )
}
