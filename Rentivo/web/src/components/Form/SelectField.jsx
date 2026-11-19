import { useState } from 'react'
import { Plus } from 'lucide-react'
import { cn } from '../../utils/cn'

export default function SelectField({
  label,
  name,
  required,
  placeholder = 'Select…',
  options = [],
  register,
  setValue,
  error,
  quickAdd,
  className,
}) {
  const [quickAddOpen, setQuickAddOpen] = useState(false)

  const handleSuccess = (newItem) => {
    setValue?.(name, String(newItem.id), { shouldValidate: true })
    setQuickAddOpen(false)
  }

  return (
    <div className={className}>
      {label && (
        <label htmlFor={name} className="block text-sm font-medium text-gray-700 mb-1">
          {label}
          {required && <span className="ml-0.5 text-red-500">*</span>}
        </label>
      )}
      <div className="flex items-center gap-1.5">
        <select
          id={name}
          {...(register ? register(name) : {})}
          className={cn(
            'flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 disabled:bg-gray-50 disabled:text-gray-500',
            error && 'border-red-400 focus:border-red-500 focus:ring-red-500',
          )}
        >
          <option value="">{placeholder}</option>
          {options.map((o) => (
            <option key={o.value} value={String(o.value)}>
              {o.label}
            </option>
          ))}
        </select>
        {quickAdd && (
          <button
            type="button"
            onClick={() => setQuickAddOpen(true)}
            title={`Quick add ${quickAdd.label}`}
            className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg border border-dashed border-gray-300 text-gray-400 transition-colors hover:border-primary-400 hover:bg-primary-50 hover:text-primary-600"
          >
            <Plus className="h-4 w-4" />
          </button>
        )}
      </div>
      {error && <p className="mt-1 text-xs text-red-600">{error.message}</p>}

      {quickAdd &&
        quickAddOpen &&
        quickAdd.render({
          isOpen: quickAddOpen,
          onClose: () => setQuickAddOpen(false),
          onSuccess: handleSuccess,
        })}
    </div>
  )
}
