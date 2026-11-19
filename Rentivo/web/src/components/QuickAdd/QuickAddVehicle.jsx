import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { post } from '../../services/apiClient'
import Modal from '../Modal/Modal'
import FormField from '../Form/FormField'

export default function QuickAddVehicle({ isOpen, onClose, onSuccess }) {
  const qc = useQueryClient()
  const { register, handleSubmit, reset, formState: { errors } } = useForm()

  const mutation = useMutation({
    mutationFn: (data) => post('/vehicles', { ...data, status: 'ACTIVE', is_active: true }),
    onSuccess: (res) => {
      qc.invalidateQueries({ queryKey: ['vehicles-all'] })
      onSuccess(res)
      reset()
    },
  })

  const handleClose = () => { reset(); mutation.reset(); onClose() }

  return (
    <Modal
      isOpen={isOpen}
      onClose={handleClose}
      title="Quick Add Vehicle"
      onConfirm={handleSubmit((d) => mutation.mutate(d))}
      confirmLabel="Add Vehicle"
      loading={mutation.isPending}
    >
      <form className="space-y-3">
        <FormField label="Registration Number" name="registration_number" register={register} error={errors.registration_number} required />
        <div className="grid grid-cols-2 gap-3">
          <FormField label="Vehicle Type" name="vehicle_type" register={register} error={errors.vehicle_type} />
          <FormField label="Make / Model" name="make_model" register={register} error={errors.make_model} />
        </div>
        {mutation.error && (
          <p className="text-sm text-red-600">{mutation.error.response?.data?.message ?? 'Save failed'}</p>
        )}
      </form>
    </Modal>
  )
}
