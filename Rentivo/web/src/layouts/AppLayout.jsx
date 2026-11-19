import { useState } from 'react'
import { Outlet, NavLink, useNavigate } from 'react-router-dom'
import {
  LayoutDashboard, Tag, Package, Users, MapPin, Truck, User, FolderOpen,
  Warehouse, ArrowDownToLine, MoveHorizontal, AlertTriangle, Wrench,
  FileText, CreditCard, BarChart3, Settings, LogOut, ChevronDown,
  ChevronRight, Menu, X, Receipt, ClipboardList, FileSearch,
} from 'lucide-react'
import { useAuth } from '../contexts/AuthContext'
import { cn } from '../utils/cn'

const navGroups = [
  {
    label: null,
    items: [{ label: 'Dashboard', path: '/dashboard', icon: LayoutDashboard }],
  },
  {
    label: 'Masters',
    items: [
      { label: 'Categories', path: '/masters/categories', icon: Tag },
      { label: 'Products', path: '/masters/products', icon: Package },
      { label: 'Customers', path: '/masters/customers', icon: Users },
      { label: 'Locations', path: '/masters/locations', icon: MapPin },
      { label: 'Vehicles', path: '/masters/vehicles', icon: Truck },
      { label: 'Operators', path: '/masters/operators', icon: User },
      { label: 'Projects', path: '/masters/projects', icon: FolderOpen },
    ],
  },
  {
    label: 'Inventory',
    items: [
      { label: 'Openings', path: '/inventory/openings', icon: ArrowDownToLine },
      { label: 'Balances', path: '/inventory/balances', icon: Warehouse },
    ],
  },
  {
    label: 'Operations',
    items: [
      { label: 'Dispatches', path: '/dispatches', icon: ClipboardList },
      { label: 'Returns', path: '/returns', icon: MoveHorizontal },
      { label: 'Transfers', path: '/transfers', icon: MoveHorizontal },
    ],
  },
  {
    label: 'Billing',
    items: [
      { label: 'Invoices', path: '/billing/invoices', icon: FileText },
      { label: 'Uninvoiced Returns', path: '/billing/uninvoiced', icon: FileSearch },
    ],
  },
  {
    label: null,
    items: [{ label: 'Payments', path: '/payments', icon: CreditCard }],
  },
  {
    label: null,
    items: [{ label: 'Reports', path: '/reports', icon: BarChart3 }],
  },
  {
    label: 'Admin',
    items: [
      { label: 'Settings', path: '/settings', icon: Settings },
    ],
  },
]

function NavGroup({ group, collapsed, setCollapsed }) {
  const isCollapsed = collapsed[group.label]

  if (!group.label) {
    return (
      <div className="mb-1">
        {group.items.map((item) => (
          <NavLink
            key={item.path}
            to={item.path}
            className={({ isActive }) =>
              cn(
                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors',
                isActive
                  ? 'bg-primary-600 text-white'
                  : 'text-slate-400 hover:bg-slate-700 hover:text-white'
              )
            }
          >
            <item.icon className="h-4 w-4 shrink-0" />
            {item.label}
          </NavLink>
        ))}
      </div>
    )
  }

  return (
    <div className="mb-1">
      <button
        onClick={() => setCollapsed((p) => ({ ...p, [group.label]: !p[group.label] }))}
        className="flex w-full items-center justify-between px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-slate-500 hover:text-slate-400"
      >
        {group.label}
        {isCollapsed ? <ChevronRight className="h-3 w-3" /> : <ChevronDown className="h-3 w-3" />}
      </button>
      {!isCollapsed &&
        group.items.map((item) => (
          <NavLink
            key={item.path}
            to={item.path}
            className={({ isActive }) =>
              cn(
                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors',
                isActive
                  ? 'bg-primary-600 text-white'
                  : 'text-slate-400 hover:bg-slate-700 hover:text-white'
              )
            }
          >
            <item.icon className="h-4 w-4 shrink-0" />
            {item.label}
          </NavLink>
        ))}
    </div>
  )
}

export default function AppLayout() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const [collapsed, setCollapsed] = useState({})
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const [profileOpen, setProfileOpen] = useState(false)

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  return (
    <div className="flex h-screen overflow-hidden bg-gray-50">
      {/* Mobile overlay */}
      {sidebarOpen && (
        <div
          className="fixed inset-0 z-20 bg-black/50 lg:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}

      {/* Sidebar */}
      <aside
        className={cn(
          'fixed inset-y-0 left-0 z-30 flex w-64 flex-col bg-slate-800 transition-transform duration-200 lg:static lg:translate-x-0',
          sidebarOpen ? 'translate-x-0' : '-translate-x-full'
        )}
      >
        {/* Logo */}
        <div className="flex h-16 items-center gap-3 border-b border-slate-700 px-4">
          <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-600">
            <Truck className="h-5 w-5 text-white" />
          </div>
          <span className="text-lg font-bold text-white">Rentivo</span>
          <button
            className="ml-auto lg:hidden text-slate-400 hover:text-white"
            onClick={() => setSidebarOpen(false)}
          >
            <X className="h-5 w-5" />
          </button>
        </div>

        {/* Nav */}
        <nav className="flex-1 overflow-y-auto scrollbar-thin px-3 py-4 space-y-0.5">
          {navGroups.map((group, i) => (
            <NavGroup key={i} group={group} collapsed={collapsed} setCollapsed={setCollapsed} />
          ))}
        </nav>

        {/* User bottom */}
        <div className="border-t border-slate-700 p-3">
          <div className="flex items-center gap-3 rounded-lg px-2 py-2">
            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-sm font-medium text-white">
              {user?.name?.charAt(0) ?? 'U'}
            </div>
            <div className="flex-1 min-w-0">
              <p className="truncate text-sm font-medium text-white">{user?.name ?? 'User'}</p>
              <p className="truncate text-xs text-slate-400">{user?.email}</p>
            </div>
            <button onClick={handleLogout} className="text-slate-400 hover:text-red-400" title="Logout">
              <LogOut className="h-4 w-4" />
            </button>
          </div>
        </div>
      </aside>

      {/* Main */}
      <div className="flex flex-1 flex-col overflow-hidden">
        {/* Header */}
        <header className="flex h-16 items-center gap-4 border-b border-gray-200 bg-white px-4 shadow-sm">
          <button
            className="lg:hidden text-gray-500 hover:text-gray-700"
            onClick={() => setSidebarOpen(true)}
          >
            <Menu className="h-5 w-5" />
          </button>
          <div className="flex-1" />
          <div className="relative">
            <button
              onClick={() => setProfileOpen((p) => !p)}
              className="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-100"
            >
              <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-sm font-medium text-white">
                {user?.name?.charAt(0) ?? 'U'}
              </div>
              <span className="hidden sm:block font-medium">{user?.name ?? 'User'}</span>
              <ChevronDown className="h-4 w-4 text-gray-400" />
            </button>
            {profileOpen && (
              <div className="absolute right-0 top-full mt-1 w-48 rounded-xl bg-white py-1 shadow-lg ring-1 ring-gray-200 z-50">
                <button
                  onClick={handleLogout}
                  className="flex w-full items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                >
                  <LogOut className="h-4 w-4" />
                  Sign out
                </button>
              </div>
            )}
          </div>
        </header>

        {/* Content */}
        <main className="flex-1 overflow-y-auto p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
