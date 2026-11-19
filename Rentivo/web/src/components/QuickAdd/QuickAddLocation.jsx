import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { post } from '../../services/apiClient'
import Modal from '../Modal/Modal'
import FormField from '../Form/FormField'
import CustomSelect from '../Form/CustomSelect'

export default function QuickAddLocation({ isOpen, onClose, onSuccess }) {
  const qc = useQueryClient()
  const { register, handleSubmit, reset, setValue, watch, formState: { errors } } = useForm({
    defaultValues: { location_type: 'WAREHOUSE' },
  })

  const mutation = useMutation({
    mutationFn: (data) => post('/locations', { ...data, is_active: true }),
    onSuccess: (res) => {
      qc.invalidateQueries({ queryKey: ['locations-all'] })
      onSuccess(res)
      reset()
    },
  })

  const handleClose = () => { reset(); mutation.reset(); onClose() }

  return (
    <Modal
      isOpen={isOpen}
      onClose={handleClose}
      title="Quick Add Location"
      onConfirm={handleSubmit((d) => mutation.mutate(d))}
      confirmLabel="Add Location"
      loading={mutation.isPending}
    >
      <form className="space-y-3">
        <div className="grid grid-cols-2 gap-3">
          <FormField label="Code" name="code" register={register} error={errors.code} />
          <FormField label="Name" name="name" register={register} error={errors.name} required />
        </div>
        <CustomSelect
          label="Type" name="location_type"
          options={[{ value: 'WAREHOUSE', label: 'Warehouse' }, { value: 'STORE', label: 'Store' }, { value: 'OTHER', label: 'Other' }]}
          register={register} setValue={setValue} watch={watch}
        />
        <FormField label="Address" name="address" type="textarea" register={register} error={errors.address} />
        {mutation.error && (
          <p className="text-sm text-red-600">{mutation.error.response?.data?.message ?? 'Save failed'}</p>
        )}
      </form>
    </Modal>
  )
}
