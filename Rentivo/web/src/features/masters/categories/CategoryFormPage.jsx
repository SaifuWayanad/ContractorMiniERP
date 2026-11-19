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
  description: z.string().optional(),
  is_active: z.boolean().default(true),
})

export default function CategoryFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const qc = useQueryClient()
  const isEdit = Boolean(id)

  const { data: existing, isLoading } = useQuery({
    queryKey: ['category', id],
    queryFn: () => get(`/categories/${id}`),
    enabled: isEdit,
  })

  const { register, handleSubmit, reset, formState: { errors } } = useForm({ resolver: zodResolver(schema) })

  useEffect(() => {
    if (existing) {
      const cat = existing.data ?? existing
      reset({ code: cat.code, name: cat.name, description: cat.description ?? '', is_active: cat.is_active })
    }
  }, [existing, reset])

  const mutation = useMutation({
    mutationFn: (payload) => isEdit ? put(`/categories/${id}`, payload) : post('/categories', payload),
    onSuccess: (res) => {
      qc.invalidateQueries({ queryKey: ['categories'] })
      const saved = res.data ?? res
      navigate(`/masters/categories/${saved.id}`)
    },
  })

  if (isEdit && isLoading) {
    return <div className="animate-pulse h-8 w-48 rounded bg-gray-200" />
  }

  return (
    <div>
      <nav className="mb-6 flex items-center gap-1.5 text-sm text-gray-500">
        <button onClick={() => navigate('/masters/categories')} className="hover:text-gray-900">Categories</button>
        <ChevronRight className="h-3.5 w-3.5" />
        <span className="text-gray-900">{isEdit ? 'Edit Category' : 'New Category'}</span>
      </nav>

      <h1 className="mb-6 text-2xl font-bold text-gray-900">{isEdit ? 'Edit Category' : 'New Category'}</h1>

      <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <form className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <FormField label="Code" name="code" register={register} error={errors.code} />
            <FormField label="Name" name="name" register={register} error={errors.name} required />
          </div>
          <FormField label="Description" name="description" type="textarea" register={register} error={errors.description} />
          <div className="flex items-center gap-2 pt-1">
            <input type="checkbox" id="is_active" {...register('is_active')} className="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            <label htmlFor="is_active" className="text-sm text-gray-700">Active</label>
          </div>
          {mutation.error && (
            <p className="text-sm text-red-600">{mutation.error.response?.data?.message ?? 'Save failed'}</p>
          )}
          <div className="border-t border-gray-100 pt-4 flex justify-end">
            <button
              type="button"
              onClick={handleSubmit((d) => mutation.mutate(d))}
              disabled={mutation.isPending}
              className="flex items-center gap-2 rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60"
            >
              {mutation.isPending && <span className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />}
              <Save className="h-4 w-4" />
              {isEdit ? 'Update Category' : 'Create Category'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
