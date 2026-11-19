import { useState } from 'react'
import { Eye, Pencil, Trash2 } from 'lucide-react'
import Modal from './Modal/Modal'

export default function ActionButtons({ onView, onEdit, onDelete, deleteLabel = 'this record' }) {
  const [confirmOpen, setConfirmOpen] = useState(false)

  return (
    <>
      <div className="flex items-center gap-0.5">
        {onView && (
          <button
            onClick={onView}
            title="View"
            className="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-600"
          >
            <Eye className="h-4 w-4" />
          </button>
        )}
        {onEdit && (
          <button
            onClick={onEdit}
            title="Edit"
            className="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-amber-50 hover:text-amber-600"
          >
            <Pencil className="h-4 w-4" />
          </button>
        )}
        {onDelete && (
          <button
            onClick={() => setConfirmOpen(true)}
            title="Delete"
            className="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600"
          >
            <Trash2 className="h-4 w-4" />
          </button>
        )}
      </div>

      <Modal
        isOpen={confirmOpen}
        onClose={() => setConfirmOpen(false)}
        title="Confirm Delete"
        confirmLabel="Delete"
        confirmVariant="danger"
        onConfirm={() => { onDelete?.(); setConfirmOpen(false) }}
      >
        <p className="text-sm text-gray-600">
          Are you sure you want to delete <span className="font-medium">{deleteLabel}</span>? This action cannot be undone.
        </p>
      </Modal>
    </>
  )
}
