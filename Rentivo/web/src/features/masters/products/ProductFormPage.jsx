import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import { ChevronRight, Save } from 'lucide-react'
import { get, post, put } from '../../../services/apiClient'
import FormField from '../../../components/Form/FormField'
import CustomSelect from '../../../components/Form/CustomSelect'
import QuickAddCategory from '../../../components/QuickAdd/QuickAddCategory'
import QuickAddUOM from '../../../components/QuickAdd/QuickAddUOM'

const DEFAULT_UOM = [
  { value: 'Days', label: 'Days' },
  { value: 'Hours', label: 'Hours' },
  { value: 'Weeks', label: 'Weeks' },
  { value: 'Months', label: 'Months' },
  { value: 'Nos', label: 'Nos' },
  { value: 'Sets', label: 'Sets' },
  { value: 'Trips', label: 'Trips' },
]

const schema = z.object({
  category_id: z.string().optional(),
  code: z.string().optional(),
  name: z.string().min(1, 'Required'),
  description: z.string().optional(),
  unit_of_measure: z.string().optional(),
  tracking_method: z.enum(['QUANTITY', 'SERIALIZED']).default('QUANTITY'),
  minimum_rental_days: z.string().default('1'),
  minimum_rental_amount: z.string().default('0'),
  addon_amount_per_day: z.string().default('0'),
  is_active: z.boolean().default(true),
})

export default function ProductFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const qc = useQueryClient()
  const isEdit = Boolean(id)
  const [uomOptions, setUomOptions] = useState(DEFAULT_UOM)

  const { data: existing, isLoading } = useQuery({
    queryKey: ['product', id],
    queryFn: () => get(`/products/${id}`),
    enabled: isEdit,
  })

  const { data: categoriesData } = useQuery({
    queryKey: ['categories-all'],
    queryFn: () => get('/categories', { per_page: 200 }),
  })

  const { register, handleSubmit, reset, setValue, watch, formState: { errors } } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { tracking_method: 'QUANTITY', minimum_rental_days: '1', minimum_rental_amount: '0', addon_amount_per_day: '0', is_active: true },
  })

  useEffect(() => {
    if (existing) {
      const p = existing.data ?? existing
      // Ensure current UOM value exists in options
      if (p.unit_of_measure && !uomOptions.find((o) => o.value === p.unit_of_measure)) {
        setUomOptions((prev) => [...prev, { value: p.unit_of_measure, label: p.unit_of_measure }])
      }
      reset({ ...p, category_id: p.category_id ? String(p.category_id) : '' })
    }
  }, [existing, reset])

  const mutation = useMutation({
    mutationFn: (payload) => isEdit ? put(`/products/${id}`, payload) : post('/products', payload),
    onSuccess: (res) => { qc.invalidateQueries({ queryKey: ['products'] }); navigate(`/masters/products/${(res.data ?? res).id}`) },
  })

  const categories = (categoriesData?.data ?? []).map((c) => ({ value: c.id, label: c.name }))

  const handleUomQuickAdd = (item, originalOnSuccess) => {
    setUomOptions((prev) =>
      prev.find((o) => o.value === item.id) ? prev : [...prev, { value: item.id, label: item.name }]
    )
    originalOnSuccess(item)
  }

  if (isEdit && isLoading) return <div className="animate-pulse h-8 w-48 rounded bg-gray-200" />

  return (
    <div>
      <nav className="mb-6 flex items-center gap-1.5 text-sm text-gray-500">
        <button onClick={() => navigate('/masters/products')} className="hover:text-gray-900">Products</button>
        <ChevronRight className="h-3.5 w-3.5" />
        <span className="text-gray-900">{isEdit ? 'Edit Product' : 'New Product'}</span>
      </nav>
      <h1 className="mb-6 text-2xl font-bold text-gray-900">{isEdit ? 'Edit Product' : 'New Product'}</h1>

      <div className="space-y-5">
        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
          <h2 className="mb-4 text-sm font-semibold text-gray-700">General Information</h2>
          <div className="space-y-4">
            {/* Row 1: Category + Code */}
            <div className="grid grid-cols-2 gap-4">
              <CustomSelect label="Category" name="category_id" options={categories} register={register} setValue={setValue} watch={watch} error={errors.category_id}
                quickAdd={{ label: 'category', render: (props) => <QuickAddCategory {...props} /> }} />
              <FormField label="Code" name="code" register={register} error={errors.code} />
            </div>
            {/* Row 2: Unit of Measure + Name */}
            <div className="grid grid-cols-2 gap-4">
              <CustomSelect
                label="Unit of Measure"
                name="unit_of_measure"
                options={uomOptions}
                register={register}
                setValue={setValue}
                watch={watch}
                error={errors.unit_of_measure}
                quickAdd={{
                  label: 'unit',
                  render: ({ isOpen, onClose, onSuccess }) => (
                    <QuickAddUOM
                      isOpen={isOpen}
                      onClose={onClose}
                      onSuccess={(item) => handleUomQuickAdd(item, onSuccess)}
                    />
                  ),
                }}
              />
              <FormField label="Name" name="name" register={register} error={errors.name} required />
            </div>
            <FormField label="Description" name="description" type="textarea" register={register} error={errors.description} />
            <CustomSelect label="Tracking Method" name="tracking_method"
              options={[{ value: 'QUANTITY', label: 'Quantity' }, { value: 'SERIALIZED', label: 'Serialized' }]}
              register={register} setValue={setValue} watch={watch} />
          </div>
        </div>

        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
          <h2 className="mb-4 text-sm font-semibold text-gray-700">Rental Pricing</h2>
          <div className="grid grid-cols-3 gap-4">
            <FormField label="Min Rental Days" name="minimum_rental_days" type="number" register={register} error={errors.minimum_rental_days} />
            <FormField label="Min Rental Amount (₹)" name="minimum_rental_amount" type="number" register={register} error={errors.minimum_rental_amount} />
            <FormField label="Add-on per Day (₹)" name="addon_amount_per_day" type="number" register={register} error={errors.addon_amount_per_day} />
          </div>
        </div>

        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
          <div className="space-y-4">
            <div className="flex items-center gap-2">
              <input type="checkbox" id="is_active" {...register('is_active')} className="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
              <label htmlFor="is_active" className="text-sm text-gray-700">Active</label>
            </div>
            {mutation.error && <p className="text-sm text-red-600">{mutation.error.response?.data?.message ?? 'Save failed'}</p>}
            <div className="border-t border-gray-100 pt-4 flex justify-end">
              <button type="button" onClick={handleSubmit((d) => mutation.mutate(d))} disabled={mutation.isPending}
                className="flex items-center gap-2 rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60">
                {mutation.isPending && <span className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />}
                <Save className="h-4 w-4" />{isEdit ? 'Update Product' : 'Create Product'}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
