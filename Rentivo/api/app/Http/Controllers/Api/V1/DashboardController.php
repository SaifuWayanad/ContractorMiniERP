<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Dispatch;
use App\Models\EquipmentReturn;
use App\Models\Invoice;
use App\Models\InventoryBalance;
use App\Models\Project;
use App\Models\ReturnDetail;
use App\Models\InvoiceAllocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $activeProjects = Project::where('status', 'ACTIVE')->count();

        $equipmentAtSites = InventoryBalance::where('location_type', 'PROJECT')
            ->sum('quantity_on_hand');

        $warehouseStock = InventoryBalance::where('location_type', 'WAREHOUSE')
            ->sum('quantity_on_hand');

        $dispatchesToday = Dispatch::where('status', 'POSTED')
            ->whereDate('dispatch_date', today())
            ->count();

        $dispatchesThisMonth = Dispatch::where('status', 'POSTED')
            ->whereMonth('dispatch_date', now()->month)
            ->whereYear('dispatch_date', now()->year)
            ->count();

        $returnsToday = EquipmentReturn::where('status', 'POSTED')
            ->whereDate('return_date', today())
            ->count();

        $invoicedThisMonth = Invoice::where('status', '!=', 'CANCELLED')
            ->whereMonth('invoice_date', now()->month)
            ->whereYear('invoice_date', now()->year)
            ->sum('grand_total');

        $financialOutstanding = Invoice::whereIn('status', ['POSTED', 'PART_PAID'])
            ->selectRaw('SUM(grand_total - amount_paid) as outstanding')
            ->value('outstanding') ?? 0;

        $uninvoicedCount = ReturnDetail::whereHas('equipmentReturn', fn ($q) => $q->where('status', 'POSTED'))
            ->whereDoesntHave('invoiceAllocations')
            ->count();

        return response()->json([
            'active_projects' => $activeProjects,
            'equipment_at_sites' => (float) $equipmentAtSites,
            'warehouse_stock' => (float) $warehouseStock,
            'dispatches_today' => $dispatchesToday,
            'dispatches_this_month' => $dispatchesThisMonth,
            'returns_today' => $returnsToday,
            'invoiced_this_month' => (float) $invoicedThisMonth,
            'financial_outstanding' => (float) $financialOutstanding,
            'uninvoiced_returns_count' => $uninvoicedCount,
        ]);
    }
}
