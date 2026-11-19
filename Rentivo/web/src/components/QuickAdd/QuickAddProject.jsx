import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { get, post } from '../../services/apiClient'
import Modal from '../Modal/Modal'
import FormField from '../Form/FormField'
import CustomSelect from '../Form/CustomSelect'

export default function QuickAddProject({ isOpen, onClose, onSuccess }) {
  const qc = useQueryClient()
  const { register, handleSubmit, reset, setValue, watch, formState: { errors } } = useForm({
    defaultValues: { status: 'DRAFT' },
  })

  const { data: customersData } = useQuery({
    queryKey: ['customers-all'],
    queryFn: () => get('/customers', { per_page: 500, status: 'ACTIVE' }),
    enabled: isOpen,
  })

  const mutation = useMutation({
    mutationFn: (data) => post('/projects', data),
    onSuccess: (res) => {
      qc.invalidateQueries({ queryKey: ['projects-all'] })
      onSuccess(res)
      reset()
    },
  })

  const handleClose = () => { reset(); mutation.reset(); onClose() }
  const customers = customersData?.data ?? []

  return (
    <Modal
      isOpen={isOpen}
      onClose={handleClose}
      title="Quick Add Project"
      size="lg"
      onConfirm={handleSubmit((d) => mutation.mutate(d))}
      confirmLabel="Add Project"
      loading={mutation.isPending}
    >
      <form className="space-y-3">
        <CustomSelect
          label="Customer" name="customer_id" required
          options={customers.map((c) => ({ value: c.id, label: c.name }))}
          register={register} setValue={setValue} watch={watch}
          error={errors.customer_id}
        />
        <div className="grid grid-cols-2 gap-3">
          <FormField label="Code" name="code" register={register} error={errors.code} />
          <FormField label="Name" name="name" register={register} error={errors.name} required />
        </div>
        <FormField label="Start Date" name="start_date" type="date" register={register} error={errors.start_date} required />
        {mutation.error && (
          <p className="text-sm text-red-600">{mutation.error.response?.data?.message ?? 'Save failed'}</p>
        )}
      </form>
    </Modal>
  )
}
