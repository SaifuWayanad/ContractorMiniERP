import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { get, post } from '../../services/apiClient'
import Modal from '../Modal/Modal'
import FormField from '../Form/FormField'
import CustomSelect from '../Form/CustomSelect'

export default function QuickAddProduct({ isOpen, onClose, onSuccess }) {
  const qc = useQueryClient()
  const { register, handleSubmit, reset, setValue, watch, formState: { errors } } = useForm({
    defaultValues: { tracking_method: 'QUANTITY', minimum_rental_days: '1', minimum_rental_amount: '0', addon_amount_per_day: '0' },
  })

  const { data: categoriesData } = useQuery({
    queryKey: ['categories-all'],
    queryFn: () => get('/categories', { per_page: 200 }),
    enabled: isOpen,
  })

  const mutation = useMutation({
    mutationFn: (data) => post('/products', { ...data, is_active: true }),
    onSuccess: (res) => {
      qc.invalidateQueries({ queryKey: ['products-all'] })
      onSuccess(res)
      reset()
    },
  })

  const handleClose = () => { reset(); mutation.reset(); onClose() }
  const categories = categoriesData?.data ?? []

  return (
    <Modal
      isOpen={isOpen}
      onClose={handleClose}
      title="Quick Add Product"
      size="lg"
      onConfirm={handleSubmit((d) => mutation.mutate(d))}
      confirmLabel="Add Product"
      loading={mutation.isPending}
    >
      <form className="space-y-3">
        <div className="grid grid-cols-2 gap-3">
          <FormField label="Code" name="code" register={register} error={errors.code} />
          <FormField label="Name" name="name" register={register} error={errors.name} required />
        </div>
        <div className="grid grid-cols-2 gap-3">
          <CustomSelect
            label="Category" name="category_id"
            options={categories.map((c) => ({ value: c.id, label: c.name }))}
            register={register} setValue={setValue} watch={watch}
            error={errors.category_id}
          />
          <FormField label="Unit of Measure" name="unit_of_measure" register={register} error={errors.unit_of_measure} />
        </div>
        <div className="grid grid-cols-3 gap-3">
          <FormField label="Min Days" name="minimum_rental_days" type="number" register={register} error={errors.minimum_rental_days} />
          <FormField label="Min Amount" name="minimum_rental_amount" type="number" register={register} error={errors.minimum_rental_amount} />
          <FormField label="Add-on/Day" name="addon_amount_per_day" type="number" register={register} error={errors.addon_amount_per_day} />
        </div>
        {mutation.error && (
          <p className="text-sm text-red-600">{mutation.error.response?.data?.message ?? 'Save failed'}</p>
        )}
      </form>
    </Modal>
  )
}
