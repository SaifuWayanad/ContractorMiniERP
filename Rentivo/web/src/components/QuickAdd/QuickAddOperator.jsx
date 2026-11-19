import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { post } from '../../services/apiClient'
import Modal from '../Modal/Modal'
import FormField from '../Form/FormField'

export default function QuickAddOperator({ isOpen, onClose, onSuccess }) {
  const qc = useQueryClient()
  const { register, handleSubmit, reset, formState: { errors } } = useForm()

  const mutation = useMutation({
    mutationFn: (data) => post('/operators', { ...data, status: 'ACTIVE', is_active: true }),
    onSuccess: (res) => {
      qc.invalidateQueries({ queryKey: ['operators-all'] })
      onSuccess(res)
      reset()
    },
  })

  const handleClose = () => { reset(); mutation.reset(); onClose() }

  return (
    <Modal
      isOpen={isOpen}
      onClose={handleClose}
      title="Quick Add Operator"
      onConfirm={handleSubmit((d) => mutation.mutate(d))}
      confirmLabel="Add Operator"
      loading={mutation.isPending}
    >
      <form className="space-y-3">
        <FormField label="Name" name="name" register={register} error={errors.name} required />
        <div className="grid grid-cols-2 gap-3">
          <FormField label="Phone" name="phone" register={register} error={errors.phone} />
          <FormField label="License Number" name="license_number" register={register} error={errors.license_number} />
        </div>
        {mutation.error && (
          <p className="text-sm text-red-600">{mutation.error.response?.data?.message ?? 'Save failed'}</p>
        )}
      </form>
    </Modal>
  )
}
