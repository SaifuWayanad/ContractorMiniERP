import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { get } from '../../services/apiClient'
import DataTable from '../../components/DataTable/DataTable'

const columns = [
  { key: 'return_number', label: 'Return #' },
  { key: 'customer_name', label: 'Customer' },
  { key: 'project_name', label: 'Project' },
  { key: 'product_name', label: 'Product' },
  { key: 'return_date', label: 'Return Date' },
  { key: 'quantity_returned', label: 'Returned Qty' },
  { key: 'quantity_invoiced', label: 'Invoiced Qty' },
  { key: 'uninvoiced_quantity', label: 'Uninvoiced Qty', render: (v) => <span className="font-semibold text-orange-700">{v}</span> },
  { key: 'estimated_amount', label: 'Est. Amount', render: (v) => `₹${Number(v ?? 0).toLocaleString('en-IN')}` },
]

export default function UninvoicedReturnsPage() {
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['uninvoiced-returns', page, search],
    queryFn: () => get('/billing/uninvoiced-returns', { page, search, per_page: 20 }),
  })

  const rows = (data?.data ?? []).map((row) => ({
    ...row,
    customer_name: row.customer?.name ?? row.return?.customer?.name ?? row.customer_name,
    project_name: row.project?.name ?? row.return?.project?.name ?? row.project_name,
    product_name: row.product?.name ?? row.product_name,
  }))

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Uninvoiced Returns</h1>
        <p className="mt-1 text-sm text-gray-500">Posted returns awaiting invoice generation</p>
      </div>
      <DataTable columns={columns} data={rows} loading={isLoading}
        pagination={data ? { page, perPage: 20, total: data.meta?.total ?? data.total ?? rows.length } : undefined}
        onPageChange={setPage} onSearch={setSearch} searchPlaceholder="Search uninvoiced returns…" />
    </div>
  )
}
