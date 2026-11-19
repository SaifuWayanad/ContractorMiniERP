<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InventoryBalance;
use App\Models\Project;
use App\Models\ProjectClosure;
use App\Models\ProductMovement;
use App\Services\NumberingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectClosureController extends Controller
{
    public function __construct(private readonly NumberingService $numberingService) {}

    public function outstanding(Project $project): JsonResponse
    {
        $balances = InventoryBalance::with(['product:id,name,code,unit_of_measure'])
            ->where('location_type', 'PROJECT')
            ->where('location_id', $project->id)
            ->where('quantity_on_hand', '>', 0)
            ->get();

        return response()->json([
            'project' => $project->load('customer:id,name'),
            'outstanding_equipment' => $balances,
            'total_outstanding' => $balances->sum('quantity_on_hand'),
        ]);
    }

    public function transactions(Project $project): JsonResponse
    {
        $movements = ProductMovement::with('product:id,name,code')
            ->where(function ($q) use ($project) {
                $q->where('from_location_type', 'PROJECT')->where('from_location_id', $project->id)
                  ->orWhere('to_location_type', 'PROJECT')->where('to_location_id', $project->id);
            })
            ->where('status', 'POSTED')
            ->orderBy('movement_date', 'desc')
            ->get();

        return response()->json($movements);
    }

    public function initiate(Request $request, Project $project): JsonResponse
    {
        if (!in_array($project->status, ['ACTIVE'])) {
            return response()->json(['message' => 'Only ACTIVE projects can be closed.'], 422);
        }

        $existingClosure = ProjectClosure::where('project_id', $project->id)
            ->whereNotIn('status', ['CANCELLED'])
            ->first();

        if ($existingClosure) {
            return response()->json(['message' => 'A closure process already exists for this project.', 'closure' => $existingClosure], 422);
        }

        $closure = DB::transaction(function () use ($project, $request) {
            $dispatched = ProductMovement::where('to_location_type', 'PROJECT')
                ->where('to_location_id', $project->id)
                ->where('movement_type', 'DISPATCH')
                ->where('status', 'POSTED')
                ->sum('quantity');

            $returned = ProductMovement::where('from_location_type', 'PROJECT')
                ->where('from_location_id', $project->id)
                ->where('movement_type', 'RETURN')
                ->where('status', 'POSTED')
                ->sum('quantity');

            $transferredOut = ProductMovement::where('from_location_type', 'PROJECT')
                ->where('from_location_id', $project->id)
                ->where('movement_type', 'TRANSFER_OUT')
                ->where('status', 'POSTED')
                ->sum('quantity');

            $lost = ProductMovement::where('from_location_type', 'PROJECT')
                ->where('from_location_id', $project->id)
                ->whereIn('movement_type', ['LOST', 'WRITE_OFF'])
                ->where('status', 'POSTED')
                ->sum('quantity');

            $invoices = Invoice::where('project_id', $project->id)
                ->whereIn('status', ['POSTED', 'PART_PAID', 'PAID'])
                ->get();

            $invoicedAmount = $invoices->sum('grand_total');
            $paidAmount = $invoices->sum('amount_paid');

            $closure = ProjectClosure::create([
                'closure_number' => $this->numberingService->next('CLOSURE'),
                'project_id' => $project->id,
                'customer_id' => $project->customer_id,
                'initiated_date' => today(),
                'status' => 'DRAFT',
                'total_dispatched_quantity' => $dispatched,
                'total_returned_quantity' => $returned,
                'total_transferred_out_quantity' => $transferredOut,
                'total_lost_quantity' => $lost,
                'total_invoiced_amount' => $invoicedAmount,
                'total_paid_amount' => $paidAmount,
                'financial_outstanding' => $invoicedAmount - $paidAmount,
                'created_by' => $request->user()->id,
            ]);

            $project->update(['status' => 'CLOSURE_IN_PROGRESS']);

            return $closure;
        });

        return response()->json($closure->load(['project:id,name,code', 'customer:id,name']), 201);
    }

    public function reconciliation(Project $project): JsonResponse
    {
        $closure = ProjectClosure::where('project_id', $project->id)
            ->whereNotIn('status', ['CANCELLED'])
            ->firstOrFail();

        $outstanding = InventoryBalance::with('product:id,name,code,unit_of_measure')
            ->where('location_type', 'PROJECT')
            ->where('location_id', $project->id)
            ->where('quantity_on_hand', '>', 0)
            ->get();

        $uninvoicedReturns = \App\Models\ReturnDetail::with(['product:id,name,code', 'equipmentReturn:id,return_number,return_date'])
            ->whereHas('equipmentReturn', fn ($q) => $q->where('project_id', $project->id)->where('status', 'POSTED'))
            ->get()
            ->filter(fn ($d) => $d->uninvoiced_quantity > 0)
            ->values();

        return response()->json([
            'closure' => $closure,
            'outstanding_equipment' => $outstanding,
            'uninvoiced_returns' => $uninvoicedReturns,
            'can_close' => $outstanding->isEmpty() && $uninvoicedReturns->isEmpty(),
        ]);
    }

    public function preview(Request $request, Project $project): JsonResponse
    {
        $closure = ProjectClosure::where('project_id', $project->id)
            ->whereNotIn('status', ['CANCELLED'])
            ->firstOrFail();

        $invoices = Invoice::where('project_id', $project->id)
            ->whereNotIn('status', ['CANCELLED'])
            ->get();

        return response()->json([
            'closure' => $closure->load(['project', 'customer']),
            'invoices' => $invoices,
            'invoice_total' => $invoices->whereNotIn('status', ['CANCELLED'])->sum('grand_total'),
            'paid_total' => $invoices->sum('amount_paid'),
            'outstanding' => $invoices->whereIn('status', ['POSTED', 'PART_PAID'])->sum(fn ($i) => $i->grand_total - $i->amount_paid),
        ]);
    }

    public function approve(Request $request, Project $project): JsonResponse
    {
        $closure = ProjectClosure::where('project_id', $project->id)
            ->where('status', 'READY_FOR_APPROVAL')
            ->firstOrFail();

        $closure->update([
            'status' => 'CLOSED',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json($closure->fresh());
    }

    public function complete(Request $request, Project $project): JsonResponse
    {
        $closure = ProjectClosure::where('project_id', $project->id)
            ->whereNotIn('status', ['CLOSED', 'CANCELLED'])
            ->firstOrFail();

        $outstanding = InventoryBalance::where('location_type', 'PROJECT')
            ->where('location_id', $project->id)
            ->where('quantity_on_hand', '>', 0)
            ->exists();

        if ($outstanding) {
            return response()->json(['message' => 'Cannot close project with outstanding equipment.'], 422);
        }

        DB::transaction(function () use ($project, $closure, $request) {
            $closure->update([
                'status' => 'CLOSED',
                'closure_date' => today(),
                'closed_by' => $request->user()->id,
                'closed_at' => now(),
            ]);

            $project->update([
                'status' => 'CLOSED',
                'actual_closure_date' => today(),
            ]);
        });

        return response()->json($closure->fresh()->load(['project', 'customer']));
    }

    public function statement(Project $project): JsonResponse
    {
        $closure = ProjectClosure::where('project_id', $project->id)
            ->firstOrFail();

        $invoices = Invoice::where('project_id', $project->id)
            ->whereNotIn('status', ['CANCELLED'])
            ->with(['invoiceDetails.product'])
            ->get();

        $movements = ProductMovement::with('product:id,name,code')
            ->where(function ($q) use ($project) {
                $q->where('from_location_type', 'PROJECT')->where('from_location_id', $project->id)
                  ->orWhere('to_location_type', 'PROJECT')->where('to_location_id', $project->id);
            })
            ->where('status', 'POSTED')
            ->orderBy('movement_date')
            ->get();

        return response()->json([
            'closure' => $closure->load(['project.customer', 'approvedBy:id,name', 'closedBy:id,name']),
            'invoices' => $invoices,
            'movements' => $movements,
            'financial_summary' => [
                'invoiced_total' => $invoices->sum('grand_total'),
                'paid_total' => $invoices->sum('amount_paid'),
                'outstanding' => $invoices->whereIn('status', ['POSTED', 'PART_PAID'])->sum(fn ($i) => $i->grand_total - $i->amount_paid),
            ],
        ]);
    }
}
