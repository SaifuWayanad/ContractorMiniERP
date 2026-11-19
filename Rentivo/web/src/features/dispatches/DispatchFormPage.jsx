import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import { ChevronRight, Plus, Save, Trash2 } from 'lucide-react'
import { get, post } from '../../services/apiClient'
import FormField from '../../components/Form/FormField'
import CustomSelect from '../../components/Form/CustomSelect'
import QuickAddCustomer from '../../components/QuickAdd/QuickAddCustomer'

const schema = z.object({
  customer_id: z.string().min(1, 'Required'),
  project_id: z.string().min(1, 'Required'),
  source_location_id: z.string().min(1, 'Required'),
  dispatch_date: z.string().min(1, 'Required'),
  vehicle_id: z.string().optional(),
  operator_id: z.string().optional(),
  remarks: z.string().optional(),
})

const emptyLine = () => ({ product_id: '', quantity: '', expected_return_date: '', remarks: '' })

export default function DispatchFormPage() {
  const navigate = useNavigate()
  const qc = useQueryClient()
  const [lines, setLines] = useState([emptyLine()])
  const [lineError, setLineError] = useState('')

  const { register, handleSubmit, setValue, watch, formState: { errors } } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { dispatch_date: new Date().toISOString().slice(0, 10) },
  })

  const customerId = watch('customer_id')

  const { data: customersData } = useQuery({ queryKey: ['customers-all'], queryFn: () => get('/customers', { per_page: 500 }) })
  const { data: projectsData } = useQuery({ queryKey: ['projects-all', customerId], queryFn: () => get('/projects', { per_page: 500, customer_id: customerId }), enabled: !!customerId })
  const { data: locationsData } = useQuery({ queryKey: ['locations-all'], queryFn: () => get('/locations', { per_page: 200 }) })
  const { data: productsData } = useQuery({ queryKey: ['products-all'], queryFn: () => get('/products', { per_page: 500 }) })
  const { data: vehiclesData } = useQuery({ queryKey: ['vehicles-all'], queryFn: () => get('/vehicles', { per_page: 200 }) })
  const { data: operatorsData } = useQuery({ queryKey: ['operators-all'], queryFn: () => get('/operators', { per_page: 200 }) })

  const mutation = useMutation({
    mutationFn: (payload) => post('/dispatches', payload),
    onSuccess: (res) => { qc.invalidateQueries({ queryKey: ['dispatches'] }); navigate(`/dispatches/${(res.data ?? res).id}`) },
  })

  const customers = (customersData?.data ?? []).map((c) => ({ value: c.id, label: c.name }))
  const projects = (projectsData?.data ?? []).map((p) => ({ value: p.id, label: p.name }))
  const locations = (locationsData?.data ?? []).map((l) => ({ value: l.id, label: l.name }))
  const products = (productsData?.data ?? []).map((p) => ({ value: p.id, label: p.name }))
  const vehicles = (vehiclesData?.data ?? []).map((v) => ({ value: v.id, label: v.registration_number }))
  const operators = (operatorsData?.data ?? []).map((o) => ({ value: o.id, label: o.name }))

  const updateLine = (i, field, value) => setLines((prev) => prev.map((l, idx) => idx === i ? { ...l, [field]: value } : l))
  const addLine = () => setLines((prev) => [...prev, emptyLine()])
  const removeLine = (i) => setLines((prev) => prev.filter((_, idx) => idx !== i))

  const onSubmit = handleSubmit((header) => {
    const validLines = lines.filter((l) => l.product_id && l.quantity)
    if (validLines.length === 0) { setLineError('Add at least one product line'); return }
    setLineError('')
    mutation.mutate({
      ...header,
      details: validLines.map((l) => ({
        product_id: l.product_id,
        quantity: l.quantity,
        expected_return_date: l.expected_return_date || undefined,
        remarks: l.remarks || undefined,
      })),
    })
  })

  return (
    <div>
      <nav className="mb-6 flex items-center gap-1.5 text-sm text-gray-500">
        <button onClick={() => navigate('/dispatches')} className="hover:text-gray-900">Dispatches</button>
        <ChevronRight className="h-3.5 w-3.5" />
        <span className="text-gray-900">New Dispatch</span>
      </nav>
      <h1 className="mb-6 text-2xl font-bold text-gray-900">New Dispatch</h1>

      <div className="space-y-5">
        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
          <h2 className="mb-4 text-sm font-semibold text-gray-700">Dispatch Details</h2>
          <div className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <CustomSelect label="Customer" name="customer_id" required options={customers} register={register} setValue={(n, v, o) => { setValue(n, v, o); setValue('project_id', '') }} watch={watch} error={errors.customer_id}
                quickAdd={{ label: 'customer', render: (p) => <QuickAddCustomer {...p} /> }} />
              <CustomSelect label="Project" name="project_id" required options={projects} register={register} setValue={setValue} watch={watch} error={errors.project_id}
                disabled={!customerId} placeholder={customerId ? 'Select project…' : 'Select customer first'} />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <CustomSelect label="Source Location" name="source_location_id" required options={locations} register={register} setValue={setValue} watch={watch} error={errors.source_location_id} />
              <FormField label="Dispatch Date" name="dispatch_date" type="date" register={register} error={errors.dispatch_date} required />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <CustomSelect label="Vehicle" name="vehicle_id" options={vehicles} register={register} setValue={setValue} watch={watch} placeholder="Select vehicle (optional)" />
              <CustomSelect label="Operator" name="operator_id" options={operators} register={register} setValue={setValue} watch={watch} placeholder="Select operator (optional)" />
            </div>
            <FormField label="Remarks" name="remarks" type="textarea" register={register} error={errors.remarks} />
          </div>
        </div>

        <div className="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
          <div className="border-b border-gray-100 px-6 py-4 flex items-center justify-between">
            <h2 className="text-sm font-semibold text-gray-700">Line Items</h2>
            <button type="button" onClick={addLine} className="flex items-center gap-1.5 rounded-lg border border-primary-300 px-3 py-1.5 text-xs font-medium text-primary-600 hover:bg-primary-50">
              <Plus className="h-3.5 w-3.5" /> Add Product
            </button>
          </div>
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b border-gray-100">
              <tr>
                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 w-80">Product</th>
                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 w-28">Quantity</th>
                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 w-40">Exp. Return Date</th>
                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Remarks</th>
                <th className="w-10" />
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {lines.map((line, i) => (
                <tr key={i}>
                  <td className="px-4 py-2">
                    <CustomSelect name={`line_product_${i}`} options={products} placeholder="Select product…"
                      register={() => ({})} setValue={(_, v) => updateLine(i, 'product_id', v)}
                      watch={() => line.product_id} />
                  </td>
                  <td className="px-4 py-2">
                    <input type="number" value={line.quantity} onChange={(e) => updateLine(i, 'quantity', e.target.value)} placeholder="0"
                      className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500" />
                  </td>
                  <td className="px-4 py-2">
                    <input type="date" value={line.expected_return_date} onChange={(e) => updateLine(i, 'expected_return_date', e.target.value)}
                      className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500" />
                  </td>
                  <td className="px-4 py-2">
                    <input type="text" value={line.remarks} onChange={(e) => updateLine(i, 'remarks', e.target.value)} placeholder="Optional"
                      className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500" />
                  </td>
                  <td className="px-4 py-2">
                    <button type="button" onClick={() => removeLine(i)} disabled={lines.length === 1}
                      className="rounded p-1 text-gray-400 hover:text-red-500 disabled:opacity-30">
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {lineError && <p className="px-6 py-3 text-sm text-red-600">{lineError}</p>}
        </div>

        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 flex justify-end">
          {mutation.error && <p className="mr-auto text-sm text-red-600">{mutation.error.response?.data?.message ?? 'Save failed'}</p>}
          <button type="button" onClick={onSubmit} disabled={mutation.isPending}
            className="flex items-center gap-2 rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60">
            {mutation.isPending && <span className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />}
            <Save className="h-4 w-4" /> Create Dispatch
          </button>
        </div>
      </div>
    </div>
  )
}
