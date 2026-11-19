<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryBalance;
use App\Models\ProductMovement;
use App\Models\Invoice;
use App\Models\ReturnDetail;
use App\Models\Customer;
use App\Models\Loss;
use App\Models\Damage;
use App\Models\ProjectClosure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function projectOutstanding(Request $request): JsonResponse
    {
        $query = InventoryBalance::with(['product:id,name,code,unit_of_measure'])
            ->where('location_type', 'PROJECT')
            ->where('quantity_on_hand', '>', 0)
            ->when($request->project_id, fn ($q) => $q->where('location_id', $request->project_id))
            ->when($request->product_id, fn ($q) => $q->where('product_id', $request->product_id));

        return response()->json($query->paginate($request->per_page ?? 50));
    }

    public function customerOutstanding(Request $request): JsonResponse
    {
        $customers = Customer::with(['projects' => fn ($q) => $q->whereIn('status', ['ACTIVE', 'CLOSURE_IN_PROGRESS'])])
            ->when($request->customer_id, fn ($q) => $q->where('id', $request->customer_id))
            ->get()
            ->map(function ($customer) {
                $projectIds = $customer->projects->pluck('id');

                $equipment = InventoryBalance::with('product:id,name,code,unit_of_measure')
                    ->where('location_type', 'PROJECT')
                    ->whereIn('location_id', $projectIds)
                    ->where('quantity_on_hand', '>', 0)
                    ->get();

                return [
                    'customer' => ['id' => $customer->id, 'name' => $customer->name, 'code' => $customer->code],
                    'active_projects' => $customer->projects->count(),
                    'equipment_summary' => $equipment,
                    'total_quantity' => $equipment->sum('quantity_on_hand'),
                ];
            })
            ->filter(fn ($c) => $c['total_quantity'] > 0)
            ->values();

        return response()->json($customers);
    }

    public function itemMovement(Request $request): JsonResponse
    {
        $request->validate(['product_id' => ['required', 'exists:products,id']]);

        $query = ProductMovement::with(['product:id,name,code'])
            ->where('product_id', $request->product_id)
            ->when($request->from_date, fn ($q) => $q->where('movement_date', '>=', $request->from_date))
            ->when($request->to_date, fn ($q) => $q->where('movement_date', '<=', $request->to_date))
            ->where('status', 'POSTED')
            ->orderBy('movement_date')
            ->orderBy('id');

        return response()->json($query->paginate($request->per_page ?? 100));
    }

    public function availableInventory(Request $request): JsonResponse
    {
        $query = InventoryBalance::with(['product.category'])
            ->where('location_type', 'WAREHOUSE')
            ->where('quantity_on_hand', '>', 0)
            ->when($request->location_id, fn ($q) => $q->where('location_id', $request->location_id))
            ->when($request->category_id, fn ($q) => $q->whereHas('product', fn ($p) => $p->where('category_id', $request->category_id)));

        return response()->json($query->paginate($request->per_page ?? 50));
    }

    public function inventoryByLocation(Request $request): JsonResponse
    {
        $query = InventoryBalance::with(['product:id,name,code,unit_of_measure'])
            ->where('quantity_on_hand', '>', 0)
            ->when($request->location_type, fn ($q) => $q->where('location_type', $request->location_type))
            ->orderBy('location_type')
            ->orderBy('location_id');

        return response()->json($query->paginate($request->per_page ?? 100));
    }

    public function uninvoicedReturns(Request $request): JsonResponse
    {
        $query = ReturnDetail::with([
            'equipmentReturn:id,return_number,return_date,project_id,customer_id',
            'product:id,name,code,unit_of_measure',
            'invoiceAllocations',
        ])
            ->whereHas('equipmentReturn', fn ($q) => $q->where('status', 'POSTED'))
            ->when($request->customer_id, fn ($q) => $q->whereHas('equipmentReturn', fn ($r) => $r->where('customer_id', $request->customer_id)))
            ->get()
            ->filter(fn ($d) => $d->uninvoiced_quantity > 0)
            ->values();

        return response()->json($query);
    }

    public function invoiceRegister(Request $request): JsonResponse
    {
        $query = Invoice::with(['customer:id,name,code', 'project:id,name,code'])
            ->when($request->customer_id, fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->project_id, fn ($q) => $q->where('project_id', $request->project_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->from_date, fn ($q) => $q->where('invoice_date', '>=', $request->from_date))
            ->when($request->to_date, fn ($q) => $q->where('invoice_date', '<=', $request->to_date))
            ->orderBy('invoice_date', 'desc');

        return response()->json($query->paginate($request->per_page ?? 50));
    }

    public function customerFinancialOutstanding(Request $request): JsonResponse
    {
        $query = Customer::withSum(
            ['invoices as invoiced_total' => fn ($q) => $q->whereIn('status', ['POSTED', 'PART_PAID', 'PAID'])],
            'grand_total'
        )
            ->withSum(
                ['payments as paid_total' => fn ($q) => $q->where('status', 'POSTED')],
                'allocated_amount'
            )
            ->having(DB::raw('(COALESCE(invoiced_total, 0) - COALESCE(paid_total, 0))'), '>', 0)
            ->when($request->customer_id, fn ($q) => $q->where('id', $request->customer_id));

        return response()->json($query->paginate($request->per_page ?? 50));
    }

    public function lostEquipment(Request $request): JsonResponse
    {
        $query = Loss::with(['customer:id,name', 'project:id,name', 'lossDetails.product:id,name,code'])
            ->where('status', 'POSTED')
            ->when($request->customer_id, fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->project_id, fn ($q) => $q->where('project_id', $request->project_id))
            ->orderBy('loss_date', 'desc');

        return response()->json($query->paginate($request->per_page ?? 25));
    }

    public function damagedEquipment(Request $request): JsonResponse
    {
        $query = Damage::with(['customer:id,name', 'project:id,name', 'damageDetails.product:id,name,code'])
            ->where('status', 'POSTED')
            ->when($request->customer_id, fn ($q) => $q->where('customer_id', $request->customer_id))
            ->orderBy('damage_date', 'desc');

        return response()->json($query->paginate($request->per_page ?? 25));
    }

    public function projectClosures(Request $request): JsonResponse
    {
        $query = ProjectClosure::with(['customer:id,name', 'project:id,name,code'])
            ->when($request->customer_id, fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('closure_date', 'desc');

        return response()->json($query->paginate($request->per_page ?? 25));
    }

    public function rentalRevenue(Request $request): JsonResponse
    {
        $query = Invoice::with(['customer:id,name', 'project:id,name', 'invoiceDetails.product:id,name'])
            ->where('invoice_type', 'RENTAL')
            ->whereIn('status', ['POSTED', 'PART_PAID', 'PAID'])
            ->when($request->customer_id, fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->project_id, fn ($q) => $q->where('project_id', $request->project_id))
            ->when($request->from_date, fn ($q) => $q->where('invoice_date', '>=', $request->from_date))
            ->when($request->to_date, fn ($q) => $q->where('invoice_date', '<=', $request->to_date))
            ->orderBy('invoice_date', 'desc');

        return response()->json($query->paginate($request->per_page ?? 50));
    }
}
