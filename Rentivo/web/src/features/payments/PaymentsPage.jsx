import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Plus } from 'lucide-react'
import { get } from '../../services/apiClient'
import DataTable from '../../components/DataTable/DataTable'
import StatusBadge from '../../components/StatusBadge/StatusBadge'

const columns = [
  { key: 'receipt_number', label: 'Receipt #' },
  { key: 'customer_name', label: 'Customer' },
  { key: 'payment_date', label: 'Date' },
  { key: 'payment_method', label: 'Method' },
  { key: 'amount', label: 'Amount', render: (v) => `₹${Number(v).toLocaleString('en-IN')}` },
  { key: 'allocated_amount', label: 'Allocated', render: (v) => `₹${Number(v ?? 0).toLocaleString('en-IN')}` },
  { key: 'status', label: 'Status', render: (v) => <StatusBadge status={v} /> },
]

export default function PaymentsPage() {
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['payments', page, search],
    queryFn: () => get('/payments', { page, search, per_page: 20 }),
  })

  const rows = (data?.data ?? []).map((row) => ({
    ...row,
    customer_name: row.customer?.name ?? row.customer_name,
  }))

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <div><h1 className="text-2xl font-bold text-gray-900">Payments</h1><p className="mt-1 text-sm text-gray-500">Customer payment receipts</p></div>
        <button className="flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
          <Plus className="h-4 w-4" /> Record Payment
        </button>
      </div>
      <DataTable columns={columns} data={rows} loading={isLoading}
        pagination={data ? { page, perPage: 20, total: data.meta?.total ?? data.total ?? rows.length } : undefined}
        onPageChange={setPage} onSearch={setSearch} searchPlaceholder="Search payments…" />
    </div>
  )
}
