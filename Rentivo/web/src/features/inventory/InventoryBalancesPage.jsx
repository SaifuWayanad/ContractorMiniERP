import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { get } from '../../services/apiClient'
import DataTable from '../../components/DataTable/DataTable'

const columns = [
  { key: 'product_name', label: 'Product' },
  { key: 'location_type', label: 'Location Type' },
  { key: 'location_name', label: 'Location / Project' },
  { key: 'quantity_on_hand', label: 'On Hand', render: (v) => <span className="font-medium">{v}</span> },
  { key: 'quantity_reserved', label: 'Reserved' },
]

export default function InventoryBalancesPage() {
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['inventory-balances', page, search],
    queryFn: () => get('/inventory/balances', { page, search, per_page: 25 }),
  })

  const rows = (data?.data ?? []).map((row) => ({
    ...row,
    product_name: row.product?.name ?? row.product_name,
    location_name: row.location?.name ?? row.project?.name ?? row.location_name ?? '—',
  }))

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Inventory Balances</h1>
        <p className="mt-1 text-sm text-gray-500">Current stock quantities across all locations and projects</p>
      </div>
      <DataTable columns={columns} data={rows} loading={isLoading}
        pagination={data ? { page, perPage: 25, total: data.meta?.total ?? data.total ?? rows.length } : undefined}
        onPageChange={setPage} onSearch={setSearch} searchPlaceholder="Search by product or location…" />
    </div>
  )
}
