import { cn } from '../../utils/cn'

const baseInput = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 disabled:bg-gray-50 disabled:text-gray-500'

export default function FormField({
  label,
  name,
  register,
  error,
  type = 'text',
  children,
  required,
  ...rest
}) {
  return (
    <div>
      {label && (
        <label htmlFor={name} className="block text-sm font-medium text-gray-700 mb-1">
          {label}
          {required && <span className="ml-0.5 text-red-500">*</span>}
        </label>
      )}
      {children ? (
        children
      ) : type === 'textarea' ? (
        <textarea
          id={name}
          rows={3}
          {...(register ? register(name) : {})}
          {...rest}
          className={cn(baseInput, 'resize-none', error && 'border-red-400 focus:border-red-500 focus:ring-red-500')}
        />
      ) : (
        <input
          id={name}
          type={type}
          {...(register ? register(name) : {})}
          {...rest}
          className={cn(baseInput, error && 'border-red-400 focus:border-red-500 focus:ring-red-500')}
        />
      )}
      {error && <p className="mt-1 text-xs text-red-600">{error.message}</p>}
    </div>
  )
}

export { baseInput }
