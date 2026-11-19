import { Outlet } from 'react-router-dom'
import { Truck } from 'lucide-react'

export default function AuthLayout() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-100 px-4">
      <div className="w-full max-w-md">
        <div className="mb-8 flex flex-col items-center">
          <div className="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-600 shadow-lg">
            <Truck className="h-8 w-8 text-white" />
          </div>
          <h1 className="text-2xl font-bold text-gray-900">Rentivo</h1>
          <p className="mt-1 text-sm text-gray-500">Equipment Rental Management</p>
        </div>
        <div className="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-200">
          <Outlet />
        </div>
      </div>
    </div>
  )
}
