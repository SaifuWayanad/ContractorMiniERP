import { useEffect } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import { ChevronRight, Save } from 'lucide-react'
import { get, post, put } from '../../../services/apiClient'
import FormField from '../../../components/Form/FormField'
import CustomSelect from '../../../components/Form/CustomSelect'

const schema = z.object({
  registration_number: z.string().min(1, 'Required'),
  vehicle_type: z.string().optional(),
  make_model: z.string().optional(),
  capacity: z.string().optional(),
  status: z.enum(['ACTIVE', 'INACTIVE', 'MAINTENANCE']).default('ACTIVE'),
  notes: z.string().optional(),
  is_active: z.boolean().default(true),
})

export default function VehicleFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const qc = useQueryClient()
  const isEdit = Boolean(id)

  const { data: existing, isLoading } = useQuery({
    queryKey: ['vehicle', id],
    queryFn: () => get(`/vehicles/${id}`),
    enabled: isEdit,
  })

  const { register, handleSubmit, reset, setValue, watch, formState: { errors } } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { status: 'ACTIVE', is_active: true },
  })

  useEffect(() => {
    if (existing) reset({ ...(existing.data ?? existing) })
  }, [existing, reset])

  const mutation = useMutation({
    mutationFn: (payload) => isEdit ? put(`/vehicles/${id}`, payload) : post('/vehicles', payload),
    onSuccess: (res) => { qc.invalidateQueries({ queryKey: ['vehicles'] }); navigate(`/masters/vehicles/${(res.data ?? res).id}`) },
  })

  if (isEdit && isLoading) return <div className="animate-pulse h-8 w-48 rounded bg-gray-200" />

  return (
    <div>
      <nav className="mb-6 flex items-center gap-1.5 text-sm text-gray-500">
        <button onClick={() => navigate('/masters/vehicles')} className="hover:text-gray-900">Vehicles</button>
        <ChevronRight className="h-3.5 w-3.5" />
        <span className="text-gray-900">{isEdit ? 'Edit Vehicle' : 'New Vehicle'}</span>
      </nav>
      <h1 className="mb-6 text-2xl font-bold text-gray-900">{isEdit ? 'Edit Vehicle' : 'New Vehicle'}</h1>
      <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <form className="space-y-4">
          <FormField label="Registration Number" name="registration_number" register={register} error={errors.registration_number} required />
          <div className="grid grid-cols-2 gap-4">
            <FormField label="Vehicle Type" name="vehicle_type" register={register} error={errors.vehicle_type} />
            <FormField label="Make / Model" name="make_model" register={register} error={errors.make_model} />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <FormField label="Capacity" name="capacity" register={register} error={errors.capacity} />
            <CustomSelect label="Status" name="status"
              options={[{ value: 'ACTIVE', label: 'Active' }, { value: 'INACTIVE', label: 'Inactive' }, { value: 'MAINTENANCE', label: 'Maintenance' }]}
              register={register} setValue={setValue} watch={watch} />
          </div>
          <FormField label="Notes" name="notes" type="textarea" register={register} error={errors.notes} />
          {mutation.error && <p className="text-sm text-red-600">{mutation.error.response?.data?.message ?? 'Save failed'}</p>}
          <div className="border-t border-gray-100 pt-4 flex justify-end">
            <button type="button" onClick={handleSubmit((d) => mutation.mutate(d))} disabled={mutation.isPending}
              className="flex items-center gap-2 rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60">
              {mutation.isPending && <span className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />}
              <Save className="h-4 w-4" />{isEdit ? 'Update Vehicle' : 'Create Vehicle'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
