import { useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { get } from '../../services/apiClient'
import StatusBadge from '../../components/StatusBadge/StatusBadge'
import { cn } from '../../utils/cn'

const tabs = ['Overview', 'Equipment', 'Projects', 'Invoices', 'Payments', 'Financial']

export default function Customer360Page() {
  const { id } = useParams()
  const [activeTab, setActiveTab] = useState('Overview')

  const { data: customerData, isLoading } = useQuery({
    queryKey: ['customer-360', id],
    queryFn: () => get(`/customers/${id}/360`),
  })

  const customer = customerData?.data ?? customerData

  if (isLoading) return <div className="animate-pulse space-y-4"><div className="h-8 w-64 rounded bg-gray-200" /><div className="h-40 rounded-xl bg-gray-200" /></div>
  if (!customer) return <p className="text-gray-500">Customer not found.</p>

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">{customer.name}</h1>
        <p className="mt-1 text-sm text-gray-500">{customer.code} · Customer 360 View</p>
      </div>

      {/* KPI cards */}
      <div className="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        {[
          { label: 'Active Projects', value: customer.stats?.active_projects ?? '—' },
          { label: 'Equipment at Sites', value: customer.stats?.total_equipment ?? '—' },
          { label: 'Pending Returns', value: customer.stats?.pending_returns ?? '—' },
          { label: 'Uninvoiced Value', value: customer.stats?.uninvoiced_value != null ? `₹${Number(customer.stats.uninvoiced_value).toLocaleString('en-IN', { maximumFractionDigits: 0 })}` : '—' },
          { label: 'Invoiced', value: customer.stats?.total_invoiced != null ? `₹${Number(customer.stats.total_invoiced).toLocaleString('en-IN', { maximumFractionDigits: 0 })}` : '—' },
          { label: 'Outstanding', value: customer.stats?.financial_outstanding != null ? `₹${Number(customer.stats.financial_outstanding).toLocaleString('en-IN', { maximumFractionDigits: 0 })}` : '—' },
        ].map((kpi) => (
          <div key={kpi.label} className="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
            <p className="text-xs font-medium text-gray-500">{kpi.label}</p>
            <p className="mt-1 text-xl font-bold text-gray-900">{kpi.value}</p>
          </div>
        ))}
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200 mb-6">
        <div className="flex gap-1">
          {tabs.map((tab) => (
            <button
              key={tab}
              onClick={() => setActiveTab(tab)}
              className={cn(
                'px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors',
                activeTab === tab
                  ? 'border-primary-600 text-primary-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700'
              )}
            >
              {tab}
            </button>
          ))}
        </div>
      </div>

      {/* Tab content */}
      <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        {activeTab === 'Overview' && (
          <div className="grid grid-cols-2 gap-4 text-sm">
            <div><span className="font-medium text-gray-600">Legal Name:</span> <span>{customer.legal_name ?? '—'}</span></div>
            <div><span className="font-medium text-gray-600">Contact:</span> <span>{customer.contact_person ?? '—'}</span></div>
            <div><span className="font-medium text-gray-600">Phone:</span> <span>{customer.phone ?? '—'}</span></div>
            <div><span className="font-medium text-gray-600">Email:</span> <span>{customer.email ?? '—'}</span></div>
            <div><span className="font-medium text-gray-600">Status:</span> <StatusBadge status={customer.status} /></div>
            <div><span className="font-medium text-gray-600">Tax Number:</span> <span>{customer.tax_number ?? '—'}</span></div>
            <div className="col-span-2"><span className="font-medium text-gray-600">Billing Address:</span> <span>{customer.billing_address ?? '—'}</span></div>
          </div>
        )}
        {activeTab !== 'Overview' && (
          <p className="text-sm text-gray-400">Data for {activeTab} tab loaded from API — drill-down views available in full implementation.</p>
        )}
      </div>
    </div>
  )
}
