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
import QuickAddCustomer from '../../../components/QuickAdd/QuickAddCustomer'

const schema = z.object({
  customer_id: z.string().min(1, 'Required'),
  code: z.string().optional(),
  name: z.string().min(1, 'Required'),
  site_address: z.string().optional(),
  contact_person: z.string().optional(),
  contact_phone: z.string().optional(),
  start_date: z.string().min(1, 'Required'),
  expected_end_date: z.string().optional(),
  status: z.enum(['DRAFT', 'ACTIVE', 'CLOSURE_IN_PROGRESS', 'CLOSED', 'CANCELLED']).default('DRAFT'),
  notes: z.string().optional(),
})

const STATUS_OPTIONS = [
  { value: 'DRAFT', label: 'Draft' },
  { value: 'ACTIVE', label: 'Active' },
  { value: 'CLOSURE_IN_PROGRESS', label: 'Closure In Progress' },
  { value: 'CLOSED', label: 'Closed' },
  { value: 'CANCELLED', label: 'Cancelled' },
]

export default function ProjectFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const qc = useQueryClient()
  const isEdit = Boolean(id)

  const { data: existing, isLoading } = useQuery({
    queryKey: ['project', id],
    queryFn: () => get(`/projects/${id}`),
    enabled: isEdit,
  })

  const { data: customersData } = useQuery({
    queryKey: ['customers-all'],
    queryFn: () => get('/customers', { per_page: 500, status: 'ACTIVE' }),
  })

  const { register, handleSubmit, reset, setValue, watch, formState: { errors } } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { status: 'DRAFT' },
  })

  useEffect(() => {
    if (existing) {
      const p = existing.data ?? existing
      reset({ ...p, customer_id: String(p.customer_id) })
    }
  }, [existing, reset])

  const mutation = useMutation({
    mutationFn: (payload) => isEdit ? put(`/projects/${id}`, payload) : post('/projects', payload),
    onSuccess: (res) => {
      qc.invalidateQueries({ queryKey: ['projects'] })
      navigate(`/projects/${(res.data ?? res).id}`)
    },
  })

  const customers = (customersData?.data ?? []).map((c) => ({ value: c.id, label: c.name }))

  if (isEdit && isLoading) return <div className="animate-pulse h-8 w-48 rounded bg-gray-200" />

  return (
    <div>
      <nav className="mb-6 flex items-center gap-1.5 text-sm text-gray-500">
        <button onClick={() => navigate('/masters/projects')} className="hover:text-gray-900">Projects</button>
        <ChevronRight className="h-3.5 w-3.5" />
        <span className="text-gray-900">{isEdit ? 'Edit Project' : 'New Project'}</span>
      </nav>
      <h1 className="mb-6 text-2xl font-bold text-gray-900">{isEdit ? 'Edit Project' : 'New Project'}</h1>

      <div className="space-y-5">
        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
          <h2 className="mb-4 text-sm font-semibold text-gray-700">Project Details</h2>
          <div className="space-y-4">
            <CustomSelect label="Customer" name="customer_id" required options={customers} register={register} setValue={setValue} watch={watch} error={errors.customer_id}
              quickAdd={{ label: 'customer', render: (props) => <QuickAddCustomer {...props} /> }} />
            <div className="grid grid-cols-2 gap-4">
              <FormField label="Code" name="code" register={register} error={errors.code} />
              <CustomSelect label="Status" name="status" options={STATUS_OPTIONS} register={register} setValue={setValue} watch={watch} />
            </div>
            <FormField label="Name" name="name" register={register} error={errors.name} required />
            <div className="grid grid-cols-2 gap-4">
              <FormField label="Start Date" name="start_date" type="date" register={register} error={errors.start_date} required />
              <FormField label="Expected End Date" name="expected_end_date" type="date" register={register} error={errors.expected_end_date} />
            </div>
          </div>
        </div>

        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
          <h2 className="mb-4 text-sm font-semibold text-gray-700">Site &amp; Contact</h2>
          <div className="space-y-4">
            <FormField label="Site Address" name="site_address" type="textarea" register={register} error={errors.site_address} />
            <div className="grid grid-cols-2 gap-4">
              <FormField label="Contact Person" name="contact_person" register={register} error={errors.contact_person} />
              <FormField label="Contact Phone" name="contact_phone" register={register} error={errors.contact_phone} />
            </div>
          </div>
        </div>

        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
          <FormField label="Notes" name="notes" type="textarea" register={register} error={errors.notes} />
        </div>

        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 flex justify-end">
          {mutation.error && <p className="mb-4 text-sm text-red-600">{mutation.error.response?.data?.message ?? 'Save failed'}</p>}
          <button type="button" onClick={handleSubmit((d) => mutation.mutate(d))} disabled={mutation.isPending}
            className="flex items-center gap-2 rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60">
            {mutation.isPending && <span className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />}
            <Save className="h-4 w-4" />{isEdit ? 'Update Project' : 'Create Project'}
          </button>
        </div>
      </div>
    </div>
  )
}
