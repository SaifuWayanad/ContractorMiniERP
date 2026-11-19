import { useState } from 'react'
import { X } from 'lucide-react'

export default function QuickAddUOM({ isOpen, onClose, onSuccess }) {
  const [value, setValue] = useState('')
  const [error, setError] = useState('')

  if (!isOpen) return null

  const handleSubmit = (e) => {
    e.preventDefault()
    const trimmed = value.trim()
    if (!trimmed) { setError('Enter a unit name'); return }
    onSuccess({ id: trimmed, name: trimmed })
    setValue('')
    setError('')
    onClose()
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div className="w-full max-w-sm rounded-xl bg-white p-6 shadow-2xl">
        <div className="mb-4 flex items-center justify-between">
          <h3 className="text-base font-semibold text-gray-900">Add Unit of Measure</h3>
          <button type="button" onClick={onClose} className="rounded-lg p-1 text-gray-400 hover:text-gray-600">
            <X className="h-4 w-4" />
          </button>
        </div>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Unit Name</label>
            <input
              type="text"
              value={value}
              onChange={(e) => { setValue(e.target.value); setError('') }}
              placeholder="e.g. Days, Hours, Nos…"
              autoFocus
              className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
            />
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
          </div>
          <div className="flex justify-end gap-2">
            <button type="button" onClick={onClose} className="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
            <button type="submit" className="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">Add</button>
          </div>
        </form>
      </div>
    </div>
  )
}
