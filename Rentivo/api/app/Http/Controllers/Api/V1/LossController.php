<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Loss;
use App\Models\RentalPeriod;
use App\Services\InventoryService;
use App\Services\NumberingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LossController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly NumberingService $numberingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Loss::with(['customer:id,name', 'project:id,name'])
            ->when($request->customer_id, fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->project_id, fn ($q) => $q->where('project_id', $request->project_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('loss_date', 'desc');

        return response()->json($query->paginate($request->per_page ?? 25));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'project_id' => ['required', 'exists:projects,id'],
            'loss_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.source_dispatch_detail_id' => ['required', 'exists:dispatch_details,id'],
            'details.*.product_id' => ['required', 'exists:products,id'],
            'details.*.quantity_lost' => ['required', 'numeric', 'min:0.001'],
            'details.*.recovery_amount' => ['nullable', 'numeric', 'min:0'],
            'details.*.remarks' => ['nullable', 'string'],
        ]);

        $loss = DB::transaction(function () use ($validated, $request) {
            $loss = Loss::create([
                'loss_number' => $this->numberingService->next('LOSS'),
                'customer_id' => $validated['customer_id'],
                'project_id' => $validated['project_id'],
                'loss_date' => $validated['loss_date'],
                'reason' => $validated['reason'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'status' => 'DRAFT',
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['details'] as $detail) {
                $loss->lossDetails()->create($detail);
            }

            return $loss;
        });

        return response()->json($loss->load(['customer:id,name', 'project:id,name', 'lossDetails.product:id,name,code']), 201);
    }

    public function show(Loss $loss): JsonResponse
    {
        return response()->json($loss->load(['customer', 'project', 'lossDetails.product']));
    }

    public function update(Request $request, Loss $loss): JsonResponse
    {
        if ($loss->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT losses can be edited.'], 422);
        }

        $loss->update($request->only(['loss_date', 'reason', 'remarks']));

        return response()->json($loss->fresh());
    }

    public function destroy(Loss $loss): JsonResponse
    {
        if ($loss->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT losses can be deleted.'], 422);
        }

        $loss->lossDetails()->delete();
        $loss->delete();

        return response()->json(null, 204);
    }

    public function submit(Request $request, Loss $loss): JsonResponse
    {
        if ($loss->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT losses can be submitted.'], 422);
        }

        $loss->update(['status' => 'SUBMITTED']);

        return response()->json($loss->fresh());
    }

    public function approve(Request $request, Loss $loss): JsonResponse
    {
        if ($loss->status !== 'SUBMITTED') {
            return response()->json(['message' => 'Only SUBMITTED losses can be approved.'], 422);
        }

        $loss->update(['status' => 'APPROVED', 'approved_by' => $request->user()->id, 'approved_at' => now()]);

        return response()->json($loss->fresh());
    }

    public function post(Request $request, Loss $loss): JsonResponse
    {
        if ($loss->status !== 'APPROVED') {
            return response()->json(['message' => 'Only APPROVED losses can be posted.'], 422);
        }

        DB::transaction(function () use ($loss, $request) {
            foreach ($loss->lossDetails as $detail) {
                $this->inventoryService->decrementBalance(
                    $detail->product_id, 'PROJECT', $loss->project_id, $detail->quantity_lost
                );

                $this->inventoryService->createMovement([
                    'product_id' => $detail->product_id,
                    'movement_type' => 'LOST',
                    'source_type' => Loss::class,
                    'source_id' => $loss->id,
                    'source_detail_id' => $detail->id,
                    'from_location_type' => 'PROJECT',
                    'from_location_id' => $loss->project_id,
                    'quantity' => $detail->quantity_lost,
                    'movement_date' => $loss->loss_date,
                    'reference_dispatch_detail_id' => $detail->source_dispatch_detail_id,
                    'status' => 'POSTED',
                    'created_by' => $request->user()->id,
                    'posted_by' => $request->user()->id,
                ]);

                // Close rental periods for lost quantity
                RentalPeriod::where('source_dispatch_detail_id', $detail->source_dispatch_detail_id)
                    ->where('status', 'OPEN')
                    ->update([
                        'rental_end_date' => $loss->loss_date,
                        'end_reason' => 'LOST',
                        'status' => 'CLOSED',
                        'closed_at' => now(),
                    ]);
            }

            $loss->update(['status' => 'POSTED', 'posted_by' => $request->user()->id, 'posted_at' => now()]);
        });

        return response()->json($loss->fresh());
    }

    public function cancel(Request $request, Loss $loss): JsonResponse
    {
        if ($loss->status === 'POSTED') {
            return response()->json(['message' => 'Posted losses cannot be cancelled.'], 422);
        }

        $loss->update(['status' => 'CANCELLED']);

        return response()->json($loss->fresh());
    }
}
