import { useParams, useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { get } from '../../services/apiClient'
import StatusBadge from '../../components/StatusBadge/StatusBadge'
import { cn } from '../../utils/cn'

const tabs = ['Overview', 'Equipment', 'Dispatches', 'Returns', 'Transfers', 'Invoices', 'Payments']

export default function ProjectDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const [activeTab, setActiveTab] = useState('Overview')

  const { data, isLoading } = useQuery({
    queryKey: ['project', id],
    queryFn: () => get(`/projects/${id}`),
  })

  const project = data?.data ?? data

  if (isLoading) return <div className="animate-pulse h-8 w-48 rounded bg-gray-200" />
  if (!project) return <p className="text-gray-500">Project not found.</p>

  return (
    <div>
      <div className="mb-6 flex items-start justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{project.name}</h1>
          <p className="mt-1 text-sm text-gray-500">{project.code} · {project.customer?.name}</p>
        </div>
        <div className="flex items-center gap-3">
          <StatusBadge status={project.status} />
          {project.status === 'ACTIVE' && (
            <button onClick={() => navigate(`/projects/${id}/closure`)} className="rounded-lg border border-orange-300 px-4 py-2 text-sm font-medium text-orange-600 hover:bg-orange-50">
              Initiate Closure
            </button>
          )}
        </div>
      </div>

      <div className="border-b border-gray-200 mb-6">
        <div className="flex gap-1">
          {tabs.map((tab) => (
            <button key={tab} onClick={() => setActiveTab(tab)}
              className={cn('px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors',
                activeTab === tab ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700')}>
              {tab}
            </button>
          ))}
        </div>
      </div>

      <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        {activeTab === 'Overview' && (
          <div className="grid grid-cols-2 gap-4 text-sm">
            <div><span className="font-medium text-gray-600">Start Date:</span> {project.start_date}</div>
            <div><span className="font-medium text-gray-600">Expected End:</span> {project.expected_end_date ?? '—'}</div>
            <div><span className="font-medium text-gray-600">Contact:</span> {project.contact_person ?? '—'}</div>
            <div><span className="font-medium text-gray-600">Phone:</span> {project.contact_phone ?? '—'}</div>
            <div className="col-span-2"><span className="font-medium text-gray-600">Site Address:</span> {project.site_address ?? '—'}</div>
            <div className="col-span-2"><span className="font-medium text-gray-600">Notes:</span> {project.notes ?? '—'}</div>
          </div>
        )}
        {activeTab !== 'Overview' && (
          <p className="text-sm text-gray-400">{activeTab} data for this project — loaded from API.</p>
        )}
      </div>
    </div>
  )
}
