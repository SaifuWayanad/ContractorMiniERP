import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import { Eye } from 'lucide-react'
import { get } from '../../services/apiClient'
import DataTable from '../../components/DataTable/DataTable'
import StatusBadge from '../../components/StatusBadge/StatusBadge'

const columns = [
  { key: 'invoice_number', label: 'Invoice #' },
  { key: 'customer_name', label: 'Customer' },
  { key: 'project_name', label: 'Project' },
  { key: 'invoice_date', label: 'Date' },
  { key: 'grand_total', label: 'Total', render: (v) => `₹${Number(v).toLocaleString('en-IN')}` },
  { key: 'status', label: 'Status', render: (v) => <StatusBadge status={v} /> },
  { key: 'actions', label: '' },
]

export default function InvoicesPage() {
  const navigate = useNavigate()
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['invoices', page, search],
    queryFn: () => get('/invoices', { page, search, per_page: 20 }),
  })

  const rows = (data?.data ?? []).map((row) => ({
    ...row,
    customer_name: row.customer?.name ?? row.customer_name,
    project_name: row.project?.name ?? row.project_name,
    actions: <button onClick={() => navigate(`/billing/invoices/${row.id}`)} className="rounded p-1 text-gray-400 hover:text-primary-600 hover:bg-primary-50"><Eye className="h-4 w-4" /></button>,
  }))

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Invoices</h1>
        <p className="mt-1 text-sm text-gray-500">Rental invoices and billing records</p>
      </div>
      <DataTable columns={columns} data={rows} loading={isLoading}
        pagination={data ? { page, perPage: 20, total: data.meta?.total ?? data.total ?? rows.length } : undefined}
        onPageChange={setPage} onSearch={setSearch} searchPlaceholder="Search invoices…" />
    </div>
  )
}
