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
  { key: 'category_name', label: 'Category' },
  { key: 'unit_of_measure', label: 'UOM' },
  { key: 'minimum_rental_amount', label: 'Min Amount', render: (v) => `₹${v}` },
  { key: 'is_active', label: 'Status', render: (v) => <StatusBadge status={v ? 'ACTIVE' : 'INACTIVE'} /> },
  { key: 'actions', label: '', width: 'w-28' },
]

export default function ProductsPage() {
  const navigate = useNavigate()
  const qc = useQueryClient()
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['products', page, search],
    queryFn: () => get('/products', { page, search, per_page: 20 }),
  })

  const deleteMutation = useMutation({
    mutationFn: (id) => del(`/products/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['products'] }),
  })

  const rows = (data?.data ?? []).map((row) => ({
    ...row,
    category_name: row.category?.name ?? row.category_name,
    actions: (
      <ActionButtons
        onView={() => navigate(`/masters/products/${row.id}`)}
        onEdit={() => navigate(`/masters/products/${row.id}/edit`)}
        onDelete={() => deleteMutation.mutate(row.id)}
        deleteLabel={row.name}
      />
    ),
  }))

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Products</h1>
          <p className="mt-1 text-sm text-gray-500">Manage rental products and pricing</p>
        </div>
        <button onClick={() => navigate('/masters/products/new')} className="flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
          <Plus className="h-4 w-4" /> Add Product
        </button>
      </div>
      <DataTable columns={columns} data={rows} loading={isLoading}
        pagination={data ? { page, perPage: 20, total: data.meta?.total ?? data.total ?? rows.length } : undefined}
        onPageChange={setPage} onSearch={setSearch} searchPlaceholder="Search products…" />
    </div>
  )
}
