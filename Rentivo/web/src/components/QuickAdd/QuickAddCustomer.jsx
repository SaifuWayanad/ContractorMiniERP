import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { post } from '../../services/apiClient'
import Modal from '../Modal/Modal'
import FormField from '../Form/FormField'

export default function QuickAddCustomer({ isOpen, onClose, onSuccess }) {
  const qc = useQueryClient()
  const { register, handleSubmit, reset, formState: { errors } } = useForm()

  const mutation = useMutation({
    mutationFn: (data) => post('/customers', { ...data, status: 'ACTIVE' }),
    onSuccess: (res) => {
      qc.invalidateQueries({ queryKey: ['customers-all'] })
      onSuccess(res)
      reset()
    },
  })

  const handleClose = () => { reset(); mutation.reset(); onClose() }

  return (
    <Modal
      isOpen={isOpen}
      onClose={handleClose}
      title="Quick Add Customer"
      onConfirm={handleSubmit((d) => mutation.mutate(d))}
      confirmLabel="Add Customer"
      loading={mutation.isPending}
    >
      <form className="space-y-3">
        <div className="grid grid-cols-2 gap-3">
          <FormField label="Code" name="code" register={register} error={errors.code} />
          <FormField label="Name" name="name" register={register} error={errors.name} required />
        </div>
        <FormField label="Contact Person" name="contact_person" register={register} error={errors.contact_person} />
        <div className="grid grid-cols-2 gap-3">
          <FormField label="Phone" name="phone" register={register} error={errors.phone} />
          <FormField label="Email" name="email" type="email" register={register} error={errors.email} />
        </div>
        {mutation.error && (
          <p className="text-sm text-red-600">{mutation.error.response?.data?.message ?? 'Save failed'}</p>
        )}
      </form>
    </Modal>
  )
}
