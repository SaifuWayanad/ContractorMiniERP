<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EquipmentReturn;
use App\Models\DispatchDetail;
use App\Models\ReturnDetail;
use App\Services\ReturnService;
use App\Services\NumberingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    public function __construct(
        private readonly ReturnService $returnService,
        private readonly NumberingService $numberingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = EquipmentReturn::with(['customer:id,name,code', 'project:id,name,code'])
            ->when($request->customer_id, fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->project_id, fn ($q) => $q->where('project_id', $request->project_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('return_date', 'desc')
            ->orderBy('id', 'desc');

        return response()->json($query->paginate($request->per_page ?? 25));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'project_id' => ['required', 'exists:projects,id'],
            'return_date' => ['required', 'date'],
            'warehouse_location_id' => ['required', 'exists:locations,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'operator_id' => ['nullable', 'exists:operators,id'],
            'remarks' => ['nullable', 'string'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.dispatch_detail_id' => ['required', 'exists:dispatch_details,id'],
            'details.*.product_id' => ['required', 'exists:products,id'],
            'details.*.quantity_returned' => ['required', 'numeric', 'min:0.001'],
            'details.*.condition' => ['required', 'in:GOOD,DAMAGED,NEEDS_INSPECTION'],
            'details.*.remarks' => ['nullable', 'string'],
        ]);

        $return = DB::transaction(function () use ($validated, $request) {
            $return = EquipmentReturn::create([
                'return_number' => $this->numberingService->next('RETURN'),
                'customer_id' => $validated['customer_id'],
                'project_id' => $validated['project_id'],
                'return_date' => $validated['return_date'],
                'warehouse_location_id' => $validated['warehouse_location_id'],
                'vehicle_id' => $validated['vehicle_id'] ?? null,
                'operator_id' => $validated['operator_id'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'status' => 'DRAFT',
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['details'] as $detailData) {
                $return->returnDetails()->create($detailData);
            }

            return $return;
        });

        return response()->json($return->load(['customer:id,name', 'project:id,name', 'returnDetails.product:id,name,code']), 201);
    }

    public function show(EquipmentReturn $return): JsonResponse
    {
        return response()->json($return->load([
            'customer', 'project', 'warehouseLocation',
            'vehicle', 'operator',
            'returnDetails.product',
            'returnDetails.dispatchDetail',
            'returnDetails.invoiceAllocations',
        ]));
    }

    public function update(Request $request, EquipmentReturn $return): JsonResponse
    {
        if ($return->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT returns can be edited.'], 422);
        }

        $validated = $request->validate([
            'return_date' => ['sometimes', 'date'],
            'warehouse_location_id' => ['sometimes', 'exists:locations,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'operator_id' => ['nullable', 'exists:operators,id'],
            'remarks' => ['nullable', 'string'],
        ]);

        $return->update($validated);

        return response()->json($return->fresh());
    }

    public function destroy(EquipmentReturn $return): JsonResponse
    {
        if ($return->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT returns can be deleted.'], 422);
        }

        $return->returnDetails()->delete();
        $return->delete();

        return response()->json(null, 204);
    }

    public function submit(Request $request, EquipmentReturn $return): JsonResponse
    {
        if ($return->status !== 'DRAFT') {
            return response()->json(['message' => 'Only DRAFT returns can be submitted.'], 422);
        }

        $return->update(['status' => 'SUBMITTED']);

        return response()->json($return->fresh());
    }

    public function approve(Request $request, EquipmentReturn $return): JsonResponse
    {
        if ($return->status !== 'SUBMITTED') {
            return response()->json(['message' => 'Only SUBMITTED returns can be approved.'], 422);
        }

        $return->update([
            'status' => 'APPROVED',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json($return->fresh());
    }

    public function post(Request $request, EquipmentReturn $return): JsonResponse
    {
        $this->returnService->post($return->load('returnDetails'), $request->user()->id);

        return response()->json($return->fresh()->load(['returnDetails.product:id,name,code']));
    }

    public function cancel(Request $request, EquipmentReturn $return): JsonResponse
    {
        if ($return->status === 'POSTED') {
            return response()->json(['message' => 'Posted returns cannot be directly cancelled.'], 422);
        }

        $return->update(['status' => 'CANCELLED']);

        return response()->json($return->fresh());
    }
}
