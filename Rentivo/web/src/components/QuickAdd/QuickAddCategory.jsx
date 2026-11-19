import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { post } from '../../services/apiClient'
import Modal from '../Modal/Modal'
import FormField from '../Form/FormField'

export default function QuickAddCategory({ isOpen, onClose, onSuccess }) {
  const qc = useQueryClient()
  const { register, handleSubmit, reset, formState: { errors } } = useForm()

  const mutation = useMutation({
    mutationFn: (data) => post('/categories', { ...data, is_active: true }),
    onSuccess: (res) => {
      qc.invalidateQueries({ queryKey: ['categories-all'] })
      onSuccess(res)
      reset()
    },
  })

  const handleClose = () => { reset(); mutation.reset(); onClose() }

  return (
    <Modal
      isOpen={isOpen}
      onClose={handleClose}
      title="Quick Add Category"
      onConfirm={handleSubmit((d) => mutation.mutate(d))}
      confirmLabel="Add Category"
      loading={mutation.isPending}
    >
      <form className="space-y-3">
        <FormField label="Code" name="code" register={register} error={errors.code} />
        <FormField label="Name" name="name" register={register} error={errors.name} required />
        <FormField label="Description" name="description" type="textarea" register={register} error={errors.description} />
        {mutation.error && (
          <p className="text-sm text-red-600">{mutation.error.response?.data?.message ?? 'Save failed'}</p>
        )}
      </form>
    </Modal>
  )
}
