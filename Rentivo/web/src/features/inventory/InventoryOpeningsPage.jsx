import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Plus, Send } from 'lucide-react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import { get, post } from '../../services/apiClient'
import DataTable from '../../components/DataTable/DataTable'
import Modal from '../../components/Modal/Modal'
import FormField from '../../components/Form/FormField'
import CustomSelect from '../../components/Form/CustomSelect'
import StatusBadge from '../../components/StatusBadge/StatusBadge'
import QuickAddLocation from '../../components/QuickAdd/QuickAddLocation'
import QuickAddProduct from '../../components/QuickAdd/QuickAddProduct'

const schema = z.object({
  location_id: z.string().min(1, 'Required'),
  product_id: z.string().min(1, 'Required'),
  quantity: z.string().min(1),
  opening_date: z.string().min(1),
  remarks: z.string().optional(),
})

const columns = [
  { key: 'opening_number', label: 'Number' },
  { key: 'product_name', label: 'Product' },
  { key: 'location_name', label: 'Location' },
  { key: 'quantity', label: 'Qty' },
  { key: 'opening_date', label: 'Date' },
  { key: 'status', label: 'Status', render: (v) => <StatusBadge status={v} /> },
  { key: 'actions', label: '' },
]

export default function InventoryOpeningsPage() {
  const qc = useQueryClient()
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const [modalOpen, setModalOpen] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['inventory-openings', page, search],
    queryFn: () => get('/inventory/openings', { page, search, per_page: 20 }),
  })

  const { data: locationsData } = useQuery({ queryKey: ['locations-all'], queryFn: () => get('/locations', { per_page: 200 }) })
  const { data: productsData } = useQuery({ queryKey: ['products-all'], queryFn: () => get('/products', { per_page: 500 }) })

  const { register, handleSubmit, reset, setValue, watch, formState: { errors } } = useForm({ resolver: zodResolver(schema) })

  const createMutation = useMutation({
    mutationFn: (payload) => post('/inventory/openings', payload),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['inventory-openings'] }); setModalOpen(false); reset() },
  })

  const postMutation = useMutation({
    mutationFn: (id) => post(`/inventory/openings/${id}/post`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['inventory-openings'] }),
  })

  const rows = (data?.data ?? []).map((row) => ({
    ...row,
    product_name: row.product?.name ?? row.product_name,
    location_name: row.location?.name ?? row.location_name,
    actions: row.status === 'DRAFT' ? (
      <button onClick={() => postMutation.mutate(row.id)} className="flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-green-700 bg-green-50 hover:bg-green-100">
        <Send className="h-3 w-3" /> Post
      </button>
    ) : null,
  }))

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <div><h1 className="text-2xl font-bold text-gray-900">Inventory Openings</h1><p className="mt-1 text-sm text-gray-500">Record initial stock quantities</p></div>
        <button onClick={() => { reset(); setModalOpen(true) }} className="flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
          <Plus className="h-4 w-4" /> New Opening
        </button>
      </div>
      <DataTable columns={columns} data={rows} loading={isLoading}
        pagination={data ? { page, perPage: 20, total: data.meta?.total ?? data.total ?? rows.length } : undefined}
        onPageChange={setPage} onSearch={setSearch} searchPlaceholder="Search openings…" />
      <Modal isOpen={modalOpen} onClose={() => { setModalOpen(false); reset() }}
        title="New Inventory Opening"
        onConfirm={handleSubmit((d) => createMutation.mutate(d))}
        confirmLabel="Create" loading={createMutation.isPending}>
        <form className="space-y-4">
          <CustomSelect
            label="Location" name="location_id" required
            options={(locationsData?.data ?? []).map((l) => ({ value: l.id, label: l.name }))}
            register={register} setValue={setValue} watch={watch}
            error={errors.location_id}
            quickAdd={{ label: 'location', render: (props) => <QuickAddLocation {...props} /> }}
          />
          <CustomSelect
            label="Product" name="product_id" required
            options={(productsData?.data ?? []).map((p) => ({ value: p.id, label: p.name }))}
            register={register} setValue={setValue} watch={watch}
            error={errors.product_id}
            quickAdd={{ label: 'product', render: (props) => <QuickAddProduct {...props} /> }}
          />
          <div className="grid grid-cols-2 gap-4">
            <FormField label="Quantity" name="quantity" type="number" register={register} error={errors.quantity} required />
            <FormField label="Opening Date" name="opening_date" type="date" register={register} error={errors.opening_date} required />
          </div>
          <FormField label="Remarks" name="remarks" type="textarea" register={register} error={errors.remarks} />
        </form>
      </Modal>
    </div>
  )
}
