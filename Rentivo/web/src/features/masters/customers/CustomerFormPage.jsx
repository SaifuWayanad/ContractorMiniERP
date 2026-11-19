import { useEffect } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import { ChevronRight, Save } from 'lucide-react'
import { get, post, put } from '../../../services/apiClient'
import FormField from '../../../components/Form/FormField'

const schema = z.object({
  code: z.string().optional(),
  name: z.string().min(1, 'Required'),
  legal_name: z.string().optional(),
  contact_person: z.string().optional(),
  phone: z.string().optional(),
  email: z.string().email().optional().or(z.literal('')),
  billing_address: z.string().optional(),
  pincode: z.string().optional(),
  area: z.string().optional(),
  tax_number: z.string().optional(),
  credit_limit: z.string().optional(),
  payment_terms: z.string().optional(),
  notes: z.string().optional(),
})

export default function CustomerFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const qc = useQueryClient()
  const isEdit = Boolean(id)

  const { data: existing, isLoading } = useQuery({
    queryKey: ['customer', id],
    queryFn: () => get(`/customers/${id}`),
    enabled: isEdit,
  })

  const { register, handleSubmit, reset, formState: { errors } } = useForm({
    resolver: zodResolver(schema),
  })

  useEffect(() => {
    if (existing) reset({ ...(existing.data ?? existing) })
  }, [existing, reset])

  const mutation = useMutation({
    mutationFn: (payload) => isEdit ? put(`/customers/${id}`, payload) : post('/customers', payload),
    onSuccess: (res) => { qc.invalidateQueries({ queryKey: ['customers'] }); navigate(`/masters/customers/${(res.data ?? res).id}`) },
  })

  if (isEdit && isLoading) return <div className="animate-pulse h-8 w-48 rounded bg-gray-200" />

  return (
    <div>
      <nav className="mb-6 flex items-center gap-1.5 text-sm text-gray-500">
        <button onClick={() => navigate('/masters/customers')} className="hover:text-gray-900">Customers</button>
        <ChevronRight className="h-3.5 w-3.5" />
        <span className="text-gray-900">{isEdit ? 'Edit Customer' : 'New Customer'}</span>
      </nav>
      <h1 className="mb-6 text-2xl font-bold text-gray-900">{isEdit ? 'Edit Customer' : 'New Customer'}</h1>

      <div className="space-y-5">
        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
          <h2 className="mb-4 text-sm font-semibold text-gray-700">Basic Information</h2>
          <div className="space-y-4">
            <FormField label="Code" name="code" register={register} error={errors.code} />
            <div className="grid grid-cols-2 gap-4">
              <FormField label="Name" name="name" register={register} error={errors.name} required />
              <FormField label="Legal Name" name="legal_name" register={register} error={errors.legal_name} />
            </div>
          </div>
        </div>

        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
          <h2 className="mb-4 text-sm font-semibold text-gray-700">Contact</h2>
          <div className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <FormField label="Contact Person" name="contact_person" register={register} error={errors.contact_person} />
              <FormField label="Phone" name="phone" register={register} error={errors.phone} />
            </div>
            <FormField label="Email" name="email" type="email" register={register} error={errors.email} />
            <FormField label="Billing Address" name="billing_address" type="textarea" register={register} error={errors.billing_address} />
            <div className="grid grid-cols-2 gap-4">
              <FormField label="Pincode" name="pincode" register={register} error={errors.pincode} />
              <FormField label="Area" name="area" register={register} error={errors.area} />
            </div>
          </div>
        </div>

        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
          <h2 className="mb-4 text-sm font-semibold text-gray-700">Financial</h2>
          <div className="grid grid-cols-2 gap-4">
            <FormField label="Tax Number" name="tax_number" register={register} error={errors.tax_number} />
            <FormField label="Credit Limit (₹)" name="credit_limit" type="number" register={register} error={errors.credit_limit} />
            <FormField label="Payment Terms" name="payment_terms" register={register} error={errors.payment_terms} />
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
            <Save className="h-4 w-4" />{isEdit ? 'Update Customer' : 'Create Customer'}
          </button>
        </div>
      </div>
    </div>
  )
}
