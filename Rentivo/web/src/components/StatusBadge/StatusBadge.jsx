import { cn } from '../../utils/cn'

const statusMap = {
  DRAFT: 'bg-gray-100 text-gray-700',
  SUBMITTED: 'bg-yellow-100 text-yellow-800',
  APPROVED: 'bg-blue-100 text-blue-800',
  POSTED: 'bg-green-100 text-green-800',
  CANCELLED: 'bg-red-100 text-red-700',
  REVERSED: 'bg-purple-100 text-purple-700',
  ACTIVE: 'bg-green-100 text-green-800',
  INACTIVE: 'bg-gray-100 text-gray-600',
  BLOCKED: 'bg-red-100 text-red-700',
  OPEN: 'bg-blue-100 text-blue-800',
  CLOSED: 'bg-gray-100 text-gray-600',
  PART_PAID: 'bg-orange-100 text-orange-700',
  PAID: 'bg-green-100 text-green-800',
  RECONCILIATION: 'bg-indigo-100 text-indigo-800',
  PENDING_INVOICING: 'bg-yellow-100 text-yellow-800',
  READY_FOR_APPROVAL: 'bg-blue-100 text-blue-800',
  CLOSURE_IN_PROGRESS: 'bg-orange-100 text-orange-700',
}

export default function StatusBadge({ status }) {
  if (!status) return null
  const classes = statusMap[status] ?? 'bg-gray-100 text-gray-700'
  return (
    <span className={cn('inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium', classes)}>
      {status.replace(/_/g, ' ')}
    </span>
  )
}
