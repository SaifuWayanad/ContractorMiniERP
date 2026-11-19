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
  code: z.string().optional(),
  name: z.string().min(1, 'Required'),
  location_type: z.enum(['WAREHOUSE', 'STORE', 'OTHER']).default('WAREHOUSE'),
  address: z.string().optional(),
  contact_person: z.string().optional(),
  phone: z.string().optional(),
  email: z.string().optional(),
  is_active: z.boolean().default(true),
})

const TYPE_OPTIONS = [
  { value: 'WAREHOUSE', label: 'Warehouse' },
  { value: 'STORE', label: 'Store' },
  { value: 'OTHER', label: 'Other' },
]

export default function LocationFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const qc = useQueryClient()
  const isEdit = Boolean(id)

  const { data: existing, isLoading } = useQuery({
    queryKey: ['location', id],
    queryFn: () => get(`/locations/${id}`),
    enabled: isEdit,
  })

  const { register, handleSubmit, reset, setValue, watch, formState: { errors } } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { location_type: 'WAREHOUSE', is_active: true },
  })

  useEffect(() => {
    if (existing) {
      const loc = existing.data ?? existing
      reset({ ...loc })
    }
  }, [existing, reset])

  const mutation = useMutation({
    mutationFn: (payload) => isEdit ? put(`/locations/${id}`, payload) : post('/locations', payload),
    onSuccess: (res) => {
      qc.invalidateQueries({ queryKey: ['locations'] })
      const saved = res.data ?? res
      navigate(`/masters/locations/${saved.id}`)
    },
  })

  if (isEdit && isLoading) return <div className="animate-pulse h-8 w-48 rounded bg-gray-200" />

  return (
    <div>
      <nav className="mb-6 flex items-center gap-1.5 text-sm text-gray-500">
        <button onClick={() => navigate('/masters/locations')} className="hover:text-gray-900">Locations</button>
        <ChevronRight className="h-3.5 w-3.5" />
        <span className="text-gray-900">{isEdit ? 'Edit Location' : 'New Location'}</span>
      </nav>

      <h1 className="mb-6 text-2xl font-bold text-gray-900">{isEdit ? 'Edit Location' : 'New Location'}</h1>

      <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <form className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <FormField label="Code" name="code" register={register} error={errors.code} />
            <FormField label="Name" name="name" register={register} error={errors.name} required />
          </div>
          <CustomSelect label="Type" name="location_type" options={TYPE_OPTIONS} register={register} setValue={setValue} watch={watch} />
          <FormField label="Address" name="address" type="textarea" register={register} error={errors.address} />
          <div className="grid grid-cols-2 gap-4">
            <FormField label="Contact Person" name="contact_person" register={register} error={errors.contact_person} />
            <FormField label="Phone" name="phone" register={register} error={errors.phone} />
          </div>
          <FormField label="Email" name="email" type="email" register={register} error={errors.email} />
          <div className="flex items-center gap-2 pt-1">
            <input type="checkbox" id="is_active" {...register('is_active')} className="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            <label htmlFor="is_active" className="text-sm text-gray-700">Active</label>
          </div>
          {mutation.error && <p className="text-sm text-red-600">{mutation.error.response?.data?.message ?? 'Save failed'}</p>}
          <div className="border-t border-gray-100 pt-4 flex justify-end">
            <button type="button" onClick={handleSubmit((d) => mutation.mutate(d))} disabled={mutation.isPending}
              className="flex items-center gap-2 rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60">
              {mutation.isPending && <span className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />}
              <Save className="h-4 w-4" />{isEdit ? 'Update Location' : 'Create Location'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
