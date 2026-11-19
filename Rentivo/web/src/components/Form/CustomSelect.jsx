import { useState, useRef, useEffect } from 'react'
import { ChevronDown, Search, Plus, Check } from 'lucide-react'
import { cn } from '../../utils/cn'

export default function CustomSelect({
  label,
  name,
  required,
  placeholder = 'Select…',
  options = [],
  register,
  setValue,
  watch,
  error,
  quickAdd,
  className,
  disabled = false,
}) {
  const [open, setOpen] = useState(false)
  const [search, setSearch] = useState('')
  const [quickAddOpen, setQuickAddOpen] = useState(false)
  const containerRef = useRef(null)
  const searchRef = useRef(null)

  const currentValue = watch?.(name) ?? ''
  const selectedOption = options.find((o) => String(o.value) === String(currentValue))

  const filtered = search
    ? options.filter((o) => o.label.toLowerCase().includes(search.toLowerCase()))
    : options

  useEffect(() => {
    if (open) searchRef.current?.focus()
  }, [open])

  useEffect(() => {
    const handleClickOutside = (e) => {
      if (containerRef.current && !containerRef.current.contains(e.target)) {
        setOpen(false)
        setSearch('')
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  const handleSelect = (opt) => {
    setValue?.(name, String(opt.value), { shouldValidate: true })
    setOpen(false)
    setSearch('')
  }

  const handleQuickAddSuccess = (newItem) => {
    setValue?.(name, String(newItem.id), { shouldValidate: true })
    setQuickAddOpen(false)
  }

  const handleKeyDown = (e) => {
    if (e.key === 'Escape') { setOpen(false); setSearch('') }
  }

  return (
    <div className={cn('relative', className)} ref={containerRef} onKeyDown={handleKeyDown}>
      {label && (
        <label className="block text-sm font-medium text-gray-700 mb-1">
          {label}
          {required && <span className="ml-0.5 text-red-500">*</span>}
        </label>
      )}

      {/* Hidden input for react-hook-form registration */}
      {register && <input type="hidden" {...register(name)} />}

      {/* Trigger */}
      <button
        type="button"
        disabled={disabled}
        onClick={() => !disabled && setOpen((v) => !v)}
        className={cn(
          'flex w-full items-center justify-between rounded-lg border bg-white px-3 py-2 text-sm text-left transition-colors focus:outline-none focus:ring-1 focus:ring-primary-500',
          selectedOption ? 'text-gray-900' : 'text-gray-400',
          error
            ? 'border-red-400 focus:border-red-500 focus:ring-red-500'
            : open
            ? 'border-primary-500 ring-1 ring-primary-500'
            : 'border-gray-300 hover:border-gray-400',
          disabled && 'cursor-not-allowed bg-gray-50 text-gray-400',
        )}
      >
        <span className="truncate">{selectedOption?.label ?? placeholder}</span>
        <ChevronDown
          className={cn(
            'ml-2 h-4 w-4 flex-shrink-0 text-gray-400 transition-transform duration-150',
            open && 'rotate-180',
          )}
        />
      </button>

      {error && <p className="mt-1 text-xs text-red-600">{error.message}</p>}

      {/* Dropdown panel */}
      {open && (
        <div className="absolute z-50 mt-1 w-full min-w-[12rem] rounded-xl border border-gray-200 bg-white shadow-xl">
          {/* Search box */}
          <div className="border-b border-gray-100 p-2">
            <div className="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1.5">
              <Search className="h-3.5 w-3.5 flex-shrink-0 text-gray-400" />
              <input
                ref={searchRef}
                type="text"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="Search..."
                className="flex-1 bg-transparent text-sm text-gray-700 outline-none placeholder:text-gray-400"
              />
            </div>
          </div>

          {/* Options list */}
          <ul className="max-h-52 overflow-y-auto py-1">
            {filtered.length === 0 ? (
              <li className="px-3 py-2.5 text-sm text-gray-400 text-center">No results found</li>
            ) : (
              filtered.map((opt) => {
                const isSelected = String(opt.value) === String(currentValue)
                return (
                  <li
                    key={opt.value}
                    onClick={() => handleSelect(opt)}
                    className={cn(
                      'flex cursor-pointer items-center justify-between px-3 py-2 text-sm transition-colors',
                      isSelected
                        ? 'bg-primary-50 text-primary-700 font-medium'
                        : 'text-gray-700 hover:bg-gray-50',
                    )}
                  >
                    <span>{opt.label}</span>
                    {isSelected && <Check className="h-3.5 w-3.5 flex-shrink-0" />}
                  </li>
                )
              })
            )}
          </ul>

          {/* Quick add */}
          {quickAdd && (
            <div className="border-t border-gray-100 p-1.5">
              <button
                type="button"
                onClick={() => { setOpen(false); setSearch(''); setQuickAddOpen(true) }}
                className="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-sm font-medium text-primary-600 hover:bg-primary-50 transition-colors"
              >
                <Plus className="h-3.5 w-3.5" />
                Create new {quickAdd.label}
              </button>
            </div>
          )}
        </div>
      )}

      {/* Quick add modal */}
      {quickAdd && quickAddOpen && quickAdd.render({
        isOpen: quickAddOpen,
        onClose: () => setQuickAddOpen(false),
        onSuccess: handleQuickAddSuccess,
      })}
    </div>
  )
}
