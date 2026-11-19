import { useQuery } from '@tanstack/react-query'
import { get } from '../../services/apiClient'
import {
  FolderOpen, Package, ArrowDownToLine, FileSearch,
  FileText, DollarSign, AlertCircle,
} from 'lucide-react'

const statConfig = [
  { key: 'active_projects', label: 'Active Projects', icon: FolderOpen, color: 'bg-blue-50 text-blue-600' },
  { key: 'equipment_at_sites', label: 'Equipment at Sites', icon: Package, color: 'bg-purple-50 text-purple-600' },
  { key: 'pending_returns', label: 'Pending Returns', icon: ArrowDownToLine, color: 'bg-orange-50 text-orange-600' },
  { key: 'uninvoiced_return_count', label: 'Uninvoiced Returns', icon: FileSearch, color: 'bg-yellow-50 text-yellow-600', subKey: 'uninvoiced_estimated_value', subLabel: 'est. value' },
  { key: 'monthly_invoiced', label: 'Invoiced This Month', icon: FileText, color: 'bg-green-50 text-green-600', isCurrency: true },
  { key: 'financial_outstanding', label: 'Financial Outstanding', icon: DollarSign, color: 'bg-red-50 text-red-600', isCurrency: true },
]

function StatCard({ config, stats }) {
  const value = stats?.[config.key]
  const subValue = config.subKey ? stats?.[config.subKey] : null

  return (
    <div className="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
      <div className="flex items-start justify-between">
        <div>
          <p className="text-sm font-medium text-gray-500">{config.label}</p>
          <p className="mt-1 text-2xl font-bold text-gray-900">
            {value == null ? (
              <span className="inline-block h-7 w-20 animate-pulse rounded bg-gray-200" />
            ) : config.isCurrency ? (
              `₹${Number(value).toLocaleString('en-IN', { maximumFractionDigits: 0 })}`
            ) : (
              Number(value).toLocaleString()
            )}
          </p>
          {subValue != null && (
            <p className="mt-0.5 text-xs text-gray-400">
              ₹{Number(subValue).toLocaleString('en-IN', { maximumFractionDigits: 0 })} {config.subLabel}
            </p>
          )}
        </div>
        <div className={`rounded-lg p-2.5 ${config.color}`}>
          <config.icon className="h-5 w-5" />
        </div>
      </div>
    </div>
  )
}

export default function DashboardPage() {
  const { data, isError } = useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: () => get('/dashboard/stats'),
    retry: 1,
  })

  const stats = data?.data ?? data

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p className="mt-1 text-sm text-gray-500">Overview of your equipment rental operations</p>
      </div>

      {isError && (
        <div className="mb-4 flex items-center gap-2 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
          <AlertCircle className="h-4 w-4 shrink-0" />
          Failed to load dashboard stats. Check that the API server is running.
        </div>
      )}

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {statConfig.map((config) => (
          <StatCard key={config.key} config={config} stats={stats} />
        ))}
      </div>
    </div>
  )
}
