import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { useLocation } from 'react-router-dom'
import { get } from '../../services/apiClient'
import DataTable from '../../components/DataTable/DataTable'
import { cn } from '../../utils/cn'

const reports = [
  { id: 'project-outstanding', label: 'Project-wise Outstanding', endpoint: '/reports/project-outstanding',
    columns: [{ key: 'project_name', label: 'Project' }, { key: 'product_name', label: 'Product' }, { key: 'quantity', label: 'Qty' }] },
  { id: 'customer-outstanding', label: 'Customer-wise Outstanding', endpoint: '/reports/customer-outstanding',
    columns: [{ key: 'customer_name', label: 'Customer' }, { key: 'total_quantity', label: 'Total Qty' }] },
  { id: 'uninvoiced-returns', label: 'Uninvoiced Returns', endpoint: '/reports/uninvoiced-returns',
    columns: [{ key: 'return_number', label: 'Return #' }, { key: 'product_name', label: 'Product' }, { key: 'uninvoiced_quantity', label: 'Uninvoiced Qty' }, { key: 'estimated_amount', label: 'Est. Amount' }] },
  { id: 'invoice-register', label: 'Invoice Register', endpoint: '/reports/invoice-register',
    columns: [{ key: 'invoice_number', label: 'Invoice #' }, { key: 'customer_name', label: 'Customer' }, { key: 'grand_total', label: 'Total' }, { key: 'status', label: 'Status' }] },
  { id: 'customer-financial-outstanding', label: 'Customer Financial Outstanding', endpoint: '/reports/customer-financial-outstanding',
    columns: [{ key: 'customer_name', label: 'Customer' }, { key: 'invoiced', label: 'Invoiced' }, { key: 'paid', label: 'Paid' }, { key: 'outstanding', label: 'Outstanding' }] },
  { id: 'item-movement', label: 'Item Movement', endpoint: '/reports/item-movement',
    columns: [{ key: 'product_name', label: 'Product' }, { key: 'movement_type', label: 'Type' }, { key: 'quantity', label: 'Qty' }, { key: 'movement_date', label: 'Date' }] },
  { id: 'available-inventory', label: 'Available Inventory', endpoint: '/reports/available-inventory',
    columns: [{ key: 'product_name', label: 'Product' }, { key: 'location_name', label: 'Location' }, { key: 'quantity_on_hand', label: 'On Hand' }] },
]

export default function ReportsPage() {
  const [activeReport, setActiveReport] = useState(reports[0])
  const [page, setPage] = useState(1)

  const { data, isLoading } = useQuery({
    queryKey: ['report', activeReport.id, page],
    queryFn: () => get(activeReport.endpoint, { page, per_page: 25 }),
    enabled: !!activeReport,
  })

  const rows = data?.data ?? []

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Reports</h1>
        <p className="mt-1 text-sm text-gray-500">Operational and financial reports</p>
      </div>

      <div className="flex gap-6">
        {/* Sidebar */}
        <div className="w-56 shrink-0">
          <div className="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 overflow-hidden">
            {reports.map((report) => (
              <button
                key={report.id}
                onClick={() => { setActiveReport(report); setPage(1) }}
                className={cn(
                  'w-full px-4 py-3 text-left text-sm border-b last:border-0 border-gray-100 hover:bg-gray-50',
                  activeReport.id === report.id ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-700'
                )}
              >
                {report.label}
              </button>
            ))}
          </div>
        </div>

        {/* Table */}
        <div className="flex-1 min-w-0">
          <DataTable
            columns={activeReport.columns}
            data={rows}
            loading={isLoading}
            pagination={data ? { page, perPage: 25, total: data.meta?.total ?? data.total ?? rows.length } : undefined}
            onPageChange={setPage}
          />
        </div>
      </div>
    </div>
  )
}
