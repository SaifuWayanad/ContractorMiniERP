import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import { ChevronRight, Save } from 'lucide-react'
import { get, post } from '../../services/apiClient'
import FormField from '../../components/Form/FormField'
import CustomSelect from '../../components/Form/CustomSelect'

const schema = z.object({
  customer_id: z.string().min(1, 'Required'),
  project_id: z.string().min(1, 'Required'),
  return_date: z.string().min(1, 'Required'),
  warehouse_location_id: z.string().min(1, 'Required'),
  vehicle_id: z.string().optional(),
  operator_id: z.string().optional(),
  remarks: z.string().optional(),
})

const CONDITION_OPTIONS = [
  { value: 'GOOD', label: 'Good' },
  { value: 'DAMAGED', label: 'Damaged' },
  { value: 'NEEDS_INSPECTION', label: 'Needs Inspection' },
]

export default function ReturnFormPage() {
  const navigate = useNavigate()
  const qc = useQueryClient()
  const [lines, setLines] = useState([])
  const [lineError, setLineError] = useState('')

  const { register, handleSubmit, setValue, watch, formState: { errors } } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { return_date: new Date().toISOString().slice(0, 10) },
  })

  const customerId = watch('customer_id')
  const projectId = watch('project_id')

  const { data: customersData } = useQuery({ queryKey: ['customers-all'], queryFn: () => get('/customers', { per_page: 500 }) })
  const { data: projectsData } = useQuery({ queryKey: ['projects-all', customerId], queryFn: () => get('/projects', { per_page: 500, customer_id: customerId }), enabled: !!customerId })
  const { data: locationsData } = useQuery({ queryKey: ['locations-all'], queryFn: () => get('/locations', { per_page: 200 }) })
  const { data: vehiclesData } = useQuery({ queryKey: ['vehicles-all'], queryFn: () => get('/vehicles', { per_page: 200 }) })
  const { data: operatorsData } = useQuery({ queryKey: ['operators-all'], queryFn: () => get('/operators', { per_page: 200 }) })
  const { data: dispatchDetailsData } = useQuery({
    queryKey: ['dispatch-details-open', projectId],
    queryFn: () => get('/dispatch-details', { project_id: projectId, returnable: true, per_page: 200 }),
    enabled: !!projectId,
  })

  useEffect(() => {
    const details = dispatchDetailsData?.data ?? []
    setLines(details.map((d) => ({
      dispatch_detail_id: d.id,
      product_id: d.product_id,
      product_name: d.product?.name ?? '',
      max_qty: d.quantity - (d.returned_quantity ?? 0),
      quantity_returned: '',
      condition: 'GOOD',
      remarks: '',
      selected: false,
    })))
  }, [dispatchDetailsData])

  const updateLine = (i, field, value) => setLines((prev) => prev.map((l, idx) => idx === i ? { ...l, [field]: value } : l))
  const toggleLine = (i) => setLines((prev) => prev.map((l, idx) => idx === i ? { ...l, selected: !l.selected, quantity_returned: l.selected ? '' : String(l.max_qty) } : l))

  const customers = (customersData?.data ?? []).map((c) => ({ value: c.id, label: c.name }))
  const projects = (projectsData?.data ?? []).map((p) => ({ value: p.id, label: p.name }))
  const locations = (locationsData?.data ?? []).map((l) => ({ value: l.id, label: l.name }))
  const vehicles = (vehiclesData?.data ?? []).map((v) => ({ value: v.id, label: v.registration_number }))
  const operators = (operatorsData?.data ?? []).map((o) => ({ value: o.id, label: o.name }))

  const mutation = useMutation({
    mutationFn: (payload) => post('/returns', payload),
    onSuccess: (res) => { qc.invalidateQueries({ queryKey: ['returns'] }); navigate(`/returns/${(res.data ?? res).id}`) },
  })

  const onSubmit = handleSubmit((header) => {
    const selectedLines = lines.filter((l) => l.selected && l.quantity_returned)
    if (selectedLines.length === 0) { setLineError('Select at least one item to return'); return }
    setLineError('')
    mutation.mutate({
      ...header,
      details: selectedLines.map((l) => ({
        dispatch_detail_id: l.dispatch_detail_id,
        product_id: l.product_id,
        quantity_returned: l.quantity_returned,
        condition: l.condition,
        remarks: l.remarks || undefined,
      })),
    })
  })

  return (
    <div>
      <nav className="mb-6 flex items-center gap-1.5 text-sm text-gray-500">
        <button onClick={() => navigate('/returns')} className="hover:text-gray-900">Returns</button>
        <ChevronRight className="h-3.5 w-3.5" />
        <span className="text-gray-900">New Return</span>
      </nav>
      <h1 className="mb-6 text-2xl font-bold text-gray-900">New Return</h1>

      <div className="space-y-5">
        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
          <h2 className="mb-4 text-sm font-semibold text-gray-700">Return Details</h2>
          <div className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <CustomSelect label="Customer" name="customer_id" required options={customers} register={register} setValue={(n, v, o) => { setValue(n, v, o); setValue('project_id', '') }} watch={watch} error={errors.customer_id} />
              <CustomSelect label="Project" name="project_id" required options={projects} register={register} setValue={setValue} watch={watch} error={errors.project_id}
                disabled={!customerId} placeholder={customerId ? 'Select project…' : 'Select customer first'} />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <FormField label="Return Date" name="return_date" type="date" register={register} error={errors.return_date} required />
              <CustomSelect label="Warehouse Location" name="warehouse_location_id" required options={locations} register={register} setValue={setValue} watch={watch} error={errors.warehouse_location_id} />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <CustomSelect label="Vehicle" name="vehicle_id" options={vehicles} register={register} setValue={setValue} watch={watch} placeholder="Select vehicle (optional)" />
              <CustomSelect label="Operator" name="operator_id" options={operators} register={register} setValue={setValue} watch={watch} placeholder="Select operator (optional)" />
            </div>
            <FormField label="Remarks" name="remarks" type="textarea" register={register} error={errors.remarks} />
          </div>
        </div>

        <div className="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
          <div className="border-b border-gray-100 px-6 py-4">
            <h2 className="text-sm font-semibold text-gray-700">Items to Return</h2>
            {!projectId && <p className="mt-1 text-xs text-gray-400">Select a project to load dispatched items</p>}
          </div>
          {lines.length === 0 && projectId ? (
            <p className="px-6 py-8 text-center text-sm text-gray-400">No returnable items found for this project</p>
          ) : (
            <table className="w-full text-sm">
              <thead className="bg-gray-50 border-b border-gray-100">
                <tr>
                  <th className="w-10 px-4 py-3" />
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 w-28">Available</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 w-28">Qty Return</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 w-44">Condition</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Remarks</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {lines.map((line, i) => (
                  <tr key={i} className={line.selected ? 'bg-primary-50/40' : ''}>
                    <td className="px-4 py-2 text-center">
                      <input type="checkbox" checked={line.selected} onChange={() => toggleLine(i)}
                        className="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                    </td>
                    <td className="px-4 py-2 font-medium text-gray-900">{line.product_name}</td>
                    <td className="px-4 py-2 text-gray-500">{line.max_qty}</td>
                    <td className="px-4 py-2">
                      <input type="number" value={line.quantity_returned} onChange={(e) => updateLine(i, 'quantity_returned', e.target.value)}
                        disabled={!line.selected} max={line.max_qty} min="0.001" placeholder="0"
                        className="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 disabled:bg-gray-50 disabled:text-gray-400" />
                    </td>
                    <td className="px-4 py-2">
                      <select value={line.condition} onChange={(e) => updateLine(i, 'condition', e.target.value)} disabled={!line.selected}
                        className="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 disabled:bg-gray-50 disabled:text-gray-400">
                        {CONDITION_OPTIONS.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                      </select>
                    </td>
                    <td className="px-4 py-2">
                      <input type="text" value={line.remarks} onChange={(e) => updateLine(i, 'remarks', e.target.value)} disabled={!line.selected}
                        placeholder="Optional"
                        className="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 disabled:bg-gray-50 disabled:text-gray-400" />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
          {lineError && <p className="px-6 py-3 text-sm text-red-600">{lineError}</p>}
        </div>

        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 flex justify-end">
          {mutation.error && <p className="mr-auto text-sm text-red-600">{mutation.error.response?.data?.message ?? 'Save failed'}</p>}
          <button type="button" onClick={onSubmit} disabled={mutation.isPending}
            className="flex items-center gap-2 rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60">
            {mutation.isPending && <span className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />}
            <Save className="h-4 w-4" /> Create Return
          </button>
        </div>
      </div>
    </div>
  )
}
