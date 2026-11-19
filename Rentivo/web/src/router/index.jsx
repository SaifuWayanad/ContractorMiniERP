import { createBrowserRouter, Navigate } from 'react-router-dom'
import AppLayout from '../layouts/AppLayout'
import AuthLayout from '../layouts/AuthLayout'
import LoginPage from '../features/auth/LoginPage'
import DashboardPage from '../features/dashboard/DashboardPage'
import CategoriesPage from '../features/masters/categories/CategoriesPage'
import CategoryFormPage from '../features/masters/categories/CategoryFormPage'
import CategoryDetailPage from '../features/masters/categories/CategoryDetailPage'
import ProductsPage from '../features/masters/products/ProductsPage'
import ProductFormPage from '../features/masters/products/ProductFormPage'
import ProductDetailPage from '../features/masters/products/ProductDetailPage'
import CustomersPage from '../features/masters/customers/CustomersPage'
import CustomerFormPage from '../features/masters/customers/CustomerFormPage'
import CustomerDetailPage from '../features/masters/customers/CustomerDetailPage'
import LocationsPage from '../features/masters/locations/LocationsPage'
import LocationFormPage from '../features/masters/locations/LocationFormPage'
import LocationDetailPage from '../features/masters/locations/LocationDetailPage'
import VehiclesPage from '../features/masters/vehicles/VehiclesPage'
import VehicleFormPage from '../features/masters/vehicles/VehicleFormPage'
import VehicleDetailPage from '../features/masters/vehicles/VehicleDetailPage'
import OperatorsPage from '../features/masters/operators/OperatorsPage'
import OperatorFormPage from '../features/masters/operators/OperatorFormPage'
import OperatorDetailPage from '../features/masters/operators/OperatorDetailPage'
import ProjectsPage from '../features/masters/projects/ProjectsPage'
import ProjectFormPage from '../features/masters/projects/ProjectFormPage'
import InventoryOpeningsPage from '../features/inventory/InventoryOpeningsPage'
import InventoryBalancesPage from '../features/inventory/InventoryBalancesPage'
import DispatchesPage from '../features/dispatches/DispatchesPage'
import DispatchDetailPage from '../features/dispatches/DispatchDetailPage'
import DispatchFormPage from '../features/dispatches/DispatchFormPage'
import ReturnsPage from '../features/returns/ReturnsPage'
import ReturnDetailPage from '../features/returns/ReturnDetailPage'
import ReturnFormPage from '../features/returns/ReturnFormPage'
import TransfersPage from '../features/transfers/TransfersPage'
import TransferDetailPage from '../features/transfers/TransferDetailPage'
import TransferFormPage from '../features/transfers/TransferFormPage'
import InvoicesPage from '../features/billing/InvoicesPage'
import UninvoicedReturnsPage from '../features/billing/UninvoicedReturnsPage'
import PaymentsPage from '../features/payments/PaymentsPage'
import Customer360Page from '../features/customer360/Customer360Page'
import ProjectDetailPage from '../features/projects/ProjectDetailPage'
import ProjectClosurePage from '../features/projects/ProjectClosurePage'
import ReportsPage from '../features/reports/ReportsPage'
import ProtectedRoute from './ProtectedRoute'

export const router = createBrowserRouter([
  {
    path: '/login',
    element: <AuthLayout />,
    children: [{ index: true, element: <LoginPage /> }],
  },
  {
    path: '/',
    element: <ProtectedRoute><AppLayout /></ProtectedRoute>,
    children: [
      { index: true, element: <Navigate to="/dashboard" replace /> },
      { path: 'dashboard', element: <DashboardPage /> },
      { path: 'masters/categories', element: <CategoriesPage /> },
      { path: 'masters/categories/new', element: <CategoryFormPage /> },
      { path: 'masters/categories/:id', element: <CategoryDetailPage /> },
      { path: 'masters/categories/:id/edit', element: <CategoryFormPage /> },
      { path: 'masters/products', element: <ProductsPage /> },
      { path: 'masters/products/new', element: <ProductFormPage /> },
      { path: 'masters/products/:id', element: <ProductDetailPage /> },
      { path: 'masters/products/:id/edit', element: <ProductFormPage /> },
      { path: 'masters/customers', element: <CustomersPage /> },
      { path: 'masters/customers/new', element: <CustomerFormPage /> },
      { path: 'masters/customers/:id', element: <CustomerDetailPage /> },
      { path: 'masters/customers/:id/edit', element: <CustomerFormPage /> },
      { path: 'masters/locations', element: <LocationsPage /> },
      { path: 'masters/locations/new', element: <LocationFormPage /> },
      { path: 'masters/locations/:id', element: <LocationDetailPage /> },
      { path: 'masters/locations/:id/edit', element: <LocationFormPage /> },
      { path: 'masters/vehicles', element: <VehiclesPage /> },
      { path: 'masters/vehicles/new', element: <VehicleFormPage /> },
      { path: 'masters/vehicles/:id', element: <VehicleDetailPage /> },
      { path: 'masters/vehicles/:id/edit', element: <VehicleFormPage /> },
      { path: 'masters/operators', element: <OperatorsPage /> },
      { path: 'masters/operators/new', element: <OperatorFormPage /> },
      { path: 'masters/operators/:id', element: <OperatorDetailPage /> },
      { path: 'masters/operators/:id/edit', element: <OperatorFormPage /> },
      { path: 'masters/projects', element: <ProjectsPage /> },
      { path: 'masters/projects/new', element: <ProjectFormPage /> },
      { path: 'masters/projects/:id/edit', element: <ProjectFormPage /> },
      { path: 'inventory/openings', element: <InventoryOpeningsPage /> },
      { path: 'inventory/balances', element: <InventoryBalancesPage /> },
      { path: 'dispatches', element: <DispatchesPage /> },
      { path: 'dispatches/new', element: <DispatchFormPage /> },
      { path: 'dispatches/:id', element: <DispatchDetailPage /> },
      { path: 'returns', element: <ReturnsPage /> },
      { path: 'returns/new', element: <ReturnFormPage /> },
      { path: 'returns/:id', element: <ReturnDetailPage /> },
      { path: 'transfers', element: <TransfersPage /> },
      { path: 'transfers/new', element: <TransferFormPage /> },
      { path: 'transfers/:id', element: <TransferDetailPage /> },
      { path: 'billing/invoices', element: <InvoicesPage /> },
      { path: 'billing/uninvoiced', element: <UninvoicedReturnsPage /> },
      { path: 'payments', element: <PaymentsPage /> },
      { path: 'customers/:id/360', element: <Customer360Page /> },
      { path: 'projects/:id', element: <ProjectDetailPage /> },
      { path: 'projects/:id/closure', element: <ProjectClosurePage /> },
      { path: 'reports/*', element: <ReportsPage /> },
    ],
  },
])
